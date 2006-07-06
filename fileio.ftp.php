<?php
/* File I/O Wrapper */
include_once('./lib_ftp.php');

/* private */ function noPath(&$str, $key) {
	$str=basename($str);
}

function file_func($action,$file='',$size='',$imgsize='') {
	global $ftplog;
	if(!isset($GLOBALS['ftplog'])) ftp_log('load');
	
	// Remove Path for ftp_log()
		$lfile=$file;
		if(is_array($lfile)) array_walk($lfile,'noPath');
		else noPath($lfile,'');
	
	switch($action) {
		case 'init':
			if(!file_exists(FTP_FILE_LOG)) touch(FTP_FILE_LOG);
			ftp_func('mkdir',array(IMG_DIR,THUMB_DIR));
			break;
		case 'exist':
			if(!$file) return true;  // Function exists
			return ftp_log('exist',$lfile);
			break;
		case 'size':
			if(!$file) return true;
			return ftp_log('size',$lfile);
			break;
		case 'imgsize':
			if(!$file) return true;
			return ftp_log('imgsize',$lfile);
			break;
		case 'del':
			if(!$file) return true;
			ftp_func('del',$file);
			ftp_log('del',$lfile);
			ftp_log('write');
			return true;
			break;
		case 'upload':
		/* Upload img with sample
		   $lfile=array(2) filename without path
		   $file=array(2) filename with path
		   $size=array(2) file size
		   $imgsize=$file[0] img size */
			if(!$file) return true;
			ftp_func('put',$file,$file);
			ftp_log('update',$lfile[0],$size[0],$imgsize);
			if(isset($lfile[1])) ftp_log('update',$lfile[1],$size[1]);
			ftp_log('write');
			foreach($file as $fil) @unlink($fil);
			return true;
			break;
		default:
			return false;
	}
}

?>