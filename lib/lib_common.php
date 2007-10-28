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
function form(&$dat, $resno, $iscollapse=true, $retURL=PHP_SELF, $name='', $mail='', $sub='', $com='', $cat='', $mode='regist'){
	global $PTE, $PMS, $ADDITION_INFO, $language;
	$pte_vals = array('{$SELF}'=>$retURL, '{$FORMTOP}'=>'', '{$MODE}'=>$mode);
	$isedit = ($mode == 'edit'); // 是否為編輯模式
	if($resno && !$isedit){
		$links = '[<a href="'.PHP_SELF2.'?'.time().'">'._T('return').'</a>]';
		$PMS->useModuleMethods('LinksAboveBar', array(&$links,'reply',$resno)); // "LinksAboveBar" Hook Point
		$pte_vals['{$FORMTOP}'] = $links.'<div class="bar_reply">'._T('form_top').'</div>';
	}
	if(USE_FLOATFORM && !$resno && $iscollapse) $pte_vals['{$FORMTOP}'] .= "\n".'[<span id="show" class="hide" onmouseover="showform();" onclick="showform();">'._T('form_showpostform').'</span><span id="hide" class="show" onmouseover="hideform();" onclick="hideform();">'._T('form_hidepostform').'</span>]';
	$pte_vals += array('{$MAX_FILE_SIZE}' => MAX_KB * 1024,
		'{$RESTO}' => $resno ? '<input type="hidden" name="resto" value="'.$resno.'" />' : '',
		'{$FORM_NAME_TEXT}' => _T('form_name'),
		'{$FORM_NAME_FIELD}' => '<input class="hide" type="text" name="name" value="spammer" /><input type="text" name="'.FT_NAME.'" id="fname" size="28" value="'.$name.'" />',
		'{$FORM_EMAIL_TEXT}' => _T('form_email'),
		'{$FORM_EMAIL_FIELD}' => '<input type="text" name="'.FT_EMAIL.'" id="femail" size="28" value="'.$mail.'" /><input type="text" class="hide" name="email" value="foo@foo.bar" />',
		'{$FORM_TOPIC_TEXT}' => _T('form_topic'),
		'{$FORM_TOPIC_FIELD}' => '<input class="hide" value="DO NOT FIX THIS" type="text" name="sub" /><input type="text" name="'.FT_SUBJECT.'" id="fsub" size="28" value="'.$sub.'" />',
		'{$FORM_SUBMIT}' => '<input type="submit" name="sendbtn" value="'._T('form_submit_btn').'" />',
		'{$FORM_COMMENT_TEXT}' => _T('form_comment'),
		'{$FORM_COMMENT_FIELD}' => '<textarea name="'.FT_COMMENT.'" id="fcom" cols="48" rows="4" style="width: 400px; height: 80px;">'.$com.'</textarea><textarea name="com" class="hide" cols="48" rows="4">EID OG SMAPS</textarea>',
		'{$FORM_DELETE_PASSWORD_FIELD}' => '<input type="password" name="pwd" size="8" maxlength="8" value="" />',
		'{$FORM_DELETE_PASSWORD_TEXT}' => _T('form_delete_password'),
		'{$FORM_DELETE_PASSWORD_NOTICE}' => _T('form_delete_password_notice'),
		'{$FORM_EXTRA_COLUMN}' => '',
		'{$FORM_NOTICE}' => _T('form_notice',MAX_KB,MAX_W,MAX_H),
		'{$HOOKPOSTINFO}' => '',
		'{$ADDITION_INFO}' => $ADDITION_INFO,
		'{$FORM_NOTICE_NOSCRIPT}' => _T('form_notice_noscript'));
	$PMS->useModuleMethods('PostForm', array(&$pte_vals['{$FORM_EXTRA_COLUMN}'])); // "PostForm" Hook Point
	if(!$isedit && (RESIMG || !$resno)){
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
		$pte_vals += array('{$FORM_CATEGORY_FIELD}' => '<input type="text" name="category" size="28" value="'.$cat.'" />',
			'{$FORM_CATEGORY_TEXT}' => _T('form_category'),
			'{$FORM_CATEGORY_NOTICE}' => _T('form_category_notice'));
	}
	if(STORAGE_LIMIT) $pte_vals['{$FORM_NOTICE_STORAGE_LIMIT}'] = _T('form_notice_storage_limit',total_size(),STORAGE_MAX);
	$PMS->useModuleMethods('PostInfo', array(&$pte_vals['{$HOOKPOSTINFO}'])); // "PostInfo" Hook Point

	if(USE_FLOATFORM && !$resno && $iscollapse) $pte_vals['{$FORMBOTTOM}'] = '<script type="text/javascript">hideform();</script>';
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
	$proto = preg_replace('|<br\s*/?>|',"\n",$proto);
	$proto = preg_replace_callback('/(>|^)([^<]+?)(<.*?>|$)/m','auto_link_callback',$proto);
	return str_replace("\n",'<br />',$proto);
}

/* 引用標註 */
function quoteLight($comment){
	return preg_replace('/(^|<br \/>)((?:&gt;|＞).*?)(?=<br \/>|$)/u', '$1<span class="resquote">$2</span>', $comment);
}

/* 取得完整的網址 */
function fullURL(){
	return 'http://'.$_SERVER['HTTP_HOST'].substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], PHP_SELF));
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
	$HOST = strtolower($HOST); $IPbin = ip2bin($IP);
	$checkTwice = ($IP != $HOST); // 是否需檢查第二次
	$IsBanned = false;
	foreach($BANPATTERN as $pattern){
		$slash = substr_count($pattern, '/');
		if($slash==2){ // RegExp
			$pattern .= 'i';
		}elseif($slash==1){ // CIDR Notation
			$slashpos = strpos($pattern, '/');
			$cidr = substr(ip2bin(substr($pattern, 0, $slashpos)), 0, substr($pattern, $slashpos + 1));
			if(preg_match('/^'.$cidr.'/', $IPbin)){ $IsBanned = true; break; }
			continue;
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
function ip2bin($ip){
	$ipbin = '';
	foreach(explode('.', $ip) as $part) $ipbin .= str_pad(decbin($part), 8, '0', STR_PAD_LEFT);
	return $ipbin;
}

/* 後端登入權限管理 */
function adminAuthenticate($mode){
	@session_start();
	$loginkey = md5($_SERVER['HTTP_USER_AGENT'].ADMIN_PASS.$_SERVER['REMOTE_ADDR']);
	switch($mode){
		case 'logout':
			if(isset($_SESSION['pmcLogin'])) unset($_SESSION['pmcLogin']);
			return true; break;
		case 'login':
			$_SESSION['pmcLogin'] = $loginkey;
			break;
		case 'check':
			if(isset($_SESSION['pmcLogin']) && $_SESSION['pmcLogin']==$loginkey){
				session_regenerate_id(true); // 更換 Session id key 避免 Hijacking
				return true;
			}
			return false;
			break;
	}
}

/* 取得 (Transparent) Proxy 提供之 IP 參數 */
function getREMOTE_ADDR(){
	if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
		$tmp = preg_split('/[ ,]+/', $_SERVER['HTTP_X_FORWARDED_FOR']);
		return $tmp[0];
	}
	return $_SERVER['REMOTE_ADDR'];
}
?>