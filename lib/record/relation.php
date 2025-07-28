<?php

namespace Husqvarna\Dealer\Record;

use SimpleXMLElement;

class Relation
{
    public function __construct(
        public string $model,
        public string $product,
    ) {}

    public static function fromXml(SimpleXMLElement $xml): self
    {
        return new Relation(
            (string)$xml->ELEMENT_NAME,
            (string)$xml->PRODUKT_NAME,
        );
    }
}