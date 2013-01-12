<?php
/**
 * Pixmicat! PIO 公用程式 - PIO 匯入匯出轉換器
 *
 * 這是 PIO 額外提供的功能，可以執行匯出備份、匯入備份、來源轉換等動作。
 * 請修改下方 PIO_ANOTHER_CONNSTR PIO 連線字串 (匯入及轉換動作才需要，匯入則直接使用設定檔設定)
 *
 * @package PMCUtility
 * @version $Id$
 * @date $Date$
 */
include('./config.php');
require ROOTPATH.'lib/pmclibrary.php';

define('PIO_ANOTHER_CONNSTR', 'sqlite3://another-pixmicat.db3/imglog/'); // Another-PIO 連線字串 (此來源必須無任何資料，全新)
$PIOEnv = array( // PIO 環境常數
	'BOARD' => '.',
	'LUTCACHE' => './lutcache.dat',
	'NONAME' => DEFAULT_NONAME,
	'NOTITLE' => DEFAULT_NOTITLE,
	'NOCOMMENT' => DEFAULT_NOCOMMENT,
	'LOG_MAX' => defined('LOG_MAX') ? LOG_MAX : 0,
	'PERIOD.POST' => RENZOKU,
	'PERIOD.IMAGEPOST' => RENZOKU2
);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="zh-tw">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Content-Language" content="zh-tw" />
<title>PIO -> PIO Im/Exporter</title>
</head>
<body>

<div id="main">
請選擇下列一項進行操作：
<ul>
	<li><a href="?mode=export">匯出目前 PIO 資料成中介檔案</a></li>
	<li><a href="?mode=import">匯入目前中介檔案到 Another-PIO (請修改此頁 PHP 設定)</a></li>
	<li><a href="?mode=convert">將目前 PIO 資料轉換到 Another-PIO</a></li>
</ul>
<hr />
<div id="result">
<?php
$mode = isset($_GET['mode']) ? $_GET['mode'] : '';
switch($mode){
	case 'export': doExport(); break;
	case 'import': doImport(); break;
	case 'convert': doConvert(); break;
}

function doExport(){
	global $PIOEnv;
	if(preg_match('/^(.*):\/\//i', CONNECTION_STRING, $backend)) define('PIO_FROM', $backend[1]);
	include('./lib/pio/pio.'.PIO_FROM.'.php');
	$pio1 = 'PIO'.PIO_FROM; $PIO = new $pio1(CONNECTION_STRING, $PIOEnv);

	$gp = gzopen('piodata.log.gz', 'w9');
	gzwrite($gp, $PIO->dbExport());
	gzclose($gp);
	echo '<a href="piodata.log.gz">下載 piodata.log.gz 中介檔案</a>';
}

function doImport(){
	global $PIOEnv;
	if(preg_match('/^(.*):\/\//i', PIO_ANOTHER_CONNSTR, $backend)) define('PIO_FROM', $backend[1]);
	include('./lib/pio/pio.'.PIO_FROM.'.php');
	$pio1 = 'PIO'.PIO_FROM; $PIO = new $pio1(PIO_ANOTHER_CONNSTR, $PIOEnv);

	if(!file_exists('piodata.log.gz')){ echo '檔案不存在，請先放置在相同目錄。'; return; }
	$data = '';
	$gp = gzopen('piodata.log.gz', 'r');
	while(!gzeof($gp)) $data .= gzread($gp, 4096);
	gzclose($gp);
	echo $PIO->dbImport($data) ? '匯入成功' : '匯入失敗';
}

function doConvert(){
	global $PIOEnv;
	if(preg_match('/^(.*):\/\//i', CONNECTION_STRING, $backend)) define('PIO_FROM', $backend[1]);
	if(preg_match('/^(.*):\/\//i', PIO_ANOTHER_CONNSTR, $backend)) define('PIO_TO', $backend[1]);

	include('./lib/pio/pio.'.PIO_FROM.'.php');
	include('./lib/pio/pio.'.PIO_TO.'.php');

	$pio1 = 'PIO'.PIO_FROM; $pio2 = 'PIO'.PIO_TO;
	$PIOa = new $pio1(CONNECTION_STRING, $PIOEnv); $PIOb = new $pio2(PIO_ANOTHER_CONNSTR, $PIOEnv);
	echo $PIOb->dbImport($PIOa->dbExport()) ? '轉換成功' : '轉換失敗'; // PIOa -> PIOb
}
?>
</div>
</div>

</body>
</html>