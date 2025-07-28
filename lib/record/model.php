<?php

namespace Husqvarna\Dealer\Record;

use Husqvarna\Dealer\Import\XPath;
use SimpleXMLElement;

class Model
{
    public static function fromXml(SimpleXMLElement $xml): Model
    {
        return new Model(
            article: (string)$xml['name'],
            sections: [(string)$xml->PARENT_NAME],

            groupBy: array_map(
                fn(SimpleXMLElement $group) => (string)$group['name'],
                $xml->xpath('./CONTROLATTRIBUTE/ATTRIBUT[@name="GROUP_BY"]/ATTRIBUTWERT') ?: []
            ),

            texts: XPath::extractElementTexts($xml),
            image: Xpath::findFirstPicture($xml),
        );
    }

    public static function merge(Model $primary, Model $joined): Model
    {
        return new Model(
            $primary->article,
            array_merge($primary->sections, $joined->sections),
            $primary->groupBy,
            $primary->texts,
            $primary->image,
        );
    }

    public function __construct(
        public readonly string $article,
        public readonly array $sections,
        public readonly array $groupBy,
        /**
         * @var array{BEZEICHNUNG: string, MODELLNAME_GEN_HQV: ?string, BESCHRTEXT_GEN_D_HQV: ?string, BESCHRTEXT_GEN_W_HQV: ?string}
         */
        public readonly array $texts,

        public readonly  string $image,
    ) {}

    public function hasOffers(): bool
    {
        return count($this->groupBy) > 0;
    }
}
