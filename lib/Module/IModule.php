<?php
namespace Pixmicat\Module;

/**
 * Module Interface
 */
interface IModule
{
    /**
     * 回傳模組名稱方法
     *
     * @return string 模組名稱。建議回傳格式: mod_xxx : 簡短註解
     */
    function getModuleName();

    /**
     * 回傳模組版本號方法
     *
     * @return string 模組版本號
     */
    function getModuleVersionInfo();
}
