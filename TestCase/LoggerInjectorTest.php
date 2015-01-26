<?php

use Pixmicat\Logger\LoggerInjector;
use Pixmicat\Logger\LoggerInterceptor;
use Pixmicat\PMCLibrary;

class TempClass {
	public function printMessage($msg) {
		return "Hello, $msg!";
	}

	public function throwException() {
		throw new RuntimeException('Exception thrown.');
	}
}

class LoggerInjectorTest extends PHPUnit_Framework_TestCase {
	private $agent;

	public function setUp() {
		$this->agent = new LoggerInjector(new TempClass(),
			new LoggerInterceptor(PMCLibrary::getLoggerInstance('TempClass')));
	}

	public function testInstance() {
		$this->assertNotNull($this->agent);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testInstanceInvaildPrincipal() {
		new LoggerInjector(
			array(1, 2, 3),
			new LoggerInterceptor(PMCLibrary::getLoggerInstance('TempClass'))
		);
	}

	/**
	 * @expectedException PHPUnit_Framework_Error
	 */
	public function testInstanceInvaildInterceptor() {
		new LoggerInjector(new TempClass(), new TempClass());
	}

	/**
	 * @expectedException PHPUnit_Framework_Error
	 */
	public function testInstanceInvaildInterceptor2() {
		new LoggerInjector(new TempClass(), NULL);
	}

	public function testCall() {
		$this->assertEquals('Hello, Mary!', $this->agent->printMessage('Mary'));
	}

	public function testCallNotExists() {
		$this->assertNull($this->agent->NonExists());
	}

	public function testCallException() {
		$this->assertNull($this->agent->throwException());
	}
}