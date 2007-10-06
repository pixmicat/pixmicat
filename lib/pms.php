<?php
/**
 * Pixmicat! Module System
 *
 * 增加掛載點供函式掛上並在需要時依序呼叫以動態改變內容或達成各種效果
 * 
 * @package PMCLibrary
 * @version $Id$
 * @date $Date$
 */

class PMS{
	var $ENV;
	var $moduleInstance, $moduleLists;
	var $hookPoints;

	/* Constructor */
	function PMS($ENV){
		$this->ENV = $ENV; // 環境變數
		// 掛載點
		$this->hookPoints = array(
			'Head'=>array(), 'Toplink'=>array(), 'LinksAboveBar'=>array(),
			'PostInfo'=>array(), 'PostForm'=>array(),
			'ThreadFront'=>array(), 'ThreadRear'=>array(),
			'ThreadPost'=>array(), 'ThreadReply'=>array(),
			'Foot'=>array(), 'ModulePage'=>array(),
			'RegistBegin'=>array(), 'RegistBeforeCommit'=>array(), 'UsageExceed'=>array(),
			'AdminList'=>array(), 'Authenticate'=>array()
		);
		$this->moduleInstance = array(); // 存放各模組實體
		$this->moduleLists = array(); // 存放各模組類別名稱
	}

	// 模組載入相關
	/* 進行初始化 */
	function init(){
		$this->loadModules();
		return true;
	}

	/* 載入擴充模組 */
	function loadModules(){
		$loadlist = $this->ENV['MODULE.LOADLIST'];
		foreach($loadlist as $f){
			$mpath = $this->ENV['MODULE.PATH'].$f.'.php';
			if(is_file($mpath)){
				include($mpath);
				$this->moduleInstance[$f] = new $f();
				$this->moduleLists[] = $f;
			}
		}
	}

	/* 取得載入模組列表 */
	function getLoadedModules(){
		return $this->moduleLists;
	}

	/* 取得特定模組方法列表 */
	function getModuleMethods($module){
		return array_search($module, $this->moduleLists)!==false ? get_class_methods($module) : array();
	}

	// 提供給模組的取用資訊
	/* 取得模組註冊獨立頁面之網址 */
	function getModulePageURL($name){
		return $this->ENV['MODULE.PAGE'].$name;
	}

	// 模組掛載與使用相關
	/* 自動掛載相關模組方法於掛載點並回傳掛載點 (Returning by Reference) */
	function &__autoHookMethods($hookPoint){
		if(count($this->hookPoints[$hookPoint])==0){ // 尚未掛載
			foreach($this->moduleLists as $m){
				if(method_exists($this->moduleInstance[$m], 'autoHook'.$hookPoint)){
					$this->hookModuleMethod($hookPoint, array(&$this->moduleInstance[$m], 'autoHook'.$hookPoint));
				}
			}
		}
		return $this->hookPoints[$hookPoint];
	}

	/* 將模組方法掛載於特定掛載點 */
	function hookModuleMethod($hookPoint, $methodObject){
		if(isset($this->hookPoints[$hookPoint])) $this->hookPoints[$hookPoint][] = $methodObject;
	}

	/* 使用模組方法 */
	function useModuleMethods($hookPoint, $parameter){
		$arrMethod =& $this->__autoHookMethods($hookPoint); // 取得掛載點模組方法
		$imax = count($arrMethod);
		for($i = 0; $i < $imax; $i++){
			call_user_func_array($arrMethod[$i], $parameter);
		}
	}
}
?>