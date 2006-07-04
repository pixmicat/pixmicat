<?php
define("FUTABA_VER", 'Pixmicat!-Log 3rd.Release b060617-PIO'); // 版本資訊文字
/*
Pixmicat! : 圖咪貓貼圖版程式
http://pixmicat.openfoundry.org/
版權所有 © 2005-2006 Pixmicat! Development Team

版權聲明：
此程式是基於レッツPHP!<http://php.s3.to/>的gazou.php、
雙葉<http://www.2chan.net>的futaba.php所改寫之衍生著作程式，屬於自由軟體，
以The Clarified Artistic License作為發佈授權條款。
您可以遵照The Clarified Artistic License來自由使用、散播、修改或製成衍生著作。
更詳細的條款及定義請參考隨附"LICENSE"條款副本。

發佈這一程式的目的是希望它有用，但沒有任何擔保，甚至沒有適合特定目的而隱含的擔保。
關於此程式相關的問題請不要詢問レッツPHP!及雙葉。

如果您沒有隨著程式收到一份The Clarified Artistic License副本，
請瀏覽http://pixmicat.openfoundry.org/license/以取得一份。

最低運行需求：
PHP 4.3.0 / 27 December 2002 (gd_info[取得GD資訊], md5_file[取得檔案內容MD5], //u[PCRE_UTF8])
GD Version 2.0.28 / 21 July 2004 (ImageCreateFromGIF[GIF讀取支援])

設置方法：
根目錄的權限請設為777，
只要將pixmicat.php執行過一遍，必要的檔案和資料夾權限皆會自動設定，
自動設定完成後請刪除此檔案底部之init(); // ←■■！程式環境初始化(略)，
以免無謂的迴圈費時。
細部的設定請打開config.php參考註解修改。
*/

extract($_POST);
extract($_GET);

$upfile = isset($_FILES['upfile']['tmp_name']) ? $_FILES['upfile']['tmp_name'] : '';
$upfile_name = isset($_FILES['upfile']['name']) ? $_FILES['upfile']['name'] : '';
$upfile_status = isset($_FILES['upfile']['error']) ? $_FILES['upfile']['error'] : 4;

include_once('./lib_common.php'); // 引入共通函式檔案
include_once('./config.php'); // 引入設定檔
if(USE_TEMPLATE) include_once('./lib_pte.php'); // 引入PTE外部函式庫
include_once('./lib_fileio.php'); // 引入FileIO
include_once('./lib_pio.php'); // 引入PIO

