<?php
/*
FileIO - Normal
@Version : 0.2 20061223
*/

class FileIO{
	var $path, $imgPath, $thumbPath;

	/* private 藉由檔名分辨圖檔存放位置 */
	function _getImagePhysicalPath($imgname){
		return (substr($imgname, -5)=='s.jpg' ? $this->thumbPath : $this->imgPath).$imgname;
	}

	function FileIO($parameter=''){
		$this->path = realpath('.').DIRECTORY_SEPARATOR;
		$this->imgPath = $this->path.IMG_DIR; $this->thumbPath = $this->path.THUMB_DIR;
	}

	function init(){
		return true;
	}

	function imageExists($imgname){
		return file_exists($this->_getImagePhysicalPath($imgname));
	}

	function deleteImage($imgname){
		if(is_array($imgname)){ foreach($imgname as $i){ if(!@unlink($this->_getImagePhysicalPath($i))) return false; } return true; }
		else{ return @unlink($this->_getImagePhysicalPath($imgname)); }
	}

	function uploadImage($imgname='', $imgpath='', $imgsize=0){
		return false;
	}

	function getImageFilesize($imgname){
		return @filesize($this->_getImagePhysicalPath($imgname));
	}

	function getImageURL($imgname){
		return $this->getImageLocalURL($imgname);
	}
}
?>