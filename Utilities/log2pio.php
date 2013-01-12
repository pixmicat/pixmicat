<?php
/**
 * Pixmicat! PIO 公用程式 - Pixmicat!-Log -> Pixmciat-PIO (Log) 資料格式轉換器
 *
 * 本程式可以自 Log 版轉換格式自 PIO 版 Log 資料來源。
 *
 * 注意：本程式是給 Log 版舊程式使用以轉換，非直接用在 PIO 新版上面
 *
 * @package PMCUtility
 * @version $Id$
 * @date $Date$
 */
include_once('./config.php');
define('DEL_ZOMBIE', true); // 如果有文章沒有出現在樹狀結構，是否不要轉換直接刪除？
define('SAVE_LOG', true); // 是否儲存新結構 (舊結構將保留並更名)

if (!defined('LOGFILE'))
	die('This php is for Pixmicat!-Log only.');

// 各資料儲存檔位置
$logimg = file(LOGFILE); $logimg_cnt = count($logimg);
$trees = array(); // 文章回應對應編號陣列 (回應No. => 首篇No.)
$logtree = array_map('rtrim', file(TREEFILE));
foreach($logtree as $treeline){ // 解析樹狀結構製成對應索引
	if($treeline=='') continue;
	$tline = explode(',', $treeline); $tline_cnt = count($tline);
	$trees[$tline[0]] = 0;
	for($t = 1; $t < $tline_cnt; $t++){ $trees[$tline[$t]] = $tline[0]; }
}
unset($logtree);

// 圖檔存放位置
$dirimg = realpath('.').DIRECTORY_SEPARATOR.IMG_DIR;

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

/* 取得回應的對象編號 */
function getReplyTargetNo($no){
	global $trees;

	if(isset($trees[$no])) return $trees[$no];
	return false;
}

/* 更改 log 檔結構 */
// OLD: 編號,時間 (ID),名稱,E-Mail,標題,內文,狀態旗標,主機位置,編碼後文章密碼,附加圖檔類型,預覽圖寬,預覽圖長,Unix時間撮記,附加圖檔MD5,
//      0    1         2    3      4    5    6        7        8              9           10       11       12           13
// NEW: 編號,回應目標編號,附加圖檔MD5,類別標籤,Unix時間撮記,附加圖檔類型,圖檔寬,圖檔長,圖檔大小,預覽圖寬,預覽圖長,編碼後文章密碼,時間 (ID),名稱,E-mail,標題,內文,主機位置,狀態旗標,
//      0    1            2           3        4            5            6      7     8        9        10       11             12        13   14     15   16   17       18
header('Content-Type: text/plain; charset=utf-8');
$newLine = array(); // 新資料格式
if(count(explode(',', $logimg[0])) != 15) die('File structure error. maybe it\'s already a PIO structure.');
for($i = 0; $i < $logimg_cnt; $i++){
	$l = explode(',', $logimg[$i]); // 舊資料格式 (用逗號拆開)
	$s = getImageWH($l[12].$l[9]); // 圖檔寬長 (寬, 長)
	$l[6] = str_replace('_THREADSTOP_', '_TS_', $l[6]); // 討論串停止旗標自 _THREADSTOP_ 改為 _TS_
	if(!DEL_ZOMBIE || getReplyTargetNo($l[0]) !== false) $newLine[] = implode(',', array($l[0], getReplyTargetNo($l[0]), $l[13], '', $l[12], $l[9], $s[0], $s[1], getImageSizeText($l[12].$l[9]), (int) $l[10], (int) $l[11], $l[8], $l[1], $l[2], $l[3], $l[4], $l[5], $l[7], $l[6], ''))."\r\n";
}
$writeContent = implode('', $newLine);

if(SAVE_LOG){
	rename(LOGFILE, LOGFILE.'.old');
	$fs = fopen(LOGFILE, 'w');
	fwrite($fs, $writeContent);
	fclose($fs);
	die('File save OK. The old file already renamed.');
}else{
	echo $writeContent;
}
?>