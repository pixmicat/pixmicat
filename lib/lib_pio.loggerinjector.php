<?php
/**
 * PIO 事件紀錄器注入
 *
 * @package PMCLibrary
 * @version $Id$
 */

class PIOLoggerInjector {
	private $dataSource;
	private $logger;

	public function __construct(IPIO $dataSource, $logger){
		$this->setDataSource($dataSource);
		$this->setLogger($logger);
	}

	public function setDataSource(IPIO $dataSource) {
		$this->dataSource = $dataSource;
	}

	public function setLogger(ILogger $logger) {
		$this->logger = $logger;
	}

	/**
	 * 以包裹方法呼叫來注入紀錄器
	 *
	 * @param  string $name 呼叫方法名稱
	 * @param  array $args 呼叫方法參數
	 * @return mixed       呼叫方法回傳值
	 */
	public function __call($name, $args) {
		if (method_exists($this->dataSource, $name)) {
			$result = null;
			$this->logger->info('Executing %s method', $name);
			$this->logger->debug('Args: %s', $args);

			try {
				$result = call_user_func_array(array($this->dataSource, $name), $args);
			} catch (Exception $e) {
				$this->logger->error('[%s] %s', $name, $e);
			}

			$this->logger->debug('Return: %s', $result);
			return $result;
		}
    }
}