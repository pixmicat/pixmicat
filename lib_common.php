<?php
// Revision : 2007/1/31 11:35

/* 輸出表頭 */
function head(&$dat){
	global $PMS;
	header('Content-Type: '.((strpos($_SERVER['HTTP_ACCEPT'],'application/xhtml+xml')!==FALSE) ? 'application/xhtml+xml' : 'text/html').'; charset=utf-8'); // 如果瀏覽器支援XHTML標準MIME就輸出
	$dat .= '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="zh-tw">
<head>
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="Sat, 1 Jan 2000 00:00:00 GMT" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Content-Language" content="zh-tw" />
<title>'.TITLE.'</title>
<link rel="stylesheet" type="text/css" href="mainstyle.css" />
';
	$PMS->useModuleMethods('Head', array(&$dat)); // "Head" Hook Point
$dat .= '<!--[if IE]><script type="text/javascript" src="iedivfix.js"></script><![endif]-->
<script type="text/javascript" src="mainscript.js"></script>
<script type="text/javascript">
// <![CDATA[
var ext="'.ALLOW_UPLOAD_EXT.'".toUpperCase().split("|");
// ]]>
</script>
</head>
<body>

<div id="header">
<div id="toplink">
[<a href="'.HOME.'" rel="_top">回首頁</a>]
';
	if(USE_SEARCH) $dat .= '[<a href="'.PHP_SELF.'?mode=search">搜尋</a>]'."\n";
	$PMS->useModuleMethods('Toplink', array(&$dat)); // "Toplink" Hook Point
	$dat .= TOP_LINKS.'
[<a href="'.PHP_SELF.'?mode=status">系統資訊</a>]
[<a href="'.PHP_SELF.'?mode=admin">管理區</a>]
[<a href="'.PHP_SELF2.'?">重新整理</a>]
</div>
<br />
<h1>'.TITLE.'</h1>
<hr class="top" />
</div>

';
}

/* 發表用表單輸出 */
function form(&$dat, $resno){
	global $PMS, $ADDITION_INFO;
	$msg = '';
	if($resno){
		$msg .= '
[<a href="'.PHP_SELF2.'?'.time().'">回到版面</a>]
<div class="bar_reply">回應模式</div>';
	}
	if(USE_FLOATFORM && !$resno) $msg .= "\n".'[<span id="show" class="hide" onmouseover="showform();" onclick="showform();">投稿</span><span id="hide" class="show" onmouseover="hideform();" onclick="hideform();">隱藏表單</span>]';
	$dat .= '<form action="'.PHP_SELF.'" method="post" enctype="multipart/form-data" onsubmit="return c();" id="postform_main">
<div id="postform">'.$msg.'
<input type="hidden" name="mode" value="regist" />
<input type="hidden" name="MAX_FILE_SIZE" value="'.(MAX_KB * 1024).'" />
<input type="hidden" name="upfile_path" value="" />
';
	if($resno) $dat .= '<input type="hidden" name="resto" value="'.$resno.'" />'."\n";
	$dat .= '<div style="text-align: center;">
<table cellpadding="1" cellspacing="1" id="postform_tbl" style="margin: 0px auto; text-align: left;">
<tr><td class="Form_bg"><b>名 稱</b></td><td><input class="hide" type="text" name="name" value="spammer" /><input type="text" name="'.FT_NAME.'" id="fname" size="28" /></td></tr>
<tr><td class="Form_bg"><b>E-mail</b></td><td><input type="text" name="'.FT_EMAIL.'" id="femail" size="28" /><input type="text" class="hide" name="email" value="foo@foo.bar" /></td></tr>
<tr><td class="Form_bg"><b>標 題</b></td><td><input class="hide" value="DO NOT FIX THIS" type="text" name="sub" /><input type="text" name="'.FT_SUBJECT.'" id="fsub" size="28" /><input type="submit" name="sendbtn" value="送 出" /></td></tr>
<tr><td class="Form_bg"><b>內 文</b></td><td><textarea name="'.FT_COMMENT.'" id="fcom" cols="48" rows="4" style="width: 400px; height: 80px;"></textarea><textarea name="com" class="hide" cols="48" rows="4">EID OG SMAPS</textarea></td></tr>
';
	if(RESIMG || !$resno){
		$dat .= '<tr><td class="Form_bg"><b>附加圖檔</b></td><td><input type="file" name="upfile" id="fupfile" size="25" /> <input class="hide" type="checkbox" name="reply" value="yes" />[<input type="checkbox" name="noimg" id="noimg" value="on" /><label for="noimg">無貼圖</label>]';
		if(USE_UPSERIES) $dat .= ' [<input type="checkbox" name="up_series" id="up_series" value="on"'.((isset($_GET["upseries"]) && $resno)?' checked="checked"':'').' /><label for="up_series">連貼機能</label>]'; // 啟動連貼機能
		$dat .= '</td></tr>'."\n";
	}
	if(USE_CATEGORY) $dat .= '<tr><td class="Form_bg"><b>類別標籤</b></td><td><input type="text" name="category" size="28" /><small>(請以 , 逗號分隔多個標籤)</small></td></tr>'."\n";
	$dat .= '<tr><td class="Form_bg"><b>刪除用密碼</b></td><td><input type="password" name="pwd" size="8" maxlength="8" value="" /><small>(刪除文章用。英數字8字元以內)</small></td></tr>
<tr><td colspan="2">
<div id="postinfo">
<ul>
<li>可附加圖檔類型：GIF, JPG, PNG，瀏覽器才能正常附加圖檔</li>
<li>附加圖檔最大上傳資料量為 '.MAX_KB.' KB。當回文時E-mail填入sage為不推文功能</li>
<li>當檔案超過寬 '.MAX_W.' 像素、高 '.MAX_H.' 像素時會自動縮小尺寸顯示</li>'."\n";
	if(STORAGE_LIMIT) $dat .= "<li>目前附加圖檔使用量大小： ".total_size()." KB / ".STORAGE_MAX." KB</li>\n";
	$PMS->useModuleMethods('PostInfo', array(&$dat)); // "PostInfo" Hook Point
	$dat .= $ADDITION_INFO.'
</ul>
<noscript><div>＊您選擇關閉了JavaScript，但這對您的瀏覽及發文應無巨大影響</div></noscript>
</div>
</td></tr>
</table>
</div>
<script type="text/javascript">l1();</script>
<hr />
</div>
</form>
';
	if(USE_FLOATFORM && !$resno) $dat .= '<script type="text/javascript">hideform();</script>'."\n\n";
}

