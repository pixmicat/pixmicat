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
	var $loaded;

	/* Constructor */
	function PMS($ENV){
		$this->loaded = false; // 是否載入完成 (模組及函式)
		$this->ENV = $ENV; // 環境變數
		$this->hooks = array_flip(array('Head', 'Toplink', 'LinksAboveBar', 'PostInfo', 'PostForm',
			'ThreadFront', 'ThreadRear', 'ThreadPost', 'ThreadReply',
			'Foot', 'ModulePage', 'RegistBegin', 'RegistBeforeCommit', 'UsageExceed',
			'AdminList', 'Authenticate'
		));
		$this->hookPoints = array(); // 掛載點
		$this->moduleInstance = array(); // 存放各模組實體
		$this->moduleLists = array(); // 存放各模組類別名稱
	}

	// 模組載入相關
	/* 載入模組 */
	function init(){
		$this->loaded = true;
		$this->loadModules();
		return true;
	}

	/* 單載入模式 */
	function onlyLoad($specificModule){
		// 搜尋載入模組列表有沒有，沒有就直接取消程式
		if(array_search($specificModule, $this->ENV['MODULE.LOADLIST'])===false) return false;
		$this->loadModules($specificModule);
		return isset($this->hookPoints['ModulePage']);
	}

	/* 載入擴充模組 */
	function loadModules($specificModule=false){
		$loadlist = $specificModule ? array($specificModule) : $this->ENV['MODULE.LOADLIST'];
		foreach($loadlist as $f){
			$mpath = $this->ENV['MODULE.PATH'].$f.'.php';
			if(is_file($mpath) && !isset($this->moduleInstance[$f])){
				include_once($mpath);
				$this->moduleInstance[$f] = new $f();
				$this->moduleLists[] = $f;
			}
		}
	}

	/* 取得載入模組列表 */
	function getLoadedModules(){
		if(!$this->loaded) $this->init();
		return $this->moduleLists;
	}

	/* 取得特定模組方法列表 */
	function getModuleMethods($module){
		if(!$this->loaded) $this->init();
		return array_search($module, $this->moduleLists)!==false ? get_class_methods($module) : array();
	}

	// 提供給模組的取用資訊
	/* 取得模組註冊獨立頁面之網址 */
	function getModulePageURL($name){
		return $this->ENV['MODULE.PAGE'].$name;
	}

	// 模組掛載與使用相關
	/* 自動掛載相關模組方法於掛載點並回傳掛載點 (Return by Reference) */
	function &__autoHookMethods($hookPoint){
		if(isset($this->hooks[$hookPoint]) && !isset($this->hookPoints[$hookPoint])){ // 尚未掛載
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
		if(!isset($this->hooks[$hookPoint])) return false;
		if(!isset($this->hookPoints[$hookPoint])) $this->hookPoints[$hookPoint] = array();
		$this->hookPoints[$hookPoint][] = $methodObject;
	}

	/* 使用模組方法 */
	function useModuleMethods($hookPoint, $parameter){
		if(!$this->loaded) $this->init();
		$arrMethod =& $this->__autoHookMethods($hookPoint); // 取得掛載點模組方法
		$imax = count($arrMethod);
		for($i = 0; $i < $imax; $i++) call_user_func_array($arrMethod[$i], $parameter);
	}
}
?>