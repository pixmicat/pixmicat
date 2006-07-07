<?php
$prepared = 0;

/* private 使用SQL字串和MySQL伺服器要求 */
function _mysql_call($query){
	global $con;

	$ret = mysql_query($query);
	if(!$ret) error('MySQL SQL指令錯誤：<p />指令: '.$query.'<br />錯誤訊息: (#'.mysql_errno().') '.mysql_error());
	return $ret;
}

/* private 修改特定編號討論串狀態 */
function _stopThread($tno){
	global $con;

	$line = _mysql_call('SELECT status FROM '.SQLLOG.' WHERE no = '.$tno.' AND resto = 0');
	$status = mysql_result($line, 0, 0); $status = ($status=='') ? 'T' : ''; // 取出討論串狀態並修改
	if(!_mysql_call('UPDATE '.SQLLOG." SET status = '$status' WHERE no = $tno")) echo "[ERROR] 更新討論串狀態失敗<br>"; // 更新討論串屬性
	mysql_free_result($line);
}

/* private 輸出符合標準的索引鍵陣列 */
function _ArrangeArrayStructure($line){
	global $con;

	$posts = array();
	$countline = mysql_num_rows($line); // 行數
	while($row=mysql_fetch_row($line)){
		$tline = array();
		list($tline['no'], $tline['now'], $tline['name'], $tline['email'], $tline['sub'], $tline['com'], $tline['url'], $tline['host'], $tline['pw'], $tline['ext'], $tline['w'], $tline['h'], $tline['time'], $tline['chk']) = $row;
		if($countline==1){ $posts = array_reverse($tline); break; } // 單行作法
		$posts[] = array_reverse($tline); // list()是由右至左代入的
	}
	mysql_free_result($line);
	return $posts;
}

/* PIO模組版本 */
function pioVersion() {
	return 'v20060706α';
}

/* 處理連線字串/連接 */
function dbConnect($connStr=CONNECTION_STRING){
	if($connStr){ // 有連線字串
		// 格式： mysql://帳號:密碼@伺服器位置:埠號(可省略)/資料庫/資料表/
		// 示例： mysql://pixmicat:1234@127.0.0.1/pixmicat_use/imglog/
		if(preg_match('/^mysql:\/\/(.*)\:(.*)\@(.*(?:\:[0-9]+)?)\/(.*)\/(.*)\/$/i', $connStr, $linkinfos)){
			define('MYSQL_USER', $linkinfos[1]); // 登入帳號
			define('MYSQL_PASSWORD', $linkinfos[2]); // 登入密碼
			define('MYSQL_SERVER', $linkinfos[3]); // 登入伺服器 (含埠號)
			define('MYSQL_DBNAME', $linkinfos[4]); // 資料庫名稱
			define('SQLLOG', $linkinfos[5]); // 資料表名稱
		}
	}
}

/* 初始化 */
function dbInit(){
	global $con, $prepared;
	dbPrepare();
	if(mysql_num_rows(mysql_query("SHOW TABLES LIKE '".SQLLOG."'"))!=1){ // 資料表不存在
		$result = "CREATE TABLE ".SQLLOG." (primary key(no),
index (resto),index (root),index (time),
no int(1) not null auto_increment,
resto int(1) not null,
root timestamp(14) null DEFAULT 0,
time int(1) not null,
md5 varchar(32) not null,
tim bigint(1) not null,
ext varchar(4) not null,
w smallint(1) not null,
h smallint(1) not null,
pwd varchar(8) not null,
now varchar(255) not null,
name varchar(255) not null,
email varchar(255) not null,
sub varchar(255) not null,
com text not null,
host varchar(255) not null,
status varchar(4) not null)
TYPE = MYISAM
COMMENT = 'For Pixmicat! use'";
		$result2 = @mysql_query("SHOW CHARACTER SET like 'utf8'"); // 是否支援UTF-8 (MySQL 4.1.1開始支援)
		if($result2 && mysql_num_rows($result2)){
			$result .= ' CHARACTER SET utf8 COLLATE utf8_general_ci'; // 資料表追加UTF-8編碼
			mysql_free_result($result2);
		}
		mysql_query($result); // 正式新增資料表
	}
}

