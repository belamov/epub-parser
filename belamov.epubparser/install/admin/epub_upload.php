<?php
$path = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/belamov.epubparser/admin/epub_upload.php';
if (!file_exists($path)) {
    $path = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/belamov.epubparser/admin/epub_upload.php';
}
require_once $path;