/* 更新記錄檔檔案／輸出討論串 */
function updatelog($resno=0,$page_num=0){
	global $path;
	$st = 0; $tmp_page_num = 0;
	$kill_sensor = false;

	$tree=fetchThreadList();
	$counttree=threadCount();

	if($resno) {
		if(!is_Thread($resno)) error('欲回應之文章並不存在！');
		$torder_flip=array_flip($tree);
		$st=$torder_flip[$resno];
	}

	// 附加檔案容量限制功能：預測將被刪除檔案
	$tmp_total_size = total_size(); // 取得目前附加檔案使用量
	$tmp_STORAGE_MAX = STORAGE_MAX * (($tmp_total_size >= STORAGE_MAX) ? 1 : 0.95); // 預估上限值，如果發生一開始就超過上限就直接用上限取代
	if(STORAGE_LIMIT && ($tmp_total_size >= $tmp_STORAGE_MAX)){ // 超過預估上限值 (或直接超過上限)
		$kill_sensor = true; // 預測標記打開
		$arr_kill = delOldAttachments($tmp_total_size,$tmp_STORAGE_MAX);
	}

	$porder_flip=array_flip(fetchPostList());
	if(!$resno){ // php動態生成討論串用
		if($page_num < 0 || ($page_num * PAGE_DEF) >= $counttree) error('對不起，您所要求的頁數並不存在'); // $page_num 超過範圍則錯誤
		$tmp_page_num = $page_num; // 進行跳頁所用
	}else $tmp_page_num = 0; // 回應模式不跳頁 (此為討論串分頁用，不同於回應分頁)
	if(USE_TEMPLATE){ // 使用樣板
		$PTE = new PmcTplEmbed(); // 造一個樣板函式庫物件
		$PTE->LoadTemplate(TEMPLATE_FILE); // 讀取樣板檔
	}
	for($page = $tmp_page_num * PAGE_DEF; $page < $counttree; $page += PAGE_DEF){
   		$dat = '';
   		head($dat);
   		form($dat,$resno);
   		if(!$resno) $st = $page;
   		$dat .= '<div id="contents">

<form action="'.PHP_SELF.'" method="post">
<div id="threads">

';

		for($i = $st; $i < $st + PAGE_DEF; $i++){
			$imgsrc = $img_thumb = $imgwh_bar = '';
			$IMG_BAR = $REPLYBTN = $QUOTEBTN = $WARN_OLD = $WARN_BEKILL = $WARN_ENDREPLY = $WARN_HIDEPOST = '';

			if(!isset($tree[$i])) break;
			$treeline = fetchPostList($tree[$i]);
			$treeline_count = count($treeline);
//			list($no,$now,$name,$email,$sub,$com,$url,$host,,$ext,$w,$h,$time,) = array_values(fetchPosts($treeline[0]));
			extract(fetchPosts($treeline[0]));

			// 設定一些欄位
			if(CLEAR_SAGE) $email = preg_replace('/^sage( *)/i', '', trim($email)); // 清除E-mail中的「sage」關鍵字
			if($email) $name = "<a href=\"mailto:$email\">$name</a>";
			if(AUTO_LINK) $com = auto_link($com); // 內文自動作成連結
			$com = quoteLight($com);
			$name = preg_replace('/(◆.{10})/', '<span class="nor">$1</span>', $name); // Trip取消粗體

			// 附加檔案名稱
			$src = IMG_DIR.$time.$ext;
			$img = $path.$src;
			if(!USE_TEMPLATE) $dat .= "<div class=\"threadpost\">\n";
			if($ext && file_func('exist',$img)){
				$size = file_func('size',$img);
				$size = ($size>=1024) ? (int)($size/1024)." K" : $size." "; // KB和B的判別
				if($w && $h){ // 有長寬屬性
					if(file_func('exist',$path.THUMB_DIR.$time.'s.jpg')){
						$img_thumb = '<small>[以預覽圖顯示]</small>';
						$imgsrc = '<a href="'.$src.'" rel="_blank"><img src="'.THUMB_DIR.$time.'s.jpg" style="width: '.$w.'px; height: '.$h.'px;" class="img" alt="'.$size.'B" title="'.$size.'B" /></a>';
					}elseif($ext=='.swf'){ // swf檔案僅留連結就好
					}else{
						$imgsrc = '<a href="'.$src.'" rel="_blank"><img src="nothumb.gif" class="img" alt="'.$size.'B" title="'.$size.'B" /></a>';
					}
				}else{ // 沒有長寬屬性
					$imgsrc = '<a href="'.$src.'" rel="_blank"><img src="nothumb.gif" class="img" alt="'.$size.'B" title="'.$size.'B" /></a>';
				}
				if(SHOW_IMGWH){ // 顯示附加檔案之原檔長寬尺寸
					$imgwh_bar = file_func('imgsize',$img);
				}
				$IMG_BAR = '檔名：<a href="'.$src.'" rel="_blank">'.$time.$ext.'</a>-('.$size.'B'.$imgwh_bar.') '.$img_thumb;
				if(!USE_TEMPLATE) $dat .= $IMG_BAR.'<br />'.$imgsrc;
			}
			// 回應 / 引用連結
			if(!$resno){
				$REPLYBTN = '[<a href="'.PHP_SELF.'?res='.$no.'">回應</a>]';
				$QUOTEBTN = '<a href="'.PHP_SELF.'?res='.$no.'#q'.$no.'" class="qlink">';
			}else{
				$QUOTEBTN = '<a href="javascript:quote('.$no.');" class="qlink">';
			}
			// 快要被刪除的提示
			if($porder_flip[$no] >= LOG_MAX * 0.95) $WARN_OLD = '<span class="warn_txt">這篇已經很舊了，不久後就會刪除。</span><br />'."\n";
			// 預測刪除過大檔
			if(STORAGE_LIMIT && $kill_sensor) if(isset($arr_kill[$no])) $WARN_BEKILL = '<span class="warn_txt">這篇因附加檔案容量限制，附加檔案不久後就會刪除。</span><br />'."\n";
			// 被標記為禁止回應
			if(strpos($url, '_THREADSTOP_')!==FALSE) $WARN_ENDREPLY = '<span class="warn_txt">這篇討論串已被管理員標記為禁止回應。</span><br />'."\n";
			// 討論串主文章生成
			if(!USE_TEMPLATE){
				$dat .= '<input type="checkbox" name="'.$no.'" value="delete" /><span class="title">'.$sub.'</span>
名稱: <span class="name">'.$name.'</span> ['.$now.'] '.$QUOTEBTN.'No.'.$no.'</a>&nbsp;'.$REPLYBTN;
				$dat .= "\n<div class=\"quote\">$com</div>\n";
				$dat .= $WARN_OLD.$WARN_BEKILL.$WARN_ENDREPLY;
			}

			// 準備回應模式及回應分頁
			if(!$resno){
				$s = $treeline_count - RE_DEF;
				if($s < 1) $s = 1;
				elseif($s > 1){ $WARN_HIDEPOST = '<span class="warn_txt2">有回應 '.($s - 1).' 篇被省略。要閱讀所有回應請按下回應連結。</span><br />'."\n"; if(!USE_TEMPLATE) $dat .= $WARN_HIDEPOST; }
				$RES_start = $s; // 回應分頁開始指標
				$RES_end = $treeline_count; // 回應分頁結束指標
			}else{ // 回應模式
				$RES_start = 1;
				$RES_end = $treeline_count;
				if(RE_PAGE_DEF){ // RE_PAGE_DEF有設定 (開啟分頁)
					$countresALL = $treeline_count - 1; // 總回應數
					if($countresALL > 0){ // 有回應才做分頁動作
						if($page_num==='RE_PAGE_MAX'){ // 特殊值：最末頁
							$page_num = intval($countresALL / RE_PAGE_DEF); // 最末頁資料指標位置
							if(!($countresALL % RE_PAGE_DEF)) $page_num--; // 如果回應數和一頁顯示取餘數=0，則-1
						}
						if($page_num < 0) $page_num = 0; // 負數
						if($page_num * RE_PAGE_DEF >= $countresALL) error('對不起，您所要求的頁數並不存在'); // 超過最大筆數，顯示錯誤
						$RES_end = ($page_num + 1) * RE_PAGE_DEF + 1; if($RES_end > $treeline_count) $RES_end = $treeline_count; // 分頁結束指標超過範圍
						$RES_start = $page_num * RE_PAGE_DEF + 1;
					}elseif($page_num > 0) error('對不起，您所要求的頁數並不存在'); // 沒有回應的情況只允許page_num = 0 或負數
				}
			}
			if(USE_TEMPLATE) $dat .= $PTE->ReplaceStrings_Main(array('{$NO}'=>$no, '{$SUB}'=>$sub, '{$NAME}'=>$name, '{$NOW}'=>$now, '{$COM}'=>$com, '{$REPLYBTN}'=>$REPLYBTN, '{$IMG_BAR}'=>$IMG_BAR, '{$IMG_SRC}'=>$imgsrc, '{$WARN_OLD}'=>$WARN_OLD, '{$WARN_BEKILL}'=>$WARN_BEKILL, '{$WARN_ENDREPLY}'=>$WARN_ENDREPLY, '{$WARN_HIDEPOST}'=>$WARN_HIDEPOST));
			else $dat .= "</div>\n"; // 討論串首篇收尾用

			// 生成回應
			for($k = $RES_start; $k < $RES_end; $k++){
				$imgsrc = $img_thumb = $imgwh_bar = '';
				$IMG_BAR = $QUOTEBTN = $WARN_BEKILL = '';

//				list($no,$now,$name,$email,$sub,$com,$url,$host,,$ext,$w,$h,$time,) = fetchPosts($treeline[$k]);
				extract(fetchPosts($treeline[$k]));

				// 設定一些欄位
				if(CLEAR_SAGE) $email = preg_replace('/^sage( *)/i', '', trim($email)); // 清除E-mail中的「sage」關鍵字
				if($email) $name = "<a href=\"mailto:$email\">$name</a>";
				if(AUTO_LINK) $com = auto_link($com); // 內文自動作成連結
				$com = quoteLight($com);
				if(USE_QUOTESYSTEM){ // 啟用引用瀏覽系統
					if(preg_match_all('/((?:&gt;)+|＞)(?:No\.)?(\d+)/i', $com, $matches, PREG_SET_ORDER)){ // 找尋>>No.xxx
						foreach($matches as $val){
							if($r_page=array_search($val[2], $treeline)){ // $r_page !==0 (首篇) 就算找到
								// 在顯示區間內，輸出錨點即可
								if($r_page >= $RES_start && $r_page <= $RES_end) $com = str_replace($val[0], '<a class="qlink" href="#r'.$val[2].'" onclick="replyhl('.$val[2].');">'.$val[0].'</a>', $com);
								// 非顯示區間，輸出頁面導引及錨點
								else $com = str_replace($val[0], '<a class="qlink" href="'.PHP_SELF.'?res='.$treeline[0].(RE_PAGE_DEF ? '&amp;page_num='.floor(($r_page - 1) / RE_PAGE_DEF) : '').'#r'.$val[2].'">'.$val[0].'</a>', $com);
							}
						}
					}
				}
				$name = preg_replace('/(◆.{10})/', '<span class="nor">$1</span>', $name); // Trip取消粗體

				// 附加檔案名稱
				$src = IMG_DIR.$time.$ext;
				$img = $path.$src;
				if($ext && file_func('exist',$img)){
					$size = file_func('size',$img);
					$size = ($size>=1024) ? (int)($size/1024)." K" : $size." "; // KB和B的判別
					if($w && $h){ // 有長寬屬性
						if(file_func('exist',$path.THUMB_DIR.$time.'s.jpg')){
							$img_thumb = '<small>[以預覽圖顯示]</small>';
							$imgsrc = '<a href="'.$src.'" rel="_blank"><img src="'.THUMB_DIR.$time.'s.jpg" style="width: '.$w.'px; height: '.$h.'px;" class="img" alt="'.$size.'B" title="'.$size.'B" /></a>';
						}elseif($ext=='.swf'){ // swf檔案僅留連結就好
						}else{
							$imgsrc = '<a href="'.$src.'" rel="_blank"><img src="nothumb.gif" class="img" alt="'.$size.'B" title="'.$size.'B" /></a>';
						}
					}else{ // 沒有長寬屬性
						$imgsrc = '<a href="'.$src.'" rel="_blank"><img src="nothumb.gif" class="img" alt="'.$size.'B" title="'.$size.'B" /></a>';
					}
					if(SHOW_IMGWH){ // 顯示附加檔案之原檔長寬尺寸
						$imgwh_bar = file_func('imgsize',$img);
					}
					$IMG_BAR = '檔名：<a href="'.$src.'" rel="_blank">'.$time.$ext.'</a>-('.$size.'B'.$imgwh_bar.') '.$img_thumb;
					if(!USE_TEMPLATE){
						$IMG_BAR = '<br />&nbsp;'.$IMG_BAR;
						$imgsrc = '<br />'.$imgsrc;
					}
				}
				// 引用連結
				$QUOTEBTN = $resno ? '<a href="javascript:quote('.$no.');" class="qlink">' : '<a href="'.PHP_SELF.'?res='.$treeline[0].'#q'.$no.'" class="qlink">';
				// 預測刪除過大檔
				if(STORAGE_LIMIT && $kill_sensor) if(isset($arr_kill[$no])) $WARN_BEKILL = '<span class="warn_txt">這篇因附加檔案容量限制，附加檔案不久後就會刪除。</span><br />'."\n";
				// 討論串回應生成
				if(USE_TEMPLATE) $dat .= $PTE->ReplaceStrings_Reply(array('{$NO}'=>$no, '{$SUB}'=>$sub, '{$NAME}'=>$name, '{$NOW}'=>$now, '{$COM}'=>$com, '{$IMG_BAR}'=>$IMG_BAR, '{$IMG_SRC}'=>$imgsrc, '{$WARN_BEKILL}'=>$WARN_BEKILL));
				else{
					$dat .= '<div class="reply" id="r'.$no.'">
<input type="checkbox" name="'.$no.'" value="delete" /><span class="title">'.$sub.'</span> 名稱: <span class="name">'.$name.'</span> ['.$now.'] '.$QUOTEBTN.'No.'.$no.'</a>&nbsp;'.$IMG_BAR.$imgsrc.'
<div class="quote">'.$com.'</div>
'.$WARN_BEKILL."</div>\n";
				}
			}
			$dat .= USE_TEMPLATE ? $PTE->ReplaceStrings_Separate() : "<hr />\n\n";
			clearstatcache(); // 刪除STAT暫存檔
			if($resno) break; // 為回應模式時僅輸出單一討論串
		}
		$dat .= '</div>

<div id="del">
<table style="float: right;">
<tr><td align="center" style="white-space: nowrap;">
<input type="hidden" name="mode" value="usrdel" />
【刪除文章】[<input type="checkbox" name="onlyimgdel" id="onlyimgdel" value="on" /><label for="onlyimgdel">僅刪除附加檔案</label>]<br />
刪除用密碼: <input type="password" name="pwd" size="8" maxlength="8" value="" />
<input type="submit" value=" 刪除 " />
<script type="text/javascript">l();</script>
</td></tr>
</table>
</div>
</form>

<div id="page_switch">
';
		$prev = ($resno) ? (($page_num-1) * RE_PAGE_DEF) : ($st - PAGE_DEF);
		$next = ($resno) ? (($page_num+1) * RE_PAGE_DEF) : ($st + PAGE_DEF);
		// 換頁判斷
		if($resno){ // 回應分頁
			if(RE_PAGE_DEF > 0){ // 回應分頁開啟
				$dat .= '<table border="1"><tr>';
				if($prev >= 0) $dat .= '<td><form action="'.PHP_SELF.'?res='.$resno.'&amp;page_num='.$prev/RE_PAGE_DEF.'" method="post"><div><input type="submit" value="上一頁" /></div></form></td>';
				else $dat .= '<td style="white-space: nowrap;">第一頁</td>';
				$dat .= "<td>";
				if($countresALL==0) $dat .= '[<b>0</b>] '; // 無回應
				else{
					for($i = 0; $i < $countresALL ; $i += RE_PAGE_DEF){
						if($page_num==$i/RE_PAGE_DEF) $dat .= '[<b>'.$i/RE_PAGE_DEF.'</b>] ';
						else $dat .= '[<a href="'.PHP_SELF.'?res='.$resno.'&amp;page_num='.$i/RE_PAGE_DEF.'">'.$i/RE_PAGE_DEF.'</a>] ';
					}
				}
				$dat .= '</td>';
				if($countresALL > $next) $dat .= '<td><form action="'.PHP_SELF.'?res='.$resno.'&amp;page_num='.$next/RE_PAGE_DEF.'" method="post"><div><input type="submit" value="下一頁" /></div></form></td>';
				else $dat .= '<td style="white-space: nowrap;">最後一頁</td>';
				$dat .= '</tr></table>'."\n";
			}
		}else{ // 一般分頁
			$dat .= '<table border="1"><tr>';
			if($prev >= 0){
				if($prev==0) $dat .= '<td><form action="'.PHP_SELF2.'" method="get">';
				else{
					if((STATIC_HTML_UNTIL != -1) && (($prev/PAGE_DEF) > STATIC_HTML_UNTIL)) $dat .= '<td><form action="'.PHP_SELF.'?page_num='.$prev/PAGE_DEF.'" method="post">';
					else $dat .= '<td><form action="'.$prev/PAGE_DEF.PHP_EXT.'" method="get">';
				}
				$dat .= '<div><input type="submit" value="上一頁" /></div></form></td>';
			}else $dat .= '<td style="white-space: nowrap;">第一頁</td>';
			$dat .= '<td>';
			for($i = 0; $i < $counttree ; $i += PAGE_DEF){
				if($st==$i) $dat .= "[<b>".$i/PAGE_DEF."</b>] ";
				else{
					if($i==0) $dat .= '[<a href="'.PHP_SELF2.'?">0</a>] ';
					elseif((STATIC_HTML_UNTIL != -1) && (($i/PAGE_DEF) > STATIC_HTML_UNTIL)) $dat .= '[<a href="'.PHP_SELF.'?page_num='.$i/PAGE_DEF.'">'.$i/PAGE_DEF.'</a>] ';
					else $dat .= '[<a href="'.$i/PAGE_DEF.PHP_EXT.'?">'.$i/PAGE_DEF.'</a>] ';
				}
			}
			$dat .= '</td>';
			if($counttree > $next){
				if((STATIC_HTML_UNTIL != -1) && (($next/PAGE_DEF) > STATIC_HTML_UNTIL)) $dat .= '<td><form action="'.PHP_SELF.'?page_num='.$next/PAGE_DEF.'" method="post">';
				else $dat .= '<td><form action="'.$next/PAGE_DEF.PHP_EXT.'" method="get">';
				$dat .= '<div><input type="submit" value="下一頁" /></div></form></td>';
			}else $dat .= '<td style="white-space: nowrap;">最後一頁</td>';
			$dat .= '</tr></table>'."\n";
		}
		$dat .= '<br style="clear: left;" />
</div>

</div>

';

		foot($dat);
		if(!$page_num){ // 非使用php輸出方式，而是靜態生成
			if($resno){ echo $dat; break; }
			if($page==0) $logfilename = PHP_SELF2;
			else $logfilename = $page/PAGE_DEF.PHP_EXT;
			$fp = fopen($logfilename, 'w');
			stream_set_write_buffer($fp, 0);
			fwrite($fp, $dat);
			fclose($fp);
			@chmod($logfilename, 0666);
		}else{ // php輸出
			print $dat;
			break; // 只執行一次迴圈，即印出一頁內容
		}
		if((STATIC_HTML_UNTIL != -1) && STATIC_HTML_UNTIL==($page/PAGE_DEF)) break; // 生成靜態頁面數目限制
	}
}

