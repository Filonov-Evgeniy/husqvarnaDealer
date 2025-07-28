<?php

namespace Husqvarna\Dealer\Record;

use Husqvarna\Dealer\Import\XPath;
use SimpleXMLElement;

class Product
{
    public static function fromXml(SimpleXMLElement $xml): Product
    {
        $properties = [];
        $attributes = $xml->xpath('./ATTRIBUTE/ATTRIBUT');

        foreach ($attributes as $attribute) {
            $name = (string)$attribute['name'];
            $values = $attribute->xpath('./ATTRIBUTWERT');

            if (count($values) === 1) {
                [$value] = $values;
                $properties[$name] = (string)$value['name'];
            }
        }
        
        return new Product(
            (string)$xml['name'],
            XPath::extractElementTexts($xml),
            $properties,
            Xpath::findFirstPicture($xml),
            XPath::extractProductRelations($xml),
        );
    }

    public function __construct(
        public readonly string $article,

        /**
         * @var array{BEZEICHNUNG: string}
         */
        public readonly array $texts,

        /**
         * @var array<string>
         */
        public readonly array $properties,

        public readonly string $image,

        public readonly array $relations,
    ) {}

    public function getPropertyValue(string $name): ?string
    {
        return $this->properties[$name] ?? null;
    }
}