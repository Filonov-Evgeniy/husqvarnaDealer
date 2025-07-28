<?php

namespace Husqvarna\Dealer\Repositories;

use Bitrix\Catalog\GroupTable;
use Bitrix\Catalog\Model\Price;
use Bitrix\Catalog\PriceTable;
use CCSVData;
use Husqvarna\Dealer\Source;
use RuntimeException;

class PriceRepository
{
    public function __construct(
        private readonly ProductRepository $productRepository,
    ) {}

    public function sync(Source $source, int $limit, int $offset = 0): int
    {
        $file = $this->openCsvFile($source->csvFileName());
        $this->skipRows($file, $offset);

        $batch = $this->fetchBatch($file, $limit);
        $this->processBatch($batch);

        $remaining = $this->countRemainingRows($file);
        $file->CloseFile();

        return $remaining;
    }

    private function openCsvFile(string $filePath): CCSVData
    {
        $file = new CCSVData('R', true);
        $file->LoadFile($filePath);
        $file->SetDelimiter();

        return $file;
    }

    private function processBatch(array $batch): void
    {
        $articles = array_column($batch, 0);
        $priceId = $this->getBasePriceTypeId();
        $productMap = $this->productRepository->findProductsByArticles($articles);
        $existsPrices = $this->getExistingPrices($productMap);

        foreach ($batch as $item) {
            [$article, $description, $purchasePrice, $purchaseCurrency,
                $dealerPrice, $retailPrice, $retailCurrency] = $item;

            $id = $productMap[$article];

            if ($id === null) {
                continue;
            }

            $price = (str_starts_with($article, '3HS') || str_starts_with($article, '3RS'))
                ? $purchasePrice * 2
                : $purchasePrice * 2.65;

            if (isset($existsPrices[$id])) {
                Price::update($id, [
                    'CATALOG_GROUP_ID' => $priceId,
                    'PRICE' => $price,
                    'PRICE_SCALE' => $price,
                    'CURRENCY' => $purchaseCurrency,
                ]);
            } else {
                PriceTable::add([
                    'PRODUCT_ID' => $id,
                    'CATALOG_GROUP_ID' => $priceId,
                    'PRICE' => $price,
                    'PRICE_SCALE' => $price,
                    'CURRENCY' => $purchaseCurrency,
                ]);
            }
        }
    }

    private function skipRows(CCSVData $file, int $offset): void
    {
        $skipped = 0;
        while ($skipped < $offset && $file->Fetch() !== false) {
            $skipped++;
        }
    }

    private function fetchBatch(CCSVData $file, int $limit): array
    {
        $batch = [];
        $counter = 0;

        while ($counter < $limit && ($row = $file->Fetch())) {
            $batch[] = $row;
            $counter++;
        }

        return $batch;
    }

    private function countRemainingRows(CCSVData $file): int
    {
        $remaining = 0;
        while ($file->Fetch()) {
            $remaining++;
        }
        return $remaining;
    }

    private function getBasePriceTypeId(): int
    {
        $basePriceType = GroupTable::getList([
            'filter' => ['BASE' => 'Y'],
            'select' => ['ID'],
            'cache' => ['ttl' => 3600],
        ])->fetch();

        return $basePriceType['ID'];
    }

    private function getExistingPrices(array $ids): array
    {
        $query = PriceTable::getList([
            'select' => ['ID', 'PRODUCT_ID'],
            'filter' => ['PRODUCT_ID' => $ids],
        ]);

        $prices = [];
        while ($row = $query->fetch()) {
            $prices[$row['ID']] = $row['PRODUCT_ID'];
        }

        return $prices;
    }
}