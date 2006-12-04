<?php
/*
FileIO - Normal
@Version : 0.2 20061204
*/

class FileIO{
	/* private 藉由檔名分辨圖檔存放位置 */
	function _getImagePhysicalPath($imgname){
		$path = realpath('.').DIRECTORY_SEPARATOR;
		return (substr($imgname, -5)=='s.jpg' ? $path.THUMB_DIR : $path.IMG_DIR).$imgname;
	}

	function init(){
		return true;
	}

	function imageExists($imgname){
		return file_exists(FileIO::_getImagePhysicalPath($imgname));
	}

	function deleteImage($imgname){
		if(is_array($imgname)) foreach($imgname as $i){ @unlink(FileIO::_getImagePhysicalPath($i)); }
		else{ return @unlink(FileIO::_getImagePhysicalPath($imgname)); }
	}

	function uploadImage($imgname='', $imgsize=''){
		return false;
	}

	function getImageFilesize($imgname){
		return filesize(FileIO::_getImagePhysicalPath($imgname));
	}

	function getImageURL($imgname){
		return (substr($imgname, -5)=='s.jpg' ? THUMB_DIR : IMG_DIR).$imgname;
	}
}
?>