/* 輸出頁尾文字 */
function foot(&$dat){
	global $PMS;
	$dat .= '<div id="footer">
<!-- GazouBBS v3.0 --><!-- ふたば改0.8 --><!-- Pixmicat! -->
';
	$PMS->useModuleMethods('Foot', array(&$dat)); // "Foot" Hook Point
	$dat .= '<small>- <a href="http://php.s3.to" rel="_top">GazouBBS</a> + <a href="http://www.2chan.net/" rel="_top">futaba</a> + <a href="http://pixmicat.openfoundry.org/" rel="_blank">Pixmicat!</a> -</small>
<script type="text/javascript">preset();</script>
</div>

</body>
</html>';
}

/* 網址自動連結 */
function auto_link($proto){
	return preg_replace('/(https?|ftp|news)(:\/\/[\w\+\$\;\?\.\{\}%,!#~*\/:@&=_-]+)/u', '<a href="$1$2" rel="_blank">$1$2</a>', $proto);
}

/* 引用標註 */
function quoteLight($comment){
	return preg_replace('/(^|<br \/>)((?:&gt;|＞).*?)(?=<br \/>|$)/u', '$1<span class="resquote">$2</span>', $comment);
}

/* 取得完整的網址 */
function fullURL(){
	return 'http://'.$_SERVER['HTTP_HOST'].preg_replace('/(.*)\/.+$/', '$1/', $_SERVER['PHP_SELF']);
}

/* 反櫻花字 */
function anti_sakura($str){
	return preg_match('/[\x{E000}-\x{F848}]/u', $str);
}

/* 輸出錯誤畫面 */
function error($mes, $dest=''){
	if(is_file($dest)) unlink($dest);
	head($dat);
	echo $dat;
	echo '<div id="error">
<div style="text-align: center; font-size: 1.5em; font-weight: bold;">
<span style="color: red;">'.$mes.'</span><p />
<a href="'.PHP_SELF2.'?'.time().'">回到版面</a>　<a href="javascript:history.back();">回上頁</a>
</div>
<hr />
</div>
';
	die("</body>\n</html>");
}

/* 生成預覽圖：需要開啟GD模組 (GD 2.0.28以上) */
function thumb($path, $tim, $ext, $in_w, $in_h, $out_w, $out_h){
	if(!function_exists('ImageCreateTrueColor')) return; // GD未開或版本太舊
	$fname = $path.$tim.$ext;
	$thumb_dir = THUMB_DIR; // 預覽圖儲存目錄位置

	// 取得原附加圖檔之長寬及類型
	switch($ext){
		case '.gif': // GIF
			$im_in = @ImageCreateFromGIF($fname);
			break;
		case '.jpg': // JPEG
    		$im_in = @ImageCreateFromJPEG($fname);
			break;
		case '.png': // PNG
			$im_in = @ImageCreateFromPNG($fname);
			break;
		case '.bmp': // BMP
			$im_in = @ImageCreateFromBMP($fname);
			break;
		default: return; // GD不支援的類型
	}
	if(!$im_in) return; // GD不支援的類型
	// 生成預覽圖圖像
	$im_out = ImageCreateTrueColor($out_w, $out_h);
	ImageCopyResampled($im_out, $im_in, 0, 0, 0, 0, $out_w, $out_h, $in_w, $in_h); // 重取樣並縮小
	// 儲存預覽圖
	ImageJPEG($im_out, $thumb_dir.$tim.'s.jpg', THUMB_Q);
	chmod($thumb_dir.$tim.'s.jpg', 0666);
	// 刪除暫存之圖檔
	ImageDestroy($im_in);
	ImageDestroy($im_out);
}

