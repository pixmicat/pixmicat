<?php
define("PIXMICAT_VER", 'Pixmicat!-PIO 4th.Release.2-dev (b070807)'); // 版本資訊文字
/*
Pixmicat! : 圖咪貓貼圖版程式
http://pixmicat.openfoundry.org/
版權所有 © 2005-2007 Pixmicat! Development Team

版權聲明：
此程式是基於レッツPHP!<http://php.s3.to/>的gazou.php、
双葉ちゃん<http://www.2chan.net>的futaba.php所改寫之衍生著作程式，屬於自由軟體，
以The Clarified Artistic License作為發佈授權條款。
您可以遵照The Clarified Artistic License來自由使用、散播、修改或製成衍生著作。
更詳細的條款及定義請參考隨附"LICENSE"條款副本。

發佈這一程式的目的是希望它有用，但沒有任何擔保，甚至沒有適合特定目的而隱含的擔保。
關於此程式相關的問題請不要詢問レッツPHP!及双葉ちゃん。

如果您沒有隨著程式收到一份The Clarified Artistic License副本，
請瀏覽http://pixmicat.openfoundry.org/license/以取得一份。

最低運行需求：
PHP 4.3.0 / 27 December 2002
GD Version 2.0.28 / 21 July 2004

建議運行環境：
PHP 5.2.0 或更高版本並開啟 GD 和 zlib 支援，如支援 ImageMagick 建議使用
安裝 PHP 編譯快取套件 (如eAccelerator, XCache, APC) 或其他快取套件 (如memcached) 更佳
如伺服器支援 SQLite, MySQL, PostgreSQL 等請盡量使用

設置方法：
根目錄的權限請設為777，
首先將pixmicat.php執行過一遍，必要的檔案和資料夾權限皆會自動設定，
自動設定完成後請刪除或註解起來此檔案底部之init(); // ←■■！程式環境初始化(略)一行，
然後再執行一遍pixmicat.php，即完成初始化程序，可以開始使用。

細部的設定請打開config.php參考註解修改，另有 Wiki (http://pixmicat.wikidot.com/pmcuse:config)
說明條目可資參考。
*/

include_once('./config.php'); // 引入設定檔
include_once('./lib/lib_language.php'); // 引入語系
include_once('./lib/lib_common.php'); // 引入共通函式檔案
include_once('./lib/lib_fileio.php'); // 引入FileIO
include_once('./lib/lib_pio.php'); // 引入PIO
include_once('./lib/lib_pms.php'); // 引入PMS
include_once('./lib/lib_pte.php'); // 引入PTE外部函式庫

$PTE = new PTELibrary(TEMPLATE_FILE); // PTE Library

/* 更新記錄檔檔案／輸出討論串 */
function updatelog($resno=0,$page_num=0){
	global $PIO, $FileIO, $PTE, $PMS, $language;

	$page_start = $page_end = 0; // 靜態頁面編號
	$inner_for_count = 1; // 內部迴圈執行次數
	$kill_sensor = $old_sensor = false; // 預測系統啟動旗標
	$arr_kill = $arr_old = array(); // 過舊編號陣列
	$pte_vals = array('{$THREADFRONT}'=>'','{$THREADREAR}'=>'','{$SELF}'=>PHP_SELF);

	if(!$resno){
		if($page_num==0){ // remake模式 (PHP動態輸出多頁份)
			$threads = $PIO->fetchThreadList(); // 取得全討論串列表
			$threads_count = count($threads);
			$inner_for_count = $threads_count > PAGE_DEF ? PAGE_DEF : $threads_count;
			$page_end = ceil($threads_count / PAGE_DEF) - 1; // 頁面編號最後值
		}else{ // 討論串分頁模式 (PHP動態輸出一頁份)
			$threads_count = $PIO->threadCount(); // 討論串個數
			if($page_num < 0 || ($page_num * PAGE_DEF) >= $threads_count) error(_T('page_not_found')); // $page_num超過範圍
			$page_start = $page_end = $page_num; // 設定靜態頁面編號
			$threads = $PIO->fetchThreadList($page_num * PAGE_DEF, PAGE_DEF); // 取出分頁後的討論串首篇列表
			$inner_for_count = count($threads); // 討論串個數就是迴圈次數
		}
	}else{ if(!$PIO->isThread($resno)){ error(_T('thread_not_found')); } }

	// 預測過舊文章和將被刪除檔案
	if($PIO->postCount() >= LOG_MAX * 0.95){
		$old_sensor = true; // 標記打開
		$arr_old = array_flip($PIO->fetchPostList()); // 過舊文章陣列
	}
	$tmp_total_size = total_size(); // 目前附加圖檔使用量
	$tmp_STORAGE_MAX = STORAGE_MAX * (($tmp_total_size >= STORAGE_MAX) ? 1 : 0.95); // 預估上限值
	if(STORAGE_LIMIT && ($tmp_total_size >= $tmp_STORAGE_MAX)){
		$kill_sensor = true; // 標記打開
		$arr_kill = $PIO->delOldAttachments($tmp_total_size, $tmp_STORAGE_MAX); // 過舊附檔陣列
	}

	// 生成靜態頁面一頁份內容
	for($page = $page_start; $page <= $page_end; $page++){
		$dat = '';
		head($dat,$resno);
		form($dat, $resno);
		$pte_vals['{$THREADS}'] = '';
		$pte_vals['{$THREADFRONT}'] = '';
		$pte_vals['{$THREADREAR}'] = '';
		$PMS->useModuleMethods('ThreadFront', array(&$pte_vals['{$THREADFRONT}'],$resno)); // "ThreadFront" Hook Point
		// 輸出討論串內容
		for($i = 0; $i < $inner_for_count; $i++){
			// 取出討論串編號
			if($resno) $tID = $resno; // 單討論串輸出 (回應模式)
			elseif($page_start==$page_end) $tID = $threads[$i]; // 一頁內容 (一般模式)
			else{ // 多頁內容 (remake模式)
				if(($page * PAGE_DEF + $i) >= $threads_count) break; // 超出索引代表已全部完成
				$tID = $threads[$page * PAGE_DEF + $i];
			}
			// 取出討論串結構及回應個數等資訊
			$tree = $PIO->fetchPostList($tID); // 整個討論串樹狀結構
			$tree_count = count($tree) - 1; // 討論串回應個數
			// 計算回應分頁範圍
			$RES_start = $RES_amount = 0;
			$hiddenReply = 0; // 被隱藏回應數
			if($resno){ // 回應模式
				if($tree_count && RE_PAGE_DEF){ // 有回應且RE_PAGE_DEF > 0才做分頁動作
					if($page_num==='all'){ // show all
						$page_num = 0;
						$RES_start = 1; $RES_amount = $tree_count;
					}else{
						if($page_num==='RE_PAGE_MAX') $page_num = ceil($tree_count / RE_PAGE_DEF) - 1; // 特殊值：最末頁
						if($page_num < 0) $page_num = 0; // 負數
						if($page_num * RE_PAGE_DEF >= $tree_count) error(_T('page_not_found'));
						$RES_start = $page_num * RE_PAGE_DEF + 1; // 開始
						$RES_amount = RE_PAGE_DEF; // 取幾個
					}
				}elseif($page_num > 0) error(_T('page_not_found')); // 沒有回應的情況只允許page_num = 0 或負數
				else{ $RES_start = 1; $RES_amount = $tree_count; } // 輸出全部回應
			}else{ // 一般模式下的回應隱藏
				$RES_start = $tree_count - RE_DEF + 1; if($RES_start < 1) $RES_start = 1; // 開始
				$RES_amount = RE_DEF; // 取幾個
				$hiddenReply = $RES_start - 1; // 被隱藏回應數
			}
			// $RES_start, $RES_amount 拿去算新討論串結構 (分頁後, 部分回應隱藏)
			$tree_cut = array_slice($tree, $RES_start, $RES_amount); array_unshift($tree_cut, $tID); // 取出特定範圍回應
			$posts = $PIO->fetchPosts($tree_cut); // 取得文章架構內容
			$pte_vals['{$THREADS}'] .= arrangeThread($PTE, $tree, $tree_cut, $posts, $hiddenReply, $resno, $arr_kill, $arr_old, $kill_sensor, $old_sensor); // 交給這個函式去搞討論串印出
		}
		$PMS->useModuleMethods('ThreadRear', array(&$pte_vals['{$THREADREAR}'],$resno)); // "ThreadRear" Hook Point
		$pte_vals += array('{$DEL_HEAD_TEXT}' => '<input type="hidden" name="mode" value="usrdel" />'._T('del_head'),
			'{$DEL_IMG_ONLY_FIELD}' => '<input type="checkbox" name="onlyimgdel" id="onlyimgdel" value="on" />',
			'{$DEL_IMG_ONLY_TEXT}' => _T('del_img_only'),
			'{$DEL_PASS_TEXT}' => _T('del_pass'),
			'{$DEL_PASS_FIELD}' => '<input type="password" name="pwd" size="8" value="" />',
			'{$DEL_SUBMIT_BTN}' => '<input type="submit" value="'._T('del_btn').'" />');

		$pte_vals['{$PAGENAV}'] = '<div id="page_switch">';

		// 換頁判斷
		$prev = ($resno ? $page_num : $page) - 1;
		$next = ($resno ? $page_num : $page) + 1;
		if($resno){ // 回應分頁
			if(RE_PAGE_DEF > 0){ // 回應分頁開啟
				$AllRes = isset($_GET['page_num']) && $_GET['page_num']=='all'; // 是否使用 ALL 全部輸出
				$pte_vals['{$PAGENAV}'] .= '<table border="1"><tr>';
				if($prev >= 0) $pte_vals['{$PAGENAV}'] .= '<td><form action="'.PHP_SELF.'?res='.$resno.'&amp;page_num='.$prev.'" method="post"><div><input type="submit" value="'._T('prev_page').'" /></div></form></td>';
				else $pte_vals['{$PAGENAV}'] .= '<td style="white-space: nowrap;">'._T('first_page').'</td>';
				$pte_vals['{$PAGENAV}'] .= "<td>";
				if($tree_count==0) $pte_vals['{$PAGENAV}'] .= '[<b>0</b>] '; // 無回應
				else{
					for($i = 0; $i < $tree_count ; $i += RE_PAGE_DEF){
						if(!$AllRes && $page_num==$i/RE_PAGE_DEF) $pte_vals['{$PAGENAV}'] .= '[<b>'.$i/RE_PAGE_DEF.'</b>] ';
						else $pte_vals['{$PAGENAV}'] .= '[<a href="'.PHP_SELF.'?res='.$resno.'&amp;page_num='.$i/RE_PAGE_DEF.'">'.$i/RE_PAGE_DEF.'</a>] ';
					}
					$pte_vals['{$PAGENAV}'] .= $AllRes ? '[<b>'._T('all_pages').'</b>] ' : ($tree_count > RE_PAGE_DEF ? '[<a href="'.PHP_SELF.'?res='.$resno.'&amp;page_num=all">'._T('all_pages').'</a>] ' : '');
				}
				$pte_vals['{$PAGENAV}'] .= '</td>';
				if(!$AllRes && $tree_count > $next * RE_PAGE_DEF) $pte_vals['{$PAGENAV}'] .= '<td><form action="'.PHP_SELF.'?res='.$resno.'&amp;page_num='.$next.'" method="post"><div><input type="submit" value="'._T('next_page').'" /></div></form></td>';
				else $pte_vals['{$PAGENAV}'] .= '<td style="white-space: nowrap;">'._T('last_page').'</td>';
				$pte_vals['{$PAGENAV}'] .= '</tr></table>'."\n";
			}
		}else{ // 一般分頁
			$pte_vals['{$PAGENAV}'] .= '<table border="1"><tr>';
			if($prev >= 0){
				if($prev==0) $pte_vals['{$PAGENAV}'] .= '<td><form action="'.PHP_SELF2.'" method="get">';
				else{
					if((STATIC_HTML_UNTIL != -1) && ($prev > STATIC_HTML_UNTIL)) $pte_vals['{$PAGENAV}'] .= '<td><form action="'.PHP_SELF.'?page_num='.$prev.'" method="post">';
					else $pte_vals['{$PAGENAV}'] .= '<td><form action="'.$prev.PHP_EXT.'" method="get">';
				}
				$pte_vals['{$PAGENAV}'] .= '<div><input type="submit" value="'._T('prev_page').'" /></div></form></td>';
			}else $pte_vals['{$PAGENAV}'] .= '<td style="white-space: nowrap;">'._T('first_page').'</td>';
			$pte_vals['{$PAGENAV}'] .= '<td>';
			for($i = 0; $i < $threads_count ; $i += PAGE_DEF){
				if($page==$i/PAGE_DEF) $pte_vals['{$PAGENAV}'] .= "[<b>".$i/PAGE_DEF."</b>] ";
				else{
					if($i==0) $pte_vals['{$PAGENAV}'] .= '[<a href="'.PHP_SELF2.'?">0</a>] ';
					elseif(STATIC_HTML_UNTIL != -1 && $i/PAGE_DEF > STATIC_HTML_UNTIL) $pte_vals['{$PAGENAV}'] .= '[<a href="'.PHP_SELF.'?page_num='.$i/PAGE_DEF.'">'.$i/PAGE_DEF.'</a>] ';
					else $pte_vals['{$PAGENAV}'] .= '[<a href="'.$i/PAGE_DEF.PHP_EXT.'?">'.$i/PAGE_DEF.'</a>] ';
				}
			}
			$pte_vals['{$PAGENAV}'] .= '</td>';
			if($threads_count > $next * PAGE_DEF){
				if((STATIC_HTML_UNTIL != -1) && ($next > STATIC_HTML_UNTIL)) $pte_vals['{$PAGENAV}'] .= '<td><form action="'.PHP_SELF.'?page_num='.$next.'" method="post">';
				else $pte_vals['{$PAGENAV}'] .= '<td><form action="'.$next.PHP_EXT.'" method="get">';
				$pte_vals['{$PAGENAV}'] .= '<div><input type="submit" value="'._T('next_page').'" /></div></form></td>';
			}else $pte_vals['{$PAGENAV}'] .= '<td style="white-space: nowrap;">'._T('last_page').'</td>';
			$pte_vals['{$PAGENAV}'] .= '</tr></table>'."\n";
		}
		$pte_vals['{$PAGENAV}'] .= '<br style="clear: left;" />
</div>';
		$dat .= $PTE->ParseBlock('MAIN',$pte_vals);
		foot($dat);

		// 存檔 / 輸出
		if(!$page_num){ // 非使用php輸出方式，而是靜態生成
			if($resno){ echo $dat; break; } // 回應分頁第0頁
			if($page==0) $logfilename = PHP_SELF2;
			else $logfilename = $page.PHP_EXT;
			$fp = fopen($logfilename, 'w');
			stream_set_write_buffer($fp, 0);
			fwrite($fp, $dat);
			fclose($fp);
			@chmod($logfilename, 0666);
		}else{ // php輸出
			print $dat;
			break; // 只執行一次迴圈，即印出一頁內容
		}
		if((STATIC_HTML_UNTIL != -1) && STATIC_HTML_UNTIL==$page) break; // 生成靜態頁面數目限制
	}
}

