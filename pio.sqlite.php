<?php
/*
PIO - Pixmicat! database I/O
SQLite API
*/
$prepared = 0;

/* private 使用SQL字串和SQLite要求 */
function _sqlite_call($query){
	global $con;
	$ret = @sqlite_query($con,$query);
	if(!$ret) error('SQLite SQL指令錯誤：<p />指令: '.$query);
	return $ret;
}

/* private 輸出符合標準的索引鍵陣列 */
function _ArrangeArrayStructure($line){
	global $con;

	$posts = array();
	$arrIDKey = array('no'=>'', 'now'=>'', 'name'=>'', 'email'=>'', 'sub'=>'', 'com'=>'', 'status'=>'url', 'host'=>'', 'pwd'=>'pw', 'ext'=>'', 'w'=>'', 'h'=>'', 'tim'=>'time', 'md5'=>'chk'); // SQLite 欄位鍵 => 標準欄位鍵
	while($row=sqlite_fetch_array($line, SQLITE_ASSOC)){
		$tline = array();
		foreach($arrIDKey as $mID => $mVal) $tline[($mVal ? $mVal : $mID)] = $row[$mID]; // 逐個取值並代入
		$posts[] = $tline;
	}
	return $posts;
}

/* SQLite的sqlite_result頂替函數 */
/* private */ function sqlite_result($rh,$row,$field){
	$currrow=sqlite_fetch_all($rh,SQLITE_NUM);
	return $currrow[$row][$field];
}

/* PIO模組版本 */
/* 輸入 void, 輸出 版本號 as string */
function pioVersion() {
	return 'v20060710α';
}

/* 處理連線字串/連接 */
/* 輸入 連線字串 as string, 輸出 void */
function dbConnect($connStr){
	// 格式： sqlite://SQLite檔案之位置/資料表/
	// 示例： sqlite://pixmicat.db/imglog/
	if(preg_match('/^sqlite:\/\/(.*)\/(.*)\/$/i', $connStr, $linkinfos)){
		define('SQLITE_DBF', $linkinfos[1]); // SQLite檔案之位置
		define('SQLLOG', $linkinfos[2]); // 資料表名稱
	}
}

/* 初始化 */
/* 輸入 void, 輸出 void */
function dbInit(){
	global $con, $prepared;
	dbPrepare();
	if(sqlite_num_rows(sqlite_query($con,"select name from sqlite_master where name like '".SQLLOG."'"))===0){ // 資料表不存在
		$is_executed = true;
		$result = 'CREATE TABLE '.SQLLOG.' (
"no" INTEGER  NOT NULL PRIMARY KEY,
"resto" INTEGER  NOT NULL,
"root" TIMESTAMP DEFAULT \'0\' NOT NULL,
"time" INTEGER  NOT NULL,
"md5" VARCHAR(32)  NOT NULL,
"tim" INTEGER  NOT NULL,
"ext" VARCHAR(5)  NOT NULL,
"w" INTEGER  NOT NULL,
"h" INTEGER  NOT NULL,
"pwd" VARCHAR(8)  NOT NULL,
"now" VARCHAR(255)  NOT NULL,
"name" VARCHAR(255)  NOT NULL,
"email" VARCHAR(255)  NOT NULL,
"sub" VARCHAR(255)  NOT NULL,
"com" TEXT  NOT NULL,
"host" VARCHAR(255)  NOT NULL,
"status" VARCHAR(4)  NOT NULL
);';
		
		$idx = array('resto', 'root', 'time');
		foreach($idx as $x) {
			$result .= 'CREATE INDEX IDX_'.SQLLOG.'_'.$x.' ON '.SQLLOG.'('.$x.');';
		}
		$result .= 'CREATE INDEX IDX_'.SQLLOG.'_resto_no ON '.SQLLOG.'(resto,no);';
		$result .= 'INSERT INTO '.SQLLOG.' (resto,root,time,md5,tim,ext,w,h,pwd,now,name,email,sub,com,host,status) VALUES (0, datetime("now"), 1111111111, "", 1111111111111, "", 0, 0, "", "05/01/01(六)00:00 ID:00000000", "無名氏", "", "無標題", "無內文", "", "");';
		sqlite_exec($con, $result); // 正式新增資料表
		dbCommit();
	}
}