/* 準備/讀入 */
function dbPrepare($reload=false){
	global $con, $prepared;
	if($prepared && !$reload) return true;

	if(@!$con=mysql_pconnect(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD)){
		echo 'It occurred a fatal error when connecting to the MySQL server.<p>';
		echo 'Check your MySQL login setting in config file or the MySQL server status.';
		exit;
	}
	@mysql_select_db(MYSQL_DBNAME, $con);
	@mysql_query("SET NAMES 'utf8'"); // MySQL資料以UTF-8模式傳送
	//@mysql_query('START TRANSACTION'); // 啟動交易性能模式 (據說會降低效能，但可防止資料寫入不一致)

	$prepared = 1;
}

/* 提交/儲存 */
function dbCommit(){
	global $con, $prepared;
	if(!$prepared) return false;
	if(postCount() >= LOG_MAX) delOldPostes();
	//@mysql_query('COMMIT'); // 交易性能模式提交
}

/* 優化資料表 */
function dbOptimize($doit=false){
	global $con;
	if(!$doit) return true; // 支援最佳化資料表
	else{
		if(!_mysql_call('OPTIMIZE TABLES '.SQLLOG)) return false;
		else return true;
	}
}

/* 刪除舊文 */
function delOldPostes(){
	global $con, $path;
	$oldAttachments = array(); // 舊文的附加檔案清單
	$countline = postCount(); // 文章數目
	$cutIndex = $countline - LOG_MAX + 1; // LIMIT用，取出最舊的幾篇
	if(!$result=_mysql_call('SELECT no,ext,tim FROM '.SQLLOG.' ORDER BY no LIMIT 0, '.$cutIndex)) echo '[ERROR] 取出舊文失敗<br />';
	else{
		while($row=mysql_fetch_row($result)){ // 個別跑舊文迴圈
			list($dno, $dext, $dtim) = $row;
			if($dext){ // 有附加檔
				$dfile = $path.IMG_DIR.$dtim.$dext; // 附加檔案名稱
				$dthumb = $path.THUMB_DIR.$dtim.'s.jpg'; // 預覽檔案名稱
				if(file_func('exist', $dfile)) $oldAttachments[] = $dfile;
				if(file_func('exist', $dthumb)) $oldAttachments[] = $dthumb;
			}
			// 逐次搜尋舊文之回應
			if(!$resultres=_mysql_call('SELECT ext,tim FROM '.SQLLOG." WHERE ext <> '' AND resto = $dno")) echo '[ERROR] 取出舊文之回應失敗<br />';
			while($rowres=mysql_fetch_row($resultres)){
				list($rext, $rtim) = $rowres;
				if($rext){ // 有附加檔
					$rfile = $path.IMG_DIR.$rtim.$rext; // 附加檔案名稱
					$rthumb = $path.THUMB_DIR.$rtim.'s.jpg'; // 預覽檔案名稱
					if(file_func('exist', $rfile)) $oldAttachments[] = $rfile;
					if(file_func('exist', $rthumb)) $oldAttachments[] = $rthumb;
				}
			}
			mysql_free_result($resultres);
			if(!_mysql_call('DELETE FROM '.SQLLOG.' WHERE no = '.$dno.' OR resto = '.$dno)) echo '[ERROR] 刪除舊文及其回應失敗<br />'; // 刪除文章
		}
	}
	mysql_free_result($result);
	return $oldAttachments; // 回傳需刪除檔案列表
}

/* 刪除文章 */
function removePosts($posts){
	global $con;
	$files = removeAttachments($posts); // 先取得刪除文章附件清單

	$pno = implode(', ', $posts); // ID字串
	if(!$result=_mysql_call('DELETE FROM '.SQLLOG.' WHERE no IN ('.$pno.')')) echo '[ERROR] 刪除文章失敗<br />'; // 刪掉文章
	return $files;
}

