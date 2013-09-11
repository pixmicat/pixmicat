<?php
/**
 * A simple ILogger implementation.
 * Log everything it got to the log file. (Default log level: ERROR only)
 *
 * @package PMCLibrary
 * @version $Id$
 */

class SimpleLogger implements ILogger {
	private $logName;
	private $logFile;

	public function __construct($logName, $logFile) {
		$this->logName = $logName;
		$this->logFile = $logFile;
	}

	public function isDebugEnabled() {
		return (defined('DEBUG') && DEBUG);
	}

	public function isInfoEnabled() {
		return (defined('DEBUG') && DEBUG);
	}

	public function isErrorEnabled() {
		return true;
	}

	public function debug($format, $varargs = '') {
		if (!$this->isDebugEnabled()) return;

		if (is_array($varargs)) {
			// Array into structure string
			$varargs = array(var_export($varargs, true));
		} else {
			$varargs = func_get_args();
			array_shift($varargs);
		}
		$this->logFormat('DEBUG', $format, $varargs);
	}

	public function info($format, $varargs = '') {
		if (!$this->isInfoEnabled()) return;

		$varargs = func_get_args();
		array_shift($varargs);
		$this->logFormat(' INFO', $format, $varargs);
	}

	public function error($format, $varargs = '') {
		if (!$this->isErrorEnabled()) return;

		$varargs = func_get_args();
		array_shift($varargs);
		$this->logFormat('ERROR', $format, $varargs);
	}

	/**
	 * Log with format message.
	 *
	 * @param  string $logLevel   Log level
	 * @param  string $message Format message
	 * @param  array  $vars    Prarameters
	 */
	private function logFormat($logLevel, $message, array $vars) {
		$dateTime = date('c');
		$message = vsprintf($message, $vars);
		file_put_contents(
			$this->logFile,
			"$dateTime $logLevel {$this->logName} - $message".PHP_EOL,
			FILE_APPEND
		);
	}
}