<?php

namespace Husqvarna\Dealer\Import;

use Exception;
use SimpleXMLElement;
use XMLReader;

class Cursor
{
    private XMLReader $reader;

    public function __construct(string $xml)
    {
        $this->reader = XMLReader::open($xml);
    }

    public function next(): bool
    {
        return $this->reader->read();
    }

    public function match(int $type): bool
    {
        return $this->reader->nodeType === $type;
    }

    public function isElement(): bool
    {
        return $this->match(XMLReader::ELEMENT);
    }

    public function name(): string
    {
        return $this->reader->name;
    }

    public function readOuterXml(): string
    {
        return $this->reader->readOuterXml();
    }

    /**
     * @param string $name
     * @return iterable<SimpleXMLElement>
     * @throws Exception
     */
    public function collection(string $name): iterable
    {
        $root = $this->name();
        $depth = $this->reader->depth + 1;

        while ($this->next()) {
            if ($this->match(XMLReader::END_ELEMENT) && $this->name() === $root) {
                return;
            }

            if ($this->isElement() && $this->reader->depth === $depth
                && $this->name() === $name) {

                yield new SimpleXMLElement($this->reader->readOuterXml());
            }
        }
    }
}