/* 寫入記錄檔 */
function regist($name,$email,$sub,$com,$pwd,$upfile,$upfile_path,$upfile_name,$upfile_status,$resto){
	global $path, $BAD_STRING, $BAD_FILEMD5, $BAD_IPADDR;
	$dest = ''; $mes = ''; $up_incomplete = 0; $is_admin = false;
	$pwdc = isset($_COOKIE['pwdc']) ? $_COOKIE['pwdc'] : '';

	// 封鎖及阻擋措施
	if($_SERVER['REQUEST_METHOD'] != 'POST') error('請使用此版提供的表單來上傳'); // 非正規POST方式
	$host = gethostbyaddr($_SERVER["REMOTE_ADDR"]); // 取得主機位置名稱
	// 封鎖設定：限制之主機位置名稱
	if(array_search($host, $BAD_IPADDR)!==FALSE) error('您所使用的連線已被拒絕');
	DNSBLQuery(); // DNSBL封鎖列表查詢
	// 是否以Proxy來要求 (內建名單僅適用於日本地區)
	if(PROXY_CHECK){
		if(eregi("^mail",$host) || eregi("^ns",$host) || eregi("^dns",$host) || eregi("^ftp",$host) || eregi("^prox",$host) || eregi("^pc",$host) || eregi("^[^\.]\.[^\.]$",$host)) $pxck = 1;
		if(eregi("ne\\.jp$",$host) || eregi("ad\\.jp$",$host) || eregi("bbtec\\.net$",$host) || eregi("aol\\.com$",$host) || eregi("uu\\.net$",$host) || eregi("asahi-net\\.or\\.jp$",$host) || eregi("rim\\.or\\.jp$",$host)) $pxck = 0;
		else $pxck = 1;
		if($pxck && (proxy_connect('80') || proxy_connect('8080'))) error('本版關閉使用公開Proxy寫入');
	}
	// 封鎖設定：限制出現之文字
	foreach($BAD_STRING as $value){
		if(strpos($com, $value)!==false || strpos($sub, $value)!==false || strpos($name, $value)!==false || strpos($email, $value)!==false){
			error('發出的文章中有被管理員列為限制的字句，送出失敗', $dest);
		}
	}

	// 時間
	$time = time();
	$tim = $time.substr(microtime(),2,3);

	// 判斷上傳狀態
	switch($upfile_status){
		case 1:
			error('上傳失敗<br />上傳的附加檔案容量超過PHP內定值');
			break;
		case 2:
			error('上傳失敗<br />上傳的附加檔案容量超過上傳容量限制');
			break;
		case 3:
			error('上傳失敗<br />上傳的附加檔案不完整，請回版面再重試');
			break;
		case 6:
			error('上傳失敗<br />上傳的暫存資料夾設定錯誤，請通報系統管理員');
			break;
		case 0: // 上傳正常
		case 4: // 無上傳
		default:
	}

	// 如果有上傳檔案則處理附加檔案
	if($upfile && is_file($upfile)){
		// 一‧先儲存檔案
		$dest = $path.$tim.'.tmp';
		@move_uploaded_file($upfile, $dest);
		@chmod($dest, 0666);
		if(!is_file($dest)) error('上傳失敗<br />伺服器有可能禁止上傳、沒有權限，或不支援此格式', $dest);

		// 二‧判斷上傳附加檔案途中是否有中斷
		$upsizeTTL = $_SERVER['CONTENT_LENGTH'];
		$upsizeHDR = 0;
		// 檔案路徑：IE附完整路徑，故得從隱藏表單取得
		$tmp_upfile_path = $_FILES['upfile']['name'];
		if($upfile_path) $tmp_upfile_path = get_magic_quotes_gpc() ? stripslashes($upfile_path) : $upfile_path;
		list(,$boundary) = explode('=', $_SERVER['CONTENT_TYPE']);
		foreach($_POST as $header => $value){ // 表單欄位傳送資料
			$upsizeHDR += strlen('--'.$boundary."\r\n");
			$upsizeHDR += strlen('Content-Disposition: form-data; name="$header"'."\r\n\r\n".(get_magic_quotes_gpc()?stripslashes($value):$value)."\r\n");
		}
		// 附加檔案欄位傳送資料
		$upsizeHDR += strlen('--'.$boundary."\r\n");
		$upsizeHDR += strlen('Content-Disposition: form-data; name="upfile"; filename="'.$tmp_upfile_path."\"\r\n".'Content-Type: '.$_FILES['upfile']['type']."\r\n\r\n");
		$upsizeHDR += strlen("\r\n--".$boundary."--\r\n");
		$upsizeHDR += $_FILES['upfile']['size']; // 傳送附加檔案資料量
		// 上傳位元組差值超過 HTTP_UPLOAD_DIFF：上傳附加檔案不完全
		if(($upsizeTTL - $upsizeHDR) > HTTP_UPLOAD_DIFF){
			if(KILL_INCOMPLETE_UPLOAD){
				unlink($dest);
				die('[Notice] Your sending was canceled because of the incorrect file size.'); // 給瀏覽器的提示，假如使用者還看的到的話才不會納悶
			}else{
				$up_incomplete = 1;
			}
		}

		// 三‧檢查是否為可接受的檔案
		$size = @getimagesize($dest);
		if(!is_array($size)) error('上傳失敗<br />不接受圖片以外的檔案', $dest); // $size不為陣列就不是圖檔
		switch($size[2]){ // 判斷上傳附加檔案之格式
			case 1 : $ext = ".gif"; break;
			case 2 : $ext = ".jpg"; break;
			case 3 : $ext = ".png"; break;
			case 4 : $ext = ".swf"; break;
			case 5 : $ext = ".psd"; break;
			case 6 : $ext = ".bmp"; break;
			case 13 : $ext = ".swf"; break;
			default : $ext = ".xxx"; error('附加檔案為系統不支援的格式', $dest);
		}
		$allow_exts = explode('|', strtolower(ALLOW_UPLOAD_EXT)); // 接受之附加檔案副檔名
		if(array_search(substr($ext, 1), $allow_exts)===false) error('附加檔案為系統不支援的格式', $dest); // 並無在接受副檔名之列
		// 封鎖設定：限制上傳附加檔案之MD5檢查碼
		$chk = md5_file($dest); // 檔案MD5
		if(array_search($chk, $BAD_FILEMD5)!==FALSE) error('上傳失敗<br />此附加檔案被管理員列為禁止上傳', $dest); // 在封鎖設定內則阻擋

		// 四‧計算附加檔案圖檔縮圖顯示尺寸
		$W = $imgW = $size[0];
		$H = $imgH = $size[1];
		$MAXW = $resto ? MAX_RW : MAX_W;
		$MAXH = $resto ? MAX_RH : MAX_H;
		if($W > $MAXW || $H > $MAXH){
			$W2 = $MAXW / $W;
			$H2 = $MAXH / $H;
			$key = ($W2 < $H2) ? $W2 : $H2;
			$W = ceil($W * $key);
			$H = ceil($H * $key);
		}
		$mes = '附加檔案'.CleanStr($upfile_name).'上傳完畢<br />';
	}

	// 檢查表單欄位內容並修整
	if(!$name || ereg("^[ |　|]*$", $name)){
		if(ALLOW_NONAME) $name = '無名氏';
		else error('您沒有填寫名稱', $dest);
	}
	if(!$com && $upfile_status==4) error('在沒有附加檔案的情況下，請寫入內文');
	if(!$com || ereg("^[ |　|\t]*$", $com)) $com = '無內文';
	if(!$sub || ereg("^[ |　|]*$", $sub)) $sub = '無標題';
	if(strlen($name) > 100) error('名稱過長', $dest);
	if(strlen($email) > 100) error('E-mail過長', $dest);
	if(strlen($sub) > 100) error('標題過長', $dest);
	if(strlen($resto) > 10) error('欲回應的文章編號可能有誤', $dest);

	$email = CleanStr($email); $email = str_replace("\r\n", '', $email);
	$sub = CleanStr($sub); $sub = str_replace("\r\n", '', $sub);
	$resto = CleanStr($resto); $resto = str_replace("\r\n", '', $resto);
	// 名稱修整
	$name = CleanStr($name);
	$name = str_replace('管理','"管理"', $name);
	$name = str_replace('刪除','"刪除"', $name);
	$name = str_replace('◆','◇', $name); // 防止トリップ偽造
	$name = str_replace('★','☆', $name); // 防止管理員キャップ偽造
	$name = str_replace("\r\n", '', $name);
	$is_tripped = false; // 名稱一欄是否經過Trip
	if(ereg("(#|＃)(.*)", $name, $regs)){ // 使用トリップ(Trip)機能 (ex：無名#abcd)
		$cap = $regs[2];
		$cap = strtr($cap, array("&amp;"=>"&","&#44;"=>","));
		$name = ereg_replace("(#|＃)(.*)",'', $name);
		$salt = substr($cap.'H.',1,2);
		$salt = ereg_replace("[^\.-z]",'.',$salt);
		$salt = strtr($salt,":;<=>?@[\\]^_`","ABCDEFGabcdef");
		$name = $name.'◆'.substr(crypt($cap,$salt),-10);
		$is_tripped = true; // 有Trip過。如果進入下面的Cap則要先去掉Trip留下主名稱
	}
	if(ereg("(.*)(#|＃)(.*)",$email,$aregs) && CAP_ENABLE){ // 使用管理員キャップ(Cap)機能
		$acap_name = $is_tripped ? preg_replace('/◆.{10}/', '', $name) : $name; // 識別名稱 (如果有Trip則要先拿掉)
		$acap_pwd = $aregs[3];
		$acap_pwd = strtr($acap_pwd, array("&amp;"=>"&","&#44;"=>","));
		if($acap_name==CAP_NAME && $acap_pwd==CAP_PASS){
			$name = '<span class="admin_cap">'.$name.CAP_SUFFIX.'</span>';
			$is_admin = true; // 判定為管理員
			if(stristr($aregs[1], 'sage')) $email = $aregs[1]; // 保留sage機能
			else $email = ""; // 清空E-mail一欄
		}
	}
	// 內文修整
	if((strlen($com) > COMM_MAX) && !$is_admin) error('內文過長', $dest);
	$com = CleanStr($com, $is_admin); // 引入$is_admin參數是因為當管理員キャップ啟動時，允許管理員依config設定是否使用HTML
	$com = str_replace("\r\n","\n", $com);
	$com = str_replace("\r","\n", $com);
	$com = ereg_replace("\n((　| )*\n){3,}","\n", $com);
	if(!BR_CHECK || substr_count($com,"\n") < BR_CHECK){
		$com = nl2br($com);	// 換行字元用<br />代替
	}
	$com = str_replace("\n",'', $com); // 若還有\n換行字元則取消換行
	if($up_incomplete) $com .= '<br /><br /><span class="warn_txt">注意：附加檔案上傳不完全</span>'; // 上傳附加檔案不完全的提示

	// 時間和密碼的樣式
	if($pwd=="") $pwd = ($pwdc=="") ? substr(rand(),0,8) : $pwdc;
	$pass = $pwd ? substr(md5($pwd), 2, 8) : '*';
	$youbi = array('日','一','二','三','四','五','六');
	$yd = $youbi[gmdate("w", $time+TIME_ZONE*60*60)];
	$now = gmdate("y/m/d", $time+TIME_ZONE*60*60).'('.(string)$yd.')'.gmdate("H:i", $time+TIME_ZONE*60*60);
	if(DISP_ID){ // 顯示ID
		if($email && DISP_ID==1) $now .= " ID:???";
		else $now .= " ID:".substr(crypt(md5($_SERVER["REMOTE_ADDR"].IDSEED.gmdate("Ymd", $time+TIME_ZONE*60*60)),'id'),-8);
	}

	$countline=postCount();
	$porder=fetchPostList();

	// 連續投稿 / 相同附加檔案判斷
	$imax = $countline > 50 ? 50 : $countline;
	$pwdc = substr(md5($pwdc), 2, 8); // Cookies密碼
  	for($i = 0; $i < $imax; $i++){
  		$post=fetchPosts($porder[$i]);
		list($lastno,$lname,$lcom,$lhost,$lpwd,$lext,$ltime,$lchk) = array($post['no'],$post['name'],$post['com'],$post['host'],$post['pw'],$post['ext'],$post['time'],$post['chk']);
		$ltime2 = substr($ltime , 0, -3);
		if($host==$lhost || $pass==$lpwd || $pwdc==$lpwd) $pchk = 1;
		else $pchk = 0;
		if(RENZOKU && $pchk){ // 密碼比對符合且開啟連續投稿時間限制
			if($time - $ltime2 < RENZOKU) error('連續投稿請稍候一段時間', $dest); // 投稿時間相距太短
			if($time - $ltime2 < RENZOKU2 && $upfile_name) error('連續附加檔案投稿請稍候一段時間', $dest); // 附加檔案的投稿時間相距太短
			if($com == $lcom && !$upfile_name) error('連續投稿請稍候一段時間', $dest); // 內文一樣
		}
		if($dest && $lchk==$chk && file_func('exist',$path.IMG_DIR.$ltime.$lext)) error('上傳失敗<br />近期已經有相同的附加檔案', $dest); // 相同的附加檔案
	}

	$ThreadExistsBefore=is_Thread($resto);
	// 記錄檔行數已達上限：刪除過舊檔
	if($countline >= LOG_MAX){
		$files=delOldPostes();
		if(count($files)) file_func('del',$files);
	}

	// 判斷欲回應的文章是不是剛剛被刪掉了
	if($resto){
		if($ThreadExistsBefore){ // 欲回應的討論串是否存在 (看逆轉換成功與否)
			if(!is_Thread($resto)){ // 被回應的討論串存在但已被刪
				// 提前更新投稿文字記錄檔，此筆新增亦不紀錄
				dbCommit();
				updatelog();
				error('此討論串因為過舊已被刪除！', $dest);
			}else{ // 檢查是否討論串被設為禁止回應 (順便取出原討論串的貼文時間)
				$post=fetchPosts($resto);
				list($chkurl,$chktime) = array($post['url'],$post['time']);
				$chktime = substr($chktime, 0, -3); // 拿掉微秒 (後面三個字元)
				if(stristr($chkurl, '_THREADSTOP_')) error('這篇討論串已被管理員標記為禁止回應！', $dest);
			}
		}else error('無此討論串！', $dest); // 不存在
	}

	// 寫入Log檔案
	$firstPost=fetchPostList();
	$firstPost=fetchPosts($firstPost[0]);
	$lastno=$firstPost['no']; $no = $lastno + 1;
	isset($ext) ? 0 : $ext = '';
	isset($W) ? 0 : $W = '';
	isset($H) ? 0 : $H = '';
	isset($chk) ? 0 : $chk = '';

	if($resto){
		$res_count=postCount($resto)-1;
		if(!stristr($email,'sage') && ($res_count < MAX_RES || MAX_RES == 0)){
			if(!MAX_AGE_TIME || (($time - $chktime) < (MAX_AGE_TIME * 60 * 60))){ // 討論串並無過期，推文
				$age=true;
			} else $age=false;
		} else $age=true;
	}else{
		$age=false;
	}

	// 附加檔案容量限制功能啟動：刪除過大檔
	if(STORAGE_LIMIT){
		$tmp_total_size = total_size(); // 取得目前附加檔案使用量
		if($tmp_total_size >= STORAGE_MAX){
			$files=delOldAttachments($tmp_total_size,STORAGE_MAX,false);
			file_func('del',$files);
		}
	}

	addPost($no,$resto,$now,$name,$email,$sub,$com,'',$host,$pass,$ext,$W,$H,$tim,$chk,$age); // 將新文章送到陣列第一個位置
	dbCommit();

	// Cookies儲存：密碼與E-mail部分，期限是一週
	setcookie('pwdc', $pwd, time()+7*24*3600);
	setcookie('emailc', $email, time()+7*24*3600);

	if($dest && is_file($dest)){
		rename($dest, $path.IMG_DIR.$tim.$ext);
		if(USE_THUMB) thumb($path.IMG_DIR, $tim, $ext, $imgW, $imgH, $W, $H); // 使用GD製作縮圖
	}

	// 刪除舊容量快取
	total_size(true);
	updatelog();

	// 引導使用者至新頁面
	$RedirURL = PHP_SELF2.'?'.$tim; // 定義儲存資料後轉址目標
	if(isset($_POST['up_series'])){ // 勾選連貼機能
		if($resto) $RedirURL = PHP_SELF.'?res='.$resto.'&amp;upseries=1'; // 回應後繼續轉回此主題下
		else $RedirURL = PHP_SELF.'?res='.$no.'&amp;upseries=1'; // 新增主題後繼續轉到此主題下
	}
	$RedirforJS = strtr($RedirURL, array("&amp;"=>"&")); // JavaScript用轉址目標

	echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
	echo <<< _REDIR_
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="zh-tw">
<head>
<title></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<script type="text/javascript">
// Redirection (use JS)
// <![CDATA[
function redir(){
	location.href = "$RedirforJS";
}
setTimeout("redir()",1000);
// ]]>
</script>
</head>
<body>
<div>
$mes 畫面正在切換
<p>如果瀏覽器沒有自動切換，請手動按連結前往：<a href="$RedirURL">回到版面</a></p>
</div>
</body>
</html>
_REDIR_;
}

