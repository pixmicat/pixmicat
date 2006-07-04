<?php
define('PIXMICAT_BACKEND','log');

$pio_file='./pio.'.PIXMICAT_BACKEND.'.php';
if(is_file($pio_file))
	include_once($pio_file);
?>