<?php
/*
PIO - Pixmicat! data source I/O
MySQL API
*/

class PIOmysql{
	var $username, $password, $server, $dbname, $tablename; // Local Constant
	var $con, $prepared; // Local Global

	function PIOmysql($connstr=''){
		$this->prepared = 0;
		if($connstr) $this->dbConnect($connstr);
	}

	/* PIO模組版本 */
	/* 輸入 void, 輸出 版本號 as string */
	function pioVersion(){
		return 'v20060812β';
	}

	/* private 使用SQL字串和MySQL伺服器要求 */
	function _mysql_call($query){
		$ret = mysql_query($query);
		if(!$ret) error('MySQL SQL指令錯誤：<p />指令: '.$query.'<br />錯誤訊息: (#'.mysql_errno().') '.mysql_error());
		return $ret;
	}

	/* private 輸出符合標準的索引鍵陣列 */
	function _ArrangeArrayStructure($line){
		$posts = array();
		$arrIDKey = array('no'=>'', 'resto'=>'', 'now'=>'', 'name'=>'', 'email'=>'', 'sub'=>'', 'com'=>'', 'status'=>'url', 'host'=>'', 'pwd'=>'pw', 'ext'=>'', 'w'=>'', 'h'=>'', 'tim'=>'time', 'md5'=>'chk'); // MySQL 欄位鍵 => 標準欄位鍵
		while($row=mysql_fetch_array($line, MYSQL_ASSOC)){
			$tline = array();
			foreach($arrIDKey as $mID => $mVal) $tline[($mVal ? $mVal : $mID)] = $row[$mID]; // 逐個取值並代入
			$posts[] = $tline;
		}
		mysql_free_result($line);
		return $posts;
	}

	/* 處理連線字串/連接 */
	/* 輸入 連線字串 as string, 輸出 void */
	function dbConnect($connStr){
		// 格式： mysql://帳號:密碼@伺服器位置:埠號(可省略)/資料庫/資料表/
		// 示例： mysql://pixmicat:1234@127.0.0.1/pixmicat_use/imglog/
		if(preg_match('/^mysql:\/\/(.*)\:(.*)\@(.*(?:\:[0-9]+)?)\/(.*)\/(.*)\/$/i', $connStr, $linkinfos)){
			$this->username = $linkinfos[1]; // 登入帳號
			$this->password = $linkinfos[2]; // 登入密碼
			$this->server = $linkinfos[3]; // 登入伺服器 (含埠號)
			$this->dbname = $linkinfos[4]; // 資料庫名稱
			$this->tablename = $linkinfos[5]; // 資料表名稱
		}
	}

	/* 初始化 */
	/* 輸入 void, 輸出 void */
	function dbInit(){
		$this->dbPrepare();
		if(mysql_num_rows(mysql_query("SHOW TABLES LIKE '".$this->tablename."'"))!=1){ // 資料表不存在
			$result = "CREATE TABLE ".$this->tablename." (primary key(no),
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
			$this->addPost(1, 0, '05/01/01(六)00:00', '無名氏', '', '無標題', '無內文', '', '', '', '', 0, 0, 0, ''); // 追加一筆新資料
			$this->dbCommit();
		}
	}

	/* 準備/讀入 */
	/* 輸入 是否重作 as boolean, 是否啟動交易模式 as boolean, 輸出 void */
	function dbPrepare($reload=false, $transaction=false){
		if($this->prepared && !$reload) return true;

		if($reload && $this->con) mysql_close($this->con);
		if(@!$this->con=mysql_pconnect($this->server, $this->username, $this->password)){
			echo 'It occurred a fatal error when connecting to the MySQL server.<p>';
			echo 'Check your MySQL login setting in config file or the MySQL server status.';
			exit;
		}
		@mysql_select_db($this->dbname, $this->con);
		@mysql_query("SET NAMES 'utf8'"); // MySQL資料以UTF-8模式傳送
		if($transaction) @mysql_query('START TRANSACTION'); // 啟動交易性能模式 (據說會降低效能，但可防止資料寫入不一致)

		$this->prepared = 1;
	}

