<?php

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\Extension;

$moduleId = 'husqvarna.dealer';

Loader::includeModule($moduleId);

$request = Application::getInstance()->getContext()->getRequest();

$tabControl = new CAdminTabControl('tabControl', [
    [
        'DIV' => 'parser',
        'TAB' => 'Парсер',
        'TITLE' => 'Обработка данных',
    ]
]);

$tabControl->Begin();

$options = [
    [
        'iblock_id',
        'ID инфоблока',
        '14',
        ['text', '50'],
    ],
    [
        'path',
        'Название директории',
        '',
        ['text', '50'],
    ],
];
?>
    <form>
        <?php $tabControl->BeginNextTab();
        __AdmSettingsDrawList($moduleId, $options);
        ?>
        <div id="status-label" style="display:none; font-size: 24px; text-align: center">

        </div>
        <?php
        $tabControl->Buttons() ?>
        <input type="button" id="play-button" value="<?= 'Запустить' ?>" class="adm-btn-save">
        <script>
            BX.ready(() => {
                console.log(1)
                const moduleId = 'husqvarna:dealer'

                const options = BX('parser_edit_table')
                const label = BX('status-label')

                async function* parse({iBlockId, path}) {

                    yield 'Парсинг xml...'
                    await action('ParserController.parse', {path})
                    yield 'Создание разделов...'
                    await action('SectionController.index', {iBlockId, path})
                    yield 'Синхронизация свойства';
                    await action('PropertyController.index', {iBlockId, path})

                    const {data: batchCount} = await action('ProductController.count', {path})

                    for (let batchId = 0; batchId < batchCount; ++batchId) {
                        yield `Создание/Обновление товаров ${batchId + 1}/${batchCount}`
                        await action('ProductController.index', {iBlockId, path, batchId})
                    }

                    const {data: priceCount} = await action('PriceController.index',
                        {iBlockId, path, limit: 0, offset: 0,})

                    const limit = 1000
                    const iterations = Math.ceil((priceCount + 1) / limit)

                    for (let i = 0; i < iterations; ++i) {
                        yield `Обновление цен ${i}/${iterations}`
                        await action('PriceController.index', {iBlockId, path, offset: i * limit, limit})
                    }
                }

                function action(controller, payload = {}) {

                    return BX.ajax.runAction(moduleId + '.' + controller, {
                        data: payload
                    })
                }

                BX('play-button').addEventListener('click', async function () {

                    options.style.display = 'none'
                    label.style.display = 'block'

                    const iBlockId = document.querySelector('[name=iblock_id]').value
                    const path = document.querySelector('[name=path]').value

                    for await (const step of parse({iBlockId, path})) {
                        label.textContent = step
                    }

                    label.style.display = 'none'
                    options.style.display = 'block'
                })
            })
        </script>
    </form>

<?php $tabControl->End() ?>