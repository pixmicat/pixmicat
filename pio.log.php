<?php
$porder=array();
$torder=array();
$logs=array();
$trees=array();
$restono=array();
$prepared=0;

/* PIO模組版本 */
function pioVersion() {
	return 'v20060706α';
}

/* 處理連線字串/連接 */
function dbConnect($connStr=CONNECTION_STRING) {
	if($connStr){ // 有連線字串
		if(preg_match('/^log:\/\/(.*);(.*)\/$/i', $connStr, $linkinfos)){
			define('LOGFILE', $linkinfos[1]); // 投稿文字記錄檔檔名
			define('TREEFILE', $linkinfos[2]); // 樹狀結構記錄檔檔名
		}
	}
}

/* 初始化 */
function dbInit() {
	$chkfile = array(LOGFILE, TREEFILE);
	// 逐一自動建置tree及log檔案
	foreach($chkfile as $value){
		$is_executed = true;
		if(!is_file($value)){ // 檔案不存在
			$fp = fopen($value, 'w');
			stream_set_write_buffer($fp, 0);
			if($value==LOGFILE) fwrite($fp, '1,05/01/01(六) 00:00 ID:00000000,無名氏,,無標題,無內文,,,,,,,,');
			if($value==TREEFILE) fwrite($fp, '1');
			fclose($fp);
			unset($fp);
			@chmod($value, 0666);
		}
	}
}

/* 準備/讀入 */
function dbPrepare($reload=false) {
	global $porder,$torder,$logs,$restono,$trees,$prepared;
	if($prepared && !$reload) return true;
	$lines = file(LOGFILE);
	$tree = file(TREEFILE);

	foreach($lines as $line) {
		if($line=='') continue;
		$tline=array();
		list($tline['no'],$tline['now'],$tline['name'],$tline['email'],$tline['sub'],$tline['com'],$tline['url'],$tline['host'],$tline['pw'],$tline['ext'],$tline['w'],$tline['h'],$tline['time'],$tline['chk'])=explode(',', $line);
		$porder[]=$tline['no'];
		$logs[$tline['no']]=array_reverse($tline); // list()是由右至左代入的
	}
	
	foreach($tree as $treeline) {
		if($treeline=='') continue;
		$tline=explode(',', rtrim($treeline));
		$trees[$tline[0]]=$tline;
		$torder[]=$tline[0];
		foreach($tline as $post) $restono[$post]=$tline[0];
	}
	
	$prepared=1;
}

/* 提交/儲存 */
function dbCommit() {
	global $porder,$torder,$logs,$trees,$prepared;
	if(!$prepared) return false;
	$pcount=postCount();
	$tcount=threadCount();
	
	if($pcount>=LOG_MAX) delOldPostes();
	
	$log=$tree='';
	for($post=0;$post<$pcount;$post++)
		$log.=is_Post($porder[$post])?implode(',',$logs[$porder[$post]]).",\n":'';

	for($tline=0;$tline<$tcount;$tline++)
		$tree.=is_Thread($torder[$tline])?implode(',',$trees[$torder[$tline]])."\n":'';

	$fp = fopen(LOGFILE, 'w');
	stream_set_write_buffer($fp, 0);
	flock($fp, LOCK_EX); // 鎖定檔案
	fwrite($fp, $log);
	flock($fp, LOCK_UN); // 解鎖
	fclose($fp);

	$fp = fopen(TREEFILE, 'w');
	stream_set_write_buffer($fp, 0);
	flock($fp, LOCK_EX); // 鎖定檔案
	fwrite($fp, $tree);
	flock($fp, LOCK_UN); // 解鎖
	fclose($fp);
}

/* 優化資料表 */
function dbOptimize($doit=false) {
	return false; // 不支援
}

/* 刪除舊文 */
function delOldPostes() {
	global $porder,$torder,$restono,$logs,$trees;
	$delPosts=@array_splice($porder,LOG_MAX);
	if(count($delPosts)) {
		foreach($delPosts as $post) {
			if($restono[$post]==$post) { // 討論串頭
				$delPosts=array_merge($delPosts,$trees[$post]);
			}
		}
		$delPosts=array_unique($delPosts);
		return removePosts($delPosts);
	} else return false;
}

/* 刪除文章 */
function removePosts($posts) {
	global $porder,$torder,$restono,$logs,$trees;
	$files=removeAttachments($posts);
	$porder_flip=array_flip($porder);
	$torder_flip=array_flip($torder);
	$pcount=count($posts);
	for($post=0;$post<$pcount;$post++) {
		if(!is_Post($posts[$post])) continue;
		if($restono[$posts[$post]]==$post) {
			unset($trees[$posts[$post]]);
			if(@$torder_flip[$posts[$post]]) unset($torder[$torder_flip[$posts[$post]]]);
		}
		unset($logs[$posts[$post]]);
		if(@$trees[$restono[$posts[$post]]]) {
			$tr_flip=array_flip($trees[$restono[$posts[$post]]]);
			unset($trees[$restono[$posts[$post]]][$tr_flip[$posts[$post]]]);
		}
		unset($restono[$posts[$post]]);
		if(@$porder_flip[$posts[$post]]) unset($porder[$porder_flip[$posts[$post]]]);
	}
	$porder=array_merge(array(),$porder);
	$torder=array_merge(array(),$torder);
	return $files;
}