	/* 提交/儲存 */
	/* 輸入 void, 輸出 void */
	function dbCommit(){
		if(!$this->prepared) return false;

		//@mysql_query('COMMIT'); // 交易性能模式提交
	}

	/* 優化資料表 */
	/* 輸入 是否作 as boolean, 輸出 優化成果 as boolean */
	function dbOptimize($doit=false){
		if($doit){
			$this->dbPrepare(true, false);
			if($this->_mysql_call('OPTIMIZE TABLES '.$this->tablename)) return true;
			else return false;
		}else return true; // 支援最佳化資料表
	}

	/* 刪除舊文 */
	/* 輸入 void, 輸出 舊文之附加檔案列表 as array */
	function delOldPostes(){
		global $path;
		if(!$this->prepared) $this->dbPrepare();

		$oldAttachments = array(); // 舊文的附加檔案清單
		$countline = $this->postCount(); // 文章數目
		$cutIndex = $countline - LOG_MAX + 1; // LIMIT用，取出最舊的幾篇
		if(!$result=$this->_mysql_call('SELECT no,ext,tim FROM '.$this->tablename.' ORDER BY no LIMIT 0, '.$cutIndex)) echo '[ERROR] 取出舊文失敗<br />';
		else{
			while(list($dno, $dext, $dtim)=mysql_fetch_row($result)){ // 個別跑舊文迴圈
				if($dext){
					$dfile = $path.IMG_DIR.$dtim.$dext; // 附加檔案名稱
					$dthumb = $path.THUMB_DIR.$dtim.'s.jpg'; // 預覽檔案名稱
					if(file_func('exist', $dfile)) $oldAttachments[] = $dfile;
					if(file_func('exist', $dthumb)) $oldAttachments[] = $dthumb;
				}
				// 逐次搜尋舊文之回應
				if(!$resultres=$this->_mysql_call('SELECT ext,tim FROM '.$this->tablename." WHERE ext <> '' AND resto = $dno")) echo '[ERROR] 取出舊文之回應失敗<br />';
				while(list($rext, $rtim)=mysql_fetch_row($resultres)){
					$rfile = $path.IMG_DIR.$rtim.$rext; // 附加檔案名稱
					$rthumb = $path.THUMB_DIR.$rtim.'s.jpg'; // 預覽檔案名稱
					if(file_func('exist', $rfile)) $oldAttachments[] = $rfile;
					if(file_func('exist', $rthumb)) $oldAttachments[] = $rthumb;
				}
				mysql_free_result($resultres);
				if(!$this->_mysql_call('DELETE FROM '.$this->tablename.' WHERE no = '.$dno.' OR resto = '.$dno)) echo '[ERROR] 刪除舊文及其回應失敗<br />'; // 刪除文章
			}
		}
		mysql_free_result($result);
		return $oldAttachments; // 回傳需刪除檔案列表
	}

	/* 刪除文章 */
	/* 輸入 文章編號 as array, 輸出 刪除附加檔案列表 as array */
	function removePosts($posts){
		if(!$this->prepared) $this->dbPrepare();

		$files = $this->removeAttachments($posts, true); // 先遞迴取得刪除文章及其回應附件清單
		$pno = implode(', ', $posts); // ID字串
		if(!$result=$this->_mysql_call('DELETE FROM '.$this->tablename.' WHERE no IN ('.$pno.') OR resto IN('.$pno.')')) echo '[ERROR] 刪除文章及其回應失敗<br />'; // 刪掉文章
		return $files;
	}

