<?php
namespace Pixmicat\Module;

/**
 * Pixmicat! Module System
 *
 * 增加掛載點供函式掛上並在需要時依序呼叫以動態改變內容或達成各種效果
 *
 * @package PMCLibrary
 * @version $Id$
 * @date $Date$
 */
class PMS
{
    private $env;
    private $moduleInstance;
    private $moduleLists;
    private $hookPoints;
    private $loaded;
    private $chpList;

    /* Constructor */
    public function __construct($ENV)
    {
        $this->loaded = false; // 是否載入完成 (模組及函式)
        $this->env = $ENV; // 環境變數
        $this->hooks = array_flip(
            array(
                'Head', 'Toplink', 'LinksAboveBar', 'PostInfo', 'PostForm',
                'ThreadFront', 'ThreadRear', 'ThreadPost', 'ThreadReply',
                'Foot', 'ModulePage', 'RegistBegin', 'RegistBeforeCommit', 'RegistAfterCommit', 'PostOnDeletion',
                'AdminList', 'AdminFunction', 'Authenticate', 'ThreadOrder'
            )
        );
        $this->hookPoints = array(); // 掛載點
        $this->moduleInstance = array(); // 存放各模組實體
        $this->moduleLists = array(); // 存放各模組類別名稱
        $this->chpList = array(); // CHP List
    }

    // 模組載入相關
    /* 載入模組 */
    public function init()
    {
        $this->loaded = true;
        $this->loadModules();
        return true;
    }

    /* 單載入模式 */
    public function onlyLoad($specificModule)
    {
        // 搜尋載入模組列表有沒有，沒有就直接取消程式
        if (\array_search($specificModule, $this->env['MODULE.LOADLIST']) === false) {
            return false;
        }
        $this->loadModules($specificModule);
        return isset($this->hookPoints['ModulePage']);
    }

    /* 載入擴充模組 */
    public function loadModules($specificModule=false)
    {
        $loadlist = $specificModule ? array($specificModule) : $this->env['MODULE.LOADLIST'];
        foreach ($loadlist as $f) {
            $mpath = $this->env['MODULE.PATH'].$f.'.php';
            if (\is_file($mpath) && \array_search($f, $this->moduleLists)===false) {
                include($mpath);
                $this->moduleLists[] = $f;
                $exactModuleClassName = "\\Pixmicat\\Module\\$f";
                $this->moduleInstance[$f] = new $exactModuleClassName($this); // Sent $PMS into constructor
            }
        }
    }

    /* 取得載入模組列表 */
    public function getLoadedModules()
    {
        if (!$this->loaded) {
            $this->init();
        }
        return $this->moduleLists;
    }

    /* 取得模組實體 */
    public function getModuleInstance($module)
    {
        return isset($this->moduleInstance[$module])
            ? $this->moduleInstance[$module]
            : null;
    }

    /**
     * 取得全部掛載模組實體列表。
     *
     * @return array 掛載模組實體列表
     */
    public function getModuleInstances() {
        return $this->moduleInstance;
    }

    /* 取得特定模組方法列表 */
    public function getModuleMethods($module)
    {
        if (!$this->loaded) {
            $this->init();
        }
        return \array_search($module, $this->moduleLists) !== false
            ? \get_class_methods($module)
            : array();
    }

    // 提供給模組的取用資訊
    /* 取得模組註冊獨立頁面之網址 */
    public function getModulePageURL($name)
    {
        return $this->env['MODULE.PAGE'] . $name;
    }

    // 模組掛載與使用相關
    /* 自動掛載相關模組方法於掛載點並回傳掛載點 (Return by Reference) */
    private function &autoHookMethods($hookPoint)
    {
        if (isset($this->hooks[$hookPoint]) && !isset($this->hookPoints[$hookPoint])) { // 尚未掛載
            $this->hookPoints[$hookPoint] = array();
            foreach ($this->moduleLists as $m) {
                if (\method_exists($this->moduleInstance[$m], 'autoHook' . $hookPoint)) {
                    $this->hookModuleMethod(
                        $hookPoint,
                        array(&$this->moduleInstance[$m], 'autoHook' . $hookPoint)
                    );
                }
            }
        }
        return $this->hookPoints[$hookPoint];
    }

    /* 將模組方法掛載於特定掛載點 */
    public function hookModuleMethod($hookPoint, $methodObject)
    {
        if (!isset($this->hooks[$hookPoint])) { 
            // Treat as CHP
            if (!isset($this->chpList[$hookPoint])) {
                $this->chpList[$hookPoint] = 1;
            }
        } else if (!isset($this->hookPoints[$hookPoint]) && $hookPoint != 'ModulePage') {
            // Treat as normal hook point
            if (!$this->loaded) {
                $this->init();
            }
            $this->autoHookMethods($hookPoint);
        }
        $this->hookPoints[$hookPoint][] = $methodObject;
    }

    /* 使用模組方法 */
    public function useModuleMethods($hookPoint, $parameter)
    {
        if (!$this->loaded) {
            $this->init();
        }
        $arrMethod =& $this->autoHookMethods($hookPoint); // 取得掛載點模組方法
        $imax = \count($arrMethod);
        for ($i = 0; $i < $imax; $i++) {
            \call_user_func_array($arrMethod[$i], $parameter);
        }
    }

    // CHP (Custom Hook Point)
    /* 新增 CHP */
    public function addCHP($CHPName, $methodObject)
    {
        $this->hookModuleMethod($CHPName, $methodObject);
    }

    /* 呼叫 CHP */
    public function callCHP($CHPName, $parameter)
    {
        if (!$this->loaded) {
            $this->init();
        }

        // 若尚未完全載入則載入全部模組
        if (isset($this->chpList[$CHPName])) {
            $this->useModuleMethods($CHPName, $parameter);
        }
    }
}

