<?php
/*
PIO - Pixmicat! data source I/O
PostgreSQL API
*/

class PIOpgsql{
	var $username, $password, $server, $port, $dbname, $tablename; // Local Constant
	var $con, $prepared; // Local Global

	function PIOpgsql($connstr=''){
		$this->prepared = 0;
		if($connstr) $this->dbConnect($connstr);
	}

	/* PIO模組版本 */
	/* 輸入 void, 輸出 版本號 as string */
	function pioVersion(){
		return 'v20060824β';
	}

	/* private 使用SQL字串和PostgreSQL伺服器要求 */
	function _pgsql_call($query){
		$ret = @pg_query($this->con, $query);
		if(!$ret) error('PostgreSQL SQL指令錯誤：<p />指令: '.$query.'<br/>錯誤訊息: '.pg_last_error($this->con));
		return $ret;
	}

	/* private 由資源輸出陣列 */
	function _ArrangeArrayStructure($line){
		$posts = array();
		while($row=pg_fetch_array($line, null, PGSQL_ASSOC)) $posts[] = $row;
		pg_free_result($line);
		return $posts;
	}

	/* 處理連線字串/連接 */
	/* 輸入 連線字串 as string, 輸出 void */
	function dbConnect($connStr){
		// 格式： pgsql://帳號:密碼@伺服器位置:埠號(可省略)/資料庫/資料表/
		// 示例： pgsql://pixmicat:1234@127.0.0.1/pixmicat_use/imglog/
		if(preg_match('/^pgsql:\/\/(.*)\:(.*)\@([^\:]*)((\:)([0-9]+)){0,1}\/(.*)\/(.*)\/$/i', $connStr, $linkinfos)){
			$this->username = $linkinfos[1]; // 登入帳號
			$this->password = $linkinfos[2]; // 登入密碼
			$this->server = $linkinfos[3]; // 登入伺服器
			$this->port = ($linkinfos[6] ? $linkinfos[6] : '5432'); // 登入埠號
			$this->dbname = $linkinfos[7]; // 資料庫名稱
			$this->tablename = $linkinfos[8]; // 資料表名稱
		}
	}

