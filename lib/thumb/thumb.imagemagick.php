<?php
/**
 * Thumbnail Generate API: ImageMagick Wrapper
 *
 * 提供程式便於以 ImageMagick 命令列生成預覽圖的物件
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
		$this->_exec = 'convert'; // ImageMagick "convert" Binary Location
	}

	function getClass(){
		$str = 'ImageMagick Wrapper';
		if($this->isWorking()){
			$a = null;
			preg_match('/^Version: ImageMagick (.*?) [hf]/', `$this->_exec -version`, $a);
			$str .= ' : '.$a[1];
			unset($a);
		}
		return $str;
	}

	function isWorking(){
		if(!function_exists('exec')) return false;
		@exec("$this->_exec -version", $status, $retval);
		return ($retval===0);
	}

	function setThumbnailConfig($thumbWidth, $thumbHeight, $thumbSetting){
		$this->thumbWidth = $thumbWidth;
		$this->thumbHeight = $thumbHeight;
		$this->thumbSetting = $thumbSetting;
		$this->thumbQuality = $thumbSetting['Quality'];
	}

	function makeThumbnailtoFile($destFile){
		if(!$this->isWorking()) return false;
		$CLI = "$this->_exec -thumbnail {$this->thumbWidth}x{$this->thumbHeight} -quality $this->thumbQuality -flatten \"$this->sourceFile\" \"$destFile\"";
		@exec($CLI);
		return true;
	}
}
?>