/* 輸出討論串架構 */
function arrangeThread($PTE, $tree, $tree_cut, $posts, $hiddenReply, $resno=0, $arr_kill, $arr_old, $kill_sensor, $old_sensor, $showquotelink=true){
	global $PIO, $FileIO, $PMS, $language;

	$thdat = ''; // 討論串輸出碼
	$posts_count = count($posts); // 迴圈次數
	// $i = 0 (首篇), $i = 1～n (回應)
	for($i = 0; $i < $posts_count; $i++){
		$imgsrc = $img_thumb = $imgwh_bar = '';
		$IMG_BAR = $REPLYBTN = $QUOTEBTN = $WARN_OLD = $WARN_BEKILL = $WARN_ENDREPLY = $WARN_HIDEPOST = '';
		extract($posts[$i]); // 取出討論串文章內容設定變數

		// 設定欄位值
		$name = str_replace('&'._T('trip_pre'), '&amp;'._T('trip_pre'), $name); // 避免 &#xxxx; 後面被視為 Trip 留下 & 造成解析錯誤
		if(CLEAR_SAGE) $email = preg_replace('/^sage( *)/i', '', trim($email)); // 清除E-mail中的「sage」關鍵字
		if(ALLOW_NONAME==2){ // 強制砍名
			$name = preg_match('/(\\'._T('trip_pre').'.{10})/', $name, $matches) ? '<span class="nor">'.$matches[1].'</span>' : '';
			if($email) $now = "<a href=\"mailto:$email\">$now</a>";
		}else{
			$name = preg_replace('/(\\'._T('trip_pre').'.{10})/', '<span class="nor">$1</span>', $name); // Trip取消粗體
			if($email) $name = "<a href=\"mailto:$email\">$name</a>";
		}
		if(AUTO_LINK) $com = auto_link($com);
		$com = quoteLight($com);
		if(USE_QUOTESYSTEM && $i){ // 啟用引用瀏覽系統
			if(preg_match_all('/((?:&gt;|＞)+)(?:No\.)?(\d+)/i', $com, $matches, PREG_SET_ORDER)){ // 找尋>>No.xxx
				foreach($matches as $val){
					if($r_page=array_search($val[2], $tree)){ // $r_page !==0 (首篇) 就算找到
						// 在顯示區間內，輸出錨點即可
						// $tree_cut 目前頁面顯示文章+回應
						if(array_search($val[2], $tree_cut)) $com = str_replace($val[0], '<a class="qlink" href="#r'.$val[2].'" onclick="replyhl('.$val[2].');">'.$val[0].'</a>', $com);
						// 非顯示區間，輸出頁面導引及錨點
						else $com = str_replace($val[0], '<a class="qlink" href="'.PHP_SELF.'?res='.$tree[0].(RE_PAGE_DEF ? '&amp;page_num='.floor(($r_page - 1) / RE_PAGE_DEF) : '').'#r'.$val[2].'">'.$val[0].'</a>', $com);
					}
				}
			}
		}

		// 設定附加圖檔顯示
		if($ext && $FileIO->imageExists($tim.$ext)){
			$imageURL = $FileIO->getImageURL($tim.$ext); // image URL
			$thumbURL = $FileIO->getImageURL($tim.'s.jpg'); // thumb URL

			$imgsrc = '<a href="'.$imageURL.'" rel="_blank"><img src="nothumb.gif" class="img" alt="'.$imgsize.'" title="'.$imgsize.'" /></a>'; // 預設顯示圖樣式 (無預覽圖時)
			if($tw && $th){
				if($FileIO->imageExists($tim.'s.jpg')){ // 有預覽圖
					$img_thumb = '<small>'._T('img_sample').'</small>';
					$imgsrc = '<a href="'.$imageURL.'" rel="_blank"><img src="'.$thumbURL.'" style="width: '.$tw.'px; height: '.$th.'px;" class="img" alt="'.$imgsize.'" title="'.$imgsize.'" /></a>';
				}elseif($ext=='.swf') $imgsrc = ''; // swf檔案不需預覽圖
			}
			if(SHOW_IMGWH) $imgwh_bar = ', '.$imgw.'x'.$imgh; // 顯示附加圖檔之原檔長寬尺寸
			$IMG_BAR = _T('img_filename').'<a href="'.$imageURL.'" rel="_blank">'.$tim.$ext.'</a>-('.$imgsize.$imgwh_bar.') '.$img_thumb;
		}

		// 設定回應 / 引用連結
		if($resno){ // 回應模式
			if($showquotelink) $QUOTEBTN = '<a href="javascript:quote('.$no.');" class="qlink">No.'.$no.'</a>';
			else $QUOTEBTN = '<a href="#" class="qlink">No.'.$no.'</a>';
		}else{
			if(!$i)	$REPLYBTN = '[<a href="'.PHP_SELF.'?res='.$no.'">'._T('reply_btn').'</a>]'; // 首篇
			$QUOTEBTN = '<a href="'.PHP_SELF.'?res='.$tree[0].'#q'.$no.'" class="qlink">No.'.$no.'</a>';
		}

		// 設定討論串屬性
		if(STORAGE_LIMIT && $kill_sensor) if(isset($arr_kill[$no])) $WARN_BEKILL = '<span class="warn_txt">'._T('warn_sizelimit').'</span><br />'."\n"; // 預測刪除過大檔
		if(!$i){ // 首篇 Only
			if($old_sensor) if($arr_old[$no] + 1 >= LOG_MAX * 0.95) $WARN_OLD = '<span class="warn_txt">'._T('warn_oldthread').'</span><br />'."\n"; // 快要被刪除的提示
			if(strpos($status, 'T')!==false) $WARN_ENDREPLY = '<span class="warn_txt">'._T('warn_locked').'</span><br />'."\n"; // 被標記為禁止回應
			if($hiddenReply) $WARN_HIDEPOST = '<span class="warn_txt2">'._T('notice_omitted',$hiddenReply).'</span><br />'."\n"; // 有隱藏的回應
		}
		// 對類別標籤作自動連結
		if(USE_CATEGORY){
			$ary_category = explode(',', str_replace('&#44;', ',', $category)); $ary_category = array_map('trim', $ary_category);
			$ary_category_count = count($ary_category);
			$ary_category2 = array();
			for($p = 0; $p < $ary_category_count; $p++){
				if($c = $ary_category[$p]) $ary_category2[] = '<a href="'.PHP_SELF.'?mode=category&amp;c='.urlencode($c).'">'.$c.'</a>';
			}
			$category = implode(', ', $ary_category2);
		}else $category = '';

		// 最終輸出處
		if($i){ // 回應
			$arrLabels = array('{$NO}'=>$no, '{$SUB}'=>$sub, '{$NAME}'=>$name, '{$NOW}'=>$now, '{$COM}'=>$com, '{$CATEGORY}'=>$category, '{$QUOTEBTN}'=>$QUOTEBTN, '{$IMG_BAR}'=>$IMG_BAR, '{$IMG_SRC}'=>$imgsrc, '{$WARN_BEKILL}'=>$WARN_BEKILL, '{$QUOTEBTN}'=>$QUOTEBTN, '{$NAME_TEXT}'=>_T('post_name'), '{$CATEGORY_TEXT}'=>_T('post_category'), '{$SELF}'=>PHP_SELF);
			if($resno) $arrLabels['{$RESTO}']=$resno;
			$PMS->useModuleMethods('ThreadReply', array(&$arrLabels, $posts[$i], $resno)); // "ThreadReply" Hook Point
			$thdat .= $PTE->ParseBlock('REPLY',$arrLabels);
		}else{ // 首篇
			$arrLabels = array('{$NO}'=>$no, '{$SUB}'=>$sub, '{$NAME}'=>$name, '{$NOW}'=>$now, '{$COM}'=>$com, '{$CATEGORY}'=>$category, '{$QUOTEBTN}'=>$QUOTEBTN, '{$REPLYBTN}'=>$REPLYBTN, '{$IMG_BAR}'=>$IMG_BAR, '{$IMG_SRC}'=>$imgsrc, '{$WARN_OLD}'=>$WARN_OLD, '{$WARN_BEKILL}'=>$WARN_BEKILL, '{$WARN_ENDREPLY}'=>$WARN_ENDREPLY, '{$WARN_HIDEPOST}'=>$WARN_HIDEPOST, '{$NAME_TEXT}'=>_T('post_name'), '{$CATEGORY_TEXT}'=>_T('post_category'), '{$SELF}'=>PHP_SELF);
			if($resno) $arrLabels['{$RESTO}']=$resno;
			$PMS->useModuleMethods('ThreadPost', array(&$arrLabels, $posts[$i], $resno)); // "ThreadPost" Hook Point
			$thdat .= $PTE->ParseBlock('THREAD',$arrLabels);
		}
	}
	$thdat .= $PTE->ParseBlock('THREADSEPARATE',($resno)?array('{$RESTO}'=>$resno):array());
	return $thdat;
}

