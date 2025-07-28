<?php

namespace Husqvarna\Dealer\Import;

use SimpleXMLElement;

class XPath
{
    public static function findRootSections(SimpleXMLElement $dom): array
    {
        return array_filter($dom->xpath('.//STRUKTUR_ELEMENT[@ebene="KOMM_03"]'), function (SimpleXMLElement $node) {
            $text = (string)$node->TEXTELEMENTE->TEXTART->TEXT;

            /**
             * Remove parent for this section
             */
            $node->PARENT_NAME = '';

            return in_array($text,
                ['Technical Accessories', 'Apparel and Accessories', 'Original Spare Part Kits'], // HardCode
            );
        });
    }

    /**
     * @param SimpleXMLElement[] $rootSections
     * @return SimpleXMLElement[]
     */
    public static function findNestedSections(array $rootSections): array
    {
        $sections = [];

        foreach ($rootSections as $rootSection) {
            $sections = [...$sections, $rootSection,
                ...$rootSection->xpath('.//STRUKTUR_ELEMENT[starts-with(@ebene, "KOMM_")]') ?: []
            ];
        }

        return $sections;
    }

    /**
     * @param SimpleXMLElement[] $rootSections
     * @return SimpleXMLElement[]
     */
    public static function findModels(array $rootSections): array
    {
        $models = [];
        foreach ($rootSections as $rootSection) {
            $models = [...$models,
                ...$rootSection->xpath('.//STRUKTUR_ELEMENT[@ebene="MODELL"]') ?: []
            ];
        }

        return $models;
    }


    public static function extractElementTexts(SimpleXMLElement $xml): array
    {
        $texts = [];
        $items = $xml->xpath('./TEXTELEMENTE/TEXTART');

        foreach ($items as $item) {
            $name = (string)$item['name'];

            [$value] = $item->xpath('./TEXT[@culture="EN-GB"]')
                ?: $item->xpath('./TEXT');

            $texts[$name] = strip_tags((string)$value);
        }

        return $texts;
    }

    public static function findFirstPicture(SimpleXMLElement $xml): string
    {
        [$image] = $xml->xpath('./MEDIENELEMENTE/MEDIENDATEI[starts-with(@nk, "PHO") and @culture="EN-GB"]');
        return (string)$image;
    }

    public static function extractProductRelations(SimpleXMLElement $xml): array
    {
        $relations = [];

        foreach ($xml->xpath('./BEZIEHUNGEN/BEZIEHUNGSTYP') as $attribute) {
            $key = (string)$attribute['name'];

            $relations[$key] = array_map(function (SimpleXMLElement $relationProduct) {
                return (string)$relationProduct['name'];
            }, $attribute->xpath('./PRODUKT') ?: []);
        }

        return $relations;
    }
}