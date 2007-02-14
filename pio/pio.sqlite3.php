<?php
/*
PIO - Pixmicat! data source I/O
SQLite3 (PDO) API (Note: Need PHP 5.1.0 or above)
*/

class PIOsqlite3{
	private $DSN, $tablename; // Local Constant
	private $con, $prepared; // Local Global

	public function __construct($connstr=''){
		$this->prepared = false;
		if($connstr) $this->dbConnect($connstr);
	}

	/* private 攔截SQL錯誤 */
	private function _error_handler($errtext, $errline){
		$err = "Pixmicat! SQL Error: $errtext (".print_r($this->con->errorInfo(), true)."), debug info: at line $errline";
		exit($err);
	}

	/* PIO模組版本 */
	public function pioVersion() {
		return '0.4beta (b20070214)';
	}

	/* 處理連線字串/連接 */
	public function dbConnect($connStr){
		// 格式： sqlite3://資料庫檔案之位置/資料表/
		// 示例： sqlite3://pixmicat.db/imglog/
		// 　　　 sqlite3://:memory:/imglog
		if(preg_match('/^sqlite3:\/\/(.*)\/(.*)\/$/i', $connStr, $linkinfos)){
			$this->DSN = 'sqlite:'.$linkinfos[1];
			$this->tablename = $linkinfos[2];
		}
	}

