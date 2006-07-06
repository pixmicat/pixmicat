<?
class ClsFTP{
	var $host = "localhost";//FTP HOST
	var $port = "21";		//FTP port
	var $user = "Anonymous";//FTP user
	var $pass = "Email";	//FTP password
	var $link_id = "";		//FTP hand
	var $is_login = "";		//is login 
	var $debug = 0;
	var $local_dir = "";	//local path for upload or download
	var $rootdir = "";		//FTP root path of FTP server
	var $dir = "/";			//FTP current path
	
	
	function ClsFTP($user="Anonymous",$pass="Email",$host="localhost",$port="21"){
		if($host) $this->host = $host;
		if($port) $this->port = $port;
		if($user) $this->user = $user;
		if($pass) $this->pass = $pass;
		$this->login();
		$this->rootdir 	= $this->pwd();
		$this->dir 		= $this->rootdir;
	}
	function halt($msg,$line=__LINE__){
		echo "FTP Error in line:$line<br/>\n";
		echo "FTP Error message:$msg<br/>\n";
		exit();
	}
	function login(){
		if(!$this->link_id){
			$this->link_id = ftp_connect($this->host,$this->port) or $this->halt("can not connect to host:$this->host:$this->port",__LINE__);
		}
		if(!$this->is_login){
			$this->is_login = ftp_login($this->link_id, $this->user, $this->pass) or $this->halt("ftp login faild.invaid user or password",__LINE__);
		}
	}
	function systype(){
		return ftp_systype($this->link_id);
	}
	function pwd(){
		$this->login();
		$dir = ftp_pwd($this->link_id);
		$this->dir = $dir;
		return $dir;
	}
	function cdup(){
		$this->login();
		$isok =  ftp_cdup($this->link_id);
		if($isok) $this->dir = $this->pwd();
		return $isok;
	}
	function cd($dir){
		$this->login();
		$isok = ftp_chdir($this->link_id,$dir);
		if($isok) $this->dir = $dir;
		return $isok;
	}
	function nlist($dir=""){
		$this->login();
		if(!$dir) $dir = ".";
		$arr_dir = ftp_nlist($this->link_id,$dir);
		return $arr_dir;
	}
	function rawlist($dir="/"){
		$this->login();
		$arr_dir = ftp_rawlist($this->link_id,$dir);
		return $arr_dir;
	}
	function mkdir($dir){
		$this->login();
		return @ftp_mkdir($this->link_id,$dir);
	}
	function file_size($file){
		$this->login();
		$size = ftp_size($this->link_id,$file);
		return $size;
	}
	function chmod($file,$mode=0666){
		$this->login();
		if(function_exists('ftp_chmod')) {
			return ftp_chmod($this->link_id,$file,$mode);
		} else {
			return @ftp_site($this->link_id, "CHMOD ".$mode." ".$file);
		}
	}
	function delete($remote_file){
		$this->login();
		return ftp_delete($this->link_id,$remote_file);
	}
	function get($local_file,$remote_file,$mode=FTP_BINARY){
		$this->login();
		return ftp_get($this->link_id,$local_file,$remote_file,$mode);
	}
	function put($remote_file,$local_file,$mode=FTP_BINARY){
		$this->login();
		return ftp_put($this->link_id,$remote_file,$local_file,$mode);
	}
	function put_string($remote_file,$data,$mode=FTP_BINARY){
		$this->login();
		$tmp = "/tmp";//ini_get("session.save_path");
		$tmpfile = tempnam($tmp,"tmp_");
		$fp = @fopen($tmpfile,"w+");
		if($fp){
			fwrite($fp,$data);
			fclose($fp);
		}else return 0;
		$isok = $this->put($remote_file,$tmpfile,FTP_BINARY);
		@unlink($tmpfile);
		return $isok;
	}

	function close(){
		@ftp_quit($this->link_id);
	}
}

function ftp_func($action,$file,$rfile='',$path=FTP_BASE_PATH,$host=FTP_HOST,$port=FTP_PORT,$user=FTP_USER,$pass=FTP_PASS) {
	$ftp=new ClsFTP($user,$pass,$host,$port);
	$result=false;
	$ftp->cd($path) or ($ftp->mkdir($path) && $ftp->cd($path));

	switch($action) {
		case "del":
			if(is_array($file)) foreach($file as $fil) $result = $ftp->delete($fil);
			else $result = $ftp->delete($file);
			break;
		case "mkdir":
			if(is_array($file)) foreach($file as $fil) $result = $ftp->mkdir($fil);
			else $result = $ftp->mkdir($file);
			break;
		case "chmod":
			if(is_array($file)) foreach($file as $fil) $result = $ftp->chmod($fil);
			else $result = $ftp->chmod($file);
			break;
		case "put":
			if(is_array($file)) for($i=0;$i<count($file);$i++) $result = $ftp->put($rfile[$i],$file[$i]);
			else $result = $ftp->put($rfile,$file);
			break;
	}
	$ftp->close();
	return $result;
}

function ftp_log($mode,$file='',$size='',$imgsize=''){
	global $ftplog;
	if(!isset($GLOBALS['ftplog'])&&$mode!='load') ftp_log('load');
	switch($mode) {
		case 'load':
			unset($GLOBALS['ftplog']);
			$GLOBALS['ftplog']=array();
			$tmp_ftplog=file(FTP_FILE_LOG);
			foreach($tmp_ftplog as $tline){
				list($fil,$size,$imgsize) = explode(",", $tline);
				$GLOBALS['ftplog'][$fil] = array($size,$imgsize);
			}
			break;
		case 'update':
			$ftplog[$file]=array($size,$imgsize);
			break;
		case 'del':
			if(is_array($file)) foreach($file as $fil) unset($ftplog[$fil]);
			else unset($ftplog[$file]);
			break;
		case 'size':
			return @$ftplog[$file][0];
			break;
		case 'imgsize':
			return @$ftplog[$file][1];
			break;
		case 'exist':
			return isset($ftplog[$file]);
			break;
		case 'write':
			$newlog='';
			foreach($ftplog as $key => $value)
				$newlog.="$key,$value[0],$value[1],\n";
			$fp = fopen(FTP_FILE_LOG,"r+");
			flock($fp, 2);
			ftruncate($fp,0);
			set_file_buffer($fp, 0);
			rewind($fp);
			fputs($fp, $newlog);
			break;
	}
}
?>