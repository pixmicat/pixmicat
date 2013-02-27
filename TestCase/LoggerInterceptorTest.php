<?php
class LoggerInterceptorTest extends PHPUnit_Framework_TestCase {
	public function testInstance() {
		$obj = new LoggerInterceptor(PMCLibrary::getLoggerInstance('Test'));
		$this->assertNotNull($obj);
		$this->assertInstanceOf('MethodInterceptor', $obj);
	}

	/**
	 * @expectedException PHPUnit_Framework_Error
	 */
	public function testInstanceException() {
		$obj = new LoggerInterceptor();
	}

	/**
	 * @expectedException PHPUnit_Framework_Error
	 */
	public function testInstanceException2() {
		$obj = new LoggerInterceptor(NULL);
	}
}