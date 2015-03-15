<?php
namespace Pixmicat\Module;

use Pixmicat\PMCLibrary;

/**
 * ModuleHelper
 * 預先取得 PMS 常用功能方便呼叫
 */
abstract class ModuleHelper implements IModule
{
    /** @var PMS */
    protected static $PMS;
    private $clazz;

    public function __construct(PMS $PMS)
    {
        // 儲存 $PMS 參考
        if (self::$PMS == null) {
            self::$PMS = $PMS;
        }
        $this->clazz = \get_class($this);

        // 自動註冊模組頁面
        if (\method_exists($this, 'ModulePage')) {
            $PMS->hookModuleMethod('ModulePage', $this->clazz);
        }
    }

    /**
     * moduleName 建構器，協助組合出一致的模組名稱
     *
     * @param  string $description 模組簡易用途說明
     * @return string              格式化模組名稱
     */
    protected function moduleNameBuilder($description)
    {
        return "{$this->clazz} : $description";
    }

    /**
     * 回傳模組獨立頁面 URL，並協助建立查詢參數
     *
     * @param  array $params URL 參數鍵值表
     * @return string 模組獨立頁面 URL
     * @see http_build_query()
     */
    protected function getModulePageURL(array $params = array())
    {
        $query = \count($params) != 0
            ? '&amp;' . \http_build_query($params, '', '&amp;')
            : '';
        return self::$PMS->getModulePageURL($this->clazz) . $query;
    }

    /**
     * 將模組方法掛載於特定掛載點
     *
     * @param  string   $hookPoint    掛載點名稱
     * @param  callable $methodObject 可執行函式
     */
    protected function hookModuleMethod($hookPoint, $methodObject)
    {
        self::$PMS->hookModuleMethod($hookPoint, $methodObject);
    }

    /**
     * 新增自訂掛載點
     *
     * @param string $chpName  自訂掛載點名稱
     * @param callable $callable 可執行函式
     */
    protected function addCHP($chpName, $callable)
    {
        self::$PMS->addCHP($chpName, $callable);
    }

    /**
     * 呼叫自訂掛載點
     *
     * @param string $chpName  自訂掛載點名稱
     * @param array  $params   函式參數
     */
    protected function callCHP($chpName, array $params)
    {
        self::$PMS->callCHP($chpName, $params);
    }

    /**
     * 附加翻譯資源字串。
     *
     * @param  array  $lang 翻譯資源字串陣列
     * @param  string $fallbackLang 備用語系
     * @throws \InvalidArgumentException 如果找不到設定備用語系
     */
    protected function attachLanguage(array $lang, $fallbackLang = 'en_US')
    {
        // 取出使用語言，如果不存在則用備用
        if (isset($lang[PIXMICAT_LANGUAGE])) {
            $lang = $lang[PIXMICAT_LANGUAGE];
        } elseif (isset($lang[$fallbackLang])) {
            $lang = $lang[$fallbackLang];
        } else {
            throw new \InvalidArgumentException(
                \sprintf('Assigned locale: %s not found.', $fallbackLang)
            );
        }

        $langKeys = \array_keys($lang);
        // 為字串資源鍵值加上模組名前綴
        foreach ($langKeys as $k) {
            $lang[$this->clazz . '_' . $k] = $lang[$k];
            unset($lang[$k]);
        }

        PMCLibrary::getLanguageInstance()->attachLanguage($lang);
    }

    /**
     * 取出翻譯資源檔對應字串。
     *
     * @param args 翻譯資源檔索引、其餘變數
     * @see LanguageLoader->getTranslation
     * @return string 翻譯字串
     */
    protected function _T()
    {
        $args = \func_get_args();
        // 為字串資源鍵值加上模組名前綴
        if (isset($args[0]) && !empty($args[0])) {
            $args[0] = $this->clazz . '_' . $args[0];
        }
        return \call_user_func_array(
            array(PMCLibrary::getLanguageInstance(), 'getTranslation'),
            $args
        );
    }
}