	/* 刪除舊附件 (輸出附件清單) */
	/* 輸入 附加檔案總容量 as integer, 限制檔案儲存量 as integer, 只警告 as boolean, 輸出 警告旗標 / 舊附件列表 as array */
	function delOldAttachments($total_size, $storage_max, $warnOnly=true){
		global $path;
		if(!$this->prepared) $this->dbPrepare();

		$arr_warn = $arr_kill = array(); // 警告 / 即將被刪除標記陣列
		if(!$result=$this->_mysql_call('SELECT no,ext,tim FROM '.$this->tablename." WHERE ext <> '' ORDER BY no")) echo '[ERROR] 取出舊文失敗<br />';
		else{
			while(list($dno, $dext, $dtim)=mysql_fetch_row($result)){ // 個別跑舊文迴圈
				$dfile = $path.IMG_DIR.$dtim.$dext; // 附加檔案名稱
				$dthumb = $path.THUMB_DIR.$dtim.'s.jpg'; // 預覽檔案名稱
				if(file_func('exist', $dfile)){ $total_size -= file_func('size', $dfile) / 1024; $arr_kill[] = $dno; $arr_warn[$dno] = 1; } // 標記刪除
				if(file_func('exist', $dthumb)) $total_size -= file_func('size', $dthumb) / 1024;
				if($total_size < $storage_max) break;
			}
		}
		mysql_free_result($result);
		return $warnOnly ? $arr_warn : $this->removeAttachments($arr_kill);
	}

	/* 刪除附件 (輸出附件清單) */
	/* 輸入 文章編號 as array, 是否遞迴(附加其回應附件) as boolean, 輸出 刪除附件列表 as array */
	function removeAttachments($posts, $recursion=false){
		global $path;
		if(!$this->prepared) $this->dbPrepare();

		$files = array();
		$pno = implode(', ', $posts); // ID字串
		if($recursion) $tmpSQL = 'SELECT ext,tim FROM '.$this->tablename.' WHERE (no IN ('.$pno.') OR resto IN('.$pno.")) AND ext <> ''"; // 遞迴取出 (含回應附件)
		else $tmpSQL = 'SELECT ext,tim FROM '.$this->tablename.' WHERE no IN ('.$pno.") AND ext <> ''"; // 只有指定的編號

		if(!$result=$this->_mysql_call($tmpSQL)) echo '[ERROR] 取出附件清單失敗<br />';
		else{
			while(list($dext, $dtim)=mysql_fetch_row($result)){ // 個別跑迴圈
				$dfile = $path.IMG_DIR.$dtim.$dext; // 附加檔案名稱
				$dthumb = $path.THUMB_DIR.$dtim.'s.jpg'; // 預覽檔案名稱
				if(file_func('exist', $dfile)) $files[] = $dfile;
				if(file_func('exist', $dthumb)) $files[] = $dthumb;
			}
		}
		mysql_free_result($result);
		return $files;
	}

	/* 檢查是否連續投稿 */
	/* 輸入 檢查筆數 as integer, 內文 as string, 時間戳記 as integer, 密碼 as string, Cookie儲存密碼 as string, 主機名 as string, 上傳檔案名 as string, 輸出 是否連續發文 as boolean */
	function checkSuccessivePost($lcount, $com, $timestamp, $pass, $passcookie, $host, $upload_filename){
		global $path;
		if(!$this->prepared) $this->dbPrepare();

		if(!RENZOKU) return false; // 關閉連續投稿檢查
		$tmpSQL = 'SELECT pwd,host FROM '.$this->tablename.' WHERE time > '.$timestamp - RENZOKU; // 一般投稿時間檢查
		if($upload_filename) $tmpSQL .= ' OR time > '.$timestamp - RENZOKU2; // 附加圖檔的投稿時間檢查 (與下者兩者擇一)
		else $tmpSQL .= ' OR md5(com) = "'.md5($com).'"'; // 內文一樣的檢查 (與上者兩者擇一)
		if(!$result=$this->_mysql_call($tmpSQL)) echo '[ERROR] 取出文章判斷連續發文失敗<br />';
		else{
			while(list($lpwd, $lhost)=mysql_fetch_row($result)){
				$pchk = 0;
				if($host==$lhost || $pass==$lpwd || $passcookie==$lpwd) $pchk = 1;
				if($pchk) return true; break; // 判斷為同一人發文且符合連續投稿條件
			}
			return false;
		}
	}

