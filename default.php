<?php

define('PHP_SELF', 'default.php');
require './config.php';
require ROOTPATH . 'lib/pmclibrary.php';
require ROOTPATH . 'lib/lib_errorhandler.php';
require ROOTPATH . 'lib/lib_compatible.php';
require ROOTPATH . 'lib/lib_common.php';

/**
 * 程式首次執行之初始化
 */
function init() {
    $PIO = PMCLibrary::getPIOInstance();
    $FileIO = PMCLibrary::getFileIOInstance();

    if (!is_writable(STORAGE_PATH)) {
        error(_T('init_permerror'));
    }

    createDirectories();
    $PIO->dbInit();
    $FileIO->init();
    error(_T('init_inited'));
}

function createDirectories() {
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

init();