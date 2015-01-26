<?php

use Pixmicat\PMCLibrary;

class LoggerTest extends PHPUnit_Framework_TestCase {
	public $Logger;

	protected function setUp() {
		$this->Logger = PMCLibrary::getLoggerInstance('TestCase');
		@rename(ROOTPATH.'error.log', ROOTPATH.'orig-error.log');
	}
	
	protected function tearDown() {
		@unlink(ROOTPATH.'error.log');
		@rename(ROOTPATH.'orig-error.log', ROOTPATH.'error.log');
	}

	public function testLoggerInstance() {
		$obj2 = PMCLibrary::getLoggerInstance('TestCase');
		$this->assertSame($this->Logger, $obj2);
		$this->assertNotNull($this->Logger);
	}

	public function testLoggerInstance2() {
		$obj3 = PMCLibrary::getLoggerInstance();
		$this->assertNotSame($this->Logger, $obj3);
		
		$obj4 = PMCLibrary::getLoggerInstance('Global');
		$this->assertSame($obj3, $obj4);
	}

	public function testError() {
		$this->Logger->error('This is a error.');
		$content = file_get_contents(ROOTPATH.'error.log');
		//var_dump($content);
		$this->assertRegExp('/This is a error/', $content);
	}

	public function testInfo() {
		$this->Logger->info('This is a info: %d', time());
		$content = file_get_contents(ROOTPATH.'error.log');
		//var_dump($content);
		$this->assertRegExp('/This is a info/', $content);
	}

	public function testDebug() {
		$this->Logger->debug('This is a debug message: %s', 'No name given');
		$content = file_get_contents(ROOTPATH.'error.log');
		//var_dump($content);
		$this->assertRegExp('/This is a debug message/', $content);
	}
	
	public function testDebugWithArray() {
		$this->Logger->debug('This is a debug message2: %s', array(1, 2, 3));
		$content = file_get_contents(ROOTPATH.'error.log');
		//var_dump($content);
		$this->assertRegExp('/This is a debug message2/', $content);
	}
}