	/* 檢查是否重複貼圖 */
	/* 輸入 檢查筆數 as integer, MD5雜湊值 as string, 輸出 是否重複貼圖 as boolean */
	function checkDuplicateAttechment($lcount, $md5hash){
		global $path;
		if(!$this->prepared) $this->dbPrepare();

		if(!$result=$this->_mysql_call('SELECT tim,ext FROM '.$this->tablename." WHERE ext <> '' AND md5 = '$md5hash' ORDER BY no DESC")) echo '[ERROR] 取出文章判斷重複貼圖失敗<br />';
		else{
			while(list($ltim, $lext)=mysql_fetch_row($result)){
				if(file_func('exist', $path.IMG_DIR.$ltim.$lext)){ return true; break; } // 有相同檔案
			}
			return false;
		}
	}

	/* 文章數目 */
	/* 輸入 討論串ID as integer, 輸出 討論串文章 / 總文章數目 as integer */
	function postCount($resno=0){
		if(!$this->prepared) $this->dbPrepare();

		if($resno){ // 回傳討論串總回應數目 (含本文故要加1)
			$line = $this->_mysql_call('SELECT COUNT(no) FROM '.$this->tablename.' WHERE resto = '.$resno);
			$countline = mysql_result($line, 0) + 1;
		}else{ // 回傳總文章數目
			$line = $this->_mysql_call('SELECT COUNT(no) FROM '.$this->tablename);
			$countline = mysql_result($line, 0);
		}
		mysql_free_result($line);
		return $countline;
	}

	/* 討論串數目 */
	/* 輸入 void, 輸出 討論串數目 as integer */
	function threadCount(){
		if(!$this->prepared) $this->dbPrepare();

		$tree = $this->_mysql_call('SELECT COUNT(no) FROM '.$this->tablename.' WHERE resto = 0');
		$counttree = mysql_result($tree, 0); mysql_free_result($tree); // 計算討論串目前資料筆數
		return $counttree;
	}

	/* 輸出文章清單 */
	/* 輸入 討論串編號, 開始值, 數目 as integer, 輸出 討論串結構 as array */
	function fetchPostList($resno=0, $start=0, $amount=0){
		if(!$this->prepared) $this->dbPrepare();

		$line = array();
		if($resno){ // 輸出討論串的結構 (含自己, EX : 1,2,3,4,5,6)
			$tmpSQL = 'SELECT no FROM '.$this->tablename.' WHERE no = '.$resno.' OR resto = '.$resno.' ORDER BY no';
		}else{ // 輸出所有文章編號，新的在前
			$tmpSQL = 'SELECT no FROM '.$this->tablename.' ORDER BY no DESC';
			if($amount) $tmpSQL .= " LIMIT {$start}, {$amount}"; // 有指定數量才用 LIMIT
		}
		$tree = $this->_mysql_call($tmpSQL);
		while($rows=mysql_fetch_row($tree)) $line[] = $rows[0]; // 迴圈

		mysql_free_result($tree);
		return $line;
	}

	/* 輸出討論串清單 */
	/* 輸入 開始值, 數目 as integer, 輸出 討論串首篇編號 as array */
	function fetchThreadList($start=0, $amount=0){
		if(!$this->prepared) $this->dbPrepare();

		$treeline = array();
		$tmpSQL = 'SELECT no FROM '.$this->tablename.' WHERE resto = 0 ORDER BY root DESC';
		if($amount) $tmpSQL .= " LIMIT {$start}, {$amount}"; // 有指定數量才用 LIMIT
		$tree = $this->_mysql_call($tmpSQL);
		while($rows=mysql_fetch_row($tree)) $treeline[] = $rows[0]; // 迴圈

		mysql_free_result($tree);
		return $treeline;
	}

	/* 輸出文章 */
	/* 輸入 文章編號 as array, 輸出 文章資料 as array */
	function fetchPosts($postlist){
		if(!$this->prepared) $this->dbPrepare();

		if(is_array($postlist)){ // 取多串
			$pno = implode(', ', $postlist); // ID字串
			$tmpSQL = 'SELECT * FROM '.$this->tablename.' WHERE no IN ('.$pno.') ORDER BY no';
			if(count($postlist) > 1){ if($postlist[0] > $postlist[1]) $tmpSQL .= ' DESC'; } // 由大排到小
		}else $tmpSQL = 'SELECT * FROM '.$this->tablename.' WHERE no = '.$postlist; // 取單串
		$line = $this->_mysql_call($tmpSQL);

		return $this->_ArrangeArrayStructure($line); // 重排陣列結構
	}