/* 刪除舊附件 (輸出附件清單) */
function delOldAttachments($total_size,$storage_max,$warnOnly=true){
	global $con, $path;
	$arr_warn = $arr_kill = array(); // 警告 / 即將被刪除標記陣列
	if(!$result=_mysql_call('SELECT no,ext,tim FROM '.SQLLOG.' ORDER BY no')) echo '[ERROR] 取出舊文失敗<br />';
	else{
		while($row=mysql_fetch_row($result)){ // 個別跑舊文迴圈
			list($dno, $dext, $dtim) = $row;
			if($dext){ // 有附加檔
				$dfile = $path.IMG_DIR.$dtim.$dext; // 附加檔案名稱
				$dthumb = $path.THUMB_DIR.$dtim.'s.jpg'; // 預覽檔案名稱
				if(file_func('exist', $dfile)){ $total_size -= file_func('size', $dfile) / 1024; $arr_kill[] = $dno; $arr_warn[] = $dno; } // 標記刪除
				if(file_func('exist', $dthumb)) $total_size -= file_func('size', $dthumb) / 1024;
				if($total_size < $storage_max) break;
			}
		}
	}
	mysql_free_result($result);
	return $warnOnly ? $arr_warn : removeAttachments($arr_kill);
}

/* 刪除附件 (輸出附件清單) */
function removeAttachments($posts){
	global $con, $path;

	$files = array();
	$pno = implode(', ', $posts); // ID字串
	if(!$result=_mysql_call('SELECT tim,ext FROM '.SQLLOG.' WHERE no IN ('.$pno.')')) echo '[ERROR] 取出附件清單失敗<br />';
	else{
		while($row=mysql_fetch_row($result)){ // 個別跑迴圈
			list($dext, $dtim) = $row;
			if($dext){ // 有附加檔
				$dfile = $path.IMG_DIR.$dtim.$dext; // 附加檔案名稱
				$dthumb = $path.THUMB_DIR.$dtim.'s.jpg'; // 預覽檔案名稱
				if(file_func('exist', $dfile)) $files[] = $dfile;
				if(file_func('exist', $dthumb)) $files[] = $dthumb;
			}
		}
	}
	mysql_free_result($result);
	return $files;
}

/* 文章數目 */
function postCount($resno=0){
	global $con, $prepared;
	if(!$prepared) dbPrepare();

	if($resno){ // 回傳討論串總回應數目 (含本文故要加1)
		$line = _mysql_call('SELECT COUNT(no) FROM '.SQLLOG.' WHERE resto = '.$resno);
		$countline = mysql_result($line, 0) + 1;
	}else{ // 回傳總文章數目
		$line = _mysql_call('SELECT COUNT(no) FROM '.SQLLOG);
		$countline = mysql_result($line, 0);
	}
	mysql_free_result($line);
	return $countline;
}

/* 討論串數目 */
function threadCount(){
	global $con, $prepared;
	if(!$prepared) dbPrepare();

	$tree = _mysql_call('SELECT COUNT(no) FROM '.SQLLOG.' WHERE resto = 0');
	$counttree = mysql_result($tree, 0); mysql_free_result($tree); // 計算討論串目前資料筆數
	return $counttree;
}

/* 輸出文章清單 */
function fetchPostList($resno=0,$start=0,$amount=0){
	global $con, $prepared;
	if(!$prepared) dbPrepare();

	$line = array(); $i = 0;
	if($resno){ // 輸出討論串的結構 (含自己, EX : 1,2,3,4,5,6)
		$tree = _mysql_call('SELECT no FROM '.SQLLOG.' WHERE no = '.$resno.' OR resto = '.$resno.' ORDER BY no');
	}else{ // 輸出所有文章編號，新的在前
		$tree = _mysql_call('SELECT no FROM '.SQLLOG.' ORDER BY no DESC');
	}
	if($start > 0) mysql_data_seek($tree, $start); // 移動指標
	while($rows=mysql_fetch_row($tree)){ // 迴圈
		$i++; $line[] = $rows[0];
		if($amount && $i==$amount) break; // 取夠了
	}
	mysql_free_result($tree);
	return $line;
}

/* 輸出討論串清單 */
function fetchThreadList($start=0,$amount=0) {
	global $con, $prepared;
	if(!$prepared) dbPrepare();

	$treeline = array(); $i = 0;
	$tree = _mysql_call('SELECT no FROM '.SQLLOG.' WHERE resto = 0 ORDER BY root DESC');
	if($start > 0) mysql_data_seek($tree, $start); // 移動指標
	while($rows=mysql_fetch_row($tree)){ // 迴圈
		$i++; $treeline[] = $rows[0];
		if($amount && $i==$amount) break; // 取夠了
	}
	mysql_free_result($tree);
	return $treeline;
}

