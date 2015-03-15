<?php
namespace Pixmicat;

define('PHP_SELF', 'default.php');
require './config.php';
require ROOTPATH . 'vendor/autoload.php';
require ROOTPATH . 'lib/lib_compatible.php';
require ROOTPATH . 'lib/lib_common.php';

/**
 * 程式首次執行之初始化
 *
 * @return boolean 是否無錯誤完成
 */
function init()
{
    $PIO = PMCLibrary::getPIOInstance();
    $FileIO = PMCLibrary::getFileIOInstance();

    if (file_exists(PHP_SELF2)) {
        return true;
    }

    if (!is_writable(STORAGE_PATH)) {
        error(_T('init_permerror'));
        return false;
    }

    createDirectories();
    $PIO->dbInit();
    $FileIO->init();

    return true;
}

function createDirectories()
{
    $chkfolder = array(
        IMG_DIR,
        THUMB_DIR,
        STORAGE_PATH . 'cache/'
    );

    foreach ($chkfolder as $value) {
        if (!is_dir($value)) {
            mkdir($value);
            chmod($value, 0777);
        }
    }
}

function redirect()
{
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: pixmicat.php");
}

$result = init();
if ($result) {
    redirect();
}