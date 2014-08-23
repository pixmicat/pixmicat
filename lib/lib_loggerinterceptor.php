<?php
/**
 * AOP Logger
 *
 * @package PMCLibrary
 * @version $Id$
 * @since 7th.Release
 */

/**
 * Logger Around Advice Interceptor
 */
class LoggerInterceptor implements MethodInterceptor {
	private $LOG;

	public function __construct(ILogger $logger) {
		$this->setLogger($logger);
	}

	private function setLogger(ILogger $logger) {
		$this->LOG = $logger;
	}

	public function invoke(array $callable, array $args) {
		$result = null;
		$methodName = $callable[1];
		$this->LOG->info('Executing %s method', $methodName);
		$this->LOG->debug('Args: %s', $args);

		try {
			$result = call_user_func_array($callable, $args);
		} catch (Exception $e) {
			$this->LOG->error('[%s] %s', $methodName, $e);
		}

		$this->LOG->debug('Return: %s', $result);
		return $result;
	}
}

/**
 * 事件記錄器注入
 * 使用 MethodInterceptor 代理包裹物件方法，藉此注入 Logger。
 */
class LoggerInjector {
	private $principalClass;
	private $mi;

	public function __construct($principalClass, MethodInterceptor $mi) {
		$this->setPrincipalClass($principalClass);
		$this->setMethodInterceptor($mi);
	}

	private function setPrincipalClass($principalClass) {
		if (!is_object($principalClass)) {
			throw new InvalidArgumentException('PrincipalClass is not a valid object.');
		}
		$this->principalClass = $principalClass;
	}

	private function setMethodInterceptor(MethodInterceptor $mi) {
		$this->mi = $mi;
	}

	/**
	 * 以 MethodInterceptor 注入記錄器
	 *
	 * @param  string $name 呼叫方法名稱
	 * @param  array $args 呼叫方法參數
	 * @return mixed       呼叫方法回傳值
	 */
	public function __call($name, $args) {
		if (!method_exists($this->principalClass, $name)) {
			return;
		}
		return $this->mi->invoke(array($this->principalClass, $name), $args);
	}
}