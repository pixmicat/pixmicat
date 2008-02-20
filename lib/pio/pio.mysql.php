<?php
/**
 * PIO MySQL API
 *
 * 提供存取以 MySQL 資料庫構成的資料結構後端的物件
 *
 * @package PMCLibrary
 * @version $Id$
 * @date $Date$
 */

class PIOmysql{
	var $ENV, $username, $password, $server, $dbname, $tablename; // Local Constant
	var $con, $prepared; // Local Global

	function PIOmysql($connstr='', $ENV){
		$this->ENV = $ENV;
		$this->prepared = 0;
		if($connstr) $this->dbConnect($connstr);
	}

	/* private 攔截SQL錯誤 */
	function _error_handler($errtext, $errline){
		$err = "Pixmicat! SQL Error: $errtext, debug info: at line $errline";
		trigger_error($err, E_USER_ERROR);
	}

	/* private 使用SQL字串和MySQL伺服器要求 */
	function _mysql_call($query){
		return mysql_query($query);
	}

	/* private 由資源輸出陣列 */
	function _ArrangeArrayStructure($line){
		$posts = array();
		while($row=mysql_fetch_array($line, MYSQL_ASSOC)) $posts[] = $row;
		mysql_free_result($line);
		return $posts;
	}

	/* PIO模組版本 */
	function pioVersion(){
		return '0.5 (v20071013)';
	}