/* 寫入記錄檔 */
function regist(){
	global $PIO, $FileIO, $PMS, $language, $BAD_STRING, $BAD_FILEMD5, $BAD_IPADDR;
	$dest = ''; $mes = ''; $up_incomplete = 0; $is_admin = false;
	$path = realpath('.').DIRECTORY_SEPARATOR; // 此目錄的絕對位置

	if($_SERVER['REQUEST_METHOD'] != 'POST') error(_T('regist_notpost')); // 非正規POST方式

	$name = isset($_POST[FT_NAME]) ? $_POST[FT_NAME] : '';
	$email = isset($_POST[FT_EMAIL]) ? $_POST[FT_EMAIL] : '';
	$sub = isset($_POST[FT_SUBJECT]) ? $_POST[FT_SUBJECT] : '';
	$com = isset($_POST[FT_COMMENT]) ? $_POST[FT_COMMENT] : '';
	$pwd = isset($_POST['pwd']) ? $_POST['pwd'] : '';
	$category = isset($_POST['category']) ? $_POST['category'] : '';
	$resto = isset($_POST['resto']) ? $_POST['resto'] : 0;
	$upfile = isset($_FILES['upfile']['tmp_name']) ? $_FILES['upfile']['tmp_name'] : '';
	$upfile_path = isset($_POST['upfile_path']) ? $_POST['upfile_path'] : '';
	$upfile_name = isset($_FILES['upfile']['name']) ? $_FILES['upfile']['name'] : false;
	$upfile_status = isset($_FILES['upfile']['error']) ? $_FILES['upfile']['error'] : 4;
	$pwdc = isset($_COOKIE['pwdc']) ? $_COOKIE['pwdc'] : '';

	// 欄位陷阱
	$FTname = isset($_POST['name']) ? $_POST['name'] : '';
	$FTemail = isset($_POST['email']) ? $_POST['email'] : '';
	$FTsub = isset($_POST['sub']) ? $_POST['sub'] : '';
	$FTcom = isset($_POST['com']) ? $_POST['com'] : '';
	$FTreply = isset($_POST['reply']) ? $_POST['reply'] : '';
	if($FTname != 'spammer' || $FTemail != 'foo@foo.bar' || $FTsub != 'DO NOT FIX THIS' || $FTcom != 'EID OG SMAPS' || $FTreply != '') error(_T('regist_nospam'));

	// 封鎖：IP/Hostname/DNSBL 檢查機能
	$ip = $_SERVER["REMOTE_ADDR"]; $host = gethostbyaddr($ip); $baninfo = '';
	if(BanIPHostDNSBLCheck($ip, $host, $baninfo)) error(_T('regist_ipfiltered', $baninfo));
	// 封鎖：限制出現之文字
	foreach($BAD_STRING as $value){
		if(strpos($com, $value)!==false || strpos($sub, $value)!==false || strpos($name, $value)!==false || strpos($email, $value)!==false){
			error(_T('regist_wordfiltered'));
		}
	}
	$PMS->useModuleMethods('RegistBegin', array(&$name, &$email, &$sub, &$com, array('file'=>&$upfile, 'path'=>&$upfile_path, 'name'=>&$upfile_name, 'status'=>&$upfile_status), array('ip'=>$ip, 'host'=>$host))); // "RegistBegin" Hook Point

	// 檢查是否輸入櫻花日文假名
	$chkanti = array($name, $email, $sub, $com);
	foreach($chkanti as $anti) if(anti_sakura($anti)) error(_T('regist_sakuradetected'));

	// 時間
	$time = time();
	$tim = $time.substr(microtime(),2,3);

	// 判斷上傳狀態
	switch($upfile_status){
		case 1:
			error(_T('regist_upload_exceedphp'));
			break;
		case 2:
			error(_T('regist_upload_exceedcustom'));
			break;
		case 3:
			error(_T('regist_upload_incompelete'));
			break;
		case 6:
			error(_T('regist_upload_direrror'));
			break;
		case 4: // 無上傳
			if(!$resto && !isset($_POST['noimg'])) error(_T('regist_upload_noimg'));
			break;
		case 0: // 上傳正常
		default:
	}

	// 如果有上傳檔案則處理附加圖檔
	if($upfile && is_file($upfile)){
		// 一‧先儲存檔案
		$dest = $path.$tim.'.tmp';
		@move_uploaded_file($upfile, $dest) or @copy($upfile, $dest);
		@chmod($dest, 0666);
		if(!is_file($dest)) error(_T('regist_upload_filenotfound'), $dest);

		// 二‧判斷上傳附加圖檔途中是否有中斷
		$upsizeTTL = $_SERVER['CONTENT_LENGTH'];
		if(isset($_FILES['upfile'])){ // 有傳輸資料才需要計算，避免作白工
			$upsizeHDR = 0;
			// 檔案路徑：IE附完整路徑，故得從隱藏表單取得
			$tmp_upfile_path = $upfile_name;
			if($upfile_path) $tmp_upfile_path = get_magic_quotes_gpc() ? stripslashes($upfile_path) : $upfile_path;
			list(,$boundary) = explode('=', $_SERVER['CONTENT_TYPE']);
			foreach($_POST as $header => $value){ // 表單欄位傳送資料
				$upsizeHDR += strlen('--'.$boundary."\r\n");
				$upsizeHDR += strlen('Content-Disposition: form-data; name="$header"'."\r\n\r\n".(get_magic_quotes_gpc()?stripslashes($value):$value)."\r\n");
			}
			// 附加圖檔欄位傳送資料
			$upsizeHDR += strlen('--'.$boundary."\r\n");
			$upsizeHDR += strlen('Content-Disposition: form-data; name="upfile"; filename="'.$tmp_upfile_path."\"\r\n".'Content-Type: '.$_FILES['upfile']['type']."\r\n\r\n");
			$upsizeHDR += strlen("\r\n--".$boundary."--\r\n");
			$upsizeHDR += $_FILES['upfile']['size']; // 傳送附加圖檔資料量
			// 上傳位元組差值超過 HTTP_UPLOAD_DIFF：上傳附加圖檔不完全
			if(($upsizeTTL - $upsizeHDR) > HTTP_UPLOAD_DIFF){
				if(KILL_INCOMPLETE_UPLOAD){
					unlink($dest);
					die(_T('regist_upload_killincomp')); // 給瀏覽器的提示，假如使用者還看的到的話才不會納悶
				}else $up_incomplete = 1;
			}
		}

		// 三‧檢查是否為可接受的檔案
		$size = @getimagesize($dest);
		if(!is_array($size)) error(_T('regist_upload_notimage'), $dest); // $size不為陣列就不是圖檔
		$imgsize = @filesize($dest); // 檔案大小
		$imgsize = ($imgsize>=1024) ? (int)($imgsize/1024).' KB' : $imgsize.' B'; // KB和B的判別
		switch($size[2]){ // 判斷上傳附加圖檔之格式
			case 1 : $ext = ".gif"; break;
			case 2 : $ext = ".jpg"; break;
			case 3 : $ext = ".png"; break;
			case 4 : $ext = ".swf"; break;
			case 5 : $ext = ".psd"; break;
			case 6 : $ext = ".bmp"; break;
			case 13 : $ext = ".swf"; break;
			default : $ext = ".xxx"; error(_T('regist_upload_notsupport'), $dest);
		}
		$allow_exts = explode('|', strtolower(ALLOW_UPLOAD_EXT)); // 接受之附加圖檔副檔名
		if(array_search(substr($ext, 1), $allow_exts)===false) error(_T('regist_upload_notsupport'), $dest); // 並無在接受副檔名之列
		// 封鎖設定：限制上傳附加圖檔之MD5檢查碼
		$md5chksum = md5_file($dest); // 檔案MD5
		if(array_search($md5chksum, $BAD_FILEMD5)!==FALSE) error(_T('regist_upload_blocked'), $dest); // 在封鎖設定內則阻擋

		// 四‧計算附加圖檔圖檔縮圖顯示尺寸
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
		$mes = _T('regist_uploaded',CleanStr($upfile_name));
	}

	// 檢查表單欄位內容並修整
	if(!$name || ereg("^[ |　|]*$", $name)){
		if(ALLOW_NONAME) $name = DEFAULT_NONAME;
		else error(_T('regist_withoutname'), $dest);
	}
	if(!$com && $upfile_status==4) error(_T('regist_withoutcomment'));
	if(!$com || ereg("^[ |　|\t]*$", $com)) $com = DEFAULT_NOCOMMENT;
	if(!$sub || ereg("^[ |　|]*$", $sub)) $sub = DEFAULT_NOTITLE;
	if(strlen($name) > 100) error(_T('regist_nametoolong'), $dest);
	if(strlen($email) > 100) error(_T('regist_emailtoolong'), $dest);
	if(strlen($sub) > 100) error(_T('regist_topictoolong'), $dest);
	if(strlen($resto) > 10) error(_T('regist_longthreadnum'), $dest);

	$email = CleanStr($email); $email = str_replace("\r\n", '', $email);
	$sub = CleanStr($sub); $sub = str_replace("\r\n", '', $sub);
	$resto = CleanStr($resto); $resto = str_replace("\r\n", '', $resto);
	// 名稱修整
	$name = CleanStr($name);
	$name = str_replace(_T('trip_pre'), _T('trip_pre_fake'), $name); // 防止トリップ偽造
	$name = str_replace(CAP_SUFFIX, _T('cap_char_fake'), $name); // 防止管理員キャップ偽造
	$name = str_replace("\r\n", '', $name);
	$nameOri = $name; // 名稱
	if(preg_match('/(.*?)[#＃](.*)/u', $name, $regs)){ // トリップ(Trip)機能
		$name = $nameOri = $regs[1]; $cap = strtr($regs[2], array('&amp;'=>'&'));
		$salt = preg_replace('/[^\.-z]/', '.', substr($cap.'H.', 1, 2));
		$salt = strtr($salt, ':;<=>?@[\\]^_`', 'ABCDEFGabcdef');
		$name = $name._T('trip_pre').substr(crypt($cap, $salt), -10);
	}
	if(CAP_ENABLE && preg_match('/(.*?)[#＃](.*)/', $email, $aregs)){ // 管理員キャップ(Cap)機能
		$acap_name = $nameOri; $acap_pwd = strtr($aregs[2], array('&amp;'=>'&'));
		if($acap_name==CAP_NAME && $acap_pwd==CAP_PASS){
			$name = '<span class="admin_cap">'.$name.CAP_SUFFIX.'</span>';
			$is_admin = true;
			$email = $aregs[1]; // 去除 #xx 密碼
		}
	}
	if(!$is_admin){ // 非管理員
		$name = str_replace(_T('admin'), '"'._T('admin').'"', $name);
		$name = str_replace(_T('deletor'), '"'._T('deletor').'"', $name);
	}
	$name = str_replace('&◆', '&amp;◆', $name); // 避免 &#xxxx; 後面被視為 Trip 留下 & 造成解析錯誤
	// 內文修整
	if((strlen($com) > COMM_MAX) && !$is_admin) error(_T('regist_commenttoolong'), $dest);
	$com = CleanStr($com, $is_admin); // 引入$is_admin參數是因為當管理員キャップ啟動時，允許管理員依config設定是否使用HTML
	$com = str_replace("\r\n","\n", $com);
	$com = str_replace("\r","\n", $com);
	$com = ereg_replace("\n((　| )*\n){3,}", "\n", $com);
	if(!BR_CHECK || substr_count($com,"\n") < BR_CHECK) $com = nl2br($com); // 換行字元用<br />代替
	$com = str_replace("\n",'', $com); // 若還有\n換行字元則取消換行
	if($category && USE_CATEGORY){ // 修整標籤樣式
		$category = explode(',', $category); // 把標籤拆成陣列
		$category = ','.implode(',', array_map('trim', $category)).','; // 去空白再合併為單一字串 (左右含,便可以直接以,XX,形式搜尋)
	}else{ $category = ''; }
	if($up_incomplete) $com .= '<br /><br /><span class="warn_txt">'._T('notice_incompletefile').'</span>'; // 上傳附加圖檔不完全的提示

	// 密碼和時間的樣式
	if($pwd=='') $pwd = ($pwdc=='') ? substr(rand(),0,8) : $pwdc;
	$pass = $pwd ? substr(md5($pwd), 2, 8) : '*'; // 生成真正儲存判斷用的密碼
	$youbi = array(_T('sun'),_T('mon'),_T('tue'),_T('wed'),_T('thu'),_T('fri'),_T('sat'));
	$yd = $youbi[gmdate('w', $time+TIME_ZONE*60*60)];
	$now = gmdate('y/m/d', $time+TIME_ZONE*60*60).'('.(string)$yd.')'.gmdate('H:i', $time+TIME_ZONE*60*60);
	if(DISP_ID){ // 顯示ID
		if($email && DISP_ID==1) $now .= ' ID:???';
		else $now .= ' ID:'.substr(crypt(md5($_SERVER['REMOTE_ADDR'].IDSEED.gmdate('Ymd', $time+TIME_ZONE*60*60)),'id'), -8);
	}

	// 連續投稿 / 相同附加圖檔檢查
	$checkcount = 50; // 預設檢查50筆資料
	$pwdc = substr(md5($pwdc), 2, 8); // Cookies密碼
	if($PIO->isSuccessivePost($checkcount, $com, $time, $pass, $pwdc, $host, $upfile_name)) error(_T('regist_successivepost'), $dest); // 連續投稿檢查
	if($dest){ if($PIO->isDuplicateAttechment($checkcount, $md5chksum)) error(_T('regist_duplicatefile'), $dest); } // 相同附加圖檔檢查

	if($resto) $ThreadExistsBefore = $PIO->isThread($resto);
	// 記錄檔行數已達上限：刪除過舊檔
	if($PIO->postCount() >= LOG_MAX){
		$PMS->useModuleMethods('UsageExceed', array()); // "UsageExceed" Hook Point
		$files = $PIO->delOldPostes();
		if(count($files)) $FileIO->deleteImage($files);
	}

	// 附加圖檔容量限制功能啟動：刪除過大檔
	if(STORAGE_LIMIT){
		$tmp_total_size = total_size(); // 取得目前附加圖檔使用量
		if($tmp_total_size >= STORAGE_MAX){
			$files = $PIO->delOldAttachments($tmp_total_size, STORAGE_MAX, false);
			$FileIO->deleteImage($files);
		}
	}

	// 判斷欲回應的文章是不是剛剛被刪掉了
	if($resto){
		if($ThreadExistsBefore){ // 欲回應的討論串是否存在 (看逆轉換成功與否)
			if(!$PIO->isThread($resto)){ // 被回應的討論串存在但已被刪
				// 提前更新資料來源，此筆新增亦不紀錄
				$PIO->dbCommit();
				updatelog();
				error(_T('regist_threaddeleted'), $dest);
			}else{ // 檢查是否討論串被設為禁止回應 (順便取出原討論串的貼文時間)
				$post = $PIO->fetchPosts($resto); // [特殊] 取單篇文章內容，但是回傳的$post同樣靠[$i]切換文章！
				list($chkstatus, $chktime) = array($post[0]['status'], $post[0]['tim']);
				$chktime = substr($chktime, 0, -3); // 拿掉微秒 (後面三個字元)
				if(strpos($chkstatus, 'T')!==false) error(_T('regist_threadlocked'), $dest);
			}
		}else error(_T('thread_not_found'), $dest); // 不存在
	}

	// 計算某些欄位值
	$no = $PIO->getLastPostNo('beforeCommit') + 1;
	isset($ext) ? 0 : $ext = '';
	isset($imgW) ? 0 : $imgW = 0;
	isset($imgH) ? 0 : $imgH = 0;
	isset($imgsize) ? 0 : $imgsize = '';
	isset($W) ? 0 : $W = 0;
	isset($H) ? 0 : $H = 0;
	isset($md5chksum) ? 0 : $md5chksum = '';
	$age = false;
	$status = '';
	if($resto){
		if(!stristr($email, 'sage') && ($PIO->postCount($resto) <= MAX_RES || MAX_RES==0)){
			if(!MAX_AGE_TIME || (($time - $chktime) < (MAX_AGE_TIME * 60 * 60))) $age = true; // 討論串並無過期，推文
		}
	}
	$PMS->useModuleMethods('RegistBeforeCommit', array(&$name, &$email, &$sub, &$com, &$category, &$age, $dest, $resto, array($W, $H, $imgW, $imgH), &$status)); // "RegistBeforeCommit" Hook Point

	// 正式寫入儲存
	$PIO->addPost($no,$resto,$md5chksum,$category,$tim,$ext,$imgW,$imgH,$imgsize,$W,$H,$pass,$now,$name,$email,$sub,$com,$host,$age,$status);
	$PIO->dbCommit();

	// Cookies儲存：密碼與E-mail部分，期限是一週
	setcookie('pwdc', $pwd, time()+7*24*3600);
	setcookie('emailc', $email, time()+7*24*3600);

	if($dest && is_file($dest)){
		$destFile = $path.IMG_DIR.$tim.$ext; // 圖檔儲存位置
		$thumbFile = $path.THUMB_DIR.$tim.'s.jpg'; // 預覽圖儲存位置
		rename($dest, $destFile);
		if(USE_THUMB !== 0){ // 生成預覽圖
			$thumbType = USE_THUMB; if(USE_THUMB==1){ $thumbType = 'gd'; } // 與舊設定相容
			require('./lib/thumb/thumb.'.$thumbType.'.php');
			$thObj = new ThumbWrapper($destFile, $imgW, $imgH);
			$thObj->setThumbnailConfig($W, $H, THUMB_Q);
			$thObj->makeThumbnailtoFile($thumbFile);
			@chmod($thumbFile, 0666);
			unset($thObj);
		}
		if($FileIO->uploadImage()){ // 支援上傳圖片至其他伺服器
			if(file_exists($destFile)) $FileIO->uploadImage($tim.$ext, $destFile, filesize($destFile));
			if(file_exists($thumbFile)) $FileIO->uploadImage($tim.'s.jpg', $thumbFile, filesize($thumbFile));
		}
	}

	// 刪除舊容量快取
	total_size(true);
	updatelog();

	// 引導使用者至新頁面
	$RedirURL = PHP_SELF2.'?'.$tim; // 定義儲存資料後轉址目標
	if(isset($_POST['up_series'])){ // 勾選連貼機能
		if($resto) $RedirURL = PHP_SELF.'?res='.$resto.'&amp;upseries=1'; // 回應後繼續轉回此主題下
		else{
			$lastno = $PIO->getLastPostNo('afterCommit'); // 取得此新文章編號
			$RedirURL = PHP_SELF.'?res='.$lastno.'&amp;upseries=1'; // 新增主題後繼續轉到此主題下
		}
	}
	$RedirforJS = strtr($RedirURL, array("&amp;"=>"&")); // JavaScript用轉址目標

	echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
	echo <<< _REDIR_
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="zh-tw">
<head>
<title></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Refresh" content="1;URL=$RedirURL" />
<script type="text/javascript">
// Redirection (use JS)
// <![CDATA[
function redir(){
	location.href = "$RedirforJS";
}
setTimeout("redir()", 1000);
// ]]>
</script>
</head>
<body>
<div>
_REDIR_;
echo _T('regist_redirect',$mes,$RedirURL).'</div>
</body>
</html>';
}

