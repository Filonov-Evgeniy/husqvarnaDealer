<?php

namespace Husqvarna\Dealer\Controllers;

use Bitrix\Main\Engine\Controller;
use Husqvarna\Dealer\Repositories\PropertyRepository;
use Husqvarna\Dealer\Repositories\SectionRepository;
use Husqvarna\Dealer\Source;

class PropertyController extends Controller
{
    public function indexAction(int $iBlockId, string $path): void
    {
        $repository = new PropertyRepository($iBlockId);
        $repository->sync(Source::fromName($path));
    }
}