<?php
/**
 * Pixmicat! Module System Dispatcher
 *
 * 設定環境變數並初始化物件以供使用
 * 
 * @package PMCLibrary
 * @version $Id$
 * @date $Date$
 */

$PMSEnv = array( // PMS 環境常數
	'MODULE.PATH' => './module/',
	'MODULE.PAGE' => PHP_SELF.'?mode=module&amp;load=',
	'MODULE.LOADLIST' => $ModuleList
);
require('./lib/pms.php');
$PMS = new PMS($PMSEnv);
?>