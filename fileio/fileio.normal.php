<?php
/*
FileIO - Normal
@Version : 0.2 20061205
*/

class FileIO{
	var $path, $imgPath, $thumbPath;

	/* private 藉由檔名分辨圖檔存放位置 */
	function _getImagePhysicalPath($imgname){
		return (substr($imgname, -5)=='s.jpg' ? $this->thumbPath : $this->imgPath).$imgname;
	}

	function FileIO(){
		$this->path = realpath('.').DIRECTORY_SEPARATOR;
		$this->imgPath = $this->path.IMG_DIR; $this->thumbPath = $this->path.THUMB_DIR;
	}

	function init(){
		return true;
	}

	function imageExists($imgname){
		return file_exists(FileIO::_getImagePhysicalPath($imgname));
	}

	function deleteImage($imgname){
		if(is_array($imgname)){ foreach($imgname as $i){ if(!@unlink(FileIO::_getImagePhysicalPath($i))) return false; } return true; }
		else{ return @unlink(FileIO::_getImagePhysicalPath($imgname)); }
	}

	function uploadImage($imgname='', $imgpath='', $imgsize=0){
		return false;
	}

	function getImageFilesize($imgname){
		return @filesize(FileIO::_getImagePhysicalPath($imgname));
	}

	function getImageURL($imgname){
		return (substr($imgname, -5)=='s.jpg' ? THUMB_DIR : IMG_DIR).$imgname;
	}
}
?>