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
	var $_exec, $_sys_exec, $_support_bmp;

	function ThumbWrapper($sourceFile='', $sourceWidth=0, $sourceHeight=0){
		$this->sourceFile = $sourceFile;
		$this->sourceWidth = $sourceWidth;
		$this->sourceHeight = $sourceHeight;
		$this->_exec = realpath('./repng2jpeg'.(strtoupper(substr(PHP_OS, 0, 3))==='WIN' ? '.exe' : ''));
		if(strtoupper(substr(PHP_OS, 0, 3))==='WIN' && strpos($this->_exec,' ')!==false)
			$this->_exec = '"'.$this->_exec.'"';
		if(function_exists('exec')) {
			@exec('repng2jpeg --version', $status, $retval);
			if($retval===0) {
				$this->_sys_exec = true;
				$this->_exec = 'repng2jpeg';
			}
			$this->_support_bmp = (strpos(`$this->_exec --help`,'BMP')!==false);
		}
	}

	function getClass(){
		$str = 'repng2jpeg Wrapper';
		if($this->isWorking()){
			$str .= ' : '.`$this->_exec --version`;
			if($this->_support_bmp) $str .= '(BMP supported)';
		}
		return $str;
	}

	function isWorking(){
		return ($this->_sys_exec || ($this->_exec{0} == '"' ? file_exists(substr($this->_exec,1,-1)) : file_exists($this->_exec))) && function_exists('exec') && ($this->_sys_exec || strtoupper(substr(PHP_OS, 0, 3))==='WIN' || is_executable($this->_exec));
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
			case IMAGETYPE_BMP:
				if($this->_support_bmp) break;
				else return false;
			default:
				return false;
		}
		$CLI = "$this->_exec \"$this->sourceFile\" \"$destFile\" $this->thumbWidth $this->thumbHeight $this->thumbQuality";
		@exec($CLI);
		return true;
	}
}
?>