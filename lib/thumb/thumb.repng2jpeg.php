<?php
/**
 * Thumbnail Generate API: Imagick Wrapper
 *
 * 提供程式便於以 repng2jpeg 生成預覽圖的物件
 *
 * @package PMCLibrary
 * @version $Id$
 * @date $Date$
 */

class ThumbWrapper{
	var $sourceFile, $sourceWidth, $sourceHeight, $thumbWidth, $thumbHeight, $thumbSetting, $thumbQuality;
	var $_exec;

	function ThumbWrapper($sourceFile='', $sourceWidth=0, $sourceHeight=0){
		$this->sourceFile = $sourceFile;
		$this->sourceWidth = $sourceWidth;
		$this->sourceHeight = $sourceHeight;
		$this->_exec = realpath('./repng2jpeg'.(strtoupper(substr(PHP_OS, 0, 3))==='WIN' ? '.exe' : ''));
	}

	function getClass(){
		$str = 'repng2jpeg Wrapper';
		if($this->isWorking()){
			$str .= ' : '.`$this->_exec --version`;
		}
		return $str;
	}

	function isWorking(){
		return file_exists($this->_exec) && function_exists('exec') && (strtoupper(substr(PHP_OS, 0, 3))==='WIN' || is_executable($this->_exec));
	}

	function setThumbnailConfig($thumbWidth, $thumbHeight, $thumbSetting){
		$this->thumbWidth = $thumbWidth;
		$this->thumbHeight = $thumbHeight;
		$this->thumbSetting = $thumbSetting;
		$this->thumbQuality = $thumbSetting['Quality'];
	}

	function makeThumbnailtoFile($destFile){
		if(!$this->isWorking()) return false;
		$size = getimagesize($this->sourceFile);
		switch($size[2]){
			case IMAGETYPE_JPEG:
			case IMAGETYPE_GIF:
			case IMAGETYPE_PNG:
				break; // 僅支援此三種格式
			default:
				return false;
		}
		$CLI = "$this->_exec \"$this->sourceFile\" \"$destFile\" $this->thumbWidth $this->thumbHeight $this->thumbQuality";
		@exec($CLI);
		return true;
	}
}
?>