	/* 初始化 */
	public function dbInit($isAddInitData=true){
		$this->dbPrepare();
		$nline = $this->con->query('SELECT COUNT(name) FROM sqlite_master WHERE name LIKE "'.$this->tablename.'"')->fetch();
		if($nline[0]==='0'){ // 資料表不存在
			$result = 'CREATE TABLE '.$this->tablename.' (
	"no" INTEGER  NOT NULL PRIMARY KEY,
	"resto" INTEGER  NOT NULL,
	"root" TIMESTAMP DEFAULT \'0\' NOT NULL,
	"time" INTEGER  NOT NULL,
	"md5chksum" VARCHAR(32)  NOT NULL,
	"category" VARCHAR(255)  NOT NULL,
	"tim" INTEGER  NOT NULL,
	"ext" VARCHAR(4)  NOT NULL,
	"imgw" INTEGER  NOT NULL,
	"imgh" INTEGER  NOT NULL,
	"imgsize" VARCHAR(10)  NOT NULL,
	"tw" INTEGER  NOT NULL,
	"th" INTEGER  NOT NULL,
	"pwd" VARCHAR(8)  NOT NULL,
	"now" VARCHAR(255)  NOT NULL,
	"name" VARCHAR(255)  NOT NULL,
	"email" VARCHAR(255)  NOT NULL,
	"sub" VARCHAR(255)  NOT NULL,
	"com" TEXT  NOT NULL,
	"host" VARCHAR(255)  NOT NULL,
	"status" VARCHAR(4)  NOT NULL
	);'; // For Pixmicat!-PIO [Structure V2]
			$idx = array('resto', 'root', 'time');
			foreach($idx as $x) $result .= 'CREATE INDEX IDX_'.$this->tablename.'_'.$x.' ON '.$this->tablename.'('.$x.');';
			$result .= 'CREATE INDEX IDX_'.$this->tablename.'_resto_no ON '.$this->tablename.'(resto,no);';
			if($isAddInitData) $result .= 'INSERT INTO '.$this->tablename.' (resto,root,time,md5chksum,category,tim,ext,imgw,imgh,imgsize,tw,th,pwd,now,name,email,sub,com,host,status) VALUES (0, datetime("now"), 1111111111, "", "", 1111111111111, "", 0, 0, "", 0, 0, "", "05/01/01(六)00:00", "無名氏", "", "無標題", "無內文", "", "");';
			$this->con->exec($result);
			$this->dbCommit();
		}
	}

	/* 準備/讀入 */
	public function dbPrepare($reload=false, $transaction=true){
		if($this->prepared && !$reload) return true;

		if($reload && $this->con) $this->con = null;
		($this->con = new PDO($this->DSN, '', '', array(PDO::ATTR_PERSISTENT => true))) or $this->_error_handler('Open database failed', __LINE__);
		if($transaction) $this->con->beginTransaction(); // 啟動交易性能模式

		$this->prepared = true;
	}

	/* 提交/儲存 */
	public function dbCommit(){
		if(!$this->prepared) return false;

		$this->con->commit();
	}

	/* 優化資料表 */
	public function dbOptimize($doit=false){
		if($doit){
			$this->dbPrepare(true, false);
			if($this->con->exec('VACUUM '.$this->tablename)) return true;
			else return false;
		}else return true; // 支援最佳化資料表
	}

	/* 匯入資料來源 */
	public function dbImport($data){
		$this->dbInit(false); // 僅新增結構不新增資料
		$data = explode("\r\n", $data);
		$data_count = count($data) - 1;
		$replaceComma = create_function('$txt', 'return str_replace("&#44;", ",", $txt);');
		for($i = 0; $i < $data_count; $i++){
			$line = array_map($replaceComma, explode(',', $data[$i])); // 取代 &#44; 為 ,
			$SQL = 'INSERT INTO '.$this->tablename.' (no,resto,root,time,md5chksum,category,tim,ext,imgw,imgh,imgsize,tw,th,pwd,now,name,email,sub,com,host,status) VALUES ('.
	$line[0].','.
	$line[1].',\''.
	$line[2].'\','.
	substr($line[5], 0, 10).','.
	$this->con->quote($line[3]).','.
	$this->con->quote($line[4]).','.
	$line[5].','.$this->con->quote($line[6]).','.
	$line[7].','.$line[8].','.$this->con->quote($line[9]).','.$line[10].','.$line[11].','.
	$this->con->quote($line[12]).','.
	$this->con->quote($line[13]).','.
	$this->con->quote($line[14]).','.
	$this->con->quote($line[15]).','.
	$this->con->quote($line[16]).','.
	$this->con->quote($line[17]).','.
	$this->con->quote($line[18]).',\''.
	$line[19].'\')';
			//echo $SQL."<BR>\n";
			if(!$this->con->exec($SQL)) $this->_error_handler('Insert a new post failed', __LINE__);
		}
		$this->dbCommit(); // 送交
		return true;
	}

	/* 匯出資料來源 */
	public function dbExport(){
		if(!$this->prepared) $this->dbPrepare();
		$line = $this->con->query('SELECT no,resto,root,md5chksum,category,tim,ext,imgw,imgh,imgsize,tw,th,pwd,now,name,email,sub,com,host,status FROM '.$this->tablename.' ORDER BY no DESC');
		$data = '';
		$replaceComma = create_function('$txt', 'return str_replace(",", "&#44;", $txt);');
		while($row = $line->fetch(PDO::FETCH_ASSOC)){
			$row = array_map($replaceComma, $row); // 取代 , 為 &#44;
			$data .= implode(',', $row).",\r\n";
		}
		return $data;
	}

	/* 文章數目 */
	public function postCount($resno=0){
		if(!$this->prepared) $this->dbPrepare();

		if($resno){ // 一討論串文章總數目
			$line = $this->con->query('SELECT COUNT(no) FROM '.$this->tablename.' WHERE resto = '.$resno)->fetch();
			$countline = $line[0] + 1;
		}else{ // 文章總數目
			$line = $this->con->query('SELECT COUNT(no) FROM '.$this->tablename)->fetch();
			$countline = $line[0];
		}
		return $countline;
	}

	/* 討論串數目 */
	public function threadCount(){
		if(!$this->prepared) $this->dbPrepare();

		$tree = $this->con->query('SELECT COUNT(no) FROM '.$this->tablename.' WHERE resto = 0')->fetch();
		return $tree[0]; // 討論串目前數目
	}

	/* 取得最後文章編號 */
	public function getLastPostNo($state){
		if(!$this->prepared) $this->dbPrepare();

		if($state=='afterCommit'){ // 送出後的最後文章編號
			$lastno = $this->con->query('SELECT MAX(no) FROM '.$this->tablename)->fetch();
			return $lastno[0];
		}else return 0; // 其他狀態沒用
	}

	/* 輸出文章清單 */
	public function fetchPostList($resno=0, $start=0, $amount=0){
		if(!$this->prepared) $this->dbPrepare();

		if($resno){ // 輸出討論串的結構 (含自己, EX : 1,2,3,4,5,6)
			$tmpSQL = 'SELECT no FROM '.$this->tablename.' WHERE no = '.$resno.' OR resto = '.$resno.' ORDER BY no';
		}else{ // 輸出所有文章編號，新的在前
			$tmpSQL = 'SELECT no FROM '.$this->tablename.' ORDER BY no DESC';
			if($amount) $tmpSQL .= " LIMIT {$start}, {$amount}"; // 指定數量
		}
		return $this->con->query($tmpSQL)->fetchAll(PDO::FETCH_COLUMN, 0);
	}

	/* 輸出討論串清單 */
	public function fetchThreadList($start=0, $amount=0, $isDESC=false) {
		if(!$this->prepared) $this->dbPrepare();

		$tmpSQL = 'SELECT no FROM '.$this->tablename.' WHERE resto = 0 ORDER BY '.($isDESC ? 'no' : 'root').' DESC';
		if($amount) $tmpSQL .= " LIMIT {$start}, {$amount}"; // 指定數量
		return $this->con->query($tmpSQL)->fetchAll(PDO::FETCH_COLUMN, 0);
	}

	/* 輸出文章 */
	public function fetchPosts($postlist){
		if(!$this->prepared) $this->dbPrepare();

		if(is_array($postlist)){ // 取多串
			$pno = implode(', ', $postlist); // ID字串
			$tmpSQL = 'SELECT * FROM '.$this->tablename.' WHERE no IN ('.$pno.') ORDER BY no';
			if(count($postlist) > 1){ if($postlist[0] > $postlist[1]) $tmpSQL .= ' DESC'; } // 由大排到小
		}else $tmpSQL = 'SELECT * FROM '.$this->tablename.' WHERE no = '.$postlist; // 取單串
		$line = $this->con->query($tmpSQL)->fetchAll();
		return $line;
	}

	/* 刪除舊文 */
	public function delOldPostes(){
		global $FileIO;
		if(!$this->prepared) $this->dbPrepare();

		$oldAttachments = array();
		$delStack = array(); // Records needed to be deleted
		$countline = $this->postCount();
		$cutIndex = $countline - LOG_MAX + 1;
		$result = $this->con->prepare('SELECT no,ext,tim FROM '.$this->tablename.' ORDER BY no LIMIT 0, :cutindex');
		$result->execute(array(':cutindex' => $cutIndex)) or $this->_error_handler('Get the old post failed', __LINE__);
		while(list($dno, $dext, $dtim) = $result->fetch(PDO::FETCH_NUM)){
			if($dext){
				$dfile = $dtim.$dext; $dthumb = $dtim.'s.jpg';
				if($FileIO->imageExists($dfile)) $oldAttachments[] = $dfile;
				if($FileIO->imageExists($dthumb)) $oldAttachments[] = $dthumb;
			}
			// 逐次搜尋舊文之回應
			$resultres = $this->con->prepare('SELECT ext,tim FROM '.$this->tablename.' WHERE ext <> "" AND resto = :dno');
			$resultres->execute(array(':dno' => $dno)) or $this->_error_handler('Get replies of the old post failed', __LINE__);
			while(list($rext, $rtim) = $resultres->fetch(PDO::FETCH_NUM)){
				$rfile = $rtim.$rext; $rthumb = $rtim.'s.jpg';
				if($FileIO->imageExists($rfile)) $oldAttachments[] = $rfile;
				if($FileIO->imageExists($rthumb)) $oldAttachments[] = $rthumb;
			}
			$delStack[] = $dno; // Add to stack
		}
		$delCount = count($delStack);
		for($i = 0; $i < $delCount; $i++){
			if(!$this->con->exec('DELETE FROM '.$this->tablename.' WHERE no = '.$delStack[$i].' OR resto = '.$delStack[$i])) $this->_error_handler('Delete old posts and replies failed', __LINE__);
		}
		return $oldAttachments; // 回傳需刪除檔案列表
	}

	/* 刪除舊附件 (輸出附件清單) */
	public function delOldAttachments($total_size, $storage_max, $warnOnly=true){
		global $FileIO;
		if(!$this->prepared) $this->dbPrepare();

		$arr_warn = $arr_kill = array(); // 警告 / 即將被刪除標記
		($result = $this->con->query('SELECT no,ext,tim FROM '.$this->tablename.' WHERE ext <> "" ORDER BY no')) or $this->_error_handler('Get the old post failed', __LINE__);
		while(list($dno, $dext, $dtim) = $result->fetch(PDO::FETCH_NUM)){
			$dfile = $dtim.$dext; $dthumb = $dtim.'s.jpg';
			if($FileIO->imageExists($dfile)){ $total_size -= $FileIO->getImageFilesize($dfile) / 1024; $arr_kill[] = $dno; $arr_warn[$dno] = 1; } // 標記刪除
			if($FileIO->imageExists($dthumb)) $total_size -= $FileIO->getImageFilesize($dthumb) / 1024;
			if($total_size < $storage_max) break;
		}
		return $warnOnly ? $arr_warn : $this->removeAttachments($arr_kill);
	}

	/* 刪除文章 */
	public function removePosts($posts){
		if(!$this->prepared) $this->dbPrepare();

		$files = $this->removeAttachments($posts, true); // 先遞迴取得刪除文章及其回應附件清單
		$pno = implode(', ', $posts); // ID字串
		if(!$this->con->exec('DELETE FROM '.$this->tablename.' WHERE no IN ('.$pno.') OR resto IN('.$pno.')')) $this->_error_handler('Delete old posts and replies failed', __LINE__);
		return $files;
	}

	/* 刪除附件 (輸出附件清單) */
	public function removeAttachments($posts, $recursion=false){
		global $FileIO;
		if(!$this->prepared) $this->dbPrepare();

		$files = array();
		$pno = implode(', ', $posts); // ID字串
		if($recursion) $tmpSQL = 'SELECT ext,tim FROM '.$this->tablename.' WHERE (no IN ('.$pno.') OR resto IN('.$pno.")) AND ext <> ''"; // 遞迴取出 (含回應附件)
		else $tmpSQL = 'SELECT ext,tim FROM '.$this->tablename.' WHERE no IN ('.$pno.") AND ext <> ''"; // 只有指定的編號

		($result = $this->con->query($tmpSQL)) or $this->_error_handler('Get attachments of the post failed', __LINE__);
		while(list($dext, $dtim) = $result->fetch(PDO::FETCH_NUM)){
			$dfile = $dtim.$dext; $dthumb = $dtim.'s.jpg';
			if($FileIO->imageExists($dfile)) $files[] = $dfile;
			if($FileIO->imageExists($dthumb)) $files[] = $dthumb;
		}
		return $files;
	}

	/* 新增文章/討論串 */
	public function addPost($no, $resto, $md5chksum, $category, $tim, $ext, $imgw, $imgh, $imgsize, $tw, $th, $pwd, $now, $name, $email, $sub, $com, $host, $age=false){
		if(!$this->prepared) $this->dbPrepare();

		$time = (int)substr($tim, 0, -3); // 13位數的數字串是檔名，10位數的才是時間數值
		$updatetime = gmdate('Y-m-d H:i:s'); // 更動時間 (UTC)
		if($resto){ // 新增回應
			$root = '0';
			if($age){ // 推文
				$result = $this->con->prepare('UPDATE '.$this->tablename.' SET root = :now WHERE no = :resto');
				$result->execute(array(':now' => $updatetime, ':resto' => $resto)) or $this->_error_handler('Push the post failed', __LINE__);
			}
		}else $root = $updatetime; // 新增討論串, 討論串最後被更新時間

		$query = 'INSERT INTO '.$this->tablename.' (resto,root,time,md5chksum,category,tim,ext,imgw,imgh,imgsize,tw,th,pwd,now,name,email,sub,com,host,status) VALUES ('.
	(int)$resto.','. // 回應編號
	"'$root',". // 最後更新時間
	$time.','. // 發文時間數值
	"'$md5chksum',". // 附加檔案 MD5
	$this->con->quote($category).",". // 分類標籤
	"$tim, '$ext',". // 附檔檔名
	$imgw.','.$imgh.",'".$imgsize."',".$tw.','.$th.','. // 圖檔長寬及檔案大小；預覽圖長寬
	$this->con->quote($pwd).','.
	"'$now',". // 時間(含ID)字串
	$this->con->quote($name).','.
	$this->con->quote($email).','.
	$this->con->quote($sub).','.
	$this->con->quote($com).','.
	$this->con->quote($host).", '')";
		if(!$this->con->exec($query)) $this->_error_handler('Insert a new post failed', __LINE__);
	}

	/* 檢查是否連續投稿 */
	public function isSuccessivePost($lcount, $com, $timestamp, $pass, $passcookie, $host, $isupload){
		global $FileIO;
		if(!$this->prepared) $this->dbPrepare();

		if(!RENZOKU) return false; // 關閉連續投稿檢查
		$tmpSQL = 'SELECT pwd,host FROM '.$this->tablename.' WHERE time > '.($timestamp - RENZOKU); // 一般投稿時間檢查
		if($isupload) $tmpSQL .= ' OR time > '.($timestamp - RENZOKU2); // 附加圖檔的投稿時間檢查 (與下者兩者擇一)
		else $tmpSQL .= " OR md5(com) = '".md5($com)."'"; // 內文一樣的檢查 (與上者兩者擇一)
		$this->con->sqliteCreateFunction('md5', 'md5', 1); // Register MD5 function
		($result = $this->con->query($tmpSQL)) or $this->_error_handler('Get the post to check the succession failed', __LINE__);
		while(list($lpwd, $lhost) = $result->fetch(PDO::FETCH_NUM)){
			// 判斷為同一人發文且符合連續投稿條件
			if($host==$lhost || $pass==$lpwd || $passcookie==$lpwd) return true;
		}
		return false;
	}

	/* 檢查是否重複貼圖 */
	public function isDuplicateAttechment($lcount, $md5hash){
		global $FileIO;
		if(!$this->prepared) $this->dbPrepare();

		$result = $this->con->query('SELECT tim,ext FROM '.$this->tablename.' WHERE ext <> "" AND md5chksum = "'.$md5hash.'" ORDER BY no DESC') or $this->_error_handler('Get the post to check the duplicate attachment failed', __LINE__);
		while(list($ltim, $lext) = $result->fetch(PDO::FETCH_NUM)){
			if($FileIO->imageExists($ltim.$lext)) return true; // 有相同檔案
		}
		return false;
	}

	/* 有此討論串? */
	public function isThread($no){
		if(!$this->prepared) $this->dbPrepare();

		$result = $this->con->query('SELECT no FROM '.$this->tablename.' WHERE no = '.$no.' AND resto = 0');
		return count($result->fetch()) ? true : false;
	}

	/* 搜尋文章 */
	public function searchPost($keyword, $field, $method){
		if(!$this->prepared) $this->dbPrepare();

		$keyword_cnt = count($keyword);
		$SearchQuery = 'SELECT * FROM '.$this->tablename." WHERE {$field} LIKE '%".($keyword[0])."%'";
		if($keyword_cnt > 1) for($i = 1; $i < $keyword_cnt; $i++) $SearchQuery .= " {$method} {$field} LIKE '%".($keyword[$i])."%'"; // 多重字串交集 / 聯集搜尋
		$SearchQuery .= ' ORDER BY no DESC'; // 按照號碼大小排序
		($line = $this->con->query($SearchQuery)) or $this->_error_handler('Search the post failed', __LINE__);

		return $line->fetchAll();
	}

	/* 搜尋類別標籤 */
	public function searchCategory($category){
		if(!$this->prepared) $this->dbPrepare();

		$result = $this->con->prepare('SELECT no FROM '.$this->tablename.' WHERE lower(category) LIKE :category');
		$result->execute(array(':category' => '%,'.strtolower($category).',%'));
		return $result->fetchAll(PDO::FETCH_COLUMN, 0);
	}

	/* 取出單一文章狀態 */
	public function getPostStatus($status, $statusType){
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
	public function setPostStatus($no, $status, $statusType, $newValue){
		if(!$this->prepared) $this->dbPrepare();

		$forcount = count($no);
		for($i = 0; $i < $forcount; $i++){
			$newStatus = ''; // 討論串狀態旗標字串
			switch($statusType[$i]){
				case 'TS': // 討論串是否停止
					$newStatus = $newValue[$i] ? ($status[$i].'T') : str_replace('T', '', $status[$i]);
					if(!$this->con->exec('UPDATE '.$this->tablename." SET status = '$newStatus' WHERE no = ".$no[$i])) $this->_error_handler('Update the status of the post failed', __LINE__);
					break;
				default:
			}
		}
	}
}
?>