/* 使用者刪除 */
function usrdel($no,$pwd){
	global $path, $onlyimgdel;
	// $pwd: 使用者輸入值, $pwdc: Cookie記錄密碼
	$pwdc = isset($_COOKIE['pwdc']) ? $_COOKIE['pwdc'] : '';
	if($pwd=='' && $pwdc!='') $pwd = $pwdc;
	$pwd_md5 = substr(md5($pwd),2,8);
	$host = gethostbyaddr($_SERVER['REMOTE_ADDR']);
	$search_flag = $delflag = false;
	$delno = array('dummy');
	reset($_POST);
	while($item = each($_POST)) if($item[1]=='delete') array_push($delno, $item[0]);
	$delno_count = count($delno) - 1; // 刪除筆數
	if($delno_count==0) error('你真的有要刪除嗎？請回頁面重勾選');

	$delposts=array();
	foreach($delno as $dno){
		$post=fetchPosts($dno);
		if(($pwd_md5 == $post['pw'] || $post['host'] == $host || ADMIN_PASS == $pwd)){
			$search_flag = true; // 有搜尋到
			array_push($delposts,$dno);
		}
	}
	if($search_flag){
		$files=(!$onlyimgdel)?removePosts($delposts):removeAttachments($delposts);
		file_func('del',$files);
		total_size(true); // 刪除容量快取
		dbCommit();
	}else error('無此文章或是密碼錯誤');
}

