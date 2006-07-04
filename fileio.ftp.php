<?php
/* File I/O Wrapper */
include_once('./lib_ftp.php');

/* private */ function noPath(&$str, $key) {
	$str=basename($str);
}

function file_func($action,$file='',$rfile='',$size='',$imgsize='') {
	global $ftplog;
	if(!isset($GLOBALS['ftplog'])) ftp_log('load');
	if($action!='del'&&$action!='upload') {	// Remove Path for ftp_log()
		if(is_array($file)) array_walk($file,'noPath');
		else noPath($file,'');
	}
	
	switch($action) {
		case 'exist':
			if(!$file) return true;  // Function exists
			return ftp_log('exist',$file);
			break;
		case 'size':
			if(!$file) return true;
			return ftp_log('size',$file);
			break;
		case 'imgsize':
			if(!$file) return true;
			return ftp_log('imgsize',$file);
			break;
		case 'del':
			if(!$file) return true;
			ftp_func('del',$rfile);
			ftp_log('del',$file);
			ftp_log('write');
			return true;
			break;
		case 'upload':
		/* Upload img with sample
		   $file=array(2) filename without path
		   $rfile=array(2) filename with path
		   $size=array(2) file size
		   $imgsize=$file[0] img size */
			if(!$file) return true;
			ftp_func('put',$file,$rfile);
			ftp_log('update',$file[0],$size[0],$imgsize);
			ftp_log('update',$file[1],$size[1]);
			ftp_log('write');
			foreach($rfile as $fil) @unlink($fil);
			return true;
			break;
		default:
			return false;
	}
}

?>