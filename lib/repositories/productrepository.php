<?php

namespace Husqvarna\Dealer\Repositories;

use Bitrix\Catalog\ProductTable;
use Bitrix\Main\Application;
use Bitrix\Iblock\ElementPropertyTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Entity\ReferenceField;
use CCatalogSku;
use CFile;
use CIBlockElement;
use Husqvarna\Dealer\Source;
use Bitrix\Main\Loader;

class ProductRepository
{
    private readonly int $iBlockId;
    private readonly int $offerIBlockId;
    private readonly SectionRepository $sectionRepository;

    private readonly PropertyRepository $propertyRepository;

    public function __construct(int $iBlockId)
    {
        ['IBLOCK_ID' => $offerIBlockId] = CCatalogSku::GetInfoByProductIBlock($iBlockId);
        $this->iBlockId = $iBlockId;
        $this->offerIBlockId = $offerIBlockId;
        $this->sectionRepository = new SectionRepository($iBlockId);
        $this->propertyRepository = new PropertyRepository($iBlockId);
    }

    public function applyBatch(Source $source, int $batchId): void
    {
        $products = $source->getProducts($batchId);
        $existingProducts = $this->getExistingProductsMap($products);
        $sectionsMap = $this->sectionRepository->mapFromSource($source);
        $propertiesMap = $this->propertyRepository->mapFromSource($source);
        foreach ($products as $product) {
            set_time_limit(300);
            $connection = Application::getConnection();
            $connection->startTransaction();
            if ($this->shouldUpdateProduct($product, $existingProducts)) {
                $result = $this->changeProperties($product, $existingProducts);
                if ($result) {
                    $connection->commitTransaction();
                } else {
                    $connection->rollbackTransaction();
                }
                continue;
            }

            $fields = $this->prepareProductFields($product, $existingProducts);
            $picture = CFile::MakeFileArray($source->link($product['IMAGE']));

            $fields['PREVIEW_PICTURE'] = $picture;
            $fields['DETAIL_PICTURE'] = $picture;

            if ($product['TYPE'] !== ProductTable::TYPE_OFFER) {
                $fields['IBLOCK_SECTION'] = array_map(
                    fn(string $article) => $sectionsMap[$article],
                    $product['SECTIONS']
                );
            }

            if ($product['TYPE'] === ProductTable::TYPE_OFFER) {
                
                $props = [];
                
                foreach($product['PROPERTIES'] as $code => $prop) {
                    $props[$code] = $propertiesMap[$code]['VALUES'][$prop];
                }       

                $fields['PROPERTY_VALUES'] = [
                    ...$fields['PROPERTY_VALUES'],
                    ...$props,
                ];
            }
            $element = new \CIBlockElement();
            $productId = $element->Add($fields);

            if (!$productId) {
                $symbolCode = s_url_code($product['NAME']);
                $fields['CODE'] = $symbolCode . "#" . $product['ARTICLE'];
                $productId = $element->Add($fields);
            }

            if ($productId) {
                $this->addToCatalog($productId, $product['TYPE']);
                $existingProducts[$product['ARTICLE']] = $productId;
                $result = $this->changeProperties($product, $existingProducts);
                if ($result) {
                    $connection->commitTransaction();
                } else {
                    $connection->rollbackTransaction();
                }
            } else {
                $connection->rollbackTransaction();
            }
        }
    }

    private function changeProperties($product, $existingProducts)
    {
        Loader::includeModule('iblock');
        $propertyValues = [];
        if ($product['RELATIONS']) {
            foreach ($product['RELATIONS'] as $key => $relation) {
                if ($relation) {
                    $propertyValues[$key] = $relation;
                }
            }
        }

        if (empty($propertyValues)) {
            return true;
        }

        $propertyValues['ARTICLE'] = $product['ARTICLE'];
        $propertyValues['BARCODE'] = $product['ARTICLE'];

        $element = new \CIBlockElement();
        $result = $element->Update($existingProducts[$product['ARTICLE']], ['PROPERTY_VALUES' => $propertyValues]);

        return $result;
    }

    public function findProductsByArticles(array $articles): array
    {
        $query = ElementPropertyTable::getList([
            'select' => ['IBLOCK_ELEMENT_ID', 'VALUE'],
            'filter' => [
                'ELEMENT.IBLOCK_ID' => [$this->offerIBlockId, $this->iBlockId],
                'PROPERTY.CODE' => 'ARTICLE',
                'VALUE' => $articles,
            ],
            'runtime' => [
                new ReferenceField('PROPERTY', PropertyTable::class, [
                    '=this.IBLOCK_PROPERTY_ID' => 'ref.ID',
                ]),
            ],
        ]);

        $map = [];
        while ($row = $query->fetch()) {
            $map[$row['VALUE']] = $row['IBLOCK_ELEMENT_ID'];
        }

        return $map;
    }

    private function shouldUpdateProduct(array $product, array $existingProducts): bool
    {
        return isset($existingProducts[$product['ARTICLE']]);
    }

    private function getExistingProductsMap(array $products): array
    {
        $articles = $this->extractUniqueArticles($products);
        return $this->findProductsByArticles($articles);
    }

    private function extractUniqueArticles(array $products): array
    {
        return array_unique(array_merge(
            array_column($products, 'ARTICLE'),
            array_column($products, 'CML2_LINK')
        ));
    }

    private function addToCatalog(int $productId, int $productType): void
    {
        ProductTable::add([
            'ID' => $productId,
            'TYPE' => $productType,
        ]);
    }

    private function prepareProductFields(array $product, array $existingProducts): array
    {
        $fields = [
            'IBLOCK_ID' => $this->getIBlockIdForProduct($product['TYPE']),
            'ACTIVE' => 'Y',
            'CODE' => s_url_code($product['NAME']),
            'NAME' => $product['NAME'],
            'PREVIEW_TEXT' => $product['DESCRIPTION'] ?? '',
            'DETAIL_TEXT' => $product['DESCRIPTION'] ?? '',
            'PROPERTY_VALUES' => [
                'ARTICLE' => $product['ARTICLE'],
                'BARCODE' => $product['ARTICLE'],
            ],
        ];

        if ($product['TYPE'] === ProductTable::TYPE_OFFER) {
            $fields['PROPERTY_VALUES']['CML2_LINK'] = $existingProducts[$product['CML2_LINK']];
        }

        return $fields;
    }

    private function getIBlockIdForProduct(int $productType): int
    {
        return $productType !== ProductTable::TYPE_OFFER
            ? $this->iBlockId
            : $this->offerIBlockId;
    }
}