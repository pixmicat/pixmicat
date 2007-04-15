<?php
/**
 * Pixmicat! Module System Dispatcher
 *
 * 設定環境變數並初始化物件以供使用
 * 
 * @package PMCLibrary
 * @version $Id: lib_pms.php 389 2007-04-15 13:48:08Z scribe $
 * @date $Date: 2007-04-15 21:48:08 +0800 (星期日, 15 四月 2007) $
 */

$PMSEnv = array( // PMS 環境常數
	'MODULE.PATH' => './modules/',
	'MODULE.PAGE' => PHP_SELF.'?mode=module&amp;load=',
	'MODULE.LOADLIST' => $ModuleList
);
require('./pms.php');
$PMS = new PMS($PMSEnv);
$PMS->init();
?>