/* 管理員密碼認證 */
function valid($pass){
	if($pass && $pass != ADMIN_PASS) error('密碼錯誤');
	head($dat);
	$dat .= '<div id="banner">
[<a href="'.PHP_SELF2.'?'.time().substr(microtime(),2,3).'">回到版面</a>][<a href="'.PHP_SELF.'?mode=remake">更新文章</a>]
<div class="bar_admin">管理模式</div>
</div>
<form action="'.PHP_SELF.'" method="post">
<div id="admin-check" style="text-align: center;">
';
	echo $dat;
	// 登錄用表單
	if(!$pass){
		echo <<< __VALID_EOF__
<br />
<input type="radio" name="admin" value="del" checked="checked" />管理文章<p />
<input type="hidden" name="mode" value="admin" />
<input type="password" name="pass" size="8" />
<input type="submit" value=" 認證 " />
</div>
</form>
__VALID_EOF__;
		die("\n</body>\n</html>");
	}
}

/* 管理文章模式 */
function admindel($pass){
	global $path, $onlyimgdel;
	$page = isset($_POST['page']) ? $_POST['page'] : 1;
	$delno = $thsno = array('dummy');
	$delflag = isset($_POST['delete']); // 是否有「刪除」勾選
	$thsflag = isset($_POST['stop']); // 是否有「停止」勾選
	$is_modified = false; // 是否改寫檔案

	// 刪除文章區塊
	if($delflag){
		$delno = array_merge($delno, $_POST['delete']);
		$files=($onlyimgdel!='on')?removePosts($delno):removeAttachments($delno);
		file_func('del',$files);
		total_size(true); // 刪除容量快取
		$is_modified = TRUE;
	}
	// 討論串停止區塊
	if($thsflag){
		$thsno = array_merge($thsno, $_POST['stop']);
		stopThread($thsno);
		$is_modified = true;
	}
	if(($delflag || $thsflag) && $is_modified){ // 無論如何都有檔案操作，回寫檔案
		dbCommit();
	}
	
	// 取出討論串首篇之No. (是否顯示停止勾選欄)
	$tno = array_flip(fetchThreadList());
	$porder=fetchPostList();
	$countline=postCount();

	// 印出刪除表格
	echo <<< _N_EOT_
<script type="text/javascript">
// <![CDATA[
function ChangePage(page){
	document.forms[0].page.value = page;
	document.forms[0].submit();
}
// ]]>
</script>
<input type="hidden" name="mode" value="admin" />
<input type="hidden" name="admin" value="del" />
<input type="hidden" name="pass" value="$pass" />
<input type="hidden" name="page" value="$page" />
<div style="text-align: left;"><ul><li>想刪除文章，請勾選該文章前之「刪除」核取框之後按下執行按鈕</li><li>只想刪除文章的附加檔案，請先勾選「僅刪除附加檔案」再按照一般刪文方式</li><li>想停止／繼續討論串，請勾選該文章前之「停止」核取框之後按下執行按鈕</li><li>勾選後換頁亦相當於執行，請慎用此功能</li><li>管理文章完畢，記得順手按下「更新文章」以更新靜態快取</li></ul></div>
<p><input type="submit" value=" 執行 " /> <input type="reset" value=" 重置 " /> [<input type="checkbox" name="onlyimgdel" id="onlyimgdel" value="on" /><label for="onlyimgdel">僅刪除附加檔案</label>]</p>
<table border="1" cellspacing="0" style="margin: 0px auto;">
<tr style="background-color: #6080f6;"><th>停止</th><th>刪除</th><th>投稿日</th><th>標題</th><th>名稱</th><th>內文</th><th>主機位置名稱</th><th>附加檔案 (Bytes)<br />MD5 檢查碼</th></tr>

_N_EOT_;
	$p = 0; // 欄位背景顏色變化指標
	for($j = (($page-1) * ADMIN_PAGE_DEF); $j < ($page * ADMIN_PAGE_DEF); $j++){
		$p++;
		$bg = ($p % 2) ? 'ListRow1_bg' : 'ListRow2_bg'; // 背景顏色
		extract(fetchPosts($porder[$j]));

		// 修改欄位樣式
		$now = preg_replace('/.{2}\/(.{5})\(.+?\)(.{5}).*/', '$1 $2', $now);
		$name = str_cut(strip_tags($name), 8);
		$sub = str_cut($sub, 8);
		if($email) $name = "<a href=\"mailto:$email\">$name</a>";
		$com = str_replace('<br />',' ',$com);
		$com = htmlspecialchars(str_cut(html_entity_decode($com), 20));

		// 討論串首篇停止勾選框
		if(isset($tno[$no])) $THstop = '<input type="checkbox" name="stop[]" value="'.$no.'" />'.((strpos($url, '_THREADSTOP_')!==false)?'停':'');
		else $THstop = '--';

		// 從記錄抽出附加檔案使用量並生成連結
		if($ext && file_func('exist',$path.IMG_DIR.$time.$ext)){
			$clip = '<a href="'.IMG_DIR.$time.$ext.'\" rel="_blank">'.$time.$ext.'</a>';
			$size = file_func('size',$path.IMG_DIR.$time.$ext);
			if(file_func('exist',$path.THUMB_DIR.$time.'s.jpg')) $size += file_func('size',$path.THUMB_DIR.$time.'s.jpg');
		}else{
			$clip = $chk = '--';
			$size = 0;
		}

		// 印出介面
		echo <<< _ADMINEOF_
<tr class="$bg" align="left">
<th align="center">$THstop</th><th><input type="checkbox" name="delete[]" value="$no" />$no</th><td><small>$now</small></td><td>$sub</td><td><b>$name</b></td><td><small>$com</small></td><td>$host</td><td align="center">$clip ($size)<br />$chk</td>
</tr>

_ADMINEOF_;
		if($j==$countline-1) break; // 已到達log檔陣列底端，跳出迴圈
	}
	echo '</table>
<p><input type="submit" value=" 執行 " /> <input type="reset" value=" 重置 " /></p>
<p>【 附加檔案使用容量總計 : <b>'.total_size().'</b> KB 】</p>
<hr />
';

	$page_max = ceil($countline / ADMIN_PAGE_DEF); // 總頁數
	echo '<table border="1" style="float: left;"><tr>';
	if($page > 1) echo '<td><input type="button" value="上一頁" onclick="ChangePage('.($page-1).');" /></td>';
	else echo '<td style="white-space: nowrap;">第一頁</td>';
	echo '<td>';
	for($i = 1; $i <= $page_max; $i++){
		if($i==$page) echo '[<b>'.$i.'</b>] ';
		else echo '[<a href="javascript:ChangePage('.$i.');">'.$i.'</a>] ';
	}
	echo '</td>';
	if($page < $page_max) echo '<td><input type="button" value="下一頁" onclick="ChangePage('.($page+1).');" /></td>';
	else echo '<td style="white-space: nowrap;">最後一頁</td>';
	die('</tr></table>
</div>
</form>
</body>
</html>');
}

/* 計算目前附加檔案使用容量 (單位：KB) */
function total_size($isupdate=false){
	global $path;

	$size = 0; $all = 0;
	$cache_file = "./sizecache.dat"; // 附加檔案使用容量值快取檔案

	if($isupdate){ // 刪除舊快取
		if(is_file($cache_file)) unlink($cache_file);
		return;
	}
	if(!is_file($cache_file)){ // 無快取，新增
		$line = fetchPostList();
		$linecount = postCount();
		for($k=0;$k<$linecount;$k++){
			extract(fetchPosts($line[$k]));
			// 從記錄檔抽出計算附加檔案使用量
			if($ext && file_func('exist',$path.IMG_DIR.$time.$ext)) $all += file_func('size',$path.IMG_DIR.$time.$ext); // 附加檔案合計計算
			if(file_func('exist',$path.THUMB_DIR.$time.'s.jpg')) $all += file_func('size',$path.THUMB_DIR.$time.'s.jpg'); // 預覽圖合計計算
		}
		$sp = fopen($cache_file, 'w');
		stream_set_write_buffer($sp, 0);
		fwrite($sp, $all); // 寫入目前使用容量值
		fclose($sp);
		@chmod($cache_file, 0666);
	}else{ // 使用快取
		$sp = file($cache_file);
		$all = $sp[0];
		unset($sp);
	}
	return (int)($all / 1024);
}

/* 搜尋(全文檢索)功能 */
function search(){
	if(!USE_SEARCH) error('管理員選擇不開放搜尋功能！');
	$dat = '';
	$tmp_keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : ''; // 欲搜尋的文字
	head($dat);
	$dat .= '<div id="banner">
[<a href="'.PHP_SELF2.'?'.time().substr(microtime(),2,3).'">回到版面</a>]
<div class="bar_admin">搜尋</div>
</div>
';
	echo $dat;
	if($tmp_keyword==''){
		echo '<form action="'.PHP_SELF.'?mode=search" method="post">
<div id="search">
';
		echo <<< END_OF_HTML
<ul>
<li>請輸入要搜尋的關鍵字，設定好搜尋目標之後，按下「搜尋」按鈕。</li>
<li>關鍵字使用半形空白可以區隔多個搜尋關鍵字作AND(交集)搜尋。<p />
關鍵字：<input type="text" name="keyword" size="30" />
搜尋目標：<select name="field"><option value="com" selected="selected">內文</option><option value="name">名稱</option><option value="sub">標題</option><option value="no">編號</option></select>
<input type="submit" value=" 搜尋 " />
</li>
</ul>
</div>
</form>

END_OF_HTML;
	}else{
		$tmp_searchfield = $_POST['field']; // 搜尋目標 (0:編號, 2:名稱, 4:標題, 5:內文)
		$tmp_keyword = preg_split('/(　| )+/', trim($tmp_keyword)); // 搜尋文字用空格切割

		$hits=searchPost($tmp_keyword,$tmp_searchfield,'AND');

		echo '<div id="search_result" style="text-align: center;">
<table border="0" style="margin: 0px auto; text-align: left; width: 100%;">
';
		$resultlist = '';
		foreach($hits as $h){
			extract($h);
			$resultlist .= <<< END_OF_TR
<tr><td>
<span class="title">$sub</span> 名稱: <span class="name">$name</span> [$now] No.{$no} <br />
<div class="quote">$com</div><hr />
</td></tr>

END_OF_TR;
		}
		echo $resultlist ? $resultlist : '<tr align="center"><td>找不到符合的關鍵字。</td></tr><tr align="center"><td><a href="?mode=search">[回上一頁]</a></td></tr>';
		echo "\n</table>\n</div>\n";
	}
	echo "</body>\n</html>";
}

/* 顯示系統各項資訊 */
function showstatus(){
	$countline = postCount(); // 計算投稿文字記錄檔目前資料筆數
	$counttree = threadCount(); // 計算樹狀結構記錄檔目前資料筆數
	$tmp_total_size = total_size(); // 附加檔案使用量總大小
	$tmp_log_ratio = $countline / LOG_MAX; // 記錄檔使用量
	$tmp_ts_ratio = $tmp_total_size / STORAGE_MAX; // 附加檔案使用量

	// 決定「記錄檔使用量」提示文字顏色
  	if($tmp_log_ratio < 0.3 ) $clrflag_log = '235CFF';
	elseif($tmp_log_ratio < 0.5 ) $clrflag_log = '0CCE0C';
	elseif($tmp_log_ratio < 0.7 ) $clrflag_log = 'F28612';
	elseif($tmp_log_ratio < 0.9 ) $clrflag_log = 'F200D3';
	else $clrflag_log = 'F2004A';

	// 決定「附加檔案使用量」提示文字顏色
  	if($tmp_ts_ratio < 0.3 ) $clrflag_sl = '235CFF';
	elseif($tmp_ts_ratio < 0.5 ) $clrflag_sl = '0CCE0C';
	elseif($tmp_ts_ratio < 0.7 ) $clrflag_sl = 'F28612';
	elseif($tmp_ts_ratio < 0.9 ) $clrflag_sl = 'F200D3';
	else $clrflag_sl = 'F2004A';

	// 判斷是否開啟GD模組、取出GD版本號及功能是否正常
	$func_gd = extension_loaded('gd') ? '<span style="color: blue;">已開啟</span>' : '<span style="color: red;">未開啟</span>';
	$thumb_IsAvailable = function_exists('ImageCreateTrueColor') ? '<span style="color: blue;">功能正常</span>' : '<span style="color: red">功能失常</span>';
	if($func_gdver = @gd_info()) $func_gdver = $func_gdver['GD Version'];

	$dat = '';
	head($dat);
	$dat .= '<div id="banner">
[<a href="'.PHP_SELF2.'?'.time().substr(microtime(),2,3).'">回到版面</a>]
<div class="bar_admin">系統資訊</div>
</div>
';

	$dat .= '
<div id="status-table" style="text-align: center;">
<table border="1" style="margin: 0px auto; text-align: left;">
<tr><td align="center" colspan="3">基本設定</td></tr>
<tr><td style="width: 240px;">程式版本</td><td colspan="2"> '.FUTABA_VER.' </td></tr>
<tr><td>後端</td><td colspan="2"> '.PIXMICAT_BACKEND.'</td></tr>
<tr><td>一頁顯示幾篇討論串</td><td colspan="2"> '.PAGE_DEF.' 篇</td></tr>
<tr><td>一篇討論串最多顯示之回應筆數</td><td colspan="2"> '.RE_DEF.' 筆</td></tr>
<tr><td>回應模式一頁顯示幾筆回應內容</td><td colspan="2"> '.RE_PAGE_DEF.' 筆 (全部顯示：0)</td></tr>
<tr><td>回應筆數超過多少則不自動推文</td><td colspan="2"> '.MAX_RES.' 筆 (關閉：0)</td></tr>
<tr><td>討論串可接受推文的時間範圍</td><td colspan="2"> '.MAX_AGE_TIME.' 小時 (關閉：0)</td></tr>
<tr><td>URL文字自動作成超連結</td><td colspan="2"> '.AUTO_LINK.' (是：1 否：0)</td></tr>
<tr><td>內文接受Bytes數</td><td colspan="2"> '.COMM_MAX.' Bytes (中文字為2Bytes)</td></tr>
<tr><td>接受匿名發送</td><td colspan="2"> '.ALLOW_NONAME.' (是：1 否：0)</td></tr>
<tr><td>自動刪除上傳不完整附加檔案</td><td colspan="2"> '.KILL_INCOMPLETE_UPLOAD.' (是：1 否：0)</td></tr>
<tr><td>使用預覽圖機能 (品質：'.THUMB_Q.')</td><td colspan="2"> '.USE_THUMB.' (使用：1 不使用：0)</td></tr>';
	if(USE_THUMB) $dat .= '<tr><td>└ 預覽圖生成功能</td><td colspan="2"> '.$thumb_IsAvailable.' </td></tr>'."\n";
	$dat .= '<tr><td>限制Proxy寫入</td><td colspan="2"> '.PROXY_CHECK.' (是：1 否：0)</td></tr>
<tr><td>顯示ID</td><td colspan="2"> '.DISP_ID.' (強制顯示：2 選擇性顯示：1 永遠不顯示：0)</td></tr>
<tr><td>文字換行行數上限</td><td colspan="2"> '.BR_CHECK.' 行 (不限：0)</td></tr>
<tr><td>時區設定</td><td colspan="2"> GMT '.TIME_ZONE.'</td></tr>
<tr><td>目前總討論串篇數</td><td colspan="2"> '.$counttree.' 篇</td></tr>
<tr><td align="center" colspan="3">記錄檔使用量</td></tr>
<tr align="center"><td>最大筆數</td><td>'.LOG_MAX.'</td><td rowspan="2">使用率<br /><span style="color: #'.$clrflag_log.';">'.substr(($tmp_log_ratio * 100), 0, 6).'</span> ％</td></tr>
<tr align="center"><td>目前筆數</td><td><span style="color: #'.$clrflag_log.';">'.$countline.'</span></td></tr>
<tr><td align="center" colspan="3">附加檔案容量限制功能：'.STORAGE_LIMIT.' (啟動：1 關閉：0)</td></tr>';

	if(STORAGE_LIMIT){
		$dat .= '
<tr align="center"><td>上限大小</td><td>'.STORAGE_MAX.' KB</td><td rowspan="2">使用率<br /><span style="color: #'.$clrflag_sl.'">'.substr(($tmp_ts_ratio * 100), 0, 6).'</span> ％</td></tr>
<tr align="center"><td>目前容量</td><td><span style="color: #'.$clrflag_sl.'">'.$tmp_total_size.' KB</span></td></tr>';
	}else{
		$dat .= '
<tr align="center"><td>目前容量</td><td>'.$tmp_total_size.' KB</td><td>使用率<br /><span style="color: green;">無上限</span></td></tr>';
	}

	$dat .= '
<tr><td align="center" colspan="3">伺服器支援情報</td></tr>
<tr align="center"><td colspan="2">GD函式庫 '.$func_gdver.'</td><td>'.$func_gd.'</td></tr>
</table>
<hr />
</div>'."\n";

	foot($dat);
	echo $dat;
}

/* 程式首次執行之初始化 */
function init(){
	$is_executed = false; // 是否有初始化動作
	if(!is_writable(realpath('./'))) error('根目錄沒有寫入權限，請修改權限<br />');

	$chkfolder = array(IMG_DIR, THUMB_DIR);
	// 逐一自動建置IMG_DIR和THUMB_DIR
	foreach($chkfolder as $value) if(!is_dir($value)){ mkdir($value); $is_executed = true; } // 沒有就建立

	dbInit(); // PIO Init

	if($is_executed) error('環境初始化成功！<br />請現在打開此程式刪除init()程式環境初始化區段<br />');
}

/*-----------程式各項功能主要判斷-------------*/
if(GZIP_COMPRESS_LEVEL){ ob_start(); ob_implicit_flush(0); } // 啟動Gzip壓縮緩衝
$path = realpath("./").'/'; // 此資料夾的絕對位置
$iniv = array('mode','name','email','sub','com','pwd','upfile','upfile_path','upfile_name','upfile_status','resto','pass','res','post','no');
foreach($iniv as $iniva){
	if(!isset($$iniva)) $$iniva = '';
}
//init(); // ←■■！程式環境初始化，跑過一次後請刪除此行！■■

dbPrepare();
switch($mode){
	case 'regist':
		regist($name,$email,$sub,$com,$pwd,$upfile,$upfile_path,$upfile_name,$upfile_status,$resto);
		break;
	case 'admin':
		valid($pass);
		if($admin=='del') admindel($pass);
		break;
	case 'search':
		search();
		break;
	case 'status':
		showstatus();
		break;
	case 'usrdel':
		usrdel($no,$pwd);
	case 'remake':
		updatelog();
		header('HTTP/1.1 302 Moved Temporarily');
		header('Location: '.PHP_SELF2.'?'.time().substr(microtime(),2,3));
		break;
	default:
		if($res){
			updatelog($res, (isset($_GET['page_num'])?intval($_GET['page_num']):'RE_PAGE_MAX')); // 當分頁值>0實行分頁 (若無值則預設最末頁)
		}elseif(@intval($_GET['page_num']) > 0){ // 取整數數值大於0
			updatelog(0, intval($_GET['page_num'])); // 以php顯示某頁內容印出
		}else{
			if(!is_file(PHP_SELF2)) updatelog();
			header('HTTP/1.1 302 Moved Temporarily');
			header('Location: '.PHP_SELF2.'?'.time().substr(microtime(),2,3));
		}
}
if(($Encoding = CheckSupportGZip()) && GZIP_COMPRESS_LEVEL){ // 啟動Gzip
	if(!ob_get_length()) exit; // 沒內容不必壓縮
	header('Content-Encoding: '.$Encoding);
	header('X-Content-Encoding-Level: '.GZIP_COMPRESS_LEVEL);
	header('Vary: Accept-Encoding');
	print gzencode(ob_get_clean(), GZIP_COMPRESS_LEVEL); // 壓縮內容
}else ob_end_flush(); // 沒壓縮，直接印出緩衝區內容
?>