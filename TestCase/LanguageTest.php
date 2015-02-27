<?php
require_once ROOTPATH.'lib/lib_compatible.php';

use Pixmicat\PMCLibrary;

class LanguageTest extends PHPUnit_Framework_TestCase {
	public $Lang;

	public function setUp() {
		$this->Lang = PMCLibrary::getLanguageInstance();
	}

	public function testGetInstance() {
		$expect = $this->Lang;
		$result = PMCLibrary::getLanguageInstance();
		$this->assertSame($expect, $result);
	}

	public function testGetLocale() {
		$expect = 'zh_TW';
		$result = $this->Lang->getLocale();
		$this->assertSame($expect, $result);
	}

	public function testGetLanguage() {
		$expect = 189;
		$result = count($this->Lang->getLanguage());
		$this->assertSame($expect, $result);
	}

	public function testGetTranslation() {
		$expect = '[Notice] Your sending was canceled because of the incorrect file size.';
		$result = $this->Lang->getTranslation('regist_upload_killincomp');
		$this->assertEquals($expect, $result);
	}

	public function testGetTranslationWithArgs() {
		$expect = '被列在 DNSBL(127.0.0.1) 封鎖名單之內';
		$result = $this->Lang->getTranslation('ip_dnsbl_banned', '127.0.0.1');
		$this->assertEquals($expect, $result);
	}

	public function testGetTranslationNoArg() {
		$expect = '';
		$result = $this->Lang->getTranslation();
		$this->assertEquals($expect, $result);
	}

	public function testGetTranslationIndexNotExists() {
		$expect = 'WTF_IS_THIS';
		$result = $this->Lang->getTranslation('WTF_IS_THIS');
		$this->assertEquals($expect, $result);
	}

	public function test_T() {
		$expect = '資料表最佳化';
		$result = Pixmicat\_T('admin_optimize');
		$this->assertEquals($expect, $result);
	}

	public function test_TWithArgs() {
		$expect = '【 附加圖檔使用容量總計 : <b>51200</b> KB 】';
		$result = Pixmicat\_T('admin_totalsize', '51200');
		$this->assertEquals($expect, $result);
	}

	public function test_TNoArg() {
		$expect = '';
		$result = Pixmicat\_T();
		$this->assertEquals($expect, $result);
	}

	public function test_TIndexNotExists() {
		$expect = 'WTF_IS_THIS_ANYWAY';
		$result = Pixmicat\_T('WTF_IS_THIS_ANYWAY');
		$this->assertEquals($expect, $result);
	}

	public function testAttachLanguageOldway() {
		Pixmicat\AttachLanguage(function(){
			global $language;
			$language['testIndex'] = 'testValue';
		});

		$expect = 'testValue';
		$result = Pixmicat\_T('testIndex');
		$this->assertEquals($expect, $result);
	}

	public function testAttachLanguageNewway() {
		$langArray = array();
		$langArray['testIndex2'] = 'testValue2';
		PMCLibrary::getLanguageInstance()->attachLanguage($langArray);

		$expect = 'testValue2';
		$result = Pixmicat\_T('testIndex2');
		$this->assertEquals($expect, $result);
	}
}