/* 使用者刪除 */
function usrdel(){
	global $PIO, $FileIO, $PMS, $language;
	// $pwd: 使用者輸入值, $pwdc: Cookie記錄密碼
	$pwd = isset($_POST['pwd']) ? $_POST['pwd'] : '';
	$pwdc = isset($_COOKIE['pwdc']) ? $_COOKIE['pwdc'] : '';
	$onlyimgdel = isset($_POST['onlyimgdel']) ? $_POST['onlyimgdel'] : '';
	$haveperm = ($pwd==ADMIN_PASS);
	$PMS->useModuleMethods('Authenticate', array($pwd,'userdel',&$haveperm));

	if($pwd=='' && $pwdc!='') $pwd = $pwdc;
	$pwd_md5 = substr(md5($pwd),2,8);
	$host = gethostbyaddr($_SERVER['REMOTE_ADDR']);
	$search_flag = $delflag = false;
	$delno = array();
	reset($_POST);
	while($item = each($_POST)) if($item[1]=='delete') array_push($delno, $item[0]);
	if(!count($delno)) error(_T('del_notchecked'));

	$delposts = array(); // 真正符合刪除條件文章
	$posts = $PIO->fetchPosts($delno);
	foreach($posts as $post){
		if($pwd_md5==$post['pwd'] || $host==$post['host'] || $haveperm){
			$search_flag = true; // 有搜尋到
			array_push($delposts, $post['no']);
		}
	}
	if($search_flag){
		$files = $onlyimgdel ? $PIO->removeAttachments($delposts) : $PIO->removePosts($delposts);
		$FileIO->deleteImage($files);
		total_size(true); // 刪除容量快取
		$PIO->dbCommit();
	}else error(_T('del_wrongpwornotfound'));
}

