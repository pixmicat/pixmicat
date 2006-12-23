<?php
/*
FileIO - Pixmicat! File I/O
FileIO Kernel Switcher
*/

// 引入必要函式庫
$fileio_file = './fileio/fileio.'.FILEIO_BACKEND.'.php'; // FileIO Backend
if(is_file($fileio_file)) include_once($fileio_file);
include_once('./fileio/ifs.php'); // FileIO IndexFS
$IFS = new IndexFS(FILEIO_INDEXLOG); // IndexFS 物件

// 擴充物件
class FileIOWrapper extends FileIO{
	var $absoluteURL; // 伺服器絕對位置
	function _getAbsoluteURL(){
		return 'http://'.$_SERVER['HTTP_HOST'].preg_replace('/(.*)\/.+$/', '$1/', $_SERVER['PHP_SELF']);
	}
	function getImageLocalURL($imgname){
		if(!isset($this->absoluteURL)) $this->absoluteURL = $this->_getAbsoluteURL();

		return $this->absoluteURL.(substr($imgname, -5)=='s.jpg' ? THUMB_DIR : IMG_DIR).$imgname;
	}
}

$FileIO = new FileIOWrapper(FILEIO_PARAMETER); // FileIO 物件
?>