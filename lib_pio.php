<?php
/*
PIO - Pixmicat! data source I/O
PIO Kernel Switcher
*/

// 分析連線字串
if(preg_match('/^(.*):\/\//i', CONNECTION_STRING, $backend)) define('PIXMICAT_BACKEND', $backend[1]);

// 引入必要函式庫
$pio_file = './pio/pio.'.PIXMICAT_BACKEND.'.php';
if(is_file($pio_file)) include_once($pio_file);

// PIO Kernel Switcher
$pioSwitch = 'PIO'.PIXMICAT_BACKEND;
$pio = new $pioSwitch(CONNECTION_STRING);
?>