/* 準備/讀入 */
/* 輸入 是否重作 as boolean, 輸出 void */
function dbPrepare($reload=false,$transaction=true){
	global $con, $prepared;
	if($prepared && !$reload) return true;

	if($reload && $con) sqlite_close($con);
	if(@!$con=sqlite_popen(SQLITE_DBF,0666,$sqliteerrmsg)){
		echo $sqliteerrmsg;
	}
	if($transaction) @sqlite_exec($con,'BEGIN;'); // 啟動交易性能模式

	$prepared = 1;
}

/* 提交/儲存 */
/* 輸入 void, 輸出 void */
function dbCommit(){
	global $con, $prepared;
	if(!$prepared) return false;

	@sqlite_exec($con,'COMMIT;'); // 交易性能模式提交
}

/* 優化資料表 */
/* 輸入 是否作 as boolean, 輸出 優化成果 as boolean */
function dbOptimize($doit=false){
	if($doit){
		dbPrepare(true,false);
		if(_sqlite_call('VACUUM '.SQLLOG)) return true;
		else return false;
	}else return true; // 支援最佳化資料表
}

/* 刪除舊文 */
/* 輸入 void, 輸出 舊文之附加檔案列表 as array */
function delOldPostes(){
	global $con, $path;
	$oldAttachments = array(); // 舊文的附加檔案清單
	$countline = postCount(); // 文章數目
	$cutIndex = $countline - LOG_MAX + 1; // LIMIT用，取出最舊的幾篇
	if(!$result=_sqlite_call('SELECT no,ext,tim FROM '.SQLLOG." WHERE ext <> '' ORDER BY no LIMIT 0, ".$cutIndex)) echo '[ERROR] 取出舊文失敗<br />';
	else{
		while(list($dno, $dext, $dtim)=sqlite_fetch_array($result)){ // 個別跑舊文迴圈
			$dfile = $path.IMG_DIR.$dtim.$dext; // 附加檔案名稱
			$dthumb = $path.THUMB_DIR.$dtim.'s.jpg'; // 預覽檔案名稱
			if(file_func('exist', $dfile)) $oldAttachments[] = $dfile;
			if(file_func('exist', $dthumb)) $oldAttachments[] = $dthumb;
			// 逐次搜尋舊文之回應
			if(!$resultres=_sqlite_call('SELECT ext,tim FROM '.SQLLOG." WHERE ext <> '' AND resto = $dno")) echo '[ERROR] 取出舊文之回應失敗<br />';
			while(list($rext, $rtim)=sqlite_fetch_array($resultres)){
				$rfile = $path.IMG_DIR.$rtim.$rext; // 附加檔案名稱
				$rthumb = $path.THUMB_DIR.$rtim.'s.jpg'; // 預覽檔案名稱
				if(file_func('exist', $rfile)) $oldAttachments[] = $rfile;
				if(file_func('exist', $rthumb)) $oldAttachments[] = $rthumb;
			}
			if(!_sqlite_call('DELETE FROM '.SQLLOG.' WHERE no = '.$dno.' OR resto = '.$dno)) echo '[ERROR] 刪除舊文及其回應失敗<br />'; // 刪除文章
		}
	}
	return $oldAttachments; // 回傳需刪除檔案列表
}

