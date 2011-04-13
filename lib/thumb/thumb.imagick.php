<?php
/**
 * Thumbnail Generate API: Imagick Wrapper
 *
 * 提供程式便於以 Imagick (Imagick Image Library) 生成預覽圖的物件
 *
 * @package PMCLibrary
 * @version $Id$
 * @date $Date$
 */

class ThumbWrapper{
	var $sourceFile, $sourceWidth, $sourceHeight, $thumbWidth, $thumbHeight, $thumbSetting, $thumbQuality;

	function ThumbWrapper($sourceFile='', $sourceWidth=0, $sourceHeight=0){
		$this->sourceFile = $sourceFile;
		$this->sourceWidth = $sourceWidth;
		$this->sourceHeight = $sourceHeight;
	}

	function getClass(){
		$str = 'Imagick Wrapper';
		if($this->isWorking()){
			$a = new Imagick(); $b = $a->getVersion(); $b = $b['versionString'];
			$str .= ' : '.str_replace(strrchr($b, ' '), '', $b);
			unset($a); unset($b);
		}
		return $str;
	}

	function isWorking(){
		return extension_loaded('imagick') && class_exists('Imagick');
	}

	function setThumbnailConfig($thumbWidth, $thumbHeight, $thumbSetting){
		$this->thumbWidth = $thumbWidth;
		$this->thumbHeight = $thumbHeight;
		$this->thumbSetting = $thumbSetting;
		$this->thumbQuality = $thumbSetting['Quality'];
	}

	function makeThumbnailtoFile($destFile){
		$returnVal = false;
		if(!$this->isWorking()) return false;
		$image = new Imagick($this->sourceFile);
		$image->setCompressionQuality($this->thumbQuality);
		$image->thumbnailImage($this->thumbWidth, $this->thumbHeight);
		$returnVal = $image->writeImage($destFile);
		unset($image);
		return $returnVal;
	}
}
?>