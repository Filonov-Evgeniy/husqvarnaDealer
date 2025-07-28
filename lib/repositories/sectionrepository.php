<?php

namespace Husqvarna\Dealer\Repositories;

use Bitrix\Iblock\Model\Section;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Loader;
use CFile;
use Husqvarna\Dealer\Source;
use RuntimeException;

class SectionRepository
{
    /**
     * @var class-string<SectionTable>
     */
    private string $table;

    public function __construct(private int $iBlockId)
    {
        if (!Loader::includeModule('iblock')) {
            throw new RuntimeException('iblock module not installed');
        }

        $this->table = Section::compileEntityByIblock($iBlockId);
    }

    public function sync(Source $source): void
    {
        $sections = $source->getSections();

        $exists = $this->articleMap($sections);

        foreach ($sections as ['article' => $article, 'parent' => $parent, 'image' => $image, 'name' => $name]) {

            if (isset($exists[$article])) {
                continue;
            }

            $data = [
                'UF_ARTICLE' => $article,
                'NAME' => $name,
                'IBLOCK_ID' => $this->iBlockId,
                'CODE' => s_url_code($name),
                'ACTIVE' => 'Y',
            ];

            if ($parent !== null) {
                $data['IBLOCK_SECTION_ID'] = $exists[$parent];
            }

            if ($image !== null) {
                $imageId = CFile::SaveFile(CFile::MakeFileArray($source->link($image)), 'iblock');
                $data['DETAIL_PICTURE'] = $imageId;
                $data['PICTURE'] = $imageId;
            }

            $id = $this->table::add($data)->getId();
            $exists[$article] = $id;
        }
    }

    public function mapFromSource(Source $source): array
    {
        return $this->articleMap($source->getSections());
    }

    private function articleMap(array $sections): array
    {
        $map = [];

        $query = $this->table::getList([
            'select' => ['ID', 'UF_ARTICLE'],
            'filter' => ['UF_ARTICLE' => array_column($sections, 'article')],
        ]);

        while (['ID' => $id, 'UF_ARTICLE' => $article] = $query->fetch()) {
            $map[$article] = $id;
        }

        return $map;
    }
}