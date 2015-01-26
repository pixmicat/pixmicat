<?php

use Pixmicat\PMCLibrary;

class PMSTest extends PHPUnit_Framework_TestCase {
	public $PMS;

	public function setUp() {
		$this->PMS = PMCLibrary::getPMSInstance();
	}

	public function testPMSInstance() {
		$obj2 = PMCLibrary::getPMSInstance();
		$this->assertSame($this->PMS, $obj2);

		$this->assertNotNull($this->PMS);
	}

	public function testGetLoadedModules() {
		$moduleLists = $this->PMS->getLoadedModules();
		//var_dump($moduleLists);
		$this->assertNotNull($moduleLists);
	}

	public function testGetModuleInstanceNonExists() {
		$this->assertNull($this->PMS->getModuleInstance('mod_nonexists'));
	}

	public function testGetModuleMethodsNonExists() {
		$this->assertEmpty($this->PMS->getModuleMethods('mod_nonexists'));
	}

	public function testGetModulePageURL() {
		$URL = $this->PMS->getModulePageURL('mod_test');
		$this->assertEquals('pixmicat.php?mode=module&amp;load=mod_test', $URL);
	}

	public function testCHP() {
		$this->PMS->addCHP('TestCHP', function($name){ echo "Hello World, $name"; });
		$this->PMS->callCHP('TestCHP', array('Duke'));
	}
}