	/* 有此討論串? */
	/* 輸入 討論串編號 as integer, 輸出 是否存在 as boolean */
	function is_Thread($no){
		if(!$this->prepared) $this->dbPrepare();

		$result = $this->_mysql_call('SELECT no FROM '.$this->tablename.' WHERE no = '.$no.' AND resto = 0');
		return mysql_fetch_array($result);
	}

	/* 搜尋文章 */
	/* 輸入 關鍵字 as array, 搜尋目標 as string, 搜尋方式 as string, 輸出 文章資料 as array */
	function searchPost($keyword, $field, $method){
		if(!$this->prepared) $this->dbPrepare();

		$keyword_cnt = count($keyword);
		$SearchQuery = 'SELECT * FROM '.$this->tablename." WHERE {$field} LIKE '%".($keyword[0])."%'";
		if($keyword_cnt > 1) for($i = 1; $i < $keyword_cnt; $i++) $SearchQuery .= " {$method} {$field} LIKE '%".($keyword[$i])."%'"; // 多重字串交集 / 聯集搜尋
		$SearchQuery .= ' ORDER BY no DESC'; // 按照號碼大小排序
		if(!$line=$this->_mysql_call($SearchQuery)) echo '[ERROR] 搜尋文章失敗<br />';

		return $this->_ArrangeArrayStructure($line); // 重排陣列結構
	}

	/* 新增文章/討論串 */
	/* 輸入 各種欄位值 as any, 輸出 void */
	function addPost($no, $resno, $now, $name, $email, $sub, $com, $url, $host, $pass, $ext, $W, $H, $tim, $chk, $age=false){
		if(!$this->prepared) $this->dbPrepare();

		$time = (int)substr($tim, 0, -3); // 13位數的數字串是檔名，10位數的才是時間數值
		if($resno){ // 新增回應
			$rootqu = 0;
			if($age){ // 推文
				$query = 'UPDATE '.$this->tablename.' SET root = now() WHERE no = '.$resno; // 將被回應的文章往上移動
				if(!$result=$this->_mysql_call($query)) echo '[ERROR] 推文失敗<br />';
			}
		}else $rootqu = 'now()'; // 新增討論串, 討論串最後被更新時間

		$query = 'INSERT INTO '.$this->tablename.' (resto,root,time,md5,tim,ext,w,h,pwd,now,name,email,sub,com,host,status) VALUES ('.
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
		if(!$result=$this->_mysql_call($query)) echo '[ERROR] 新增文章失敗<br />';
	}

	/* 取出單一文章狀態 */
	/* 輸入 狀態字串 as integer, 狀態類型 as string, 輸出 狀態值 as integer */
	function getPostStatus($status, $statusType){
		if(!$this->prepared) $this->dbPrepare();
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
		if(!$this->prepared) $this->dbPrepare();

		$forcount = count($no);
		for($i = 0; $i < $forcount; $i++){
			$newStatus = ''; // 討論串狀態旗標字串
			switch($statusType[$i]){
				case 'TS': // 討論串是否停止
					$newStatus = $newValue[$i] ? ($status[$i].'T') : str_replace('T', '', $status[$i]); // 更改狀態字串
					if(!$this->_mysql_call('UPDATE '.$this->tablename." SET status = '$newStatus' WHERE no = ".$no[$i])) echo "[ERROR] 更新討論串狀態失敗<br>"; // 更新討論串屬性
					break;
				default:
			}
		}
	}

	/* 取得最後文章編號 */
	/* 輸入 使用狀態 as string,輸出 編號 as integer */
	function getLastPostNo($state){
		if(!$this->prepared) $this->dbPrepare();

		if($state=='afterCommit'){ // 送出後的最後文章編號
			$tree = $this->_mysql_call('SELECT MAX(no) FROM '.$this->tablename);
			$lastno = mysql_result($tree, 0); mysql_free_result($tree);
			return $lastno;
		}else return 0; // 其他狀態沒用
	}
}
?>