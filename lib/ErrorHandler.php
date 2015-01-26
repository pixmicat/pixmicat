<?php
namespace Pixmicat;

/**
 * Global error handler
 *
 * @package PMCLibrary
 * @version $Id$
 */
class ErrorHandler
{
    /**
     * Handles normal PHP errors.
     */
    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        // Ignore @ prefix suppressed error
        if (!(\error_reporting() & $errno)) {
            return;
        }

        PMCLibrary::getLoggerInstance('Global')->
            error('Error caught: #%d: %s in %s on line %d', $errno, $errstr, $errfile, $errline);
    }

    /**
     * Handles fatal PHP errors.
     */
    public function fatalErrorHandler()
    {
        $e = \error_get_last();
        if ($e !== null) {
            PMCLibrary::getLoggerInstance('Global')->
                error(
                    'Fatal error caught: #%d: %s in %s on line %d',
                    $e['type'], $e['message'], $e['file'], $e['line']
                );
        }
    }


    /**
     * Handles thrown exceptions by program itself or PHP.
     */
    public function exceptionHandler($e)
    {
        PMCLibrary::getLoggerInstance('Global')->error('Exception caught: %s', $e);
    }

    /**
     * 註冊全域錯誤攔截。
     */
    public function register()
    {
        \set_exception_handler(array($this, 'errorHandler'));
        \set_error_handler(array($this, 'errorHandler'));
        \register_shutdown_function(array($this, 'fatalErrorHandler'));
    }
}
