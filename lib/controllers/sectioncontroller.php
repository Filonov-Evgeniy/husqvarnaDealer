<?php

namespace Husqvarna\Dealer\Controllers;

use Bitrix\Main\Engine\Controller;
use Husqvarna\Dealer\Repositories\SectionRepository;
use Husqvarna\Dealer\Source;

class SectionController extends Controller
{
    public function indexAction(int $iBlockId, string $path): void
    {
        $repository = new SectionRepository($iBlockId);
        $repository->sync(Source::fromName($path));
    }
}