<?php
/*
Pixmicat! Language module loader
*/
$langattachment=array();

function _T(/*$arg1, $arg2...$argN*/) {
	global $language,$langattachment;
	if (!isset($language))	// language file is not loaded
		LoadLanguage(PIXMICAT_LANGUAGE);
	if (!func_num_args()) // called with no arg
		return '';
	$arg_list = func_get_args();
	$arg_list[0] = isset($language[$arg_list[0]]) ? $language[$arg_list[0]] : $arg_list[0];
	return call_user_func_array('sprintf',$arg_list);
}

function LoadLanguage($locale = 'en_US') {
	global $language,$langattachment;
	if(!defined('PIXMICAT_LANGUAGE') || defined('PIXMICAT_LANGUAGE_OVERLOADING')) // language overloading
		include_once("./lib/lang/en_US.php");
	if (file_exists("./lib/lang/$locale.php"))
		include_once("./lib/lang/$locale.php");
	else
		include_once("./lib/lang/en_US.php");
	foreach($langattachment as $lf) call_user_func($lf); // Attach other language strings
}
function AttachLanguage($fcall){
	global $langattachment,$language;
	$langattachment[]=$fcall;
	call_user_func($fcall);
}
?>