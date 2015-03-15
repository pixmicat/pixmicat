<?php
namespace Pixmicat\Logger;

use Pixmicat\Aop\MethodInterceptor;

/**
 * Logger Around Advice Interceptor
 *
 * @package PMCLibrary
 * @version $Id$
 * @since 7th.Release
 */
class LoggerInterceptor implements MethodInterceptor
{
    /** @var ILogger */
    private $LOG;

    public function __construct(ILogger $logger)
    {
        $this->setLogger($logger);
    }

    private function setLogger(ILogger $logger)
    {
        $this->LOG = $logger;
    }

    public function invoke(array $callable, array $args)
    {
        $result = null;
        $methodName = $callable[1];
        $this->LOG->info('Executing %s method', $methodName);
        $this->LOG->debug('Args: %s', $args);

        try {
            $result = \call_user_func_array($callable, $args);
        } catch (\Exception $e) {
            $this->LOG->error('[%s] %s', $methodName, $e);
        }

        $this->LOG->debug('Return: %s', $result);
        return $result;
    }
}