/* 刪除文章 */
/* 輸入 文章編號 as array, 輸出 刪除附加檔案列表 as array */
function removePosts($posts){
	global $con, $prepared;
	if(!$prepared) dbPrepare();

	$files = removeAttachments($posts); // 先取得刪除文章附件清單
	$pno = implode(', ', $posts); // ID字串
	if(!$result=_sqlite_call('DELETE FROM '.SQLLOG.' WHERE no IN ('.$pno.') OR resto IN('.$pno.')')) echo '[ERROR] 刪除文章及其回應失敗<br />'; // 刪掉文章
	return $files;
}

/* 刪除舊附件 (輸出附件清單) */
/* 輸入 附加檔案總容量 as integer, 限制檔案儲存量 as integer, 只警告 as boolean, 輸出 警告旗標 / 舊附件列表 as array */
function delOldAttachments($total_size,$storage_max,$warnOnly=true){
	global $con, $path;
	$arr_warn = $arr_kill = array(); // 警告 / 即將被刪除標記陣列
	if(!$result=_sqlite_call('SELECT no,ext,tim FROM '.SQLLOG." WHERE ext <> '' ORDER BY no")) echo '[ERROR] 取出舊文失敗<br />';
	else{
		while(list($dno, $dext, $dtim)=sqlite_fetch_array($result)){ // 個別跑舊文迴圈
			$dfile = $path.IMG_DIR.$dtim.$dext; // 附加檔案名稱
			$dthumb = $path.THUMB_DIR.$dtim.'s.jpg'; // 預覽檔案名稱
			if(file_func('exist', $dfile)){ $total_size -= file_func('size', $dfile) / 1024; $arr_kill[] = $dno; $arr_warn[$dno] = 1; } // 標記刪除
			if(file_func('exist', $dthumb)) $total_size -= file_func('size', $dthumb) / 1024;
			if($total_size < $storage_max) break;
		}
	}
	return $warnOnly ? $arr_warn : removeAttachments($arr_kill);
}

/* 刪除附件 (輸出附件清單) */
/* 輸入 文章編號 as array, 輸出 刪除附件列表 as array */
function removeAttachments($posts){
	global $con, $path;

	$files = array();
	$pno = implode(', ', $posts); // ID字串
	if(!$result=_sqlite_call('SELECT tim,ext FROM '.SQLLOG.' WHERE no IN ('.$pno.') OR resto IN('.$pno.")) AND ext <> ''")) echo '[ERROR] 取出附件清單失敗<br />';
	else{
		while(list($dext, $dtim)=sqlite_fetch_array($result)){ // 個別跑迴圈
			$dfile = $path.IMG_DIR.$dtim.$dext; // 附加檔案名稱
			$dthumb = $path.THUMB_DIR.$dtim.'s.jpg'; // 預覽檔案名稱
			if(file_func('exist', $dfile)) $files[] = $dfile;
			if(file_func('exist', $dthumb)) $files[] = $dthumb;
		}
	}
	return $files;
}

/* 文章數目 */
/* 輸入 討論串ID as integer, 輸出 討論串文章 / 總文章數目 as integer */
function postCount($resno=0){
	global $con, $prepared;
	if(!$prepared) dbPrepare();

	if($resno){ // 回傳討論串總回應數目 (含本文故要加1)
		$line = _sqlite_call('SELECT COUNT(no) FROM '.SQLLOG.' WHERE resto = '.$resno);
		$countline = sqlite_result($line, 0, 0) + 1;
	}else{ // 回傳總文章數目
		$line = _sqlite_call('SELECT COUNT(no) FROM '.SQLLOG);
		$countline = sqlite_result($line, 0, 0);
	}
	return $countline;
}

/* 討論串數目 */
/* 輸入 void, 輸出 討論串數目 as integer */
function threadCount(){
	global $con, $prepared;
	if(!$prepared) dbPrepare();

	$tree = _sqlite_call('SELECT COUNT(no) FROM '.SQLLOG.' WHERE resto = 0');
	$counttree = sqlite_result($tree, 0, 0); // 計算討論串目前資料筆數
	return $counttree;
}

