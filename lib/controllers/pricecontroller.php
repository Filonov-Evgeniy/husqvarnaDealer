<?php

namespace Husqvarna\Dealer\Controllers;

use Bitrix\Main\Engine\Controller;

use Husqvarna\Dealer\Repositories\PriceRepository;
use Husqvarna\Dealer\Repositories\ProductRepository;
use Husqvarna\Dealer\Source;

class PriceController extends Controller
{
    public function indexAction(int $iBlockId, string $path, int $offset, int $limit = 1000): int
    {
        $priceRepository = new PriceRepository(new ProductRepository($iBlockId));

        return $priceRepository->sync(Source::fromName($path), $limit, $offset);
    }
}