/* 管理員密碼認證 */
function valid(){
	global $PMS, $language;
	$pass = isset($_POST['pass']) ? $_POST['pass'] : ''; // 管理者密碼
	$haveperm = false;
	if($pass) {
		if(!($haveperm = ($pass == ADMIN_PASS))) {
			$PMS->useModuleMethods('Authenticate', array($pass,'adminlogin',&$haveperm));
			if(!$haveperm) error($pass._T('admin_wrongpassword'));
		}
	}
	$dat = '';
	head($dat);
	$links = '[<a href="'.PHP_SELF2.'?'.time().'">'._T('return').'</a>] [<a href="'.PHP_SELF.'?mode=remake">'._T('admin_remake').'</a>]';
	$PMS->useModuleMethods('LinksAboveBar', array(&$links,'admin',$haveperm)); // LinksAboveBar hook point
	$dat .= '<div id="banner">'.$links.'<div class="bar_admin">'._T('admin_top').'</div>
</div>
<form action="'.PHP_SELF.'" method="post">
<div id="admin-check" style="text-align: center;">
';
	echo $dat;
	// 登錄用表單
	if(!$pass){
		echo '<br />
<input type="radio" name="admin" value="del" checked="checked" />'._T('admin_manageposts').'
<input type="radio" name="admin" value="opt" />'._T('admin_optimize').'<p />
<input type="hidden" name="mode" value="admin" />
<input type="password" name="pass" size="8" />
<input type="submit" value="'._T('admin_verify_btn').'" />
</div>
</form>';
		die("\n</body>\n</html>");
	}
}

