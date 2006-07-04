<?php
/* File I/O Wrapper */

function file_func($action,$file='',$rfile='',$size='',$imgsize='') {
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
		case 'del':	// $file is not used here
			if(!$rfile) return true;
			if(is_array($rfile)) foreach($rfile as $fil) @unlink($fil);
			else return @unlink($rfile);
			break;
		default:
			return false;
	}
}

?>