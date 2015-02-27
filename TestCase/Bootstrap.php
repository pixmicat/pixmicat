<?php
define('PHP_SELF', 'pixmicat.php');
define('DEBUG', TRUE);
$_SERVER['HTTP_HOST'] = '127.0.0.1';
require dirname(__FILE__).'/../config.php';
require ROOTPATH.'lib/PMCLibrary.php';
require ROOTPATH.'vendor/autoload.php';
require ROOTPATH.'lib/lib_compatible.php'; // 引入相容函式庫
require ROOTPATH.'lib/lib_common.php'; // 引入共通函式檔案