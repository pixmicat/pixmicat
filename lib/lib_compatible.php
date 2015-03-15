<?php
namespace Pixmicat;

/**
 * Pixmicat! compatible components
 *
 * @package PMCLibrary
 * @version $Id$
 * @deprecated 未來要拿掉
 */

/**
 * 取出翻譯資源檔對應字串。
 *
 * @param args 翻譯資源檔索引、其餘變數
 * @see LanguageLoader->getTranslation
 * @return string 翻譯後之字串
 */
function _T(/*$args[]*/)
{
    return \call_user_func_array(
        array(PMCLibrary::getLanguageInstance(), 'getTranslation'),
        \func_get_args()
    );
}

/**
 * 動態附加翻譯資源。此函式已經由 {@link #LanguageLoader->attachLanguage} 取代。
 *
 * @deprecated 7th.Release. Use LanguageLoader->attachLanguage instead.
 * @param callable $fcall 附加翻譯資源字串的函式
 */
function AttachLanguage($fcall)
{
    $GLOBALS['language'] = array();
    \call_user_func($fcall);
    PMCLibrary::getLanguageInstance()->attachLanguage($GLOBALS['language']);
}
