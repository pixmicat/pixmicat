<?php
/**
 * Global error handler
 *
 * @package PMCLibrary
 * @version $Id$
 */

function errorHandler($errno, $errstr, $errfile, $errline) {
	if (!(error_reporting() & $errno)) {
        return;
    }

    PMCLibrary::getLoggerInstance('Global')->
    	error('Error caught: #%d: %s in %s on line %d',
    	$errno, $errstr, $errfile, $errline);
}
set_error_handler('errorHandler');

function exceptionHandler($e) {
	PMCLibrary::getLoggerInstance('Global')->error('Exception caught: %s', $e);
}
set_exception_handler('exceptionHandler');