/* 輸出文章清單 */
/* 輸入 討論串編號, 開始值, 數目 as integer, 輸出 討論串結構 as array */
function fetchPostList($resno=0,$start=0,$amount=0){
	global $con, $prepared;
	if(!$prepared) dbPrepare();

	$line = array();
	if($resno){ // 輸出討論串的結構 (含自己, EX : 1,2,3,4,5,6)
		$tmpSQL = 'SELECT no FROM '.SQLLOG.' WHERE no = '.$resno.' OR resto = '.$resno.' ORDER BY no';
	}else{ // 輸出所有文章編號，新的在前
		$tmpSQL = 'SELECT no FROM '.SQLLOG.' ORDER BY no DESC';
		if($amount) $tmpSQL .= " LIMIT {$start}, {$amount}"; // 有指定數量才用 LIMIT
	}
	$tree = _sqlite_call($tmpSQL);
	while($rows=sqlite_fetch_array($tree)) $line[] = $rows[0]; // 迴圈

	return $line;
}

/* 輸出討論串清單 */
/* 輸入 開始值, 數目 as integer, 輸出 討論串首篇編號 as array */
function fetchThreadList($start=0,$amount=0) {
	global $con, $prepared;
	if(!$prepared) dbPrepare();

	$treeline = array();
	$tmpSQL = 'SELECT no FROM '.SQLLOG.' WHERE resto = 0 ORDER BY root DESC';
	if($amount) $tmpSQL .= " LIMIT {$start}, {$amount}"; // 有指定數量才用 LIMIT
	$tree = _sqlite_call($tmpSQL);
	while($rows=sqlite_fetch_array($tree)) $treeline[] = $rows[0]; // 迴圈

	return $treeline;
}

/* 輸出文章 */
/* 輸入 文章編號 as array, 輸出 文章資料 as array */
function fetchPosts($postlist){
	global $con, $prepared;
	if(!$prepared) dbPrepare();

	if(is_array($postlist)){ // 取多串
		$pno = implode(', ', $postlist); // ID字串
		$tmpSQL = 'SELECT * FROM '.SQLLOG.' WHERE no IN ('.$pno.') ORDER BY no';
		if(count($postlist) > 1){ if($postlist[0] > $postlist[1]) $tmpSQL .= ' DESC'; } // 由大排到小
	}else $tmpSQL = 'SELECT * FROM '.SQLLOG.' WHERE no = '.$postlist; // 取單串
	$line = _sqlite_call($tmpSQL);

	return _ArrangeArrayStructure($line); // 重排陣列結構
}

/* 有此討論串? */
/* 輸入 討論串編號 as integer, 輸出 是否存在 as boolean */
function is_Thread($no){
	global $con, $prepared;
	if(!$prepared) dbPrepare();

	$result = _sqlite_call('SELECT no FROM '.SQLLOG.' WHERE no = '.$no.' AND resto = 0');
	return sqlite_fetch_array($result);
}

/* 搜尋文章 */
/* 輸入 關鍵字 as array, 搜尋目標 as string, 搜尋方式 as string, 輸出 文章資料 as array */
function searchPost($keyword,$field,$method){
	global $prepared;
	if(!$prepared) dbPrepare();

	$keyword_cnt = count($keyword);
	$SearchQuery = 'SELECT * FROM '.SQLLOG." WHERE {$field} LIKE '%".($keyword[0])."%'";
	if($keyword_cnt > 1) for($i = 1; $i < $keyword_cnt; $i++) $SearchQuery .= " {$method} {$field} LIKE '%".($keyword[$i])."%'"; // 多重字串交集 / 聯集搜尋
	$SearchQuery .= ' ORDER BY no DESC'; // 按照號碼大小排序
	if(!$line=_sqlite_call($SearchQuery)) echo '[ERROR] 搜尋文章失敗<br />';

	return _ArrangeArrayStructure($line); // 重排陣列結構
}

