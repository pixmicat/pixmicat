<?php
namespace Pixmicat\Logger;

/**
 * Logger Interface.
 * 
 * @deprecated Should be replaced by PSR-3
 */
interface ILogger
{
    /**
     * 建構元。
     *
     * @param string $logName Logger 名稱
     * @param string $logFile 記錄檔案位置
     */
    function __construct($logName, $logFile);

    /**
     * 檢查是否 logger 要記錄 DEBUG 等級。
     *
     * @return boolean 要記錄 DEBUG 等級與否
     */
    function isDebugEnabled();

    /**
     * 檢查是否 logger 要記錄 INFO 等級。
     *
     * @return boolean 要記錄 INFO 等級與否
     */
    function isInfoEnabled();

    /**
     * 檢查是否 logger 要記錄 ERROR 等級。
     *
     * @return boolean 要記錄 ERROR 等級與否
     */
    function isErrorEnabled();

    /**
     * 以 DEBUG 等級記錄訊息。
     *
     * @param string $format 格式化訊息內容
     * @param mixed $varargs 參數
     */
    function debug($format, $varargs = '');

    /**
     * 以 INFO 等級記錄訊息。
     *
     * @param string $format 格式化訊息內容
     * @param mixed $varargs 參數
     */
    function info($format, $varargs = '');

    /**
     * 以 ERROR 等級記錄訊息。
     *
     * @param string $format 格式化訊息內容
     * @param mixed $varargs 參數
     */
    function error($format, $varargs = '');
}
