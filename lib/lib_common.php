<?php
/**
 * Pixmicat! Common Library
 *
 * 存放常用函式供主程式引入
 * 
 * @package PMCLibrary
 * @version $Id$
 * @date $Date$
 */

/* 輸出表頭 */
function head(&$dat,$resno=0){
	global $PTE, $PMS, $language;
	header('Content-Type: '.((strpos($_SERVER['HTTP_ACCEPT'],'application/xhtml+xml')!==FALSE) ? 'application/xhtml+xml' : 'text/html').'; charset=utf-8'); // 如果瀏覽器支援XHTML標準MIME就輸出
	$pte_vals = array('{$TITLE}'=>TITLE,'{$RESTO}'=>$resno?$resno:'');
	$dat .= $PTE->ParseBlock('HEADER',$pte_vals);
	$PMS->useModuleMethods('Head', array(&$dat,$resno)); // "Head" Hook Point
	$pte_vals+=array('{$ALLOW_UPLOAD_EXT}' => ALLOW_UPLOAD_EXT,
		'{$JS_REGIST_WITHOUTCOMMENT}' => str_replace('\'', '\\\'', _T('regist_withoutcomment')),
		'{$JS_REGIST_UPLOAD_NOTSUPPORT}' => str_replace('\'', '\\\'', _T('regist_upload_notsupport')),
		'{$JS_CONVERT_SAKURA}' => str_replace('\'', '\\\'', _T('js_convert_sakura')));
	$dat .= $PTE->ParseBlock('JSHEADER',$pte_vals);
	$dat .= '</head>';
	$pte_vals += array('{$TOP_LINKS}' => TOP_LINKS,
		'{$HOME}' => '[<a href="'.HOME.'" rel="_top">'._T('head_home').'</a>]',
		'{$STATUS}' => '[<a href="'.PHP_SELF.'?mode=status">'._T('head_info').'</a>]',
		'{$ADMIN}' => '[<a href="'.PHP_SELF.'?mode=admin">'._T('head_admin').'</a>]',
		'{$REFRESH}' => '[<a href="'.PHP_SELF2.'?">'._T('head_refresh').'</a>]',
		'{$SEARCH}' => (USE_SEARCH) ? '[<a href="'.PHP_SELF.'?mode=search">'._T('head_search').'</a>]' : '',
		'{$HOOKLINKS}' => '');
	$PMS->useModuleMethods('Toplink', array(&$pte_vals['{$HOOKLINKS}'],$resno)); // "Toplink" Hook Point
	$dat .= $PTE->ParseBlock('BODYHEAD',$pte_vals);
}

