<?php

namespace Husqvarna\Dealer\Import;

use Husqvarna\Dealer\Record\Model;
use Husqvarna\Dealer\Record\Product;
use Husqvarna\Dealer\Record\Relation;

class Context
{
    /**
     * @var array<string, Model>
     */
    private array $models = [];

    /**
     * @var array<string, string>
     */
    private array $relations = [];

    /**
     * @var array<string, string>
     */
    private array $attributes = [];

    /**
     * @param array<string, Model> $models
     * @return void
     */
    public function setModels(array $models): void
    {
        $this->models = $models;
    }

    /**
     * @param array<string, string> $relations
     * @return void
     */
    public function setRelations(array $relations): void
    {
        $this->relations = $relations;
    }

    /**
     * @param array<string, string> $attributes
     * @return void
     */
    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasAttributes(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    /**
     * @return string[]
     */
    public function getAllowedModels(): array
    {
        return array_keys($this->models);
    }

    public function getAllowedProducts(): array
    {
        return array_keys($this->relations);
    }

    /**
     * @return string[]
     */
    public function getAllowedGroups(): array
    {
        return array_unique(array_reduce($this->models,
            fn(array $groups, Model $model) => array_merge($groups, $model->groupBy), [])
        );
    }

    /**
     * @param string $name
     * @return string|null
     */
    public function getAttributeTitle(string $name): ?string
    {
        return $this->attributes[$name] ?? null;
    }

    public function getModelByProduct(Product $product): ?Model
    {
        $article = $this->relations[$product->article];

        return $this->models[$article] ?? null;
    }
}