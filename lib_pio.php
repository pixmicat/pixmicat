<?php
if(defined('CONNECTION_STRING')){ // 有連線字串
	if(preg_match('/^(.*):\/\//i', CONNECTION_STRING, $backend)){
		define('PIXMICAT_BACKEND',$backend[1]);
	}
}

$pio_file='./pio.'.PIXMICAT_BACKEND.'.php';
if(is_file($pio_file)) include_once($pio_file);
if(defined('CONNECTION_STRING')) dbConnect(CONNECTION_STRING);
?>