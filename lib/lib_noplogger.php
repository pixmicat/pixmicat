<?php
/**
 * NopLogger means it doesn't log anything.
 * Only for production use. Do not use on testing enviroments.
 * Because there is no traceable information left to provide for debugging.
 *
 * @package PMCLibrary
 * @version $Id$
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