/* 刪除舊附件 (輸出附件清單) */
function delOldAttachments($total_size,$storage_max,$warnOnly=true) {
	global $porder,$logs,$path;
	$rpord=rsort($porder);
	$arr_warn=$arr_kill=array();
	foreach($rpord as $post) {
		if(file_func('exist',$path.IMG_DIR.$logs[$post]['time'].$logs[$post]['ext'])) { $total_size -= file_func('size',$path.IMG_DIR.$logs[$post]['time'].$logs[$post]['ext']) / 1024; $arr_kill[] = $post;$arr_warn[$post] = 1; } // 標記刪除
		if(file_func('exist',$path.THUMB_DIR.$logs[$post]['time'].'s.jpg')) { $total_size -= file_func('size',$path.THUMB_DIR.$logs[$post]['time'].'s.jpg') / 1024; }
		if($total_size<$storage_max) break;
	}
	return $warnOnly?$arr_warn:removeAttachments($arr_kill);
}

/* 刪除附件 (輸出附件清單) */
function removeAttachments($posts) {
	global $logs,$path;
	$files=array();
	foreach($posts as $post) {
		if($logs[$post]['ext']) {
			if(file_func('exist',$path.IMG_DIR.$logs[$post]['time'].$logs[$post]['ext'])) $files[]=IMG_DIR.$logs[$post]['time'].$logs[$post]['ext'];
			if(file_func('exist',$path.THUMB_DIR.$logs[$post]['time'].'s.jpg')) $files[]=THUMB_DIR.$logs[$post]['time'].'s.jpg';
			$logs[$post]['ext']='';
		}
	}
	return $files;
}

/* 文章數目 */
function postCount($resno=0) {
	global $porder,$trees,$prepared;
	if(!$prepared) dbPrepare();
	return ($resno)?is_Thread($resno)?count(@$trees[$resno])-1:0:count($porder);
}

/* 討論串數目 */
function threadCount() {
	global $torder,$prepared;
	if(!$prepared) dbPrepare();
	return count($torder);
}

/* 輸出文章清單 */
function fetchPostList($resno=0,$start=0,$amount=0) {
	global $porder,$trees,$prepared;
	if(!$prepared) dbPrepare();
	return $resno?($amount?array_slice($trees[$resno],$start,$amount):$trees[$resno]):($amount?array_slice($porder,$start,$amount):$porder);
}

/* 輸出討論串清單 */
function fetchThreadList($start=0,$amount=0) {
	global $porder,$torder,$logs,$trees,$prepared;
	if(!$prepared) dbPrepare();
	return $amount?array_slice($torder,$start,$amount):$torder;
}

/* 輸出文章 */
function fetchPosts($postlist) {
	global $porder,$torder,$logs,$trees,$prepared;
	if(!$prepared) dbPrepare();
	$posts=array();
	if(!is_array($postlist)) { // Single Post
		$posts=$logs[$postlist];
	} else {
		foreach($postlist as $p) array_push($posts,$logs[$p]);
	}
	return $posts;
}

/* 有此討論串? */
function is_Thread($no) {
	global $torder,$logs,$trees,$prepared;
	if(!$prepared) dbPrepare();
	return isset($trees[$no]);
}

/* 有此文章? */
function is_Post($no) {
	global $torder,$logs,$trees,$prepared;
	if(!$prepared) dbPrepare();
	return isset($logs[$no]);
}

/* 搜尋文章 */
function searchPost($keyword,$field,$method) {
	global $logs,$prepared;
	if(!$prepared) dbPrepare();
	$foundPosts=array();
	$keyword_cnt=count($keyword);
	foreach($logs as $log) {
		$found=0;
		foreach($keyword as $k)
			if(strpos($log[$field], $k)!==FALSE) $found++;
		if($method=="AND" && $found==$keyword_cnt) array_push($foundPosts,$log); // 全部都有找到 (AND交集搜尋)
		elseif($method=="OR" && $found) array_push($foundPosts,$log); // 有找到 (OR交集搜尋)
	}
	return $foundPosts;
}

/* 新增文章/討論串 */
function addPost($no,$resno,$now,$name,$email,$sub,$com,$url,$host,$pass,$ext,$W,$H,$tim,$chk,$age=false) {
	global $porder,$torder,$logs,$trees,$restono,$prepared;
	if(!$prepared) dbPrepare();
	$tline=array();
	list($tline['no'],$tline['now'],$tline['name'],$tline['email'],$tline['sub'],$tline['com'],$tline['url'],$tline['host'],$tline['pw'],$tline['ext'],$tline['w'],$tline['h'],$tline['time'],$tline['chk'])=array($no,$now,$name,$email,$sub,$com,$url,$host,$pass,$ext,$W,$H,$tim,$chk);
	$logs[$no]=array_reverse($tline);
	array_unshift($porder,$no);

	if($resno) {
		$trees[$resno][]=$no;
		$restono[$no]=$resno;
		if($age) {
			$torder_flip=array_flip($torder);
			array_splice($torder,$torder_flip[$resno],1);
			array_unshift($torder,$resno);
		}
	} else {
		$trees[$no][0]=$no;
		$restono[$no]=$no;
		array_unshift($torder,$no);
	}
}

/* 停止討論串 */
function stopThread($no) {
	global $porder,$torder,$logs,$trees,$prepared;
	if(!$prepared) dbPrepare();

	if(is_array($no))
		foreach($no as $n)
			if(is_Thread($n)) 
				$logs[$n]['url']=($logs[$n]['url']=='')?'_THREADSTOP_':'';
	else {
		if(is_Thread($no))
			$logs[$no]['url']=($logs[$no]['url']=='')?'_THREADSTOP_':'';
	}
}
?>