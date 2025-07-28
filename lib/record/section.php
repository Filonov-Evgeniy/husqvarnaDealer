<?php

namespace Husqvarna\Dealer\Record;

use Bitrix\Main\Type\Contract\Arrayable;
use SimpleXMLElement;

class Section implements Arrayable
{
    public function __construct(
        public string $article,
        public string $name,
        public ?string $parent,
        public ?string $image,
    ) {}

    public static function fromXml(SimpleXMLElement $section): Section
    {
        [$image] = $section->xpath("./MEDIENELEMENTE/MEDIENDATEI[@culture='EN-GB']");
        [$title] = $section->xpath("./TEXTELEMENTE//TEXT[@culture='EN-GB']");

        return new Section(
            (string)$section['name'],
            (string)$title,
            (string)$section->PARENT_NAME ?: null,
            (string)$image ?: null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'article' => $this->article,
            'name' => $this->name,
        ];

        if ($this->image !== null) {
            $data['image'] = $this->image;
        }

        if ($this->parent !== null) {
            $data['parent'] = $this->parent;
        }

        return $data;
    }
}