	/* 初始化 */
	/* 輸入 void, 輸出 void */
	function dbInit(){
		$this->dbPrepare();
		if(pg_num_rows(pg_query($this->con, "SELECT relname FROM pg_class WHERE relname = '".$this->tablename."'"))!=1){ // 資料表不存在
			$result = "CREATE SEQUENCE ".$this->tablename."_no_seq;
	CREATE TABLE ".$this->tablename." (
	\"no\" int NOT NULL DEFAULT nextval('".$this->tablename."_no_seq'),
	\"resto\" int NOT NULL,
	\"root\" timestamp NULL DEFAULT '1980-01-01 00:00:00',
	\"time\" int NOT NULL,
	\"md5chksum\" varchar(32) NOT NULL,
	\"catalog\" varchar(255) NOT NULL,
	\"tim\" bigint NOT NULL,
	\"ext\" varchar(4) NOT NULL,
	\"imgw\" smallint NOT NULL,
	\"imgh\" smallint NOT NULL,
	\"imgsize\" varchar(10) NOT NULL,
	\"tw\" smallint NOT NULL,
	\"th\" smallint NOT NULL,
	\"pwd\" varchar(8) NOT NULL,
	\"now\" varchar(255) NOT NULL,
	\"name\" varchar(255) NOT NULL,
	\"email\" varchar(255) NOT NULL,
	\"sub\" varchar(255) NOT NULL,
	\"com\" text NOT NULL,
	\"host\" varchar(255) NOT NULL,
	\"status\" varchar(4) NOT NULL,
	PRIMARY KEY (\"no\"));"; // For Pixmicat!-PIO [Structure V2]
			$idxs = array('resto', 'root', 'time');
			foreach($idxs as $idx) $result .= 'CREATE INDEX '.$this->tablename.'_'.$idx.'_index ON '.$this->tablename.' ('.$idx.');';
			pg_query($this->con, $result); // 正式新增資料表
			$this->addPost(1, 0, '', '', 0, '', 0, 0, '', 0, 0, '', '05/01/01(六)00:00', '無名氏', '', '無標題', '無內文', ''); // 追加一筆新資料
			$this->dbCommit();
		}
	}

	/* 準備/讀入 */
	/* 輸入 是否重作 as boolean, 是否啟動交易模式 as boolean, 輸出 void */
	function dbPrepare($reload=false, $transaction=true){
		if($this->prepared && !$reload) return true;

		if($reload && $this->con) pg_close($this->con);
		if(@!$this->con=pg_pconnect('host='.$this->server.' port='.$this->port.' dbname='.$this->dbname.' user='.$this->username.' password='.$this->password)){
			echo 'It occurred a fatal error when connecting to the PostgreSQL server.<p>';
			echo 'Check your PostgreSQL login setting in config file or the PostgreSQL server status.';
			exit;
		}
		if($transaction) @pg_query($this->con, 'START TRANSACTION;'); // 啟動交易性能模式 (據說會降低效能，但可防止資料寫入不一致)

		$this->prepared = 1;
	}

	/* 提交/儲存 */
	/* 輸入 void, 輸出 void */
	function dbCommit(){
		if(!$this->prepared) return false;

		@pg_query($this->con, 'COMMIT;'); // 交易性能模式提交
	}

	/* 優化資料表 */
	/* 輸入 是否作 as boolean, 輸出 優化成果 as boolean */
	function dbOptimize($doit=false){
		if($doit){
			$this->dbPrepare(true, false);
			if($this->_pgsql_call('VACUUM '.$this->tablename)) return true;
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
		if(!$result=$this->_pgsql_call('SELECT no,ext,tim FROM '.$this->tablename.' ORDER BY no LIMIT '.$cutIndex)) echo '[ERROR] 取出舊文失敗<br />';
		else{
			while(list($dno, $dext, $dtim)=pg_fetch_array($result)){ // 個別跑舊文迴圈
				if($dext){
					$dfile = $path.IMG_DIR.$dtim.$dext; // 附加檔案名稱
					$dthumb = $path.THUMB_DIR.$dtim.'s.jpg'; // 預覽檔案名稱
					if(file_func('exist', $dfile)) $oldAttachments[] = $dfile;
					if(file_func('exist', $dthumb)) $oldAttachments[] = $dthumb;
				}
				// 逐次搜尋舊文之回應
				if(!$resultres=$this->_pgsql_call('SELECT ext,tim FROM '.$this->tablename." WHERE ext <> '' AND resto = $dno")) echo '[ERROR] 取出舊文之回應失敗<br />';
				while(list($rext, $rtim)=pg_fetch_array($resultres)){
					$rfile = $path.IMG_DIR.$rtim.$rext; // 附加檔案名稱
					$rthumb = $path.THUMB_DIR.$rtim.'s.jpg'; // 預覽檔案名稱
					if(file_func('exist', $rfile)) $oldAttachments[] = $rfile;
					if(file_func('exist', $rthumb)) $oldAttachments[] = $rthumb;
				}
				pg_free_result($resultres);
				if(!$this->_pgsql_call('DELETE FROM '.$this->tablename.' WHERE no = '.$dno.' OR resto = '.$dno)) echo '[ERROR] 刪除舊文及其回應失敗<br />'; // 刪除文章
			}
		}
		pg_free_result($result);
		return $oldAttachments; // 回傳需刪除檔案列表
	}

	/* 刪除文章 */
	/* 輸入 文章編號 as array, 輸出 刪除附加檔案列表 as array */
	function removePosts($posts){
		if(!$this->prepared) $this->dbPrepare();

		$files = $this->removeAttachments($posts, true); // 先遞迴取得刪除文章及其回應附件清單
		$pno = implode(', ', $posts); // ID字串
		if(!$result=$this->_pgsql_call('DELETE FROM '.$this->tablename.' WHERE no IN ('.$pno.') OR resto IN('.$pno.')')) echo '[ERROR] 刪除文章及其回應失敗<br />'; // 刪掉文章
		return $files;
	}

	/* 刪除舊附件 (輸出附件清單) */
	/* 輸入 附加檔案總容量 as integer, 限制檔案儲存量 as integer, 只警告 as boolean, 輸出 警告旗標 / 舊附件列表 as array */
	function delOldAttachments($total_size, $storage_max, $warnOnly=true){
		global $path;
		if(!$this->prepared) $this->dbPrepare();

		$arr_warn = $arr_kill = array(); // 警告 / 即將被刪除標記陣列
		if(!$result=$this->_pgsql_call('SELECT no,ext,tim FROM '.$this->tablename." WHERE ext <> '' ORDER BY no")) echo '[ERROR] 取出舊文失敗<br />';
		else{
			while(list($dno, $dext, $dtim)=pg_fetch_array($result)){ // 個別跑舊文迴圈
				$dfile = $path.IMG_DIR.$dtim.$dext; // 附加檔案名稱
				$dthumb = $path.THUMB_DIR.$dtim.'s.jpg'; // 預覽檔案名稱
				if(file_func('exist', $dfile)){ $total_size -= file_func('size', $dfile) / 1024; $arr_kill[] = $dno; $arr_warn[$dno] = 1; } // 標記刪除
				if(file_func('exist', $dthumb)) $total_size -= file_func('size', $dthumb) / 1024;
				if($total_size < $storage_max) break;
			}
		}
		pg_free_result($result);
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

		if(!$result=$this->_pgsql_call($tmpSQL)) echo '[ERROR] 取出附件清單失敗<br />';
		else{
			while(list($dext, $dtim)=pg_fetch_array($result)){ // 個別跑迴圈
				$dfile = $path.IMG_DIR.$dtim.$dext; // 附加檔案名稱
				$dthumb = $path.THUMB_DIR.$dtim.'s.jpg'; // 預覽檔案名稱
				if(file_func('exist', $dfile)) $files[] = $dfile;
				if(file_func('exist', $dthumb)) $files[] = $dthumb;
			}
		}
		pg_free_result($result);
		return $files;
	}

	/* 檢查是否連續投稿 */
	/* 輸入 檢查筆數 as integer, 內文 as string, 時間戳記 as integer, 密碼 as string, Cookie儲存密碼 as string, 主機名 as string, 上傳檔案名 as string, 輸出 是否連續發文 as boolean */
	function checkSuccessivePost($lcount, $com, $timestamp, $pass, $passcookie, $host, $upload_filename){
		global $path;
		if(!$this->prepared) $this->dbPrepare();

		if(!RENZOKU) return false; // 關閉連續投稿檢查
		$tmpSQL = 'SELECT pwd,host FROM '.$this->tablename.' WHERE time > '.($timestamp - RENZOKU); // 一般投稿時間檢查
		if($upload_filename) $tmpSQL .= ' OR time > '.($timestamp - RENZOKU2); // 附加圖檔的投稿時間檢查 (與下者兩者擇一)
		else $tmpSQL .= ' OR md5(com) = "'.md5($com).'"'; // 內文一樣的檢查 (與上者兩者擇一)
		if(!$result=$this->_pgsql_call($tmpSQL)) echo '[ERROR] 取出文章判斷連續發文失敗<br />';
		else{
			while(list($lpwd, $lhost)=pg_fetch_array($result)){
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

		if(!$result=$this->_pgsql_call('SELECT tim,ext FROM '.$this->tablename." WHERE ext <> '' AND md5chksum = '$md5hash' ORDER BY no DESC")) echo '[ERROR] 取出文章判斷重複貼圖失敗<br />';
		else{
			while(list($ltim, $lext)=pg_fetch_array($result)){
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
			$line = $this->_pgsql_call('SELECT COUNT(no) FROM '.$this->tablename.' WHERE resto = '.$resno);
			$countline = pg_fetch_result($line, 0, 0) + 1;
		}else{ // 回傳總文章數目
			$line = $this->_pgsql_call('SELECT COUNT(no) FROM '.$this->tablename);
			$countline = pg_fetch_result($line, 0, 0);
		}
		pg_free_result($line);
		return $countline;
	}

	/* 討論串數目 */
	/* 輸入 void, 輸出 討論串數目 as integer */
	function threadCount(){
		if(!$this->prepared) $this->dbPrepare();

		$tree = $this->_pgsql_call('SELECT COUNT(no) FROM '.$this->tablename.' WHERE resto = 0');
		$counttree = pg_fetch_result($tree, 0, 0); pg_free_result($tree); // 計算討論串目前資料筆數
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
			if($amount) $tmpSQL .= " LIMIT {$amount} OFFSET {$start}"; // 有指定數量才用 LIMIT
		}
		$tree = $this->_pgsql_call($tmpSQL);
		while($rows=pg_fetch_array($tree)) $line[] = $rows[0]; // 迴圈

		pg_free_result($tree);
		return $line;
	}

	/* 輸出討論串清單 */
	/* 輸入 開始值, 數目 as integer, 輸出 討論串首篇編號 as array */
	function fetchThreadList($start=0, $amount=0){
		if(!$this->prepared) $this->dbPrepare();

		$treeline = array();
		$tmpSQL = 'SELECT no FROM '.$this->tablename.' WHERE resto = 0 ORDER BY root DESC';
		if($amount) $tmpSQL .= " LIMIT {$amount} OFFSET {$start}"; // 有指定數量才用 LIMIT
		$tree = $this->_pgsql_call($tmpSQL);
		while($rows=pg_fetch_array($tree)) $treeline[] = $rows[0]; // 迴圈

		pg_free_result($tree);
		return $treeline;
	}

	/* 輸出文章 */
	/* 輸入 文章編號 as array, 輸出 文章資料 as array */
	function fetchPosts($postlist){
		if(!$this->prepared) $this->dbPrepare();

		if(is_array($postlist)){ // 取多串
			if(!count($postlist)) return array();
			$pno = implode(', ', $postlist); // ID字串
			$tmpSQL = 'SELECT * FROM '.$this->tablename.' WHERE no IN ('.$pno.') ORDER BY no';
			if(count($postlist) > 1){ if($postlist[0] > $postlist[1]) $tmpSQL .= ' DESC'; } // 由大排到小
		}else $tmpSQL = 'SELECT * FROM '.$this->tablename.' WHERE no = '.$postlist; // 取單串
		$line = $this->_pgsql_call($tmpSQL);

		return $this->_ArrangeArrayStructure($line); // 輸出陣列結構
	}

	/* 有此討論串? */
	/* 輸入 討論串編號 as integer, 輸出 是否存在 as boolean */
	function is_Thread($no){
		if(!$this->prepared) $this->dbPrepare();

		$result = $this->_pgsql_call('SELECT no FROM '.$this->tablename.' WHERE no = '.$no.' AND resto = 0');
		return pg_fetch_array($result);
	}

	/* 搜尋文章 */
	/* 輸入 關鍵字 as array, 搜尋目標 as string, 搜尋方式 as string, 輸出 文章資料 as array */
	function searchPost($keyword, $field, $method){
		if(!$this->prepared) $this->dbPrepare();

		$keyword_cnt = count($keyword);
		$SearchQuery = 'SELECT * FROM '.$this->tablename." WHERE {$field} LIKE '%".($keyword[0])."%'";
		if($keyword_cnt > 1) for($i = 1; $i < $keyword_cnt; $i++) $SearchQuery .= " {$method} {$field} LIKE '%".($keyword[$i])."%'"; // 多重字串交集 / 聯集搜尋
		$SearchQuery .= ' ORDER BY no DESC'; // 按照號碼大小排序
		if(!$line=$this->_pgsql_call($SearchQuery)) echo '[ERROR] 搜尋文章失敗<br />';

		return $this->_ArrangeArrayStructure($line); // 輸出陣列結構
	}

	/* 新增文章/討論串 */
	/* 輸入 各種欄位值 as any, 輸出 void */
	function addPost($no, $resto, $md5chksum, $catalog, $tim, $ext, $imgw, $imgh, $imgsize, $tw, $th, $pwd, $now, $name, $email, $sub, $com, $host, $age=false){
		if(!$this->prepared) $this->dbPrepare();

		$time = (int)substr($tim, 0, -3); // 13位數的數字串是檔名，10位數的才是時間數值
		if($resto){ // 新增回應
			$root = 'cast(0::abstime as timestamp)';
			if($age){ // 推文
				$query = 'UPDATE '.$this->tablename.' SET root = now() WHERE no = '.$resto; // 將被回應的文章往上移動
				if(!$result=$this->_pgsql_call($query)) echo '[ERROR] 推文失敗<br />';
			}
		}else $root = 'now()'; // 新增討論串, 討論串最後被更新時間

		$query = 'INSERT INTO '.$this->tablename.' (resto,root,time,md5chksum,catalog,tim,ext,imgw,imgh,imgsize,tw,th,pwd,now,name,email,sub,com,host,status) VALUES ('.
	(int)$resto.','. // 回應編號
	$root.','. // 最後更新時間
	$time.','. // 發文時間數值
	"'$md5chksum',". // 附加檔案md5
	"'".pg_escape_string($catalog)."',". // 分類標籤
	"'$tim', '$ext',". // 附加檔名
	$imgw.','.$imgh.",'".$imgsize."',".$tw.','.$th.','. // 圖檔長寬及檔案大小；預覽圖長寬
	"'".pg_escape_string($pass)."',".
	"'$now',". // 時間(含ID)字串
	"'".pg_escape_string($name)."',".
	"'".pg_escape_string($email)."',".
	"'".pg_escape_string($sub)."',".
	"'".pg_escape_string($com)."',".
	"'".pg_escape_string($host)."', '')";
		if(!$result=$this->_pgsql_call($query)) echo '[ERROR] 新增文章失敗<br />';
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
					if(!$this->_pgsql_call('UPDATE '.$this->tablename." SET status = '$newStatus' WHERE no = ".$no[$i])) echo "[ERROR] 更新討論串狀態失敗<br>"; // 更新討論串屬性
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
			$tree = $this->_pgsql_call('SELECT MAX(no) FROM '.$this->tablename);
			$lastno = pg_fetch_result($tree, 0, 0); pg_free_result($tree);
			return $lastno;
		}else return 0; // 其他狀態沒用
	}
}
?>