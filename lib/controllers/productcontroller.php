<?php

namespace Husqvarna\Dealer\Controllers;

use Bitrix\Main\Engine\Controller;
use Husqvarna\Dealer\Repositories\ProductRepository;
use Husqvarna\Dealer\Source;

class ProductController extends Controller
{
    public function indexAction(int $iBlockId, string $path, int $batchId): void
    {
        $productRepository = new ProductRepository($iBlockId);

        $productRepository->applyBatch(Source::fromName($path), $batchId);
    }

    public function countAction(string $path): int
    {
        return count(
            glob(Source::fromName($path)->productsPath() . '/*.json')
        );
    }
}