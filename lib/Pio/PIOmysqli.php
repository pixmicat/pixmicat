<?php
namespace Pixmicat\Pio;

use Pixmicat\PMCLibrary;

/**
 * PIO MySQL improved API
 *
 * 提供存取以 MySQL 資料庫構成的資料結構後端的物件 (使用 MySQL improved extension)
 *
 * @package PMCLibrary
 * @version $Id$
 * @date $Date$
 */

class PIOmysqli implements IPIO {
	private $ENV, $username, $password, $server, $port, $dbname, $tablename; // Local Constant
	private $con, $prepared, $useTransaction; // Local Global

	public function __construct($connstr='', $ENV){
		$this->ENV = $ENV;
		$this->prepared = 0;
		if($connstr) $this->dbConnect($connstr);
	}

	public function __destruct(){
		if (!is_null($this->con)) @\mysqli_close($this->con);
	}

	/* private 攔截SQL錯誤 */
	private function _error_handler(array $errarray, $query=''){
		$err = sprintf('%s on line %d.', $errarray[0], $errarray[1]);
		if (defined('DEBUG') && DEBUG) {
			$err .= sprintf(PHP_EOL."Description: #%d: %s".PHP_EOL.
				"SQL: %s", $this->con->errno, $this->con->error, $query);
		}
		throw new \RuntimeException($err, $this->con->errno);
	}

	/* private 使用SQL字串和MySQL伺服器要求 */
	private function _mysql_call($query, $errarray=false){
		$resource = $this->con->query($query);
		if(is_array($errarray) && $resource===false) $this->_error_handler($errarray, $query);
		else return $resource;
	}

	/**
	 * mysqli_result::fetch_all < PHP 5.3 的相容方法。
	 * 如果 < PHP 5.3，使用傳統 for 迴圈取得全部陣列。
	 *
	 * @param mysqli_result $result mysqli_result 物件
	 * @param int $resulttype 回傳陣列類型
	 * @return array 結果陣列
	 */
	private function _mysqli_fetch_all(\mysqli_result $result, $resulttype = \MYSQLI_NUM) {
		if (method_exists($result, 'fetch_all')) {
			$res = $result->fetch_all($resulttype);
		} else {
			$res = array();
			while ($row = $result->fetch_array($resulttype)) {
				$res[] = $row;
			}
		}
		return $res;
	}

	/* PIO模組版本 */
	public function pioVersion(){
		return '0.6 (v20130221)';
	}

	/* 處理連線字串/連接 */
	public function dbConnect($connStr){
		// 格式： mysqli://帳號:密碼@伺服器位置:埠號(可省略)/資料庫/資料表/
		// 示例： mysqli://pixmicat:1234@127.0.0.1/pixmicat_use/imglog/
		if(preg_match('/^mysqli:\/\/(.*)\:(.*)\@(.*)(?:\:([0-9]+))?\/(.*)\/(.*)\/$/i', $connStr, $linkinfos)){
			$this->username = $linkinfos[1]; // 登入帳號
			$this->password = $linkinfos[2]; // 登入密碼
			$this->server = $linkinfos[3]; // 登入伺服器
			$this->port = $linkinfos[4] ? intval($linkinfos[4]) : 3306; // 埠號
			$this->dbname = $linkinfos[5]; // 資料庫名稱
			$this->tablename = $linkinfos[6]; // 資料表名稱
		}
	}

