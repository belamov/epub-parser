<?php

use Bitrix\Main\Localization\Loc;

$menu = array(
    array(
        'parent_menu' => 'global_menu_content',
        'sort' => 400,
        'text' => "Загрузить epub книгу",
        'title' => "Загрузить epub книгу",
        'url' => 'epub_upload.php',
        'items_id' => 'menu_references',
    ),
);

return $menu;