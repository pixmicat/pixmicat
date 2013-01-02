<?php
/**
 * Pixmicat! compatible components
 *
 * @package PMCLibrary
 * @version $Id: lib_language.php 833 2012-12-13 15:50:32Z scribe $
 */

/**
 * 取出翻譯資源檔對應字串。
 *
 * @param args 翻譯資源檔索引、其餘變數
 * @see LanguageLoader->getTranslation
 */
function _T(/*$args[]*/) {
	return call_user_func_array(
		array(PMCLibrary::getLanguageInstance(), 'getTranslation'),
		func_get_args());
}

/**
 * 動態附加翻譯資源。此函式已經由 {@link #LanguageLoader->attachLanguage} 取代。
 *
 * @deprecated 7th.Release. Use LanguageLoader->attachLanguage instead.
 * @param callable $fcall 附加翻譯資源字串的函式
 */
function AttachLanguage($fcall){
	$GLOBALS['language'] = array();
	call_user_func($fcall);
	PMCLibrary::getLanguageInstance()->attachLanguage($GLOBALS['language']);
}

// 為了相容舊寫法而保留
$PIO = PMCLibrary::getPIOInstance();
$FileIO = PMCLibrary::getFileIOInstance();
$PTE = PMCLibrary::getPTEInstance();
$PMS = PMCLibrary::getPMSInstance();