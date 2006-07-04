<?php
/* File I/O Wrapper */

function file_func($action,$file='',$size='',$imgsize='') {
	switch($action) {
		case 'exist':
			if(!$file) return true;  // Function exists
			return is_file($file);
			break;
		case 'size':
			if(!$file) return true;
			return @filesize($file);
			break;
		case 'imgsize':
			if(!$file) return true;
			$wh = @GetImageSize($file);
			return $wh[0].'x'.$wh[1];
			break;
		case 'del':
			if(!$file) return true;
			if(is_array($file)) foreach($file as $fil) @unlink($fil);
			else return @unlink($file);
			break;
		default:
			return false;
	}
}

?>