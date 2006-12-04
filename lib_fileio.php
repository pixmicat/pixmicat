<?php
/*
FileIO Kernel Switcher
*/

// 引入必要函式庫
$fileio_file = './fileio/fileio.'.FILEIO_BACKEND.'.php';
if(is_file($fileio_file)) include_once($fileio_file);
?>