<?php
/*
PIO - Pixmicat! data source I/O
PIO Kernel Switcher
*/

// 分析連線字串
if(preg_match('/^(.*):\/\//i', CONNECTION_STRING, $backend)) define('PIXMICAT_BACKEND', $backend[1]);

// 引入必要函式庫
$pio_file = './lib/pio/pio.'.PIXMICAT_BACKEND.'.php';
$PIOEnv = array( // PIO 環境常數
	'BOARD' => '.',
	'LUTCACHE' => './lutcache.dat',
	'NONAME' => DEFAULT_NONAME,
	'NOTITLE' => DEFAULT_NOTITLE,
	'NOCOMMENT' => DEFAULT_NOCOMMENT,
	'LOG_MAX' => LOG_MAX,
	'PERIOD.POST' => RENZOKU,
	'PERIOD.IMAGEPOST' => RENZOKU2
);
if(is_file($pio_file)) include_once($pio_file);

// PIO Kernel Switcher
$pioSwitch = 'PIO'.PIXMICAT_BACKEND;
$PIO = new $pioSwitch(CONNECTION_STRING, $PIOEnv);
?>