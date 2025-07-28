<?php

namespace Husqvarna\Dealer\Import;

use Husqvarna\Dealer\Source;

class Queue
{
    private int $counter = 0;

    private array $items = [];

    public function __construct(
        private readonly Source $source,
        private readonly int $limit = 1000,
    ) {}

    public function add(array $item): void
    {
        $this->items[] = $item;
        $this->check();
    }

    private function check(): void
    {
        if (count($this->items) < $this->limit) {
            return;
        }

        $this->flush();
    }

    private function flush(): void
    {
        $this->source->writeProductBatch($this->counter, $this->items);
        $this->reset();
    }

    private function reset(): void
    {
        $this->items = [];
        ++$this->counter;
    }
}