<?php
/**
 * NopLogger means it doesn't log everything.
 * Only for production use. Do not use on testing enviroments.
 *
 * @package PMCLibrary
 * @version $Id: lib_simplelogger.php 833 2012-12-13 15:50:32Z scribe $
 */

class NopLogger implements ILogger {
	public function __construct($logName, $logFile) {}

	public function isDebugEnabled() {
		return false;
	}

	public function isInfoEnabled() {
		return false;
	}

	public function isErrorEnabled() {
		return false;
	}

	public function debug($format, $varargs = '') {}

	public function info($format, $varargs = '') {}

	public function error($format, $varargs = '') {}
}