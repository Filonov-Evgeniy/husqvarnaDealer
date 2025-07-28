<?php

namespace Husqvarna\Dealer\Repositories;

use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Iblock\PropertyTable;
use CIBlockProperty;
use CIBlockPropertyEnum;
use Husqvarna\Dealer\Source;
use CCatalogSku;

class PropertyRepository
{
    private readonly int $offerIBlockId;

    public function __construct(int $iBlockId)
    {
        ['IBLOCK_ID' => $offerIBlockId] = CCatalogSku::GetInfoByProductIBlock($iBlockId);
        $this->offerIBlockId = $offerIBlockId;
    }

    public function sync(Source $source): void
    {
        $attributes = $source->getAttributes();
        $enums = $this->resolveAttributes(array_keys($attributes));
        
        foreach ($attributes as $code => $attribute) {
            if (isset($enums[$code])) {
                continue;
            }
            
            $id = PropertyTable::add([
                'NAME' => 'NOT TRANSLATED',
                'IBLOCK_ID' => $this->offerIBlockId,
                'CODE' => $code,
                'PROPERTY_TYPE' => 'L',
                'LIST_TYPE' => 'L',
                'MULTIPLE' => 'N',
                'SORT' => 500,
            ])->getId();

            $enums[$code] = [
                'ID' => $id,
                'VALUES' => [],
            ];
        }
        
        $this->syncFields($enums, $attributes);
    }
    
    public function mapFromSource(Source $source): array
    {
        return $this->resolveAttributes(
            array_keys($source->getAttributes()),
        );
    }

    private function resolveAttributes(array $codes): array
    {
        $enums = [];
        
        $propertyQuery = PropertyTable::getList([
            'select' => ['ID', 'CODE'],
            'filter' => ['CODE' => $codes, 'IBLOCK_ID' => $this->offerIBlockId],
        ]);

        while (['ID' => $propertyId, 'CODE' => $code] = $propertyQuery->fetch()) {
            $enums[$code] = [
                'ID' => $propertyId,
                'VALUES' => [],
            ];
        }

        $enumsQuery = PropertyEnumerationTable::getList([
            'select' => ['ID', 'VALUE', 'CODE' => 'PROPERTY.CODE'],
            'filter' => ['PROPERTY_ID' => array_column($enums, 'ID')],
        ]);

        while($enum = $enumsQuery->fetch()) {
            $enums[$enum['CODE']]['VALUES'][$enum['VALUE']] = $enum['ID'];
        }

        return $enums;
    }

    private function syncFields(array $enums, array $attributes): void
    {
        $batch = [];
        foreach ($attributes as $code => $attribute) {
            $enum = $enums[$code];
            $propertyId = $enum['ID'];
            
            $values = array_diff($attribute, $enum['VALUES']);

            foreach($values as $i => $value) {
                
                $batch[] = [
                    'PROPERTY_ID' => $propertyId,
                    'VALUE' => $value,
                    'SORT' => $i * 100,
                    'XML_ID' => md5($propertyId . $value),
                ];
            }
        }    
        
        PropertyEnumerationTable::addMulti($batch);
    }
}