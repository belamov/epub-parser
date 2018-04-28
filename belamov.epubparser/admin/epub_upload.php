<?php

use belamov\EpubParser\Epub;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

defined('ADMIN_MODULE_NAME') or define('ADMIN_MODULE_NAME', 'belamov.epubparser');
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

Loader::includeModule('belamov.epubparser');
Loader::includeModule('iblock');
$app = Application::getInstance();
$context = $app->getContext();
$request = $context->getRequest();

$tabControl = new CAdminTabControl("tabControl", array(
    array(
        "DIV" => "edit1",
        "TAB" => "Загрузить книгу",
        "TITLE" => "Загрузить книгу",
    ),
));
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
try {
    if ((!empty($save) || !empty($restore)) && $request->isPost() && check_bitrix_sessid()) {
        if (!$iblockId = Option::get(ADMIN_MODULE_NAME, 'iblock_id')) {
            CAdminMessage::showMessage("Ошибка! В настройках модуля не указал инфоблок, в который нужно выгружать книгу.");
        } elseif (
            !empty($_FILES['epub_file'])
            and $_FILES['epub_file']['type'] == 'application/epub+zip'
            and $_FILES['epub_file']['size']
        ) {
            $epub = new Epub(
                $_FILES['epub_file']['tmp_name'],
                __DIR__ . "/test/",
                $_SERVER['DOCUMENT_ROOT'] . "/upload/epub_parser/" . md5($_FILES['epub_file']['name']) . "/"
            );
            $name = $request->getPost('name') ? $request->getPost('name') : $epub->getTitle();
            if (empty($name)) {
                throw new Exception("Не удалось получить имя книги из файла. Задайте его вручную");
            }
            $description = $request->getPost('description') ? $request->getPost('name') : $epub->getDescription();
            if (empty($description)) {
                throw new Exception("Не удалось получить описание книги из файла. Задайте его вручную");
            }
            $author = $request->getPost('author') ? $request->getPost('author') : $epub->getAuthor();
            if (empty($author)) {
                throw new Exception("Не удалось получить автора книги из файла. Задайте его вручную");
            }
            if ($_FILES['epub_cover']['size']) {
                $cover = $_FILES['epub_cover'];
            } else {
                $coverPath = $epub->getCoverPath();
                $cover = [
                    "name" => basename($coverPath),
                    'type' => mime_content_type($coverPath),
                    'tmp_name' => $coverPath,
                    'error' => 0,
                    'size' => filesize($coverPath)
                ];
            }
            //создаем раздел, в котором будет храниться книга
            $bs = new CIBlockSection;
            $arFields = Array(
                "ACTIVE" => 'Y',
                "IBLOCK_ID" => $iblockId,
                "NAME" => $name,
                "PICTURE" => $cover,
                "DESCRIPTION" => $description,
                "DESCRIPTION_TYPE" => "html/text",
                "UF_AUTHOR" => $author,
                "CODE" => Cutil::translit($name, "ru", ["replace_space" => "-", "replace_other" => "-"])
            );
            if (!$sectionId = $bs->Add($arFields)) {
                throw new Exception("Не получилось создать раздел в инфоблоке: " . $bs->LAST_ERROR);
            }
            $sort = 0;
            $text = "";
            $symbolsPerPage = Option::get('belamov.epubparser', 'symbols_per_page', "10000");
            $obParser = new CTextParser();
            foreach ($epub as $item) {
                if (strlen($text) > $symbolsPerPage - 100) {
                    while (strlen($text) > $symbolsPerPage - 100) {
                        // обрезает текст до последнего абзаца
                        $elText = substr($text, 0, strpos($text, '</p>', $symbolsPerPage) + 4);
                        $text = str_replace($elText, "", $text);
                        $elText = $obParser->closeTags($elText);
                        $el = new CIBlockElement;
                        $sort += 10;
                        $fields = Array(
                            "MODIFIED_BY" => $USER->GetID(),
                            "IBLOCK_SECTION_ID" => $sectionId,
                            "IBLOCK_ID" => $iblockId,
                            "NAME" => $item['name'],
                            "SORT" => $sort,
                            "CODE" => Cutil::translit($name, "en", ["replace_space" => "-", "replace_other" => "-"]),
                            "ACTIVE" => "Y",
                            "DETAIL_TEXT" => $elText,
                        );
                        if (!$elementId = $el->Add($fields)) {
                            throw new Exception("Не получилось создать элемент инфоблока: " . $el->LAST_ERROR);
                        }
                    }
                } else {
                    $text .= $item['text'];
                }
            }

            if (strlen($text) > 0) {
                $el = new CIBlockElement;
                $fields = Array(
                    "MODIFIED_BY" => $USER->GetID(),
                    "IBLOCK_SECTION_ID" => $sectionId,
                    "IBLOCK_ID" => $iblockId,
                    "NAME" => $item['name'],
                    "SORT" => $sort + 10,
                    "CODE" => Cutil::translit($name, "en", ["replace_space" => "-", "replace_other" => "-"]),
                    "ACTIVE" => "Y",
                    "DETAIL_TEXT" => $text,
                );
                if (!$elementId = $el->Add($fields)) {
                    throw new Exception("Не получилось создать элемент инфоблока: " . $el->LAST_ERROR);
                }
            }

            $epub->cleanWorkingDir();

            LocalRedirect($request->getRequestedPage() . "?mid=" . urlencode(ADMIN_MODULE_NAME) . "&lang=" . urlencode(LANGUAGE_ID) . "&" . $tabControl->ActiveTabParam() . "&status=ok");
        } else {
            LocalRedirect($request->getRequestedPage() . "?mid=" . urlencode(ADMIN_MODULE_NAME) . "&lang=" . urlencode(LANGUAGE_ID) . "&" . $tabControl->ActiveTabParam() . "&status=error");
        }
    }

    if ($request->getQuery('status') == 'ok') {
        CAdminMessage::showMessage(array(
            "MESSAGE" => 'Книга добавлена',
            "TYPE" => "OK",
        ));
    } elseif ($request->getQuery('status') == 'error') {
        CAdminMessage::showMessage("Файл книги должен быть формата epub");
    }
} catch (Exception $exception) {
    CAdminMessage::showMessage($exception->getMessage());
}