/* 新增文章/討論串 */
/* 輸入 各種欄位值 as any, 輸出 void */
function addPost($no,$resno,$now,$name,$email,$sub,$com,$url,$host,$pass,$ext,$W,$H,$tim,$chk,$age=false) {
	global $con, $prepared;
	if(!$prepared) dbPrepare();

	$time = (int)substr($tim, 0, -3); // 13位數的數字串是檔名，10位數的才是時間數值
	if($resno){ // 新增回應
		$rootqu = "'1980-01-01 00:00:00'";
		if($age){ // 推文
			$query = 'UPDATE '.SQLLOG.' SET root = "'.strftime("%Y-%m-%d %H:%M:%S",time()).'" WHERE no = '.$resno; // 將被回應的文章往上移動
			if(!$result=_sqlite_call($query)) echo '[ERROR] 推文失敗<br />';
		}
	}else $rootqu = strftime("%Y-%m-%d %H:%M:%S",time()); // 新增討論串, 討論串最後被更新時間

	$query = 'INSERT INTO '.SQLLOG.' (resto,root,time,md5,tim,ext,w,h,pwd,now,name,email,sub,com,host,status) VALUES ('.
(int)$resno.','. // 回應編號
$rootqu.','. // 最後更新時間
$time.','. // 發文時間數值
"'$chk',". // 附加檔案md5
"'$tim', '$ext',". // 附加檔名
(int)$W.', '.(int)$H.','. // 預覽圖長寬
"'".sqlite_escape_string($pass)."',".
"'$now',". // 時間(含ID)字串
"'".sqlite_escape_string($name)."',".
"'".sqlite_escape_string($email)."',".
"'".sqlite_escape_string($sub)."',".
"'".sqlite_escape_string($com)."',".
"'".sqlite_escape_string($host)."', '')";
	if(!$result=_sqlite_call($query)) echo '[ERROR] 新增文章失敗<br />';
}

/* 取出單一文章狀態 */
/* 輸入 狀態字串 as integer, 狀態類型 as string, 輸出 狀態值 as integer */
function getPostStatus($status, $statusType){
	global $con, $prepared;
	if(!$prepared) dbPrepare();
	$returnValue = 0; // 回傳值

	switch($statusType){
		case 'TS': // 討論串是否鎖定
			$returnValue = (strpos($status, 'T')!==false) ? 1 : 0; // 討論串是否鎖定
			break;
		default:
	}
	return $returnValue;
}

/* 設定文章狀態 */
/* 輸入 處理文章編號 as array, 舊值 as array, 狀態類型 as array, 新值 as array, 輸出 void */
function setPostStatus($no, $status, $statusType, $newValue){
	global $con, $prepared;
	if(!$prepared) dbPrepare();

	$forcount = count($no);
	for($i = 0; $i < $forcount; $i++){
		$newStatus = ''; // 討論串狀態旗標字串
		switch($statusType[$i]){
			case 'TS': // 討論串是否停止
				$newStatus = $newValue[$i] ? ($status[$i].'T') : str_replace('T', '', $status[$i]); // 更改狀態字串
				if(!_sqlite_call('UPDATE '.SQLLOG." SET status = '$newStatus' WHERE no = ".$no[$i])) echo "[ERROR] 更新討論串狀態失敗<br>"; // 更新討論串屬性
				break;
			default:
		}
	}
}

/* 取得最後文章編號 */
/* 輸入 使用狀態 as string,輸出 編號 as integer */
function getLastPostNo($state){
	global $con, $prepared;
	if(!$prepared) dbPrepare();

	if($state=='afterCommit'){ // 送出後的最後文章編號
		$tree = _sqlite_call('SELECT MAX(no) FROM '.SQLLOG);
		$lastno = sqlite_result($tree, 0, 0);
		return $lastno;
	}else return 0; // 其他狀態沒用
}
?>