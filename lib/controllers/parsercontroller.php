<?php

namespace Husqvarna\Dealer\Controllers;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\IO\Directory;
use Husqvarna\Dealer\Import\XmlParser;
use Husqvarna\Dealer\Source;

class ParserController extends Controller
{
    public function parseAction(string $path): void
    {
        (new XmlParser)->doParse(Source::fromName($path));
    }
}