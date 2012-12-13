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

	public function __construct($logName) {
		$this->logName = $logName;
	}

	public function log($logLevel, $message) {
		switch ($logLevel) {
			case 'DEBUG':
			case 'INFO':
				if (!(defined('DEBUG') && DEBUG)) {
					break;
				}
			case 'ERROR':
				$dateTime = date('c');
				error_log("[$dateTime] [$logLevel] $message\n", 3, $this->logName);
				break;
		}
	}
}