/* ImageCreateFromBMP : 讓GD可處理BMP圖檔
此為修改後最適化版本。原出處：http://www.php.net/imagecreate#53879
原作宣告：
*****************************
Function: ImageCreateFromBMP
Author:	DHKold
Contact: admin@dhkold.com
Date: The 15th of June 2005
Version: 2.0B
*****************************/
function ImageCreateFromBMP($filename){
	// 序章：以二進位模式開啟檔案流
	if(!$f1 = fopen($filename, 'rb')) return FALSE;

	// 第一步：讀取BMP檔頭
	$FILE = unpack('vfile_type/Vfile_size/Vreserved/Vbitmap_offset', fread($f1, 14));
	if($FILE['file_type']!=19778) return FALSE; // BM

	// 第二步：讀取BMP資訊
	// 僅支援BITMAPINFOHEADER，不支援BITMAPV4HEADER及BITMAPV5HEADER
	$BMP = unpack('Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel/Vcompression/Vsize_bitmap/Vhoriz_resolution/Vvert_resolution/Vcolors_used/Vcolors_important', fread($f1, 40));
	$BMP['colors'] = pow(2, $BMP['bits_per_pixel']);
	if($BMP['size_bitmap']==0) $BMP['size_bitmap'] = $FILE['file_size'] - $FILE['bitmap_offset'];
	$BMP['bytes_per_pixel'] = $BMP['bits_per_pixel'] / 8;
	$BMP['decal'] = ($BMP['width'] * $BMP['bytes_per_pixel'] / 4);
	$BMP['decal'] -= floor($BMP['width'] * $BMP['bytes_per_pixel'] / 4);
	$BMP['decal'] = 4 - (4 * $BMP['decal']);
	if($BMP['decal']==4) $BMP['decal'] = 0;

	// 第三步：讀取色盤資訊
	$PALETTE = array();
	if($BMP['colors'] < 16777216) $PALETTE = unpack('V'.$BMP['colors'], fread($f1, $BMP['colors'] * 4));

	// 第四步：變換每一個畫素
	// 尚不支援32bit, 32bit with BITFIELDS, 8bit with RLE8, 4bit with RLE4等格式
	$IMG = fread($f1, $BMP['size_bitmap']);
	$VIDE = chr(0);

	$res = ImageCreateTrueColor($BMP['width'], $BMP['height']);
	$P = 0;
	$Y = $BMP['height'] - 1;
	while($Y >= 0){
		$X = 0;
		while($X < $BMP['width']){
			switch($BMP['bits_per_pixel']){
				case 24: $COLOR = unpack('V', substr($IMG, $P, 3).$VIDE); break;
				case 16: $COLOR = unpack('n', substr($IMG, $P, 2)); break;
				case 8:	$COLOR = unpack('n', $VIDE.substr($IMG, $P, 1)); break;
				case 4:
					$COLOR = unpack('n', $VIDE.substr($IMG, floor($P), 1));
					if(($P*2)%2==0) $COLOR[1] = ($COLOR[1] >> 4);
					else $COLOR[1] = ($COLOR[1] & 0x0F);
					break;
				case 1:
					$COLOR = unpack('n', $VIDE.substr($IMG, floor($P), 1));
					switch(($P * 8) % 8){
						case 0: $COLOR[1] = $COLOR[1] >> 7; break;
						case 1: $COLOR[1] = ($COLOR[1] & 0x40) >> 6; break;
						case 2: $COLOR[1] = ($COLOR[1] & 0x20) >> 5; break;
						case 3: $COLOR[1] = ($COLOR[1] & 0x10) >> 4; break;
						case 4: $COLOR[1] = ($COLOR[1] & 0x8) >> 3; break;
						case 5: $COLOR[1] = ($COLOR[1] & 0x4) >> 2; break;
						case 6: $COLOR[1] = ($COLOR[1] & 0x2) >> 1; break;
						case 7: $COLOR[1] = ($COLOR[1] & 0x1);
					}
					break;
				default:
					return FALSE;
			}
			if($BMP['bits_per_pixel']!=24) $COLOR[1] = $PALETTE[$COLOR[1]+1];
			ImageSetPixel($res, $X, $Y, $COLOR[1]);
			$X++;
			$P += $BMP['bytes_per_pixel'];
		}
		$Y--;
		$P += $BMP['decal'];
	}

	// 終章：關閉檔案，回傳新圖像
	fclose($f1);
	return $res;
}