	/* 初始化 */
	public function dbInit($isAddInitData=true){
		$this->dbPrepare();
		if($this->con->query("SHOW TABLES LIKE '".$this->tablename."'")->num_rows == 0){ // 資料表不存在
			$result = "CREATE TABLE ".$this->tablename." (primary key(no),
	index (resto),index (root),index (time),
	no int(1) not null auto_increment,
	resto int(1) not null,
	root timestamp null DEFAULT 0,
	time int(1) not null,
	md5chksum varchar(32) not null,
	category varchar(255) not null,
	tim bigint(1) not null,
	ext varchar(5) not null,
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
	ENGINE = MYISAM
	COMMENT = 'PIO Structure V3'";
			if(version_compare($this->con->server_info, '5.5', '<')){ // 5.5 以前版本
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
			}

			$result2 = $this->con->query("SHOW CHARACTER SET like 'utf8'"); // 是否支援UTF-8 (MySQL 4.1.1開始支援)
			if($result2 && $result2->num_rows > 0){
				$result .= ' CHARACTER SET utf8 COLLATE utf8_general_ci'; // 資料表追加UTF-8編碼
				$result2->free();
			}
			$this->con->query($result); // 正式新增資料表
			// 追加一筆新資料
			if($isAddInitData) $this->addPost(1, 0, '', '', 0, '', 0, 0, '', 0, 0, '', '05/01/01(六)00:00', $this->ENV['NONAME'], '', $this->ENV['NOTITLE'], $this->ENV['NOCOMMENT'], '');
			$this->dbCommit();
		}
	}

	/* 準備/讀入 */
	public function dbPrepare($reload=false, $transaction=false){
		if($this->prepared) return true;

		$this->con = new \mysqli($this->server, $this->username, $this->password, $this->dbname, $this->port);
		if($this->con->connect_error)
			$this->_error_handler(array('Open database failed', __LINE__));

		$this->con->query("SET NAMES 'utf8'");

		$this->useTransaction = $transaction;
		if($transaction) $this->con->autocommit(FALSE);

		$this->prepared = 1;
	}

	/* 提交/儲存 */
	public function dbCommit(){
		if(!$this->prepared) return false;
		if($this->useTransaction) $this->con->commit();
	}

	/* 資料表維護 */
	public function dbMaintanence($action, $doit=false){
		switch($action) {
			case 'optimize':
				if($doit){
					$this->dbPrepare(false);
					return $this->con->query('OPTIMIZE TABLES '.$this->tablename);
				}else return true; // 支援最佳化資料表
				break;
			case 'check':
				if($doit){
					$this->dbPrepare(false);
					if($rs = $this->con->query('CHECK TABLE '.$this->tablename)){
						$rs->data_seek($rs->num_rows - 1);
						$row = $rs->fetch_assoc();
						return 'Table '.$row['Table'].': '.$row['Msg_type'].' = '.$row['Msg_text'];
					}
					else return false;
				}else return true; // 支援檢查資料表
				break;
			case 'repair':
				if($doit){
					$this->dbPrepare(false);
					if($rs = $this->con->query('REPAIR TABLE '.$this->tablename)){
						$rs->data_seek($rs->num_rows - 1);
						$row = $rs->fetch_assoc();
						return 'Table '.$row['Table'].': '.$row['Msg_type'].' = '.$row['Msg_text'];
					}
					else return false;
				}else return true; // 支援修復資料表
				break;
			case 'export':
				if($doit){
					$this->dbPrepare(false);
					$gp = gzopen('piodata.log.gz', 'w9');
					gzwrite($gp, $this->dbExport());
					gzclose($gp);
					return '<a href="piodata.log.gz">下載 piodata.log.gz 中介檔案</a>';
				}else return true; // 支援匯出資料
				break;
			default: return false; // 不支援
		}
	}

