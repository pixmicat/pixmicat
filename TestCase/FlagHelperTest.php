<?php
use Pixmicat\Pio\FlagHelper;
use Pixmicat\PMCLibrary;

class FlagHelperTest extends PHPUnit_Framework_TestCase {
	public static function setUpBeforeClass() {
		// Let the system load lib_pio (FlagHelper)
		PMCLibrary::getPIOInstance();
	}

	public function testInstance() {
		$fg = new FlagHelper('');
		$this->assertNotNull($fg);
	}

	public function testToString() {
		$fg = new FlagHelper('TEST');
		$this->assertEquals('TEST', $fg->toString());
	}

	public function testGet() {
		$fg = new FlagHelper('_flag1:1024__flag2:0_');
		$this->assertEquals('flag1:1024', $fg->get('flag1'));
	}

	public function testExists() {
		$fg = new FlagHelper('_flag1:1024__flag2:0_');
		$this->assertTrue($fg->exists('flag1'));
		$this->assertFalse($fg->exists('flag8'));
	}

	public function testValue() {
		$fg = new FlagHelper('_flag1:1024__flag2:0_');
		$this->assertEquals('1024', $fg->value('flag1'));
		$this->assertFalse($fg->value('flag8'));
	}

	public function testAdd() {
		$fg = new FlagHelper('_flag1:1024__flag2:0_');
		$fg->add('flag3', 'Test');
		$this->assertTrue($fg->exists('flag3'));
		$this->assertEquals('Test', $fg->value('flag3'));
		$this->assertEquals('_flag1:1024__flag2:0__flag3:Test_', $fg->toString());
	}

	public function testUpdate() {
		$fg = new FlagHelper('_flag1:1024__flag2:0_');
		$fg->update('flag2', '86400');
		$this->assertEquals('86400', $fg->value('flag2'));
		$fg->update('flag3', array('a', 'b', 'c'));
		$this->assertEquals(array('a', 'b', 'c'), $fg->value('flag3'));
		$fg->update('flag4');
		$this->assertTrue($fg->value('flag4'));
	}

	public function testReplace() {
		$fg = new FlagHelper('_flag1:1024__flag2:0_');
		$fg->update('flag2', '86400');
		$this->assertEquals('86400', $fg->value('flag2'));
	}

	public function testRemove() {
		$fg = new FlagHelper('_flag1:1024__flag2:0_');
		$fg->remove('flag1');
		$this->assertFalse($fg->exists('flag1'));
		$this->assertFalse($fg->get('flag1'));
		$this->assertFalse($fg->value('flag1'));
	}

	public function testToggle() {
		$fg = new FlagHelper('_flag1:1024__flag2:0_');
		$fg->toggle('flag3');
		$this->assertTrue($fg->value('flag3'));
		$fg->toggle('flag3');
		$this->assertFalse($fg->value('flag3'));
	}

	public function testOffsetValue() {
		$fg = new FlagHelper('_flag1:1024__flag2:0_');
		$fg->offsetValue('flag1', -1024);
		$this->assertEquals('0', $fg->value('flag1'));

		$fg->offsetValue('flag1', 65535);
		$this->assertEquals('65535', $fg->value('flag1'));

		$fg->offsetValue('flag1', -131070);
		$this->assertEquals('-65535', $fg->value('flag1'));
	}

	public function testPlus() {
		$fg = new FlagHelper('_flag1:1024__flag2:0_');
		$fg->plus('flag2');
		$this->assertEquals('1', $fg->value('flag2'));
	}

	public function testMinus() {
		$fg = new FlagHelper('_flag1:1024__flag2:0_');
		$fg->minus('flag2');
		$this->assertEquals('-1', $fg->value('flag2'));
	}

	public function testJoin() {
		$fg = new FlagHelper('_flag1:1024__flag2:0_');
		$this->assertEquals('1:2:3', $fg->join(1, 2, 3));
		$this->assertEquals('1:a:b:3', $fg->join(1, array('a', 'b'), 3));
	}

	public function testToStringClass() {
		$fg = new FlagHelper('_flag1:1024__flag2:0_');
		$this->assertEquals('Pixmicat\Pio\FlagHelper {status = _flag1:1024__flag2:0_}', (string) $fg);
	}
}