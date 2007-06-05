<?php
/**
 * Pixmicat! PIO 公用程式 - FileIO Satellite PHP
 *
 * 利用此一放置於外部空間的衛星程式，可以讓 FileIO 利用外部空間存放圖檔。
 *
 * @package PMCUtility
 * @version $Id$
 * @date $Date$
 */

define('TRANSPORT_KEY', '12345678'); // 傳輸認證金鑰
define('USER_AGENT', 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1) Gecko/20061010 Firefox/2.0'); // Just for fun ;-)
define('STORAGE_DIRECTORY', 'src/'); // 圖檔儲存目錄
$mode = isset($_POST['mode']) ? $_POST['mode'] : ''; // 要求模式
$Tkey = isset($_POST['key']) ? $_POST['key'] : ''; // 對方送來傳輸金鑰
$imgname = isset($_POST['imgname']) ? $_POST['imgname'] : ''; // 圖檔名稱

switch($mode){
	case 'init': // 初始化
		DoConstruct() ? DoOK() : DoError();
		break;
	case 'transload': // 遠端抓取
		DoTransload($imgname) ? DoOK() : DoError();
		break;
	case 'upload': // 上傳檔案
		DoUpload($imgname) ? DoOK() : DoError();
		break;
	case 'delete': // 刪除檔案
		DoDelete($imgname) ? DoOK() : DoError();
		break;
	default:
		DoNotFound();
}

/* 初始化 */
function DoConstruct(){
	global $Tkey;
	if($Tkey != TRANSPORT_KEY) return false; // 金鑰不符

	if(!is_dir(STORAGE_DIRECTORY)){ mkdir(STORAGE_DIRECTORY); @chmod(STORAGE_DIRECTORY, 0777); }
	return true;
}

/* 進行遠端抓取檔案並儲存 */
function DoTransload($imgname){
	$imgurl = isset($_POST['imgurl']) ? parse_url($_POST['imgurl']) : false; // 圖檔遠端URL位置
	if(!is_dir(STORAGE_DIRECTORY)) DoConstruct();

	if(!($fp = @fsockopen($imgurl['host'], 80))) return false;

	$out = 'GET '.$imgurl['path']." HTTP/1.1\r\n";
	$out .= 'Host: '.$imgurl['host']."\r\n";
	$out .= 'User-Agent: '.USER_AGENT."\r\n\r\n";
	fwrite($fp, $out);
	$result = '';
	while(!feof($fp)){ $result .= fgets($fp, 128); }
	fclose($fp);

	$result = explode("\r\n\r\n", $result); // 將檔頭和內容分隔開
	if(strpos($result[0], '200 OK')===false) return false; // 檔案不存在或伺服器出現問題

	$fs = fopen(STORAGE_DIRECTORY.$imgname, "wb"); // 二進位儲存
	if(fwrite($fs, $result[1])===false) return false; // 寫入錯誤
	chmod(STORAGE_DIRECTORY.$imgname, 0666);
	fclose($fs);

	return true;
}

/* 接受上傳檔案並儲存 */
function DoUpload($imgname){
	$imgfile = isset($_FILES['imgfile']['tmp_name']) ? $_FILES['imgfile']['tmp_name'] : false;
	if(!$imgfile) return false;
	if(!is_dir(STORAGE_DIRECTORY)) DoConstruct();

	$result = move_uploaded_file($imgfile, realpath('.').DIRECTORY_SEPARATOR.STORAGE_DIRECTORY.$imgname); // 搬移上傳檔案
	if($result) chmod(STORAGE_DIRECTORY.$imgname, 0666);

	return $result;
}

/* 刪除檔案 */
function DoDelete($imgname){
	global $Tkey;
	if($Tkey != TRANSPORT_KEY) return false;

	return @unlink(STORAGE_DIRECTORY.$imgname);
}

/* 阻止閒雜人士進入 */
function DoNotFound(){
	header('HTTP/1.1 404 Not Found');
	echo '<?xml version="1.0" encoding="iso-8859-1"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
         "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
 <head>
  <title>404 - Not Found</title>
 </head>
 <body>
  <h1>404 - Not Found</h1>
 </body>

</html>';
}

/* 操作成功，回傳成功訊息 */
function DoOK(){
	header('HTTP/1.1 202 Accepted');
}

/* 操作失敗，回傳錯誤訊息 */
function DoError(){
	header('HTTP/1.1 403 Forbidden');
}
?>