	/* 匯入資料來源 */
	public function dbImport($data){
		$this->dbInit(false); // 僅新增結構不新增資料
		$data = explode("\r\n", $data);
		$data_count = count($data) - 1;
		$replaceComma = create_function('$txt', 'return str_replace("&#44;", ",", $txt);');
		$SQL = 'INSERT INTO '.$this->tablename.' (no,resto,root,time,md5chksum,category,tim,ext,imgw,imgh,imgsize,tw,th,pwd,now,name,email,sub,com,host,status) VALUES '
				.'(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
		$stmt = $this->con->prepare($SQL);
		for($i = 0; $i < $data_count; $i++){
			$line = array_map($replaceComma, explode(',', $data[$i])); // 取代 &#44; 為 ,
			$tim = substr($line[5], 0, 10);
			if ($line[2] == '0') $line[2] = '0000-00-00 00:00:00';
			$stmt->bind_param('iisdssisiisiissssssss',
				$line[0],
				$line[1],
				$line[2],
				$tim,
				$line[3],
				$line[4],
				$line[5],
				$line[6],
				$line[7],
				$line[8],
				$line[9],
				$line[10],
				$line[11],
				$line[12],
				$line[13],
				$line[14],
				$line[15],
				$line[16],
				$line[17],
				$line[18],
				$line[19]);
			$stmt->execute() or $this->_error_handler(array('Insert a new post failed', __LINE__));
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
		while($row = $line->fetch_assoc()){
			$row = array_map($replaceComma, $row); // 取代 , 為 &#44;
			if($row['root']=='0000-00-00 00:00:00') $row['root'] = '0'; // 初始值設為 0
			$data .= implode(',', $row).",\r\n";
		}
		$line->free();
		return $data;
	}

	/* 文章數目 */
	public function postCount($resno=0){
		if(!$this->prepared) $this->dbPrepare();

		if($resno){ // 回傳討論串總文章數目
			$line = $this->_mysql_call('SELECT COUNT(no) FROM '.$this->tablename.' WHERE resto = '.intval($resno),
				array('Fetch count in thread failed', __LINE__));
			$rs = $line->fetch_row();
			$countline = $rs[0] + 1;
		}else{ // 回傳總文章數目
			$line = $this->_mysql_call('SELECT COUNT(no) FROM '.$this->tablename, array('Fetch count of posts failed', __LINE__));
			$rs = $line->fetch_row();
			$countline = $rs[0];
		}
		$line->free();
		return $countline;
	}

	/* 討論串數目 */
	public function threadCount(){
		if(!$this->prepared) $this->dbPrepare();

		$tree = $this->_mysql_call('SELECT COUNT(no) FROM '.$this->tablename.' WHERE resto = 0',
			array('Fetch count of threads failed', __LINE__));
		$counttree = $tree->fetch_row(); // 計算討論串目前資料筆數
		$tree->free();
		return $counttree[0];
	}

	/* 取得最後文章編號 */
	public function getLastPostNo($state){
		if(!$this->prepared) $this->dbPrepare();

                $tree = $this->_mysql_call('SELECT MAX(no) FROM '.$this->tablename, array('Get the last No. failed', __LINE__));
                $lastno = $tree->fetch_row();
                $tree->free();
                return $lastno[0];
	}

	/* 輸出文章清單 */
	public function fetchPostList($resno=0, $start=0, $amount=0){
		if(!$this->prepared) $this->dbPrepare();

		$line = array();
		$resno = intval($resno);
		if($resno){ // 輸出討論串的結構 (含自己, EX : 1,2,3,4,5,6)
			$tmpSQL = 'SELECT no FROM '.$this->tablename.' WHERE no = '.$resno.' OR resto = '.$resno.' ORDER BY no';
		}else{ // 輸出所有文章編號，新的在前
			$tmpSQL = 'SELECT no FROM '.$this->tablename.' ORDER BY no DESC';
			$start = intval($start); $amount = intval($amount);
			if($amount) $tmpSQL .= " LIMIT {$start}, {$amount}"; // 有指定數量才用 LIMIT
		}
		$tree = $this->_mysql_call($tmpSQL, array('Fetch post list failed', __LINE__));
		while($rows = $tree->fetch_row()) $line[] = $rows[0];
		$tree->free();
		return $line;
	}

	/* 輸出討論串清單 */
	public function fetchThreadList($start=0, $amount=0, $isDESC=false){
		if(!$this->prepared) $this->dbPrepare();

		$start = intval($start); $amount = intval($amount);
		$treeline = array();
		$tmpSQL = 'SELECT no FROM '.$this->tablename.' WHERE resto = 0 ORDER BY '.($isDESC ? 'no' : 'root').' DESC';
		if($amount) $tmpSQL .= " LIMIT {$start}, {$amount}"; // 有指定數量才用 LIMIT
		$tree = $this->_mysql_call($tmpSQL, array('Fetch thread list failed', __LINE__));
		while($rows = $tree->fetch_row()) $treeline[] = $rows[0];
		$tree->free();
		return $treeline;
	}

	/* 輸出文章 */
	public function fetchPosts($postlist,$fields='*'){
		if(!$this->prepared) $this->dbPrepare();

		if(is_array($postlist)){ // 取多串
			$postlist = array_filter($postlist, "is_numeric");
			if (count($postlist) == 0) return array();
			$pno = implode(',', $postlist); // ID字串
			$tmpSQL = 'SELECT '.$fields.' FROM '.$this->tablename.' WHERE no IN ('.$pno.') ORDER BY no';
			if(count($postlist) > 1){ if($postlist[0] > $postlist[1]) $tmpSQL .= ' DESC'; } // 由大排到小
		}else $tmpSQL = 'SELECT '.$fields.' FROM '.$this->tablename.' WHERE no = '.intval($postlist); // 取單串
		$line = $this->_mysql_call($tmpSQL, array('Fetch the post content failed', __LINE__));
		return $this->_mysqli_fetch_all($line, \MYSQLI_ASSOC); // 輸出陣列結構
	}

	/* 刪除舊附件 (輸出附件清單) */
	public function delOldAttachments($total_size, $storage_max, $warnOnly=true){
		$FileIO = PMCLibrary::getFileIOInstance();
		if(!$this->prepared) $this->dbPrepare();

		$arr_warn = $arr_kill = array(); // 警告 / 即將被刪除標記陣列
		$result = $this->_mysql_call('SELECT no,ext,tim FROM '.$this->tablename.' WHERE ext <> \'\' ORDER BY no',
			array('Get old posts failed', __LINE__));
		while(list($dno, $dext, $dtim) = $result->fetch_row()){ // 個別跑舊文迴圈
			$dfile = $dtim.$dext; // 附加檔案名稱
			$dthumb = $FileIO->resolveThumbName($dtim); // 預覽檔案名稱
			if($FileIO->imageExists($dfile)){ $total_size -= $FileIO->getImageFilesize($dfile) / 1024; $arr_kill[] = $dno; $arr_warn[$dno] = 1; } // 標記刪除
			if($dthumb && $FileIO->imageExists($dthumb)) $total_size -= $FileIO->getImageFilesize($dthumb) / 1024;
			if($total_size < $storage_max) break;
		}
		$result->free();
		return $warnOnly ? $arr_warn : $this->removeAttachments($arr_kill);
	}

	/* 刪除文章 */
	public function removePosts($posts){
		if(!$this->prepared) $this->dbPrepare();
		$posts = array_filter($posts, "is_numeric");
		if (count($posts) == 0) return array();

		$files = $this->removeAttachments($posts, true); // 先遞迴取得刪除文章及其回應附件清單
		$pno = implode(', ', $posts); // ID字串
		$this->_mysql_call('DELETE FROM '.$this->tablename.' WHERE no IN ('.$pno.') OR resto IN('.$pno.')',
			array('Delete old posts and replies failed', __LINE__)); // 刪掉文章
		return $files;
	}

	/* 刪除附件 (輸出附件清單) */
	public function removeAttachments($posts, $recursion=false){
		$FileIO = PMCLibrary::getFileIOInstance();
		if(!$this->prepared) $this->dbPrepare();
		$posts = array_filter($posts, "is_numeric");
		if (count($posts) == 0) return array();

		$files = array();
		$pno = implode(', ', $posts); // ID字串
		if($recursion) $tmpSQL = 'SELECT ext,tim FROM '.$this->tablename.' WHERE (no IN ('.$pno.') OR resto IN('.$pno.")) AND ext <> ''"; // 遞迴取出 (含回應附件)
		else $tmpSQL = 'SELECT ext,tim FROM '.$this->tablename.' WHERE no IN ('.$pno.") AND ext <> ''"; // 只有指定的編號

		$result = $this->_mysql_call($tmpSQL, array('Get attachments of the post failed', __LINE__));
		while(list($dext, $dtim) = $result->fetch_row()){ // 個別跑迴圈
			$dfile = $dtim.$dext; // 附加檔案名稱
			$dthumb = $FileIO->resolveThumbName($dtim); // 預覽檔案名稱
			if($FileIO->imageExists($dfile)) $files[] = $dfile;
			if($dthumb && $FileIO->imageExists($dthumb)) $files[] = $dthumb;
		}
		$result->free();
		return $files;
	}

	/* 新增文章/討論串 */
	public function addPost($no, $resto, $md5chksum, $category, $tim, $ext, $imgw, $imgh, $imgsize, $tw, $th, $pwd, $now, $name, $email, $sub, $com, $host, $age=false, $status=''){
		if(!$this->prepared) $this->dbPrepare();

		$time = (int)substr($tim, 0, -3); // 13位數的數字串是檔名，10位數的才是時間數值
		$updatetime = gmdate('Y-m-d H:i:s'); // 更動時間 (UTC)
		if($resto){ // 新增回應
			$root = '0000-00-00 00:00:00';
			if($age){ // 推文
				$result = $this->con->prepare('UPDATE '.$this->tablename.' SET root = ? WHERE no = ?');
				$result->bind_param('si', $updatetime, $resto);
				$result->execute() or $this->_error_handler(array('Push the post failed', __LINE__));
			}
		}else $root = $updatetime; // 新增討論串, 討論串最後被更新時間

		$SQL = 'INSERT INTO '.$this->tablename
				.' (no,resto,root,time,md5chksum,category,tim,ext,imgw,imgh,imgsize,tw,th,pwd,now,name,email,sub,com,host,status) VALUES '
				.'(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
		$stmt = $this->con->prepare($SQL);
		$stmt->bind_param('iisissdsiisiissssssss',
			$no,
                        $resto,
			$root,
			$time,
			$md5chksum,
			$category,
			$tim,
			$ext,
			$imgw,
			$imgh,
			$imgsize,
			$tw,
			$th,
			$pwd,
			$now,
			$name,
			$email,
			$sub,
			$com,
			$host,
			$status);
		$stmt->execute() or $this->_error_handler(array('Insert a new post failed', __LINE__));
	}

	/* 檢查是否連續投稿 */
	public function isSuccessivePost($lcount, $com, $timestamp, $pass, $passcookie, $host, $isupload){
		$FileIO = PMCLibrary::getFileIOInstance();
		if(!$this->prepared) $this->dbPrepare();

		if(!$this->ENV['PERIOD.POST']) return false; // 關閉連續投稿檢查
		$timestamp = intval($timestamp);
		$tmpSQL = 'SELECT pwd,host FROM '.$this->tablename.' WHERE time > '.($timestamp - (int)$this->ENV['PERIOD.POST']); // 一般投稿時間檢查
		if($isupload) $tmpSQL .= ' OR time > '.($timestamp - (int)$this->ENV['PERIOD.IMAGEPOST']); // 附加圖檔的投稿時間檢查 (與下者兩者擇一)
		else $tmpSQL .= ' OR md5(com) = "'.md5($com).'"'; // 內文一樣的檢查 (與上者兩者擇一)

		$result = $this->_mysql_call($tmpSQL, array('Get the post to check the succession failed', __LINE__));
		while(list($lpwd, $lhost) = $result->fetch_row()){
			// 判斷為同一人發文且符合連續投稿條件
			if($host==$lhost || $pass==$lpwd || $passcookie==$lpwd) return true;
		}
		return false;
	}

	/* 檢查是否重複貼圖 */
	public function isDuplicateAttachment($lcount, $md5hash){
		$FileIO = PMCLibrary::getFileIOInstance();
		if(!$this->prepared) $this->dbPrepare();

		$result = $this->_mysql_call('SELECT tim,ext FROM '.$this->tablename." WHERE ext <> '' AND md5chksum = '$md5hash' ORDER BY no DESC",
			array('Get the post to check the duplicate attachment failed', __LINE__));
		while(list($ltim, $lext) = $result->fetch_row()){
			if($FileIO->imageExists($ltim.$lext)) return true; // 有相同檔案
		}
		return false;
	}

	/* 有此討論串? */
	public function isThread($no){
		if(!$this->prepared) $this->dbPrepare();

		$result = $this->_mysql_call('SELECT no FROM '.$this->tablename.' WHERE no = '.intval($no).' AND resto = 0');
		return $result->fetch_row() ? true : false;
	}

	/* 搜尋文章 */
	public function searchPost($keyword, $field, $method){
		if(!$this->prepared) $this->dbPrepare();

		if (!in_array($field, array('com', 'name', 'sub', 'no'))) {
			$field = 'com';
		}
		if (!in_array($method, array('AND', 'OR'))) {
			$method = 'AND';
		}

		$keyword_cnt = count($keyword);
		$SearchQuery = 'SELECT * FROM '.$this->tablename." WHERE {$field} LIKE '".$this->con->real_escape_string('%'.$keyword[0].'%')."'";
		if($keyword_cnt > 1){
			for($i = 1; $i < $keyword_cnt; $i++){
				$SearchQuery .= " {$method} {$field} LIKE '".$this->con->real_escape_string('%'.$keyword[$i].'%')."'"; // 多重字串交集 / 聯集搜尋
			}
		}
		$SearchQuery .= ' ORDER BY no DESC'; // 按照號碼大小排序
		$line = $this->_mysql_call($SearchQuery, array('Search the post failed', __LINE__));
		return $this->_mysqli_fetch_all($line, \MYSQLI_ASSOC); // 輸出陣列結構
	}

	/* 搜尋類別標籤 */
	public function searchCategory($category){
		if(!$this->prepared) $this->dbPrepare();

		$foundPosts = array();
		$result = $this->con->prepare('SELECT no FROM '.$this->tablename.' WHERE lower(category) LIKE ? ORDER BY no DESC');
		$param = '%,'.strtolower($category).',%';
		$result->bind_param('s', $param);
		$result->bind_result($no);
		$result->execute();
		while($rows = $result->fetch()) $foundPosts[] = $no;
		return $foundPosts;
	}

	/* 取得文章屬性 */
	public function getPostStatus($status){
		return new FlagHelper($status); // 回傳 FlagHelper 物件
	}

	/* 更新文章 */
	public function updatePost($no, $newValues){
		if(!$this->prepared) $this->dbPrepare();

		$no = intval($no);
		$chk = array('resto', 'md5chksum', 'category', 'tim', 'ext', 'imgw', 'imgh', 'imgsize', 'tw', 'th', 'pwd', 'now', 'name', 'email', 'sub', 'com', 'host', 'status');
		foreach($chk as $c){
			if(isset($newValues[$c])){
				$this->_mysql_call('UPDATE '.$this->tablename." SET $c = '".$this->con->real_escape_string($newValues[$c])."', root = root WHERE no = ".$no,
					array('Update the field of the post failed', __LINE__)); // 更新討論串屬性
			}
		}
	}

	/* 設定文章屬性 */
	public function setPostStatus($no, $newStatus){
		$this->updatePost($no, array('status' => $newStatus));
	}
}
?>