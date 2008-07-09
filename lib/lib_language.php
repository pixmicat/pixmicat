<?php
/*
Pixmicat! Language module loader
*/
$langloaded = false; // Is language file loaded?

function _T(/*$arg1, $arg2...$argN*/) {
	global $language,$langloaded;
	if (!$langloaded){ // language file is not loaded
		LoadLanguage(PIXMICAT_LANGUAGE); $langloaded = true;
	}
	if (!func_num_args()) // called with no arg
		return '';
	$arg_list = func_get_args();
	$arg_list[0] = isset($language[$arg_list[0]]) ? $language[$arg_list[0]] : $arg_list[0];
	return call_user_func_array('sprintf',$arg_list);
}

function LoadLanguage($locale = 'en_US') {
	global $language;
	if(!defined('PIXMICAT_LANGUAGE') || defined('PIXMICAT_LANGUAGE_OVERLOADING')) // language overloading
		include_once("./lib/lang/en_US.php");
	if (file_exists("./lib/lang/$locale.php"))
		include_once("./lib/lang/$locale.php");
	else
		include_once("./lib/lang/en_US.php");
}
function AttachLanguage($fcall){
	global $language,$langloaded;
	if (!$langloaded){ // language file is not loaded
		LoadLanguage(PIXMICAT_LANGUAGE); $langloaded = true;
	}
	call_user_func($fcall);
}
?>