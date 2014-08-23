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
	var $CHPList;

	/* Constructor */
	function PMS($ENV){
		$this->loaded = false; // 是否載入完成 (模組及函式)
		$this->ENV = $ENV; // 環境變數
		$this->hooks = array_flip(array('Head', 'Toplink', 'LinksAboveBar', 'PostInfo', 'PostForm',
			'ThreadFront', 'ThreadRear', 'ThreadPost', 'ThreadReply',
			'Foot', 'ModulePage', 'RegistBegin', 'RegistBeforeCommit', 'RegistAfterCommit', 'PostOnDeletion',
			'AdminList', 'AdminFunction', 'Authenticate', 'ThreadOrder'
		));
		$this->hookPoints = array(); // 掛載點
		$this->moduleInstance = array(); // 存放各模組實體
		$this->moduleLists = array(); // 存放各模組類別名稱
		$this->CHPList = array(); // CHP List
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
			if(is_file($mpath) && array_search($f, $this->moduleLists)===false){
				include($mpath);
				$this->moduleLists[] = $f;
				$this->moduleInstance[$f] = new $f($this); // Sent $PMS into constructor
			}
		}
	}

	/* 取得載入模組列表 */
	function getLoadedModules(){
		if(!$this->loaded) $this->init();
		return $this->moduleLists;
	}

	/* 取得模組實體 */
	function getModuleInstance($module){
		return isset($this->moduleInstance[$module])?$this->moduleInstance[$module]:null;
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
			$this->hookPoints[$hookPoint] = array();
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
		if(!isset($this->hooks[$hookPoint])){ // Treat as CHP
			if(!isset($this->CHPList[$hookPoint])) $this->CHPList[$hookPoint] = 1;
		}else if(!isset($this->hookPoints[$hookPoint]) && $hookPoint != 'ModulePage'){ // Treat as normal hook point
			if(!$this->loaded) $this->init();
			$this->__autoHookMethods($hookPoint);
		}
		$this->hookPoints[$hookPoint][] = $methodObject;
	}

	/* 使用模組方法 */
	function useModuleMethods($hookPoint, $parameter){
		if(!$this->loaded) $this->init();
		$arrMethod =& $this->__autoHookMethods($hookPoint); // 取得掛載點模組方法
		$imax = count($arrMethod);
		for($i = 0; $i < $imax; $i++) call_user_func_array($arrMethod[$i], $parameter);
	}

	// CHP (Custom Hook Point)
	/* 新增 CHP */
	function addCHP($CHPName, $methodObject){
		$this->hookModuleMethod($CHPName, $methodObject);
	}

	/* 呼叫 CHP */
	function callCHP($CHPName, $parameter){
		if(!$this->loaded) $this->init(); // 若尚未完全載入則載入全部模組
		if(isset($this->CHPList[$CHPName])) $this->useModuleMethods($CHPName, $parameter);
	}
}

/**
 * ModuleHelper
 * 預先取得 PMS 常用功能方便呼叫
 */
abstract class ModuleHelper implements IModule {
	protected static $PMS;
	private $clazz;

	public function __construct($PMS) {
		// 儲存 $PMS 參考
		if (self::$PMS == null) {
			self::$PMS = $PMS;
		}
		$this->clazz = get_class($this);

		// 自動註冊模組頁面
		if (method_exists($this, 'ModulePage')) {
			$PMS->hookModuleMethod('ModulePage', $this->clazz);
		}
	}

	/**
	 * moduleName 建構器，協助組合出一致的模組名稱
	 *
	 * @param  string $description 模組簡易用途說明
	 * @return string              格式化模組名稱
	 */
	protected function moduleNameBuilder($description) {
		return "{$this->clazz} : $description";
	}

	/**
	 * 回傳模組獨立頁面 URL，並協助建立查詢參數
	 *
	 * @param  array $params URL 參數鍵值表
	 * @return string 模組獨立頁面 URL
	 * @see http_build_query()
	 */
	protected function getModulePageURL(array $params = array()) {
		$query = count($params) != 0 ?
			'&amp;'.http_build_query($params, '', '&amp;') : '';
		return self::$PMS->getModulePageURL($this->clazz).$query;
	}

	/**
	 * 將模組方法掛載於特定掛載點
	 *
	 * @param  string   $hookPoint    掛載點名稱
	 * @param  callable $methodObject 可執行函式
	 */
	protected function hookModuleMethod($hookPoint, $methodObject) {
		self::$PMS->hookModuleMethod($hookPoint, $methodObject);
	}

	/**
	 * 新增自訂掛載點
	 *
	 * @param string $chpName  自訂掛載點名稱
	 * @param callable $callable 可執行函式
	 */
	protected function addCHP($chpName, $callable) {
		self::$PMS->addCHP($chpName, $callable);
	}

	/**
	 * 呼叫自訂掛載點
	 *
	 * @param string $chpName  自訂掛載點名稱
	 * @param array  $params   函式參數
	 */
	protected function callCHP($chpName, array $params) {
		self::$PMS->callCHP($chpName, $params);
	}

	/**
	 * 附加翻譯資源字串。
	 *
	 * @param  array  $lang 翻譯資源字串陣列
	 * @param  string $fallbackLang 備用語系
	 * @throws InvalidArgumentException 如果找不到設定備用語系
	 */
	protected function attachLanguage(array $lang, $fallbackLang = 'en_US') {
		// 取出使用語言，如果不存在則用備用
		if (isset($lang[PIXMICAT_LANGUAGE])) {
			$lang = $lang[PIXMICAT_LANGUAGE];
		} else if (isset($lang[$fallbackLang])) {
			$lang = $lang[$fallbackLang];
		} else {
			throw new InvalidArgumentException(
				sprintf('Assigned locale: %s not found.', $fallbackLang)
			);
		}

		$langKeys = array_keys($lang);
		// 為字串資源鍵值加上模組名前綴
		foreach ($langKeys as $k) {
			$lang[$this->clazz.'_'.$k] = $lang[$k];
			unset($lang[$k]);
		}

		PMCLibrary::getLanguageInstance()->attachLanguage($lang);
	}

	/**
	 * 取出翻譯資源檔對應字串。
	 *
	 * @param args 翻譯資源檔索引、其餘變數
	 * @see LanguageLoader->getTranslation
	 */
	protected function _T() {
		$args = func_get_args();
		// 為字串資源鍵值加上模組名前綴
		if (isset($args[0]) && !empty($args[0])) {
			$args[0] = $this->clazz.'_'.$args[0];
		}
		return call_user_func_array(
			array(PMCLibrary::getLanguageInstance(), 'getTranslation'),
			$args);
	}
}