<?php
/*
Pixmicat! Module System
@Date : 2007/1/25 13:11
*/

class PMS{
	var $moduleInstance, $moduleLists;
	var $hookPoints;

	/* Constructor */
	function PMS(){
		$this->hookPoints = array('Head'=>array(), 'Body'=>array(), 'Foot'=>array(), 'ModulePage'=>array()); // 掛載點
		$this->moduleInstance = array(); // 存放各模組實體
		$this->moduleLists = array(); // 存放各模組類別名稱
	}

	// 模組載入相關
	/* 載入擴充模組 */
	function loadModules($moduleList){
		foreach($moduleList as $f){
			if(is_file('./modules/'.$f.'.php')){
				include('./modules/'.$f.'.php');
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

	// 模組掛載與使用相關
	/* 自動掛載相關模組方法於掛載點 */
	function autoHookMethods(){
		foreach(array_keys($this->hookPoints) as $h){
			foreach($this->moduleLists as $m)
				if(method_exists($this->moduleInstance[$m], 'autoHook'.$h)){
					$this->hookModuleMethod($h, array(&$this->moduleInstance[$m], 'autoHook'.$h));
				}
		}
	}

	/* 將模組方法掛載於特定掛載點 */
	function hookModuleMethod($hookPoint, $methodObject){
		if(isset($this->hookPoints[$hookPoint])) $this->hookPoints[$hookPoint][] = $methodObject;
	}

	/* 使用模組方法 */
	function useModuleMethods($hookPoint, $parameter){
		$imax = count($this->hookPoints[$hookPoint]);
		for($i = 0; $i < $imax; $i++){
			call_user_func_array($this->hookPoints[$hookPoint][$i], $parameter);
		}
		//print_r($this->hookPoints[$hookPoint]);
	}
}
$PMS = new PMS();
$PMS->loadModules($ModuleList); $PMS->autoHookMethods();
?>