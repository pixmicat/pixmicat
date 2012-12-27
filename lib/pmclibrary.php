<?php
/**
 * Pixmicat! Library Singleton Factory
 *
 * 集中函式庫以方便呼叫，並可回傳單例物件。
 *
 * @package PMCLibrary
 * @version $Id$
 * @since 7th.Release
 */

require './lib/interfaces.php';
require './lib/lib_errorhandler.php';
require './lib/lib_simplelogger.php';
require './lib/lib_compatible.php';

class PMCLibrary {
	/**
	 * 取得 PIO 函式庫物件
	 *
	 * @return IPIO PIO 函式庫物件
	 */
	public static function getPIOInstance() {
		global $PIOEnv;
		static $instPIO = null;
		if ($instPIO == null) {
			require './lib/lib_pio.php';
			require './lib/lib_pio.loggerinjector.php';
			$pioExactClass = 'PIO'.PIXMICAT_BACKEND;
			$instPIO = new PIOLoggerInjector(
				new $pioExactClass(CONNECTION_STRING, $PIOEnv),
				PMCLibrary::getLoggerInstance($pioExactClass)
			);
		}
		return $instPIO;
	}

	/**
	 * 取得 PTE 函式庫物件
	 *
	 * @return PTELibrary PTE 函式庫物件
	 */
	public static function getPTEInstance() {
		static $instPTE = null;
		if ($instPTE == null) {
			require './lib/lib_pte.php';
			$instPTE = new PTELibrary(TEMPLATE_FILE);
		}
		return $instPTE;
	}

	/**
	 * 取得 PMS 函式庫物件
	 *
	 * @return PMS PMS 函式庫物件
	 */
	public static function getPMSInstance() {
		global $ModuleList;
		static $instPMS = null;
		if ($instPMS == null) {
			require './lib/lib_pms.php';
			$instPMS = new PMS(array( // PMS 環境常數
				'MODULE.PATH' => './module/',
				'MODULE.PAGE' => PHP_SELF.'?mode=module&amp;load=',
				'MODULE.LOADLIST' => $ModuleList
			));
		}
		return $instPMS;
	}

	/**
	 * 取得 FileIO 函式庫物件
	 *
	 * @return FileIO FileIO 函式庫物件
	 */
	public static function getFileIOInstance() {
		static $instFileIO = null;
		if ($instFileIO == null) {
			require './lib/lib_fileio.php';
			$instFileIO = new FileIOWrapper(unserialize(FILEIO_PARAMETER),
				array( // FileIO 環境常數
					'IFS.PATH' => './lib/fileio/ifs.php',
					'IFS.LOG' => FILEIO_INDEXLOG,
					'PATH' => realpath('.').DIRECTORY_SEPARATOR,
					'IMG' => IMG_DIR,
					'THUMB' => THUMB_DIR
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
	public static function getLoggerInstance($name) {
		static $instLogger = array();
		if (!array_key_exists($name, $instLogger)) {
			$instLogger[$name] = new SimpleLogger($name, './error.log');
		}
		return $instLogger[$name];
	}

	/**
	 * 取得語言函式庫物件
	 *
	 * @return LanguageLoader Language 函式庫物件
	 */
	public static function getLanguageInstance() {
		static $instLanguage = null;
		if ($instLanguage == null) {
			require './lib/lib_language.php';
			$instLanguage = LanguageLoader::getInstance();
		}
		return $instLanguage;
	}
}