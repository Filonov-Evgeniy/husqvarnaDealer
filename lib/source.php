<?php

namespace Husqvarna\Dealer;

use Bitrix\Main\IO\Directory;
use InvalidArgumentException;

class Source
{
    public static function fromName(string $path): self
    {
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/upload/husqvarna.dealer/' . $path;
        if (!file_exists($fullPath)) {
            throw new InvalidArgumentException("Source path {$path} does not exist.");
        }

        return new self($fullPath);
    }

    public function __construct(
        private readonly string $directory,
    ) {}

    public function clean(): void
    {
        Directory::deleteDirectory($this->dist());
        Directory::createDirectory($this->dist());
        Directory::createDirectory($this->productsPath());
    }

    public function link(string $path): string
    {
        return $this->directory . '/' . $path;
    }

    public function xmlFileName(): string
    {
        return $this->directory . '/data.xml';
    }

    public function csvFileName(): string
    {
        return $this->directory . '/data.csv';
    }

    public function dist(): string
    {
        return $this->directory . '/dist';
    }

    public function productsPath(): string
    {
        return $this->dist() . '/products';
    }

    public function sectionsFileName(): string
    {
        return $this->dist() . '/sections.json';
    }

    public function attributesFileName(): string
    {
        return $this->dist() . '/attributes.json';
    }

    public function productBatchFileName(int $batchId): string
    {
        return $this->productsPath() . "/{$batchId}.json";
    }

    public function writeProductBatch(int $batchId, array $products): void
    {
        $this->write(
            $this->productBatchFileName($batchId),
            $products,
        );
    }

    public function writeSections(array $sections): void
    {
        $this->write($this->sectionsFileName(), $sections);
    }

    public function writeAttributes(array $attributes): void
    {
        $this->write($this->attributesFileName(), $attributes);
    }

    private function write(string $path, array $data): void
    {
        file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    public function getAttributes(): array
    {
        return $this->read($this->attributesFileName());
    }

    public function getSections(): array
    {
        return $this->read($this->sectionsFileName());
    }

    public function getProducts(int $batchId): array
    {
        return $this->read($this->productBatchFileName($batchId));
    }

    private function read(string $path): array
    {
        return json_decode(
            file_get_contents($path),
            true,
        );
    }
}