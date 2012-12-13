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

	try {
    	throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
    } catch (Exception $e) {
		PMCLibrary::getLoggerInstance()->log('ERROR', $e);
		throw $e;
	}
}
set_error_handler('errorHandler');