/* 發表用表單輸出 */
function form(&$dat, $resno){
	global $PTE, $PMS, $ADDITION_INFO, $language;
	$pte_vals = array('{$SELF}'=>PHP_SELF, '{$FORMTOP}'=>'');
	if($resno){
		$pte_vals['{$FORMTOP}'] = '[<a href="'.PHP_SELF2.'?'.time().'">'._T('return').'</a>]
<div class="bar_reply">'._T('form_top').'</div>';
	}
	if(USE_FLOATFORM && !$resno) $pte_vals['{$FORMTOP}'] .= "\n".'[<span id="show" class="hide" onmouseover="showform();" onclick="showform();">'._T('form_showpostform').'</span><span id="hide" class="show" onmouseover="hideform();" onclick="hideform();">'._T('form_hidepostform').'</span>]';
	$pte_vals += array('{$MAX_FILE_SIZE}' => MAX_KB * 1024,
		'{$RESTO}' => $resno ? '<input type="hidden" name="resto" value="'.$resno.'" />' : '',
		'{$FORM_NAME_TEXT}' => _T('form_name'),
		'{$FORM_NAME_FIELD}' => '<input class="hide" type="text" name="name" value="spammer" /><input type="text" name="'.FT_NAME.'" id="fname" size="28" />',
		'{$FORM_EMAIL_TEXT}' => _T('form_email'),
		'{$FORM_EMAIL_FIELD}' => '<input type="text" name="'.FT_EMAIL.'" id="femail" size="28" /><input type="text" class="hide" name="email" value="foo@foo.bar" />',
		'{$FORM_TOPIC_TEXT}' => _T('form_topic'),
		'{$FORM_TOPIC_FIELD}' => '<input class="hide" value="DO NOT FIX THIS" type="text" name="sub" /><input type="text" name="'.FT_SUBJECT.'" id="fsub" size="28" />',
		'{$FORM_SUBMIT}' => '<input type="submit" name="sendbtn" value="'._T('form_submit_btn').'" />',
		'{$FORM_COMMENT_TEXT}' => _T('form_comment'),
		'{$FORM_COMMENT_FIELD}' => '<textarea name="'.FT_COMMENT.'" id="fcom" cols="48" rows="4" style="width: 400px; height: 80px;"></textarea><textarea name="com" class="hide" cols="48" rows="4">EID OG SMAPS</textarea>',
		'{$FORM_DELETE_PASSWORD_FIELD}' => '<input type="password" name="pwd" size="8" maxlength="8" value="" />',
		'{$FORM_DELETE_PASSWORD_TEXT}' => _T('form_delete_password'),
		'{$FORM_DELETE_PASSWORD_NOTICE}' => _T('form_delete_password_notice'),
		'{$FORM_EXTRA_COLUMN}' => '',
		'{$FORM_NOTICE}' => _T('form_notice',MAX_KB,MAX_W,MAX_H),
		'{$HOOKPOSTINFO}' => '',
		'{$ADDITION_INFO}' => $ADDITION_INFO,
		'{$FORM_NOTICE_NOSCRIPT}' => _T('form_notice_noscript'));
	$PMS->useModuleMethods('PostForm', array(&$pte_vals['{$FORM_EXTRA_COLUMN}'])); // "PostForm" Hook Point
	if(RESIMG || !$resno){
		$pte_vals += array('{$FORM_ATTECHMENT_TEXT}' => _T('form_attechment'),
			'{$FORM_ATTECHMENT_FIELD}' => '<input type="file" name="upfile" id="fupfile" size="25" /><input class="hide" type="checkbox" name="reply" value="yes" />',
			'{$FORM_NOATTECHMENT_TEXT}' => _T('form_noattechment'),
			'{$FORM_NOATTECHMENT_FIELD}' => '<input type="checkbox" name="noimg" id="noimg" value="on" />');
		if(USE_UPSERIES) { // 啟動連貼機能
			$pte_vals['{$FORM_CONTPOST_FIELD}'] = '<input type="checkbox" name="up_series" id="up_series" value="on"'.((isset($_GET["upseries"]) && $resno)?' checked="checked"':'').' />';
			$pte_vals['{$FORM_CONTPOST_TEXT}'] = _T('form_contpost');
		}
	}
	if(USE_CATEGORY) {
		$pte_vals += array('{$FORM_CATEGORY_FIELD}' => '<input type="text" name="category" size="28" />',
			'{$FORM_CATEGORY_TEXT}' => _T('form_category'),
			'{$FORM_CATEGORY_NOTICE}' => _T('form_category_notice'));
	}
	if(STORAGE_LIMIT) $pte_vals['{$FORM_NOTICE_STORAGE_LIMIT}'] = _T('form_notice_storage_limit',total_size(),STORAGE_MAX);
	$PMS->useModuleMethods('PostInfo', array(&$pte_vals['{$HOOKPOSTINFO}'])); // "PostInfo" Hook Point

	if(USE_FLOATFORM && !$resno) $pte_vals['{$FORMBOTTOM}'] = '<script type="text/javascript">hideform();</script>';
	$dat .= $PTE->ParseBlock('POSTFORM',$pte_vals);
}

/* 輸出頁尾文字 */
function foot(&$dat){
	global $PTE, $PMS, $language;
	$pte_vals = array('{$FOOTER}'=>'<!-- GazouBBS v3.0 --><!-- ふたば改0.8 --><!-- Pixmicat! -->');
	$PMS->useModuleMethods('Foot', array(&$pte_vals['{$FOOTER}'])); // "Foot" Hook Point
	$pte_vals['{$FOOTER}'] .= '<small>- <a href="http://php.s3.to" rel="_top">GazouBBS</a> + <a href="http://www.2chan.net/" rel="_top">futaba</a> + <a href="http://pixmicat.openfoundry.org/" rel="_blank">Pixmicat!</a> -</small>';
	$dat .= $PTE->ParseBlock('FOOTER',$pte_vals);
}

/* 網址自動連結 */
function auto_link_callback($matches){ 
	return (strtolower($matches[3]) == "</a>") ? $matches[0] : preg_replace('/(https?|ftp|news)(:\/\/[\w\+\$\;\?\.\{\}%,!#~*\/:@&=_-]+)/u', '<a href="$1$2" rel="_blank">$1$2</a>', $matches[0]);
}
function auto_link($proto){
	return preg_replace_callback('/(>|^)([^<]+?)(<.*?>|$)/','auto_link_callback',$proto);
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
	global $PTE;
	if(is_file($dest)) unlink($dest);
	$pte_vals = array('{$SELF2}'=>PHP_SELF2.'?'.time(), '{$MESG}'=>$mes, '{$RETURN_TEXT}'=>_T('return'), '{$BACK_TEXT}'=>_T('error_back'));
	$dat = '';
	head($dat);
	$dat .= $PTE->ParseBlock('ERROR',$pte_vals);
	foot($dat);
	exit($dat);
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
原出處：Sea Otter @ 2005.05.10
http://www.meyu.net/star/viewthread.php?tid=267&fpage=10 */
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
	if($IsBanned){ $baninfo = _T('ip_banned'); return true; }

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
	if($isListed){ $baninfo = _T('ip_dnsbl_banned',$isListed); return true; }
	return false;
}
?>