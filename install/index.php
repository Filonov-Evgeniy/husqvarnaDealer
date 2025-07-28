<?php

use Bitrix\Main\ModuleManager;

class husqvarna_dealer extends CModule
{
    public $MODULE_ID = 'husqvarna.dealer';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;

    public function __construct()
    {
        $arModuleVersion = [];
        include(__DIR__ . '/version.php');

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = $this->MODULE_ID;
        $this->MODULE_DESCRIPTION = $this->MODULE_ID;
    }

    public function DoInstall(): void
    {
        $this->InstallFiles();
        ModuleManager::registerModule($this->MODULE_ID);
    }

    public function InstallFiles(): void
    {
        CopyDirFiles(
            __DIR__ . '/upload',
            $_SERVER['DOCUMENT_ROOT'] . '/upload',
            true,
            true,
        );
    }

    public function DoUnInstall(): void
    {
        $this->UnInstallFiles();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function UnInstallFiles(): void
    {
        DeleteDirFiles(
            __DIR__ . '/upload',
            $_SERVER['DOCUMENT_ROOT'] . '/upload',
        );
    }
}