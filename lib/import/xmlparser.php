<?php

namespace Husqvarna\Dealer\Import;

use Bitrix\Catalog\ProductTable;
use Husqvarna\Dealer\Exceptions\OfferDoesNotHaveGroupProperty;
use Husqvarna\Dealer\Record\Model;
use Husqvarna\Dealer\Record\Product;
use Husqvarna\Dealer\Record\Relation;
use Husqvarna\Dealer\Record\Section;
use Husqvarna\Dealer\Source;
use SimpleXMLElement;

class XmlParser
{
    public function doParse(Source $source): void
    {
        $context = new Context();
        $source->clean();
        $cursor = new Cursor($source->xmlFileName());

        while ($cursor->next()) {
            if (!$cursor->isElement()) {
                continue;
            }

            switch ($cursor->name()) {
                case 'ROOT_STRUKTUREN':
                    $this->parseHQVStructure($cursor, $source, $context);
                    break;
                case 'PRODUKTE_IN_STRUKTUREN':
                    $this->parseHQVRelations($cursor, $context);
                    break;
                case 'ROOT_PRODUKTE':
                    $this->parseHQVProducts($cursor, $source, $context);
                    break;
            }
        }
    }

    private function parseHQVStructure(Cursor $cursor, Source $source, Context $context): void
    {
        $dom = new SimpleXMLElement($cursor->readOuterXml());
        $rootSections = XPath::findRootSections($dom);

        /** @var Section[] $sections */
        $sections = array_map(
            fn(SimpleXMLElement $section) => Section::fromXml($section),
            XPath::findNestedSections($rootSections)
        );

        $source->writeSections($sections);

        $this->parseHQVModels($rootSections, $context);
    }

    /**
     * @param SimpleXMLElement[] $rootSections
     * @param Context $context
     * @return void
     */
    private function parseHQVModels(array $rootSections, Context $context): void
    {
        /**
         * @var array<string, Model> $models
         */
        $models = [];

        foreach (XPath::findModels($rootSections) as $xml) {
            $model = Model::fromXml($xml);

            $models[$model->article] = ($primary = $models[$model->article])
                ? Model::merge($primary, $model)
                : $model;
        }

        $context->setModels($models);
    }

    private function parseHQVRelations(Cursor $cursor, Context $context): void
    {
        $relations = [];

        foreach ($cursor->collection('PRODUKT_ZU_STRUKTUR_ELEMENT') as $xml) {
            $relation = Relation::fromXml($xml);

            if (in_array($relation->model, $context->getAllowedModels())) {
                $relations[$relation->product] = $relation->model;
            }
        }

        $context->setRelations($relations);
    }

    private function parseHQVProducts(Cursor $cursor, Source $source, Context $context): void
    {
        $properties = [];
        $processedSKU = [];
        $allowedGroups = $context->getAllowedGroups();
        $queue = new Queue($source);

        foreach ($cursor->collection('PRODUKT') as $xml) {
            $product = Product::fromXml($xml);

            if (!in_array($product->article, $context->getAllowedProducts())) {
                continue;
            }

            $model = $context->getModelByProduct($product);

            if ($model->hasOffers() && !in_array($model->article, $processedSKU)) {
                $queue->add($this->asSKU($model));
                $processedSKU[] = $model->article;
            }

            $item = $model->hasOffers()
                ? $this->asOffer($model, $product)
                : $this->asProduct($model, $product);

            $queue->add($item);

            foreach ($product->properties as $code => $value) {
                if (in_array($code, $allowedGroups)) {
                    $properties[$code][] = $value;
                }
            }
        }

        foreach ($properties as $code => $values) {
            $properties[$code] = array_values(array_unique($values));
        }

        $source->writeAttributes($properties);
    }

    private function asProduct(Model $model, Product $product): array
    {
        return [
            'TYPE' => ProductTable::TYPE_PRODUCT,
            'NAME' => $product->texts['BEZEICHNUNG'] ?? $model->texts['BEZEICHNUNG'],
            'DESCRIPTION' => (string)($model->texts['BESCHRTEXT_GEN_D_HQV']
                ?: $model->texts['BESCHRTEXT_GEN_W_HQV']),
            'ARTICLE' => $product->article,
            'SECTIONS' => $model->sections,
            'IMAGE' => $product->image ?: $model->image,
            'RELATIONS' => $product->relations,
        ];
    }

    private function asSKU(Model $model): array
    {
        return [
            'TYPE' => ProductTable::TYPE_SKU,
            'NAME' => $model->texts['BEZEICHNUNG'],
            'ARTICLE' => $model->article,
            'DESCRIPTION' => (string)($model->texts['BESCHRTEXT_GEN_D_HQV']
                ?: $model->texts['BESCHRTEXT_GEN_W_HQV']),
            'SECTIONS' => $model->sections,
            'IMAGE' => $model->image,
        ];
    }

    private function asOffer(Model $model, Product $product): array
    {
        $properties = [];
        foreach ($model->groupBy as $property) {
            $properties[$property] = $product->getPropertyValue($property);
        }

        return [
            'TYPE' => ProductTable::TYPE_OFFER,
            'NAME' => $product->texts['BEZEICHNUNG'],
            'ARTICLE' => $product->article,
            'CML2_LINK' => $model->article,
            'PROPERTIES' => $properties,
            'IMAGE' => $product->image ?: $model->image,
            'RELATIONS' => $product->relations,
        ];
    }
}