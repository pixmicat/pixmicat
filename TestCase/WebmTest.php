<?php

namespace Pixmicat;

use Pixmicat\Webm\Webm;

class WebmTest extends \PHPUnit_Framework_TestCase {

    public $configs_backup;
    
    public function setUp() {
        global $FFMPEG_CONFIGS;
        
        parent::setUp();
        $this->configs_backup = $FFMPEG_CONFIGS;
    }
    
    public function tearDown() {
        global $FFMPEG_CONFIGS;
        
        parent::tearDown();
        $FFMPEG_CONFIGS = $this->configs_backup;
    }
    
    public function testCheckEnvironment() {
        global $FFMPEG_CONFIGS;
        $FFMPEG_CONFIGS['ffmpeg.binaries'] = '/bin/xyz';
        $this->assertFalse(Webm::checkEnvironment());
    }
    
    public function testIsWebm() {
        $result = Webm::isWebm($this->fetchFixtureWebm());
        $this->assertArrayHasKey('H', $result);
        $this->assertArrayHasKey('W', $result);
        $this->assertTrue(is_integer($result['H']) && $result['H'] > 0);
        $this->assertTrue(is_integer($result['W']) && $result['W'] > 0);
    }
    
    public function testIsNotWebm() {
        $this->assertFalse(Webm::isWebm($this->fetchFixtureJPG()));
    }
    
    public function testCreateThumbnail() {
        $file = $this->fetchFixtureWebm();
        $destination = sys_get_temp_dir() . '/test_thumb.jpg';
        $W = MAX_RW;
        $H = MAX_RH;
        Webm::createThumbnail($file, $destination, Webm::isWebm($file), $W, $H);
        
        $size = getimagesize($destination);
        $this->assertTrue(is_array($size));
        $this->assertEquals($W, $size[0]);
        $this->assertEquals($H, $size[1]);
        $this->assertEquals(IMAGETYPE_JPEG, $size[2]);
    }
    
    private function fetchFixtureWebm() {
        return $this->fetchFixture('http://i.imgur.com/mDsYBAU.webm', 'test.webm');
    }
    
    private function fetchFixtureJPG() {
        return $this->fetchFixture('http://i.imgur.com/mDsYBAUl.jpg', 'test.jpg');
    }
    
    private function fetchFixture($url, $file) {
        $file = sys_get_temp_dir() . '/' . $file;
        
        if(!is_file($file)) {
            file_put_contents($file, file_get_contents($url));
        }
        
        return $file;
    }

}