$tabControl->begin();

?>
    <form method="post" enctype="multipart/form-data"
          action="<?= sprintf('%s?mid=%s&lang=%s', $request->getRequestedPage(), urlencode($mid), LANGUAGE_ID) ?>">
        <?php
        echo bitrix_sessid_post();
        $tabControl->beginNextTab();
        ?>
        <tr>
            <td width="40%">
                <label for="epub_file">Файл книги</label>
            <td width="60%">
                <input type="file" id="epub_file" name="epub_file">
            </td>
        </tr>
        <tr class="heading">
            <td colspan="2">
                Свойства ниже необязательны к заполнению. Будет произведена попытка извлечения этих свойств из файла
                книги
            </td>
        </tr>
        <tr>
            <td width="40%">
                <label for="name">Название книги:</label>
            <td width="60%">
                <input type="text" id="name" name="name">
            </td>
        </tr>
        <tr>
            <td width="40%">
                <label for="author">Автор:</label>
            <td width="60%">
                <input type="text" id="author" name="author">
            </td>
        </tr>
        <tr>
            <td width="40%">
                <label for="description">Описание:</label>
            <td width="60%">
                <textarea id="description" name="description"></textarea>
            </td>
        </tr>
        <tr>
            <td width="40%">
                <label for="epub_cover">Обложка:</label>
            <td width="60%">
                <input type="file" name="epub_cover">
            </td>
        </tr>
        <?php
        $tabControl->buttons();
        ?>
        <input type="submit"
               name="save"
               value="<?= Loc::getMessage("MAIN_SAVE") ?>"
               title="<?= Loc::getMessage("MAIN_OPT_SAVE_TITLE") ?>"
               class="adm-btn-save"
        />
        <?php
        $tabControl->end();
        ?>
    </form>

<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
