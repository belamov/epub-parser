<?php

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;


class belamov_epubparser extends CModule
{
    private $typeId;
    private $iblockCode;
    private $iblockId;

    public function __construct()
    {
        $arModuleVersion = array();

        include __DIR__ . '/version.php';

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_ID = 'belamov.epubparser';
        $this->MODULE_NAME = 'Парсер epub книг';
        $this->MODULE_DESCRIPTION = 'Модуль позволяет сохранять книги формата epub в инфоблоки и выводить их текст на сайте';
        $this->MODULE_GROUP_RIGHTS = 'N';
        $this->PARTNER_NAME = 'Creative';
        $this->PARTNER_URI = 'https://crtweb.ru';
        $this->typeId = "epub_books";
        $this->iblockCode = "epub_books";

    }

    public function doInstall()
    {
        $this->addIblockForBooks();
        ModuleManager::registerModule($this->MODULE_ID);
        $this->installFiles();
        Option::set($this->MODULE_ID, 'iblock_id', $this->iblockId);
    }

    public function doUninstall()
    {
        $this->unInstallFiles();
        $this->removeIblockForBooks();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function installFiles()
    {
        CopyDirFiles(
            $this->getInstallatorPath() . '/admin',
            Application::getDocumentRoot() . '/bitrix/admin'
        );
    }

    public function unInstallFiles()
    {
        CopyDirFiles(
            $this->getInstallatorPath() . '/admin',
            Application::getDocumentRoot() . '/bitrix/admin'
        );
    }

    private function getInstallatorPath()
    {
        return str_replace('\\', '/', __DIR__);
    }

    private function addIblockForBooks()
    {
        global $DB;
        Loader::includeModule("iblock");

        //create iblock type
        $arFields = Array(
            'ID' => $this->typeId,
            'SECTIONS' => 'Y',
            'IN_RSS' => 'N',
            'SORT' => 100,
            'LANG' => Array(
                'en' => Array(
                    'NAME' => 'Библиотека',
                    'SECTION_NAME' => 'Книга',
                    'ELEMENT_NAME' => 'Страница'
                )
            )
        );

        $obBlocktype = new \CIBlockType;
        $DB->StartTransaction();
        $res = $obBlocktype->Add($arFields);
        if (!$res) {
            $DB->Rollback();
            throw new Exception("Can't create IBlock type: " . $obBlocktype->LAST_ERROR);
        } else
            $DB->Commit();

        //add iblock
        $ib = new \CIBlock;
        $arFields = Array(
            "ACTIVE" => "Y",
            "NAME" => "Книги из epub",
            "CODE" => $this->iblockCode,
            "IBLOCK_TYPE_ID" => $this->typeId,
            "SORT" => 500,
            "VERSION" => 2,
            "SITE_ID" => ["s1"]
        );
        $result = $ib->Add($arFields);
        if (!$result) {
            throw new Exception("Can't create Iblock: " . $ib->LAST_ERROR);
        } else {
            $this->iblockId = $result;
        }

        //add properties for iblock
        $property = new \CUserTypeEntity;
        $propertyId = $property->Add([
            'ENTITY_ID' => "IBLOCK_" . $this->iblockId . "_SECTION",
            'FIELD_NAME' => "UF_AUTHOR",
            'USER_TYPE_ID' => 'string',
            'XML_ID' => "UF_AUTHOR",
            'EDIT_FORM_LABEL' => ['ru' => "Автор", 'en' => "Author"],
        ]);
        if (!$propertyId) {
            throw new Exception("Can't create section property: " . $property->LAST_ERROR);
        }

    }

    private function removeIblockForBooks()
    {
        Loader::includeModule("iblock");
        \CIBlockType::Delete($this->typeId);
    }
}