/* 管理文章模式 */
function admindel(){
	global $PIO, $FileIO, $PMS, $language;

	$pass = isset($_POST['pass']) ? $_POST['pass'] : ''; // 管理者密碼
	$page = isset($_POST['page']) ? $_POST['page'] : 0; // 切換頁數
	$onlyimgdel = isset($_POST['onlyimgdel']) ? $_POST['onlyimgdel'] : ''; // 只刪圖
	$modFunc = '';
	$delno = $thsno = array();
	$delflag = isset($_POST['delete']); // 是否有「刪除」勾選
	$thsflag = isset($_POST['stop']); // 是否有「停止」勾選
	$is_modified = false; // 是否改寫檔案

	// 刪除文章區塊
	if($delflag){
		$haveperm = ($pass == ADMIN_PASS);
		$PMS->useModuleMethods('Authenticate', array($pass,'admindel',&$haveperm));
		if(!$haveperm) error($pass._T('admin_wrongpassword'));

		$delno = array_merge($delno, $_POST['delete']);
		$files = ($onlyimgdel != 'on') ? $PIO->removePosts($delno) : $PIO->removeAttachments($delno);
		$FileIO->deleteImage($files);
		total_size(true); // 刪除容量快取
		$is_modified = TRUE;
	}
	// 討論串停止區塊
	if($thsflag){
		$haveperm = ($pass == ADMIN_PASS);
		$PMS->useModuleMethods('Authenticate', array($pass,'threadstop',&$haveperm));
		if(!$haveperm) error($pass._T('admin_wrongpassword'));

		$thsno = array_merge($thsno, $_POST['stop']);
		$threads = $PIO->fetchPosts($thsno); // 取得文章
		foreach($threads as $th){
			$tnewstatus = strpos($th['status'], 'T')!==false ? str_replace('T', '', $th['status']) : $th['status'].'T';
			$PIO->setPostStatus($th['no'], $tnewstatus);
		}
		$is_modified = true;
	}
	if(($delflag || $thsflag) && $is_modified) $PIO->dbCommit(); // 無論如何都有檔案操作，回寫檔案

	$line = $PIO->fetchPostList(0, $page * ADMIN_PAGE_DEF, ADMIN_PAGE_DEF); // 分頁過的文章列表
	$posts_count = count($line); // 迴圈次數
	$posts = $PIO->fetchPosts($line); // 文章內容陣列

	// 印出刪除表格
	echo '<script type="text/javascript">
// <![CDATA[
function ChangePage(page){
	document.forms[0].page.value = page;
	document.forms[0].submit();
}
// ]]>
</script>
<input type="hidden" name="mode" value="admin" />
<input type="hidden" name="admin" value="del" />
<input type="hidden" name="pass" value="'.$pass.'" />
<input type="hidden" name="page" value="'.$page.'" />
<div style="text-align: left;">'._T('admin_notices').'</div>
<p><input type="submit" value="'._T('admin_submit_btn').'" /> <input type="reset" value="'._T('admin_reset_btn').'" /> [<input type="checkbox" name="onlyimgdel" id="onlyimgdel" value="on" /><label for="onlyimgdel">'._T('del_img_only').'</label>]</p>
<table border="1" cellspacing="0" style="margin: 0px auto;">
<tr style="background-color: #6080f6;">'._T('admin_list_header').'</tr>
';

	for($j = 0; $j < $posts_count; $j++){
		$bg = ($j % 2) ? 'ListRow1_bg' : 'ListRow2_bg'; // 背景顏色
		extract($posts[$j]);

		// 修改欄位樣式
		$now = preg_replace('/.{2}\/(.{5})\(.+?\)(.{5}).*/', '$1 $2', $now);
		$name = htmlspecialchars(str_cut(html_entity_decode(strip_tags($name)), 8));
		$sub = htmlspecialchars(str_cut(html_entity_decode($sub), 8));
		if($email) $name = "<a href=\"mailto:$email\">$name</a>";
		$com = str_replace('<br />',' ',$com);
		$com = htmlspecialchars(str_cut(html_entity_decode($com), 20));

		// 討論串首篇停止勾選框 及 模組功能
		$modFunc = $THstop = ' ';
		$PMS->useModuleMethods('AdminList', array(&$modFunc, $posts[$j], $resto)); // "AdminList" Hook Point
		if($resto==0){ // $resto = 0 (即討論串首篇)
			$THstop = '<input type="checkbox" name="stop[]" value="'.$no.'" />'.((strpos($status, 'T')!==false)?_T('admin_stop_btn'):'');
		}

		// 從記錄抽出附加圖檔使用量並生成連結
		if($ext && $FileIO->imageExists($tim.$ext)){
			$clip = '<a href="'.$FileIO->getImageURL($tim.$ext).'" rel="_blank">'.$tim.$ext.'</a>';
			$size = $FileIO->getImageFilesize($tim.$ext);
			if($FileIO->imageExists($tim.'s.jpg')) $size += $FileIO->getImageFilesize($tim.'s.jpg');
		}else{
			$clip = $md5chksum = '--';
			$size = 0;
		}

		// 印出介面
		echo <<< _ADMINEOF_
<tr class="$bg" align="left">
<th align="center">$modFunc</th><th align="center">$THstop</th><th><input type="checkbox" name="delete[]" value="$no" />$no</th><td><small>$now</small></td><td>$sub</td><td><b>$name</b></td><td><small>$com</small></td><td>$host</td><td align="center">$clip ($size)<br />$md5chksum</td>
</tr>

_ADMINEOF_;
	}
	echo '</table>
<p><input type="submit" value="'._T('admin_submit_btn').'" /> <input type="reset" value="'._T('admin_reset_btn').'" /></p>
<p>'._T('admin_totalsize',total_size()).'</p>
<hr />
';

	$countline = $PIO->postCount(); // 總文章數
	$page_max = ceil($countline / ADMIN_PAGE_DEF) - 1; // 總頁數
	echo '<table border="1" style="float: left;"><tr>';
	if($page) echo '<td><input type="button" value="'._T('prev_page').'" onclick="ChangePage('.($page - 1).');" /></td>';
	else echo '<td style="white-space: nowrap;">'._T('first_page').'</td>';
	echo '<td>';
	for($i = 0; $i <= $page_max; $i++){
		if($i==$page) echo '[<b>'.$i.'</b>] ';
		else echo '[<a href="javascript:ChangePage('.$i.');">'.$i.'</a>] ';
	}
	echo '</td>';
	if($page < $page_max) echo '<td><input type="button" value="'._T('next_page').'" onclick="ChangePage('.($page + 1).');" /></td>';
	else echo '<td style="white-space: nowrap;">'._T('last_page').'</td>';
	die('</tr></table>
</div>
</form>
</body>
</html>');
}

/* 計算目前附加圖檔使用容量 (單位：KB) */
function total_size($isupdate=false){
	global $PIO, $FileIO;

	$size = 0; $all = 0;
	$cache_file = "./sizecache.dat"; // 附加圖檔使用容量值快取檔案

	if($isupdate){ // 刪除舊快取
		if(is_file($cache_file)) unlink($cache_file);
		return;
	}
	if(!is_file($cache_file)){ // 無快取，新增
		$line = $PIO->fetchPostList(); // 取出所有文章編號
		$posts = $PIO->fetchPosts($line);
		$linecount = count($posts);
		for($i = 0; $i < $linecount; $i++){
			extract($posts[$i]);
			// 從記錄檔抽出計算附加圖檔使用量
			if($ext && $FileIO->imageExists($tim.$ext)) $all += $FileIO->getImageFilesize($tim.$ext); // 附加圖檔合計計算
			if($FileIO->imageExists($tim.'s.jpg')) $all += $FileIO->getImageFilesize($tim.'s.jpg'); // 預覽圖合計計算
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
	global $PTE, $PIO, $FileIO, $PMS, $language;

	if(!USE_SEARCH) error(_T('search_disabled'));
	$searchKeyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : ''; // 欲搜尋的文字
	$dat = '';
	head($dat);
	$links = '[<a href="'.PHP_SELF2.'?'.time().'">'._T('return').'</a>]';
	$PMS->useModuleMethods('LinksAboveBar', array(&$links,'search'));
	$dat .= '<div id="banner">'.$links.'<div class="bar_admin">'._T('search_top').'</div>
</div>
';
	echo $dat;
	if($searchKeyword==''){
		echo '<form action="'.PHP_SELF.'" method="post">
<div id="search">
<input type="hidden" name="mode" value="search" />
';
		echo '<ul>'._T('search_notice').'<input type="text" name="keyword" size="30" />
'._T('search_target').'<select name="field"><option value="com" selected="selected">'._T('search_target_comment').'</option><option value="name">'._T('search_target_name').'</option><option value="sub">'._T('search_target_topic').'</option><option value="no">'._T('search_target_number').'</option></select>
'._T('search_method').'<select name="method"><option value="AND" selected="selected">'._T('search_method_and').'</option><option value="OR">'._T('search_method_or').'</option></select>
<input type="submit" value="'._T('search_submit_btn').'" />
</li>
</ul>
</div>
</form>';
	}else{
		$searchField = $_POST['field']; // 搜尋目標 (no:編號, name:名稱, sub:標題, com:內文)
		$searchMethod = $_POST['method']; // 搜尋方法
		$searchKeyword = preg_split('/(　| )+/', trim($searchKeyword)); // 搜尋文字用空格切割
		$hitPosts = $PIO->searchPost($searchKeyword, $searchField, $searchMethod); // 直接傳回符合的文章內容陣列

		echo '<div id="search_result">
';
		$resultlist = '';
		foreach($hitPosts as $post){
			extract($post);
			$arrLabels = array('{$NO}'=>$no, '{$SUB}'=>$sub, '{$NAME}'=>$name, '{$NOW}'=>$now, '{$COM}'=>$com, '{$CATEGORY}'=>$category, '{$NAME_TEXT}'=>_T('post_name'), '{$CATEGORY_TEXT}'=>_T('post_category'));
			$resultlist .= $PTE->ParseBlock('SEARCHRESULT',$arrLabels);
		}
		echo $resultlist ? $resultlist : '<div style="text-align: center">'._T('search_notfound').'<br/><a href="?mode=search">'._T('search_back').'</a></div>';
		echo "</div>";
	}
	echo "</body>\n</html>";
}

/* 利用類別標籤搜尋符合的文章 */
function searchCategory(){
	global $PTE, $PIO, $FileIO, $language;
	$category = isset($_GET['c']) ? strtolower(strip_tags(trim($_GET['c']))) : ''; // 搜尋之類別標籤
	$category_enc = urlencode($category); // URL 編碼後字串
	$page = isset($_GET['p']) ? @intval($_GET['p']) : 1; // 目前瀏覽頁數
	$isrecache = isset($_GET['recache']) ? true : false; // 是否強制重新生成快取
	if($page < 1) $page = 1;
	if(!$category) error(_T('category_nokeyword'));

	// 利用Session快取類別標籤出現篇別以減少負擔
	session_start(); // 啟動Session
	if(!isset($_SESSION['loglist_'.$category]) || $isrecache){
		$loglist = $PIO->searchCategory($category);
		$_SESSION['loglist_'.$category] = serialize($loglist);
	}else $loglist = unserialize($_SESSION['loglist_'.$category]);
	$loglist_count = count($loglist);
	if(!$loglist_count) error(_T('category_notfound'));
	$page_max = ceil($loglist_count / PAGE_DEF); if($page > $page_max) $page = $page_max; // 總頁數

	// 分割陣列取出適當範圍作分頁之用
	$loglist_cut = array_slice($loglist, PAGE_DEF * ($page - 1), PAGE_DEF); // 取出特定範圍文章
	$loglist_cut_count = count($loglist_cut);

	$dat = '';
	head($dat);
	$links = '[<a href="'.PHP_SELF2.'?'.time().'">'._T('return').'</a>][<a href="'.PHP_SELF.'?mode=category&amp;c='.$category_enc.'&amp;recache=1">'._T('category_recache').'</a>]';
	$PMS->useModuleMethods('LinksAboveBar', array(&$links,'category'));
	$dat .= "<div>$links</div>\n";
	for($i = 0; $i < $loglist_cut_count; $i++){
		$posts = $PIO->fetchPosts($loglist_cut[$i]); // 取得文章內容
		$dat .= arrangeThread($PTE, 0, 0, $posts, 0, $loglist_cut[$i], 0, 0, 0, 0, false); // 逐個輸出 (引用連結不顯示)
	}

	$dat .= '<table border="1"><tr>';
	if($page > 1) $dat .= '<td><form action="'.PHP_SELF.'?mode=category&amp;c='.$category_enc.'&amp;p='.($page - 1).'" method="post"><div><input type="submit" value="'._T('prev_page').'" /></div></form></td>';
	else $dat .= '<td style="white-space: nowrap;">'._T('first_page').'</td>';
	$dat .= '<td>';
	for($i = 1; $i <= $page_max ; $i++){
		if($i==$page) $dat .= "[<b>".$i."</b>] ";
		else $dat .= '[<a href="'.PHP_SELF.'?mode=category&amp;c='.$category_enc.'&amp;p='.$i.'">'.$i.'</a>] ';
	}
	$dat .= '</td>';
	if($page < $page_max) $dat .= '<td><form action="'.PHP_SELF.'?mode=category&amp;c='.$category_enc.'&amp;p='.($page + 1).'" method="post"><div><input type="submit" value="'._T('next_page').'" /></div></form></td>';
	else $dat .= '<td style="white-space: nowrap;">'._T('last_page').'</td>';
	$dat .= '</tr></table>'."\n";

	foot($dat);
	echo $dat;
}

/* 顯示已載入模組資訊 */
function listModules(){
	global $PMS, $language;
	$dat = '';
	head($dat);
	$links = '[<a href="'.PHP_SELF2.'?'.time().'">'._T('return').'</a>]';
	$PMS->useModuleMethods('LinksAboveBar', array(&$links,'modules'));
	$dat .= '<div id="banner">'.$links.'<div class="bar_admin">'._T('module_info_top').'</div>
</div>

<div id="modules">
';
	/* Module Loaded */
	$dat .= _T('module_loaded').'<ul>'."\n";
	foreach($PMS->getLoadedModules() as $m){
		$dat .= '<li>'.$m."</li>\n";
	}
	$dat .= "</ul><hr />\n";

	/* Module Infomation */
	$dat .= _T('module_info').'<ul>'."\n";
	foreach($PMS->moduleInstance as $m){
		$dat .= '<li>'.$m->getModuleName().'<div style="padding-left:2em;">'.$m->getModuleVersionInfo()."</div></li>\n";
	}
	$dat .= '</ul><hr />
</div>

';
	foot($dat);
	echo $dat;
}

/* 顯示系統各項資訊 */
function showstatus(){
	global $PTE, $PIO, $FileIO, $PMS, $language;
	$countline = $PIO->postCount(); // 計算投稿文字記錄檔目前資料筆數
	$counttree = $PIO->threadCount(); // 計算樹狀結構記錄檔目前資料筆數
	$tmp_total_size = total_size(); // 附加圖檔使用量總大小
	$tmp_log_ratio = $countline / LOG_MAX; // 記錄檔使用量
	$tmp_ts_ratio = $tmp_total_size / STORAGE_MAX; // 附加圖檔使用量

	// 決定「記錄檔使用量」提示文字顏色
  	if($tmp_log_ratio < 0.3 ) $clrflag_log = '235CFF';
	elseif($tmp_log_ratio < 0.5 ) $clrflag_log = '0CCE0C';
	elseif($tmp_log_ratio < 0.7 ) $clrflag_log = 'F28612';
	elseif($tmp_log_ratio < 0.9 ) $clrflag_log = 'F200D3';
	else $clrflag_log = 'F2004A';

	// 決定「附加圖檔使用量」提示文字顏色
  	if($tmp_ts_ratio < 0.3 ) $clrflag_sl = '235CFF';
	elseif($tmp_ts_ratio < 0.5 ) $clrflag_sl = '0CCE0C';
	elseif($tmp_ts_ratio < 0.7 ) $clrflag_sl = 'F28612';
	elseif($tmp_ts_ratio < 0.9 ) $clrflag_sl = 'F200D3';
	else $clrflag_sl = 'F2004A';

	// 生成預覽圖物件資訊及功能是否正常
	$func_thumbWork = '<span style="color: red;">'._T('info_nonfunctional').'</span>';
	$func_thumbInfo = '(No thumbnail)';
	if(USE_THUMB !== 0){
		$thumbType = USE_THUMB; if(USE_THUMB==1){ $thumbType = 'gd'; }
		require('./lib/thumb/thumb.'.$thumbType.'.php');
		$thObj = new ThumbWrapper();
		if($thObj->isWorking()) $func_thumbWork = '<span style="color: blue;">'._T('info_functional').'</span>';
		$func_thumbInfo = $thObj->getClass();
		unset($thObj);
	}

	$dat = '';
	head($dat);
	$links = '[<a href="'.PHP_SELF2.'?'.time().'">'._T('return').'</a>] [<a href="'.PHP_SELF.'?mode=moduleloaded">'._T('module_info_top').'</a>]';
	$PMS->useModuleMethods('LinksAboveBar', array(&$links,'status'));
	$dat .= '<div id="banner">'.$links.'<div class="bar_admin">'._T('info_top').'</div>
</div>
';

	$dat .= '
<div id="status-table" style="text-align: center;">
<table border="1" style="margin: 0px auto; text-align: left;">
<tr><td align="center" colspan="3">'._T('info_basic').'</td></tr>
<tr><td style="width: 240px;">'._T('info_basic_ver').'</td><td colspan="2"> '.PIXMICAT_VER.' </td></tr>
<tr><td>'._T('info_basic_pio').'</td><td colspan="2"> '.PIXMICAT_BACKEND.' : '.$PIO->pioVersion().'</td></tr>
<tr><td>'._T('info_basic_threadsperpage').'</td><td colspan="2"> '.PAGE_DEF.' '._T('info_basic_threads').'</td></tr>
<tr><td>'._T('info_basic_postsperpage').'</td><td colspan="2"> '.RE_DEF.' '._T('info_basic_posts').'</td></tr>
<tr><td>'._T('info_basic_postsinthread').'</td><td colspan="2"> '.RE_PAGE_DEF.' '._T('info_basic_posts').' '._T('info_basic_posts_showall').'</td></tr>
<tr><td>'._T('info_basic_bumpposts').'</td><td colspan="2"> '.MAX_RES.' '._T('info_basic_posts').' '._T('info_basic_0disable').'</td></tr>
<tr><td>'._T('info_basic_bumphours').'</td><td colspan="2"> '.MAX_AGE_TIME.' '._T('info_basic_hours').' '._T('info_basic_0disable').'</td></tr>
<tr><td>'._T('info_basic_urllinking').'</td><td colspan="2"> '.AUTO_LINK.' '._T('info_0no1yes').'</td></tr>
<tr><td>'._T('info_basic_com_limit').'</td><td colspan="2"> '.COMM_MAX._T('info_basic_com_after').'</td></tr>
<tr><td>'._T('info_basic_anonpost').'</td><td colspan="2"> '.ALLOW_NONAME.' '._T('info_basic_anonpost_opt').'</td></tr>
<tr><td>'._T('info_basic_del_incomplete').'</td><td colspan="2"> '.KILL_INCOMPLETE_UPLOAD.' '._T('info_0no1yes').'</td></tr>
<tr><td>'._T('info_basic_use_sample',THUMB_Q).'</td><td colspan="2"> '.USE_THUMB.' '._T('info_0notuse1use').'</td></tr>
<tr><td>'._T('info_basic_useblock').'</td><td colspan="2"> '.BAN_CHECK.' '._T('info_0disable1enable').'</td></tr>
<tr><td>'._T('info_basic_showid').'</td><td colspan="2"> '.DISP_ID.' '._T('info_basic_showid_after').'</td></tr>
<tr><td>'._T('info_basic_cr_limit').'</td><td colspan="2"> '.BR_CHECK._T('info_basic_cr_after').'</td></tr>
<tr><td>'._T('info_basic_timezone').'</td><td colspan="2"> GMT '.TIME_ZONE.'</td></tr>
<tr><td>'._T('info_basic_threadcount').'</td><td colspan="2"> '.$counttree.' '._T('info_basic_threads').'</td></tr>
<tr><td>'._T('info_basic_theme').'</td><td colspan="2"> '.$PTE->BlockValue('THEMENAME').' '.$PTE->BlockValue('THEMEVER').'<br/>by '.$PTE->BlockValue('THEMEAUTHOR').'</td></tr>
<tr><td align="center" colspan="3">'._T('info_dsusage_top').'</td></tr>
<tr align="center"><td>'._T('info_dsusage_max').'</td><td>'.LOG_MAX.'</td><td rowspan="2">'._T('info_dsusage_usage').'<br /><span style="color: #'.$clrflag_log.';">'.substr(($tmp_log_ratio * 100), 0, 6).'</span> %</td></tr>
<tr align="center"><td>'._T('info_dsusage_count').'</td><td><span style="color: #'.$clrflag_log.';">'.$countline.'</span></td></tr>
<tr><td align="center" colspan="3">'._T('info_fileusage_top').STORAGE_LIMIT.' '._T('info_0disable1enable').'</td></tr>';

	if(STORAGE_LIMIT){
		$dat .= '
<tr align="center"><td>'._T('info_fileusage_limit').'</td><td>'.STORAGE_MAX.' KB</td><td rowspan="2">'._T('info_dsusage_usage').'<br /><span style="color: #'.$clrflag_sl.'">'.substr(($tmp_ts_ratio * 100), 0, 6).'</span> %</td></tr>
<tr align="center"><td>'._T('info_fileusage_count').'</td><td><span style="color: #'.$clrflag_sl.'">'.$tmp_total_size.' KB</span></td></tr>';
	}else{
		$dat .= '
<tr align="center"><td>'._T('info_fileusage_count').'</td><td>'.$tmp_total_size.' KB</td><td>'._T('info_dsusage_usage').'<br /><span style="color: green;">'._T('info_fileusage_unlimited').'</span></td></tr>';
	}

	$dat .= '
<tr><td align="center" colspan="3">'._T('info_server_top').'</td></tr>
<tr align="center"><td colspan="2">'.$func_thumbInfo.'</td><td>'.$func_thumbWork.'</td></tr>
</table>
<hr />
</div>'."\n";

	foot($dat);
	echo $dat;
}

/* 程式首次執行之初始化 */
function init(){
	global $PIO, $FileIO, $language;
	if(!is_writable(realpath('./'))) error(_T('init_permerror'));

	$chkfolder = array(IMG_DIR, THUMB_DIR);
	// 逐一自動建置IMG_DIR和THUMB_DIR
	foreach($chkfolder as $value) if(!is_dir($value)){ mkdir($value); @chmod($value, 0777); }  // 沒有就建立

	$PIO->dbInit(); // PIO Init
	$FileIO->init(); // FileIO Init

	error(_T('init_inited'));
}

/*-----------程式各項功能主要判斷-------------*/
if(GZIP_COMPRESS_LEVEL && ($Encoding = CheckSupportGZip())){ ob_start(); ob_implicit_flush(0); } // 支援且開啟Gzip壓縮就設緩衝區
$mode = isset($_GET['mode']) ? $_GET['mode'] : ''; // 目前執行模式
if($mode=='' && isset($_POST['mode'])) $mode = $_POST['mode']; // 如果GET找不到，就用POST
if($mode != 'module'){ $PMS->init(); } // 載入所有模組

//init(); // ←■■！程式環境初始化，跑過一次後請刪除此行！■■
switch($mode){
	case 'regist':
		regist();
		break;
	case 'admin':
		$admin = isset($_POST['admin']) ? $_POST['admin'] : ''; // 管理者執行模式
		valid();
		if($admin=='del') admindel();
		if($admin=='opt'){
			if(!$PIO->dbOptimize()) echo _T('action_opt_notsupport');
			else echo _T('action_opt_optimize').($PIO->dbOptimize(true)?_T('action_opt_success'):_T('action_opt_failed'));
			die("</div></form></body>\n</html>");
		}
		break;
	case 'search':
		search();
		break;
	case 'status':
		showstatus();
		break;
	case 'category':
		searchCategory();
		break;
	case 'module':
		$loadModule = isset($_GET['load']) ? $_GET['load'] : '';
		// 僅載入指定模組
		if($PMS->init($loadModule) && array_search($loadModule, $PMS->hookPoints['ModulePage'])!==false){
			$PMS->moduleInstance[$loadModule]->ModulePage();
		}else{
			echo '404 Not Found';
		}
		break;
	case 'moduleloaded':
		listModules();
		break;
	case 'usrdel':
		usrdel();
	case 'remake':
		updatelog();
		header('HTTP/1.1 302 Moved Temporarily');
		header('Location: '.fullURL().PHP_SELF2.'?'.time());
		break;
	default:
		$res = isset($_GET['res']) ? $_GET['res'] : 0; // 欲回應編號
		if($res){ // 回應模式輸出
			$page = isset($_GET['page_num']) ? $_GET['page_num'] : 'RE_PAGE_MAX';
			if(!($page=='all' || $page=='RE_PAGE_MAX')) $page = intval($_GET['page_num']);
			updatelog($res, $page); // 實行分頁
		}elseif(@intval($_GET['page_num']) > 0){ // PHP動態輸出一頁
			updatelog(0, intval($_GET['page_num']));
		}else{ // 導至靜態庫存頁
			if(!is_file(PHP_SELF2)) updatelog();
			header('HTTP/1.1 302 Moved Temporarily');
			header('Location: '.fullURL().PHP_SELF2.'?'.time());
		}
}
if($Encoding && GZIP_COMPRESS_LEVEL){ // 有啟動Gzip
	if(!ob_get_length()) exit; // 沒內容不必壓縮
	header('Content-Encoding: '.$Encoding);
	header('X-Content-Encoding-Level: '.GZIP_COMPRESS_LEVEL);
	header('Vary: Accept-Encoding');
	print gzencode(ob_get_clean(), GZIP_COMPRESS_LEVEL); // 壓縮內容
}
?>