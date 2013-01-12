<?php
/**
 * Pixmicat! PIO 公用程式 - Pixmicat!-MySQL -> Pixmciat-PIO (MySQL) 資料格式轉換器
 *
 * 本程式可以自 MySQL 版轉換格式自 PIO 版 MySQL 資料來源。
 *
 * 注意：本程式是給 MySQL 版舊程式使用以轉換，非直接用在 PIO 新版上面
 *
 * @package PMCUtility
 * @version $Id$
 * @date $Date$
 */
include_once('./config.php');

if (!defined('MYSQL_SERVER'))
	die('This php is for Pixmicat!-MySQL only.');

$dirimg = realpath('.').DIRECTORY_SEPARATOR.IMG_DIR; // 圖檔存放位置

/* 取得圖檔的寬長以存入資料 */
function getImageWH($imgname){
	global $dirimg;

	$imgpath = $dirimg.$imgname;
	if(!file_exists($imgpath)) return array(0, 0);
	list($width, $height,) = getimagesize($imgpath);
	return array($width, $height); // 回傳寬高陣列
}

/* 取得圖檔的檔案大小字串 (單位 KB) */
function getImageSizeText($imgname){
	global $dirimg;

	$imgpath = $dirimg.$imgname;
	if(!file_exists($imgpath)) return false;
	$imgsize = filesize($imgpath);
	return ($imgsize >= 1024 ? (int)($imgsize / 1024).' KB' : $imgsize.' B'); // 回傳檔案大小字串
}


if(@!$con=mysql_connect(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD)){
	echo 'It occurred a fatal error when connecting to the MySQL server.<p>';
	echo 'Check your MySQL login setting in config file or the MySQL server status.';
	exit;
}
mysql_select_db(MYSQL_DBNAME, $con);
@mysql_query("SET NAMES 'utf8'"); // MySQL資料以UTF-8模式傳送

if(($result = mysql_query("SHOW COLUMNS FROM ".SQLLOG." LIKE 'category'"))  && mysql_num_rows($result) == 0){ // 更新資料表結構
	mysql_query('ALTER TABLE '.SQLLOG.' ADD category VARCHAR(255) NOT NULL AFTER md5, COMMENT = "For Pixmicat!-PIO [Structure V3]"'); // category
	mysql_query('ALTER TABLE '.SQLLOG.' ADD imgw SMALLINT(1) NOT NULL AFTER ext'); // imgw
	mysql_query('ALTER TABLE '.SQLLOG.' ADD imgh SMALLINT(1) NOT NULL AFTER imgw'); // imgh
	mysql_query('ALTER TABLE '.SQLLOG.' ADD imgsize VARCHAR(10) NOT NULL AFTER imgh'); // imgsize
	mysql_query('ALTER TABLE '.SQLLOG.' CHANGE md5 md5chksum VARCHAR(32) NOT NULL'); // md5chksum
	mysql_query('ALTER TABLE '.SQLLOG.' CHANGE w tw SMALLINT(1) NOT NULL'); // tw
	mysql_query('ALTER TABLE '.SQLLOG.' CHANGE h th SMALLINT(1) NOT NULL'); // th
	mysql_query('ALTER TABLE '.SQLLOG.' MODIFY status VARCHAR(255) NOT NULL'); // status
	mysql_query('UPDATE '.SQLLOG.' SET status = "_TS_" WHERE status = "T"'); // status 旗標改變
	mysql_free_result($result);

	$tmpSQL = 'SELECT no,tim,ext FROM '.SQLLOG.' WHERE ext <> "" ORDER BY no';
	if(!$result2=mysql_query($tmpSQL)) echo "sql失敗814<br>";
	while(list($dno, $dtim, $dext)=mysql_fetch_row($result2)){ // 個別跑迴圈
		$s = getImageWH($dtim.$dext); // 圖檔寬長
		mysql_query('UPDATE '.SQLLOG.' SET imgsize = "'.getImageSizeText($dtim.$dext).'", imgw = '.$s[0].', imgh = '.$s[1].' WHERE no = '.$dno);
	}
	mysql_free_result($result2);
	echo 'done.';
}else{
	echo 'It seems already done.';
}
mysql_close($con);
?>