<?php

use Pixmicat\Module\ModuleHelper;
use Pixmicat\PMCLibrary;

class mod_test extends ModuleHelper {
	public function __construct($PMS) {
		parent::__construct($PMS);
	}

	public function getModuleName() {
		return $this->moduleNameBuilder('Test Test');
	}

	public function getModuleVersionInfo() {
		return '1.0';
	}

	public function testModulePageURL(array $p = array()) {
		return $this->getModulePageURL($p);
	}

	public function testHookFunc(&$txt) {
		$txt .= ' hello';
	}

	public function testHook() {
		$this->hookModuleMethod('TopLink', array($this, 'testHookFunc'));
	}

	public function testAddCHP() {
		$this->addCHP(__CLASS__.'_test', array($this, 'testHookFunc'));
	}

	public function testCallCHP($txt) {
		$this->callCHP(__CLASS__.'_test', array(&$txt));
		return $txt;
	}

	public function testAttachLanguage() {
		$this->attachLanguage(
			array(
				'en_US' => array(
					'ABB' => '001',
					'BBC' => '002 %s'
				)
			)
		);
	}

	public function testAttachLanguageAnd_T() {
		$this->attachLanguage(
			array(
				'en_US' => array(
					'var01' => '001',
					'var02' => '002 %s'
				)
			)
		);
		return $this->_T('var02', 'Tom');
	}

	public function testStaticPMS() {
		return self::$PMS;
	}
}

class ModuleHelperTest extends PHPUnit_Framework_TestCase {
	public $mod;

	public function setUp() {
		$this->mod = new mod_test(PMCLibrary::getPMSInstance());
	}

	public function testInstance() {
		$this->assertNotNull($this->mod);
	}

	public function testGetModuleName() {
		$name = $this->mod->getModuleName();
		$this->assertEquals('mod_test : Test Test', $name);
	}

	public function testGetModuleVersionInfo() {
		$ver = $this->mod->getModuleVersionInfo();
		$this->assertEquals('1.0', $ver);
	}

	public function testGetModulePageURLNoArg() {
		$url = $this->mod->testModulePageURL();
		$this->assertEquals(PHP_SELF.'?mode=module&amp;load=mod_test', $url);
	}

	public function testGetModulePageURLWithArg1() {
		$url = $this->mod->testModulePageURL(array('hello'=>'world'));
		$this->assertEquals(PHP_SELF.'?mode=module&amp;load=mod_test&amp;hello=world', $url);
	}

	public function testGetModulePageURLWithArg2() {
		$url = $this->mod->testModulePageURL(array('hello'=>1));
		$this->assertEquals(PHP_SELF.'?mode=module&amp;load=mod_test&amp;hello=1', $url);
	}

	public function testGetModulePageURLWithArg3() {
		$url = $this->mod->testModulePageURL(array('hello'));
		$this->assertEquals(PHP_SELF.'?mode=module&amp;load=mod_test&amp;0=hello', $url);
	}

	public function testHookModuleMethod() {
		$this->mod->testHook();
	}

	public function testAddCallCHP() {
		$this->mod->testAddCHP();
		$text = 'Hi';
		$this->assertEquals('Hi hello', $this->mod->testCallCHP($text));
	}

	public function testAttachLanguage() {
		$beforeCount = count(PMCLibrary::getLanguageInstance()->getLanguage());
		$this->mod->testAttachLanguage();
		$afterCount = count(PMCLibrary::getLanguageInstance()->getLanguage());
		$this->assertEquals($beforeCount + 2, $afterCount);
	}

	public function testAttachLanguageAnd_T() {
		$this->assertEquals('002 Tom', $this->mod->testAttachLanguageAnd_T());
	}

	public function testStaticPMS() {
		$this->assertSame(PMCLibrary::getPMSInstance(), $this->mod->testStaticPMS());
	}
}