	/* 處理連線字串/連接 */
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
	function dbInit($isAddInitData=true){
		$this->dbPrepare();
		if(mysql_num_rows(mysql_query("SHOW TABLES LIKE '".$this->tablename."'"))!=1){ // 資料表不存在
			$result = "CREATE TABLE ".$this->tablename." (primary key(no),
	index (resto),index (root),index (time),
	no int(1) not null auto_increment,
	resto int(1) not null,
	root timestamp(14) null DEFAULT 0,
	time int(1) not null,
	md5chksum varchar(32) not null,
	category varchar(255) not null,
	tim bigint(1) not null,
	ext varchar(4) not null,
	imgw smallint(1) not null,
	imgh smallint(1) not null,
	imgsize varchar(10) not null,
	tw smallint(1) not null,
	th smallint(1) not null,
	pwd varchar(8) not null,
	now varchar(255) not null,
	name varchar(255) not null,
	email varchar(255) not null,
	sub varchar(255) not null,
	com text not null,
	host varchar(255) not null,
	status varchar(255) not null)
	TYPE = MYISAM
	COMMENT = 'PIO Structure V3'";
			$result2 = @mysql_query("SHOW CHARACTER SET like 'utf8'"); // 是否支援UTF-8 (MySQL 4.1.1開始支援)
			if($result2 && mysql_num_rows($result2)){
				$result .= ' CHARACTER SET utf8 COLLATE utf8_general_ci'; // 資料表追加UTF-8編碼
				mysql_free_result($result2);
			}
			mysql_query($result); // 正式新增資料表
			if($isAddInitData) $this->addPost(1, 0, '', '', 0, '', 0, 0, '', 0, 0, '', '05/01/01(六)00:00', $this->ENV['NONAME'], '', $this->ENV['NOTITLE'], $this->ENV['NOCOMMENT'], ''); // 追加一筆新資料
			$this->dbCommit();
		}
	}

	/* 準備/讀入 */
	function dbPrepare($transaction=false){
		if($this->prepared) return true;

		if(@!$this->con=mysql_pconnect($this->server, $this->username, $this->password)) $this->_error_handler('Open database failed', __LINE__);
		@mysql_select_db($this->dbname, $this->con);
		@mysql_query("SET NAMES 'utf8'"); // MySQL資料以UTF-8模式傳送
		if($transaction) @mysql_query('START TRANSACTION'); // 啟動交易性能模式 (據說會降低效能，但可防止資料寫入不一致)

		$this->prepared = 1;
	}

	/* 提交/儲存 */
	function dbCommit(){
		if(!$this->prepared) return false;

		//@mysql_query('COMMIT'); // 交易性能模式提交
	}

	/* 資料表維護 */
	function dbMaintanence($action,$doit=false){
		switch($action) {
			case 'optimize':
				if($doit){
					$this->dbPrepare(false);
					if($this->_mysql_call('OPTIMIZE TABLES '.$this->tablename)) return true;
					else return false;
				}else return true; // 支援最佳化資料表
				break;
			case 'check':
				if($doit){
					$this->dbPrepare(false);
					if($rs=$this->_mysql_call('CHECK TABLE '.$this->tablename)){
						mysql_data_seek($rs, mysql_num_rows($rs)-1);
						$row = mysql_fetch_assoc($rs);
						if ($row['Msg_type'] != "status")
							return "Table {$row_status['Table']}: {$row_status['Msg_type']} = {$row_status['Msg_text']}";
					}
					else return false;
				}else return true; // 支援檢查資料表
				break;
			case 'repair':
				if($doit){
					$this->dbPrepare(false);
					if($rs=$this->_mysql_call('REPAIR TABLE '.$this->tablename)){
						mysql_data_seek($rs, mysql_num_rows($rs)-1);
						$row = mysql_fetch_assoc($rs);
						if ($row['Msg_type'] != "status")
							return "Table {$row_status['Table']}: {$row_status['Msg_type']} = {$row_status['Msg_text']}";
					}
					else return false;
				}else return true; // 支援修復資料表
				break;
			case 'export':
				if($doit){
					$this->dbPrepare(false);
					$gp = gzopen('piodata.log.gz', 'w9');
					gzwrite($gp, $PIO->dbExport());
					gzclose($gp);
					return '<a href="piodata.log.gz">下載 piodata.log.gz 中介檔案</a>';
				}else return true; // 支援匯出資料
				break;
			default: return false; // 不支援
		}
	}

	/* 匯入資料來源 */
	function dbImport($data){
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
	substr($line[5], 0, 10).',\''.
	mysql_real_escape_string($line[3], $this->con).'\',\''.
	mysql_real_escape_string($line[4], $this->con).'\','.
	$line[5].',\''.mysql_real_escape_string($line[6], $this->con).'\','.
	$line[7].','.$line[8].',\''.mysql_real_escape_string($line[9], $this->con).'\','.$line[10].','.$line[11].',\''.
	mysql_real_escape_string($line[12], $this->con).'\',\''.
	mysql_real_escape_string($line[13], $this->con).'\',\''.
	mysql_real_escape_string($line[14], $this->con).'\',\''.
	mysql_real_escape_string($line[15], $this->con).'\',\''.
	mysql_real_escape_string($line[16], $this->con).'\',\''.
	mysql_real_escape_string($line[17], $this->con).'\',\''.
	mysql_real_escape_string($line[18], $this->con).'\',\''.
	$line[19].'\')';
			if(!$this->_mysql_call($SQL)) $this->_error_handler('Insert a new post failed', __LINE__);
		}
		$this->dbCommit(); // 送交
		return true;
	}

	/* 匯出資料來源 */
	function dbExport(){
		if(!$this->prepared) $this->dbPrepare();
		$line = $this->_mysql_call('SELECT no,resto,root,md5chksum,category,tim,ext,imgw,imgh,imgsize,tw,th,pwd,now,name,email,sub,com,host,status FROM '.$this->tablename.' ORDER BY no DESC');
		$data = '';
		$replaceComma = create_function('$txt', 'return str_replace(",", "&#44;", $txt);');
		while($row=mysql_fetch_array($line, MYSQL_ASSOC)){
			$row = array_map($replaceComma, $row); // 取代 , 為 &#44;
			if($row['root']=='0000-00-00 00:00:00') $row['root'] = '0'; // 初始值設為 0
			$data .= rtrim(implode(',', $row)).",\r\n";
		}
		mysql_free_result($line);
		return $data;
	}

	/* 文章數目 */
	function postCount($resno=0){
		if(!$this->prepared) $this->dbPrepare();

		if($resno){ // 回傳討論串總文章數目
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
	function threadCount(){
		if(!$this->prepared) $this->dbPrepare();

		$tree = $this->_mysql_call('SELECT COUNT(no) FROM '.$this->tablename.' WHERE resto = 0');
		$counttree = mysql_result($tree, 0); mysql_free_result($tree); // 計算討論串目前資料筆數
		return $counttree;
	}

	/* 取得最後文章編號 */
	function getLastPostNo($state){
		if(!$this->prepared) $this->dbPrepare();

		if($state=='afterCommit'){ // 送出後的最後文章編號
			$tree = $this->_mysql_call('SELECT MAX(no) FROM '.$this->tablename);
			$lastno = mysql_result($tree, 0); mysql_free_result($tree);
			return $lastno;
		}else return 0; // 其他狀態沒用
	}

	/* 輸出文章清單 */
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
	function fetchThreadList($start=0, $amount=0, $isDESC=false){
		if(!$this->prepared) $this->dbPrepare();

		$treeline = array();
		$tmpSQL = 'SELECT no FROM '.$this->tablename.' WHERE resto = 0 ORDER BY '.($isDESC ? 'no' : 'root').' DESC';
		if($amount) $tmpSQL .= " LIMIT {$start}, {$amount}"; // 有指定數量才用 LIMIT
		$tree = $this->_mysql_call($tmpSQL);
		while($rows=mysql_fetch_row($tree)) $treeline[] = $rows[0]; // 迴圈

		mysql_free_result($tree);
		return $treeline;
	}

	/* 輸出文章 */
	function fetchPosts($postlist){
		if(!$this->prepared) $this->dbPrepare();

		if(is_array($postlist)){ // 取多串
			$pno = implode(', ', $postlist); // ID字串
			$tmpSQL = 'SELECT * FROM '.$this->tablename.' WHERE no IN ('.$pno.') ORDER BY no';
			if(count($postlist) > 1){ if($postlist[0] > $postlist[1]) $tmpSQL .= ' DESC'; } // 由大排到小
		}else $tmpSQL = 'SELECT * FROM '.$this->tablename.' WHERE no = '.$postlist; // 取單串
		$line = $this->_mysql_call($tmpSQL);

		return $this->_ArrangeArrayStructure($line); // 輸出陣列結構
	}

	/* 刪除舊附件 (輸出附件清單) */
	function delOldAttachments($total_size, $storage_max, $warnOnly=true){
		global $FileIO;
		if(!$this->prepared) $this->dbPrepare();

		$arr_warn = $arr_kill = array(); // 警告 / 即將被刪除標記陣列
		if(!$result=$this->_mysql_call('SELECT no,ext,tim FROM '.$this->tablename." WHERE ext <> '' ORDER BY no")) $this->_error_handler('Get the old post failed', __LINE__);
		else{
			while(list($dno, $dext, $dtim)=mysql_fetch_row($result)){ // 個別跑舊文迴圈
				$dfile = $dtim.$dext; // 附加檔案名稱
				$dthumb = $dtim.'s.jpg'; // 預覽檔案名稱
				if($FileIO->imageExists($dfile)){ $total_size -= $FileIO->getImageFilesize($dfile) / 1024; $arr_kill[] = $dno; $arr_warn[$dno] = 1; } // 標記刪除
				if($FileIO->imageExists($dthumb)) $total_size -= $FileIO->getImageFilesize($dthumb) / 1024;
				if($total_size < $storage_max) break;
			}
		}
		mysql_free_result($result);
		return $warnOnly ? $arr_warn : $this->removeAttachments($arr_kill);
	}

	/* 刪除文章 */
	function removePosts($posts){
		if(!$this->prepared) $this->dbPrepare();

		$files = $this->removeAttachments($posts, true); // 先遞迴取得刪除文章及其回應附件清單
		$pno = implode(', ', $posts); // ID字串
		if(!$result=$this->_mysql_call('DELETE FROM '.$this->tablename.' WHERE no IN ('.$pno.') OR resto IN('.$pno.')')) $this->_error_handler('Delete old posts and replies failed', __LINE__); // 刪掉文章
		return $files;
	}

	/* 刪除附件 (輸出附件清單) */
	function removeAttachments($posts, $recursion=false){
		global $FileIO;
		if(!$this->prepared) $this->dbPrepare();

		$files = array();
		$pno = implode(', ', $posts); // ID字串
		if($recursion) $tmpSQL = 'SELECT ext,tim FROM '.$this->tablename.' WHERE (no IN ('.$pno.') OR resto IN('.$pno.")) AND ext <> ''"; // 遞迴取出 (含回應附件)
		else $tmpSQL = 'SELECT ext,tim FROM '.$this->tablename.' WHERE no IN ('.$pno.") AND ext <> ''"; // 只有指定的編號

		if(!$result=$this->_mysql_call($tmpSQL)) $this->_error_handler('Get attachments of the post failed', __LINE__);
		else{
			while(list($dext, $dtim)=mysql_fetch_row($result)){ // 個別跑迴圈
				$dfile = $dtim.$dext; // 附加檔案名稱
				$dthumb = $dtim.'s.jpg'; // 預覽檔案名稱
				if($FileIO->imageExists($dfile)) $files[] = $dfile;
				if($FileIO->imageExists($dthumb)) $files[] = $dthumb;
			}
		}
		mysql_free_result($result);
		return $files;
	}

	/* 新增文章/討論串 */
	function addPost($no, $resto, $md5chksum, $category, $tim, $ext, $imgw, $imgh, $imgsize, $tw, $th, $pwd, $now, $name, $email, $sub, $com, $host, $age=false, $status=''){
		if(!$this->prepared) $this->dbPrepare();

		$time = (int)substr($tim, 0, -3); // 13位數的數字串是檔名，10位數的才是時間數值
		$updatetime = gmdate('Y-m-d H:i:s'); // 更動時間 (UTC)
		if($resto){ // 新增回應
			$root = '0';
			if($age){ // 推文
				$query = 'UPDATE '.$this->tablename.' SET root = "'.$updatetime.'" WHERE no = '.$resto; // 將被回應的文章往上移動
				if(!$result=$this->_mysql_call($query)) $this->_error_handler('Push the post failed', __LINE__);
			}
		}else $root = $updatetime; // 新增討論串, 討論串最後被更新時間

		$query = 'INSERT INTO '.$this->tablename.' (resto,root,time,md5chksum,category,tim,ext,imgw,imgh,imgsize,tw,th,pwd,now,name,email,sub,com,host,status) VALUES ('.
	(int)$resto.','. // 回應編號
	"'$root',". // 最後更新時間
	$time.','. // 發文時間數值
	"'$md5chksum',". // 附加檔案md5
	"'".mysql_real_escape_string($category, $this->con)."',". // 分類標籤
	"'$tim', '$ext',". // 附加檔名
	$imgw.','.$imgh.",'".$imgsize."',".$tw.','.$th.','. // 圖檔長寬及檔案大小；預覽圖長寬
	"'".mysql_real_escape_string($pwd, $this->con)."',".
	"'$now',". // 時間(含ID)字串
	"'".mysql_real_escape_string($name, $this->con)."',".
	"'".mysql_real_escape_string($email, $this->con)."',".
	"'".mysql_real_escape_string($sub, $this->con)."',".
	"'".mysql_real_escape_string($com, $this->con)."',".
	"'".mysql_real_escape_string($host, $this->con)."', '".mysql_real_escape_string($status, $this->con)."')";
		if(!$this->_mysql_call($query)) $this->_error_handler('Insert a new post failed', __LINE__);
	}

	/* 檢查是否連續投稿 */
	function isSuccessivePost($lcount, $com, $timestamp, $pass, $passcookie, $host, $isupload){
		global $FileIO;
		if(!$this->prepared) $this->dbPrepare();

		if(!$this->ENV['PERIOD.POST']) return false; // 關閉連續投稿檢查
		$tmpSQL = 'SELECT pwd,host FROM '.$this->tablename.' WHERE time > '.($timestamp - $this->ENV['PERIOD.POST']); // 一般投稿時間檢查
		if($isupload) $tmpSQL .= ' OR time > '.($timestamp - $this->ENV['PERIOD.IMAGEPOST']); // 附加圖檔的投稿時間檢查 (與下者兩者擇一)
		else $tmpSQL .= ' OR md5(com) = "'.md5($com).'"'; // 內文一樣的檢查 (與上者兩者擇一)
		if(!$result=$this->_mysql_call($tmpSQL)) $this->_error_handler('Get the post to check the succession failed', __LINE__);
		else{
			while(list($lpwd, $lhost)=mysql_fetch_row($result)){
				// 判斷為同一人發文且符合連續投稿條件
				if($host==$lhost || $pass==$lpwd || $passcookie==$lpwd) return true;
			}
			return false;
		}
	}

	/* 檢查是否重複貼圖 */
	function isDuplicateAttechment($lcount, $md5hash){
		global $FileIO;
		if(!$this->prepared) $this->dbPrepare();

		if(!$result=$this->_mysql_call('SELECT tim,ext FROM '.$this->tablename." WHERE ext <> '' AND md5chksum = '$md5hash' ORDER BY no DESC")) $this->_error_handler('Get the post to check the duplicate attachment failed', __LINE__);
		else{
			while(list($ltim, $lext)=mysql_fetch_row($result)){
				if($FileIO->imageExists($ltim.$lext)) return true; // 有相同檔案
			}
			return false;
		}
	}

	/* 有此討論串? */
	function isThread($no){
		if(!$this->prepared) $this->dbPrepare();

		$result = $this->_mysql_call('SELECT no FROM '.$this->tablename.' WHERE no = '.$no.' AND resto = 0');
		return mysql_fetch_array($result);
	}

	/* 搜尋文章 */
	function searchPost($keyword, $field, $method){
		if(!$this->prepared) $this->dbPrepare();

		$keyword_cnt = count($keyword);
		$SearchQuery = 'SELECT * FROM '.$this->tablename." WHERE {$field} LIKE '%".($keyword[0])."%'";
		if($keyword_cnt > 1) for($i = 1; $i < $keyword_cnt; $i++) $SearchQuery .= " {$method} {$field} LIKE '%".($keyword[$i])."%'"; // 多重字串交集 / 聯集搜尋
		$SearchQuery .= ' ORDER BY no DESC'; // 按照號碼大小排序
		if(!$line=$this->_mysql_call($SearchQuery)) $this->_error_handler('Search the post failed', __LINE__);

		return $this->_ArrangeArrayStructure($line); // 輸出陣列結構
	}

	/* 搜尋類別標籤 */
	function searchCategory($category){
		if(!$this->prepared) $this->dbPrepare();

		$foundPosts = array();
		$SearchQuery = 'SELECT no FROM '.$this->tablename." WHERE lower(category) LIKE '%,".strtolower(mysql_real_escape_string($category)).",%' ORDER BY no DESC";
		$line = $this->_mysql_call($SearchQuery);
		while($rows=mysql_fetch_row($line)) $foundPosts[] = $rows[0];

		mysql_free_result($line);
		return $foundPosts;
	}

	/* 取得文章屬性 */
	function getPostStatus($status){
		return new FlagHelper($status); // 回傳 FlagHelper 物件
	}

	/* 更新文章 */
	function updatePost($no, $newValues){
		if(!$this->prepared) $this->dbPrepare();

		$chk = array('resto', 'md5chksum', 'category', 'tim', 'ext', 'imgw', 'imgh', 'imgsize', 'tw', 'th', 'pwd', 'now', 'name', 'email', 'sub', 'com', 'host', 'status');

		foreach($chk as $c)
			if(isset($newValues[$c]))
				if(!$this->_mysql_call('UPDATE '.$this->tablename." SET $c = '".mysql_real_escape_string($newValues[$c])."', root = root WHERE no = $no")) $this->_error_handler('Update the field of the post failed', __LINE__); // 更新討論串屬性
	}

	/* 設定文章屬性 */
	function setPostStatus($no, $newStatus){
		$this->updatePost($no, array('status' => $newStatus));
	}
}
?>