/* 輸出文章 */
function fetchPosts($postlist){
	global $con, $prepared;
	if(!$prepared) dbPrepare();

	if(is_array($postlist)){ // 多篇輸出 (保留功能)
		$pno = implode(', ', $postlist); // ID字串
		$line = _mysql_call('SELECT no, now, name, email, sub, com, status, host, pwd, ext, w, h, tim, md5 FROM '.SQLLOG.' WHERE no IN ('.$pno.') ORDER BY no DESC');
	}else{ // 單篇輸出
		$line = _mysql_call('SELECT no, now, name, email, sub, com, status, host, pwd, ext, w, h, tim, md5 FROM '.SQLLOG.' WHERE no = '.$postlist);
	}

	return _ArrangeArrayStructure($line); // 重排陣列結構
}

/* 有此討論串? */
function is_Thread($no){
	global $con, $prepared;
	if(!$prepared) dbPrepare();

	$result = _mysql_call('SELECT no FROM '.SQLLOG.' WHERE no = '.$no.' AND resto = 0');
	return mysql_fetch_array($result);
}

/* 有此文章? */
function is_Post($no){
	global $con, $prepared;
	if(!$prepared) dbPrepare();

	$result = _mysql_call('SELECT no FROM '.SQLLOG.' WHERE no = '.$no);
	return mysql_fetch_array($result);
}

/* 搜尋文章 */
function searchPost($keyword,$field,$method){
	global $prepared;
	if(!$prepared) dbPrepare();

	$keyword_cnt = count($keyword);
	$SearchQuery = 'SELECT no, now, name, email, sub, com, status, host, pwd, ext, w, h, tim, md5 FROM '.SQLLOG." WHERE {$field} LIKE '%".($keyword[0])."%'";
	if($keyword_cnt > 1) for($i = 1; $i < $keyword_cnt; $i++) $SearchQuery .= " {$method} {$field} LIKE '%".($keyword[$i])."%'"; // 多重字串交集 / 聯集搜尋
	$SearchQuery .= ' ORDER BY no DESC'; // 按照號碼大小排序
	if(!$line=_mysql_call($SearchQuery)) echo '[ERROR] 搜尋文章失敗<br />';

	return _ArrangeArrayStructure($line); // 重排陣列結構
}

/* 新增文章/討論串 */
function addPost($no,$resno,$now,$name,$email,$sub,$com,$url,$host,$pass,$ext,$W,$H,$tim,$chk,$age=false) {
	global $con, $prepared;
	if(!$prepared) dbPrepare();

	$time = (int)substr($tim, 0, -3); // 13位數的數字串是檔名，10位數的才是時間數值
	if($resno){ // 新增回應
		$rootqu = 0;
		if($age){ // 推文
			$query = 'UPDATE '.SQLLOG.' SET root = now() WHERE no = '.$resno; // 將被回應的文章往上移動
			if(!$result=_mysql_call($query)) echo '[ERROR] 推文失敗<br />';
		}
	}else $rootqu = 'now()'; // 新增討論串, 討論串最後被更新時間

	$query = 'INSERT INTO '.SQLLOG.' (resto,root,time,md5,tim,ext,w,h,pwd,now,name,email,sub,com,host,status) VALUES ('.
(int)$resno.','. // 回應編號
$rootqu.','. // 最後更新時間
$time.','. // 發文時間數值
"'$chk',". // 附加檔案md5
"'$tim', '$ext',". // 附加檔名
(int)$W.', '.(int)$H.','. // 預覽圖長寬
"'".mysql_escape_string($pass)."',".
"'$now',". // 時間(含ID)字串
"'".mysql_escape_string($name)."',".
"'".mysql_escape_string($email)."',".
"'".mysql_escape_string($sub)."',".
"'".mysql_escape_string($com)."',".
"'".mysql_escape_string($host)."', '')";
	if(!$result=_mysql_call($query)) echo '[ERROR] 新增文章失敗<br />';
}

/* 停止討論串 */
function stopThread($no) {
	global $con, $prepared;
	if(!$prepared) dbPrepare();

	if(is_array($no))
		foreach($no as $n) _stopThread($n);
	else{
		_stopThread($n);
	}
}
?>