<?php

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

defined('ADMIN_MODULE_NAME') or define('ADMIN_MODULE_NAME', 'belamov.epubparser');

if (!$USER->isAdmin()) {
    $APPLICATION->authForm('Nope');
}

Loader::includeModule('belamov.epubparser');
$app = Application::getInstance();
$context = $app->getContext();
$request = $context->getRequest();

$tabControl = new CAdminTabControl("tabControl", array(
    array(
        "DIV" => "edit1",
        "TAB" => Loc::getMessage("MAIN_TAB_SET"),
        "TITLE" => Loc::getMessage("MAIN_TAB_TITLE_SET"),
    ),
));

if ((!empty($save) || !empty($restore)) && $request->isPost() && check_bitrix_sessid()) {
    if (!empty($restore)) {
    } else {
        if (!empty($request->getPost('symbols_per_page'))) {
            Option::set(ADMIN_MODULE_NAME, 'symbols_per_page', $request->getPost('symbols_per_page'));
        }
    }
    LocalRedirect($request->getRequestedPage() . "?mid=" . urlencode(ADMIN_MODULE_NAME) . "&lang=" . urlencode(LANGUAGE_ID) . "&" . $tabControl->ActiveTabParam() . "&status=ok");

}
if ($request->getQuery('status') == 'ok') {
    CAdminMessage::showMessage(array(
        "MESSAGE" => 'Настройки сохранены',
        "TYPE" => "OK",
    ));
}
$tabControl->begin();
?>

<form method="post"
      action="<?= sprintf('%s?mid=%s&lang=%s', $request->getRequestedPage(), urlencode($mid), LANGUAGE_ID) ?>">
    <?php
    echo bitrix_sessid_post();
    $tabControl->beginNextTab();
    ?>
    <tr>
        <td width="40%">
            <label for="symbols_per_page">Количество символов на странице</label>
        <td width="60%">
            <input type="text" name="symbols_per_page" id="symbols_per_page"
                   value="<?= Option::get(ADMIN_MODULE_NAME, 'symbols_per_page', "10000") ?>">
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
    <input type="submit"
           name="restore"
           title="<?= Loc::getMessage("MAIN_HINT_RESTORE_DEFAULTS") ?>"
           onclick="return confirm('<?= AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING")) ?>')"
           value="По умолчанию"
    />
    <?php
    $tabControl->end();
    ?>
</form>