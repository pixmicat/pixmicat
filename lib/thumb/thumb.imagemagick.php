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
	var $sourceFile, $sourceWidth, $sourceHeight, $thumbWidth, $thumbHeight, $thumbQuality;
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
			$b = null;
			$a = preg_match('/^Version: ImageMagick (.*?) http:/', `$this->_exec -version`, $b);
			$str .= ' : '.$b[1];
			unset($a); unset($b);
		}
		return $str;
	}

	function isWorking(){
		return true; // I'm too lazy to check whether the binary is existed or not. Use it at your own risk.
	}

	function setThumbnailConfig($thumbWidth, $thumbHeight, $thumbQuality=50){
		$this->thumbWidth = $thumbWidth;
		$this->thumbHeight = $thumbHeight;
		$this->thumbQuality = $thumbQuality;
	}

	function makeThumbnailtoFile($destFile){
		if(!$this->isWorking()) return false;
		$CLI = "$this->_exec -thumbnail {$this->thumbWidth}x{$this->thumbHeight} -quality $this->thumbQuality \"$this->sourceFile\" \"$destFile\"";
		@exec($CLI);
		return true;
	}
}
?>