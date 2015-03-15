<?php
namespace Pixmicat;

use Pixmicat\FileIo\IFileIO;
use Pixmicat\Lang\LanguageLoader;
use Pixmicat\Logger\ILogger;
use Pixmicat\Logger\LoggerInjector;
use Pixmicat\Logger\LoggerInterceptor;
use Pixmicat\Logger\SimpleLogger;
use Pixmicat\Module\PMS;
use Pixmicat\Pio\IPIO;
use Pixmicat\Thumb\IThumb;

/**
 * Pixmicat! Library Singleton Factory
 *
 * 集中函式庫以方便呼叫，並可回傳單例物件。
 *
 * @package PMCLibrary
 * @version $Id$
 * @since 7th.Release
 */
class PMCLibrary
{
    /**
     * 取得 PIO 函式庫物件
     *
     * @return IPIO PIO 函式庫物件
     */
    public static function getPIOInstance()
    {
        static $instPIO = null;
        if ($instPIO == null) {
            // 分析連線字串
            $backend = '';
            if (\preg_match('/^(.*):\/\//i', \CONNECTION_STRING, $backend)) {
                define('PIXMICAT_BACKEND', $backend[1]);
            }

            $PIOEnv = array(// PIO 環境常數
                'BOARD' => \STORAGE_PATH,
                'NONAME' => \DEFAULT_NONAME,
                'NOTITLE' => \DEFAULT_NOTITLE,
                'NOCOMMENT' => \DEFAULT_NOCOMMENT,
                'PERIOD.POST' => \RENZOKU,
                'PERIOD.IMAGEPOST' => \RENZOKU2
            );

            $pioExactClass = '\Pixmicat\Pio\PIO' . \PIXMICAT_BACKEND;
            $instPIO = new LoggerInjector(
                new $pioExactClass(\CONNECTION_STRING, $PIOEnv),
                new LoggerInterceptor(self::getLoggerInstance($pioExactClass))
            );
        }
        return $instPIO;
    }

    /**
     * 取得 PTE 函式庫物件
     *
     * @return PTELibrary PTE 函式庫物件
     */
    public static function getPTEInstance()
    {
        static $instPTE = null;
        if ($instPTE == null) {
            $instPTE = new PTELibrary(\ROOTPATH . \TEMPLATE_FILE);
        }
        return $instPTE;
    }

    /**
     * 取得 PMS 函式庫物件
     *
     * @return PMS PMS 函式庫物件
     */
    public static function getPMSInstance()
    {
        global $ModuleList;
        static $instPMS = null;
        if ($instPMS == null) {
            $instPMS = new PMS(
                array( // PMS 環境常數
                    'MODULE.PATH' => \ROOTPATH . 'module/',
                    'MODULE.PAGE' => \PHP_SELF . '?mode=module&amp;load=',
                    'MODULE.LOADLIST' => $ModuleList
                )
            );
        }
        return $instPMS;
    }

    /**
     * 取得 FileIO 函式庫物件
     *
     * @return IFileIO FileIO 函式庫物件
     */
    public static function getFileIOInstance()
    {
        static $instFileIO = null;
        if ($instFileIO == null) {
            $fileIoExactClass = '\Pixmicat\FileIo\FileIO' . \FILEIO_BACKEND;
            $instFileIO = new $fileIoExactClass(
                \unserialize(\FILEIO_PARAMETER),
                array( // FileIO 環境常數
                    'IFS.LOG' => \STORAGE_PATH . \FILEIO_INDEXLOG,
                    'IMG' => \IMG_DIR,
                    'THUMB' => \THUMB_DIR
                )
            );
        }
        return $instFileIO;
    }

    /**
     * 取得 Logger 函式庫物件
     *
     * @param string $name 識別名稱
     * @return ILogger Logger 函式庫物件
     */
    public static function getLoggerInstance($name = 'Global')
    {
        static $instLogger = array();
        if (!\array_key_exists($name, $instLogger)) {
            $instLogger[$name] = new SimpleLogger($name, \STORAGE_PATH .'error.log');
        }
        return $instLogger[$name];
    }

    /**
     * 取得語言函式庫物件
     *
     * @return LanguageLoader Language 函式庫物件
     */
    public static function getLanguageInstance()
    {
        static $instLanguage = null;
        if ($instLanguage == null) {
            $instLanguage = LanguageLoader::getInstance();
        }
        return $instLanguage;
    }

    /**
     * 取得預覽圖生成物件。
     *
     * @return IThumb 預覽圖生成物件
     */
    public static function getThumbInstance()
    {
        static $instThumb = null;
        if ($instThumb == null) {
            $thumbType = \USE_THUMB;
            if (\USE_THUMB == 1) {
                $thumbType = 'Gd';
            }

            $exactClassName = "\\Pixmicat\\Thumb\\$thumbType";
            $instThumb = new $exactClassName();
        }
        return $instThumb;
    }
}