/* 文字修整 */
function CleanStr($str, $IsAdmin=false){
	$str = trim($str); // 去除前後多餘空白
	if(get_magic_quotes_gpc()) $str = stripslashes($str); // "\"斜線符號去除
	if(!($IsAdmin && CAP_ISHTML)) $str = preg_replace('/&(#[0-9]+|[a-z]+);/i', "&$1;", htmlspecialchars($str)); // 非管理員或管理員自己取消HTML使用：HTML標籤禁用
	else{ // 管理員開啟HTML
		$str = str_replace('>', '&gt;', $str); // 先將每個 > 都轉碼
		$str = preg_replace('/(<.*?)&gt;/', '$1>', $str); // 如果有<...&gt;則轉回<...>成為正常標籤
	}
	return $str;
}

/* 適用UTF-8環境的擬substr，取出特定數目字元
原出處：Sea Otter (？不確定) @ 2005.05.10 */
function str_cut($str, $maxlen=20){
    $i = $l = 0; $len = strlen($str); $f = true; $return_str = $str;
	while($i < $len){
		$chars = ord($str{$i});
		if($chars < 0x80){ $l++; $i++; }
		elseif($chars < 0xe0){ $l++; $i += 2; }
		elseif($chars < 0xf0){ $l += 2; $i += 3; }
		elseif($chars < 0xf8){ $l++; $i += 4; }
      	elseif($chars < 0xfc){ $l++; $i += 5; }
		elseif($chars < 0xfe){ $l++; $i += 6; }
		if(($l >= $maxlen) && $f){
			$return_str = substr($str, 0, $i);
			$f = false;
		}
		if(($l > $maxlen) && ($i <= $len)){
			$return_str = $return_str.'…';
			break;
		}
    }
	return $return_str;
}

/* 檢查瀏覽器和伺服器是否支援gzip壓縮方式 */
function CheckSupportGZip(){
	$HTTP_ACCEPT_ENCODING = isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '';
	if(headers_sent() || connection_aborted()) return 0; // 已送出資料，取消
	if(!(function_exists('gzencode') && function_exists('ob_start') && function_exists('ob_get_clean'))) return 0; // 伺服器相關的套件或函式無法使用，取消
	if(strpos($HTTP_ACCEPT_ENCODING, 'gzip')!==false) return 'gzip';
	return 0;
}

/* 封鎖 IP / Hostname / DNSBL 綜合性檢查 */
function BanIPHostDNSBLCheck($IP, $HOST, &$baninfo){
	if(!BAN_CHECK) return false; // Disabled
	global $BANPATTERN, $DNSBLservers, $DNSBLWHlist;

	// IP/Hostname Check
	$HOST = strtolower($HOST);
	$checkTwice = ($IP != $HOST); // 是否需檢查第二次
	$IsBanned = false;
	foreach($BANPATTERN as $pattern){
		if(substr_count($pattern, '/')==2){ // RegExp
			$pattern .= 'i';
		}elseif(strpos($pattern, '*')!==false || strpos($pattern, '?')!==false){ // Wildcard
			$pattern = '/^'.str_replace(array('.', '*', '?'), array('\.', '.*', '.?'), $pattern).'$/i';
		}else{ // Full-text
			if($IP==$pattern || ($checkTwice && $HOST==strtolower($pattern))){ $IsBanned = true; break; }
			continue;
		}
		if(preg_match($pattern, $HOST) || ($checkTwice && preg_match($pattern, $IP))){ $IsBanned = true; break; }
	}
	if($IsBanned){ $baninfo = '被列在 IP/Hostname 封鎖名單之內'; return true; }

	// DNS-based Blackhole List(DNSBL) 黑名單
	if(!$DNSBLservers[0]) return false; // Skip check
	if(array_search($IP, $DNSBLWHlist)!==false) return false; // IP位置在白名單內
	$rev = implode('.', array_reverse(explode('.', $IP)));
	$lastPoint = count($DNSBLservers) - 1; if($DNSBLservers[0] < $lastPoint) $lastPoint = $DNSBLservers[0];
	$isListed = false;
	for($i = 1; $i <= $lastPoint; $i++){
		$query = $rev.'.'.$DNSBLservers[$i].'.'; // FQDN
		$result = gethostbyname($query);
		if($result && ($result != $query)){ $isListed = $DNSBLservers[$i]; break; }
	}
	if($isListed){ $baninfo = '被列在 DNSBL('.$isListed.') 封鎖名單之內'; return true; }
	return false;
}
?>