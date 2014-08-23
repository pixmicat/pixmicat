<?php
/**
 * Pixmicat! PIO 公用程式 - 重建 tree 樹狀結構檔
 *
 * 本公用程式可嘗試修復毀損的樹狀結構檔。原理是 PIO Log 來源已有內建一些相關樹狀結構供恢復用，
 * 此程式可以將這些資訊統整後再輸出，即成為樹狀結構檔。
 *
 * 注意：若原文章儲存檔已有部分毀損，恕無法正確重建資訊。另原文章 sage 效果亦無法重現，一切按時間排序。
 *
 * @package PMCUtility
 * @version $Id$
 * @date $Date$
 */
include('./config.php');
if(preg_match('/^log:\/\/(.*)\:(.*)\/$/i', CONNECTION_STRING, $linkinfos)){
	$logfile = './'.$linkinfos[1]; // 投稿文字記錄檔檔名
	$treefile = './'.$linkinfos[2]; // 樹狀結構記錄檔檔名
}else{
	exit('PIO Connection String Error! ("log://" Expected).');
}

$tree = array(); // 樹狀結構陣列
$treeline = ''; // 樹狀結構資料
$f = file($logfile);
$f_cnt = count($f);
for($i = 0; $i < $f_cnt; $i++){
	$line = explode(',', $f[$i]);
	if($line[1]==0){ // 首篇
		if(!isset($tree[$line[0]])) $tree[$line[0]] = array($line[0]); // 僅自身一篇
		else array_unshift($tree[$line[0]], $line[0]);
		continue;
	}
	if(!isset($tree[$line[1]])) $tree[$line[1]] = array();
	array_unshift($tree[$line[1]], $line[0]);
}

foreach($tree as $t){ $treeline .= implode(',', $t)."\r\n"; } // 自陣列整理成文字檔形式
$fp = fopen($treefile.'.new', 'w');
stream_set_write_buffer($fp, 0);
fwrite($fp, $treeline);
fclose($fp);
unset($fp);
@chmod($treefile.'.new', 0666);

echo '重建完成，檔案名稱為 "'.$treefile.'.new"，請自行更名為 "'.$treefile.'"。以下是預覽：<hr/><pre>'.$treeline.'</pre>';
?>