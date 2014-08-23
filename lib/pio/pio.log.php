<?php
/**
 * PIO Log API
 *
 * 提供存取以 Log 檔案構成的資料結構後端的物件
 *
 * @package PMCLibrary
 * @version $Id$
 * @date $Date$
 * @deprecated
 */

class PIOlog implements IPIO {
	var $ENV, $logfile, $treefile, $porderfile; // Local Constant
	var $logs, $trees, $LUT, $porder, $torder, $prepared; // Local Global

	function PIOlog($connstr='', $ENV){
		$this->ENV = $ENV;
		$this->logs = $this->trees = $this->LUT = $this->porder = $this->torder = array();
		$this->prepared = 0;

		if($connstr) $this->dbConnect($connstr);
	}

	/* private 把每一行 Log 解析轉換成陣列資料 */
	function _AnalysisLogs($line){
		$tline = array();
		list($tline['no'], $tline['resto'], $tline['md5chksum'], $tline['category'], $tline['tim'], $tline['ext'], $tline['imgw'], $tline['imgh'], $tline['imgsize'], $tline['tw'], $tline['th'], $tline['pwd'], $tline['now'], $tline['name'], $tline['email'], $tline['sub'], $tline['com'], $tline['host'], $tline['status']) = explode(',', $line);
		return array_reverse($tline); // list()是由右至左代入的
	}

	/* private 將回文放進陣列 */
	function _includeReplies($posts){
		$torder_flip = array_flip($this->torder);
		foreach($posts as $post){
			if(array_key_exists($post, $torder_flip)){ // 討論串首篇
				$posts = array_merge($posts, $this->trees[$post]);
			}
		}
		return array_merge(array(), array_unique($posts)); // 去除重複值
	}

	/* private 取代 , 成為 &#44; 避免衝突 */
	function _replaceComma($txt){
		return str_replace(',', '&#44;', $txt);
	}

	/* private 由編號取出資料分析成陣列 */
	function _ArrangeArrayStructure($line){
		$line = (array)$line; // 全部視為Arrays
		$posts = array();
		foreach($line as $i){
			if(!isset($this->LUT[$i])) continue;
			if(!is_array($this->logs[$this->LUT[$i]])){ // 進行分析轉換
				$line = $this->logs[$this->LUT[$i]]; if($line=='') continue;
				$this->logs[$this->LUT[$i]] = $this->_AnalysisLogs($line);
			}
			$posts[] = $this->logs[$this->LUT[$i]];
		}
		return $posts;
	}

	/* PIO模組版本 */
	function pioVersion(){
		return '0.5 (v20080920)';
	}

	/* 處理連線字串/連接 */
	function dbConnect($connStr){
		if(preg_match('/^log:\/\/(.*)\:(.*)\/$/i', $connStr, $linkinfos)){
			$this->logfile = $this->ENV['BOARD'].$linkinfos[1]; // 投稿文字記錄檔檔名
			$this->treefile = $this->ENV['BOARD'].$linkinfos[2]; // 樹狀結構記錄檔檔名
			$this->porderfile = $this->ENV['BOARD'].$this->ENV['LUTCACHE']; // LUT索引查找表暫存檔案
		}
	}

	/* 初始化 */
	function dbInit($isAddInitData = true){
		$chkfile = array($this->logfile, $this->treefile, $this->porderfile);
		// 自動建置
		foreach($chkfile as $value){
			if(!is_file($value)){ // 檔案不存在
				$fp = fopen($value, 'w');
				stream_set_write_buffer($fp, 0);
				if($value==$this->logfile) fwrite($fp, '1,0,,,0,,0,0,,0,0,,05/01/01(六)00:00,'.$this->ENV['NONAME'].',,'.$this->ENV['NOTITLE'].','.$this->ENV['NOCOMMENT'].',,,'); // PIO Structure V3
				if($value==$this->treefile) fwrite($fp, '1');
				if($value==$this->porderfile) fwrite($fp, '1');
				fclose($fp);
				unset($fp);
				@chmod($value, 0666);
			}
		}
		return true;
	}

	/* 準備/讀入 */
	function dbPrepare($reload=false, $transaction=true){
		if($this->prepared && !$reload) return true;
		if($reload && $this->prepared) $this->porder = $this->torder = $this->LUT = $this->logs = $this->trees = array();

		$this->logs = file($this->logfile); // Log每行原始資料
		if(!file_exists($this->porderfile)){ // LUT不在，重生成
			$lut = '';
			foreach($this->logs as $line){
				if(!isset($line)) continue;
				$tmp = explode(',', $line); $lut .= $tmp[0]."\r\n";
			}
			$fp = fopen($this->porderfile, 'w'); // LUT
			stream_set_write_buffer($fp, 0);
			flock($fp, LOCK_EX); // 鎖定檔案
			fwrite($fp, $lut);
			flock($fp, LOCK_UN); // 解鎖
			fclose($fp);
		}
		$this->porder = array_map('rtrim', file($this->porderfile)); // 文章編號陣列
		$this->LUT = array_flip($this->porder); // LUT索引查找表

		$tree = array_map('rtrim', file($this->treefile));
		foreach($tree as $treeline){ // 解析樹狀結構製成索引
			if($treeline=='') continue;
			$tline = explode(',', $treeline);
			$this->torder[] = $tline[0]; // 討論串首篇編號陣列
			$this->trees[$tline[0]] = $tline; // 特定編號討論串完整結構陣列
		}
		$this->prepared = 1;
	}

	/* 提交/儲存 */
	function dbCommit(){
		if(!$this->prepared) return false;

		$log = $tree = $lut = '';
		$this->logs = array_merge(array(), $this->logs); // 更新logs鍵值
		$this->torder = array_merge(array(), $this->torder); // 更新torder鍵值
		$this->porder = $this->LUT = array(); // 重新生成索引

		foreach($this->logs as $line){
			if(!isset($line)) continue;
			if(is_array($line)){ // 已被分析過
				$log .= implode(',', $line).",\r\n";
				$lut .= ($this->porder[] = $line['no'])."\r\n";
			}else{ // 尚未分析過
				$log .= $line;
				$tmp = explode(',', $line); $lut .= ($this->porder[] = $tmp[0])."\r\n";
			}
		}
		$this->LUT = array_flip($this->porder);
		$tcount = count($this->trees);
		for($tline = 0; $tline < $tcount; $tline++){
			$tree .= $this->isThread($this->torder[$tline]) ? implode(',', $this->trees[$this->torder[$tline]])."\r\n" : '';
		}

		$fp = fopen($this->logfile, 'w'); // Log
		stream_set_write_buffer($fp, 0);
		flock($fp, LOCK_EX); // 鎖定檔案
		fwrite($fp, $log);
		flock($fp, LOCK_UN); // 解鎖
		fclose($fp);

		$fp = fopen($this->treefile, 'w'); // tree
		stream_set_write_buffer($fp, 0);
		flock($fp, LOCK_EX); // 鎖定檔案
		fwrite($fp, $tree);
		flock($fp, LOCK_UN); // 解鎖
		fclose($fp);

		$fp = fopen($this->porderfile, 'w'); // LUT
		stream_set_write_buffer($fp, 0);
		flock($fp, LOCK_EX); // 鎖定檔案
		fwrite($fp, $lut);
		flock($fp, LOCK_UN); // 解鎖
		fclose($fp);
	}

	/* 資料表維護 */
	function dbMaintanence($action,$doit=false){
		switch($action) {
			case 'export':
				if($doit){
					$this->dbPrepare(false);
					$gp = gzopen('piodata.log.gz', 'w9');
					gzwrite($gp, $this->dbExport());
					gzclose($gp);
					return '<a href="piodata.log.gz">下載 piodata.log.gz 中介檔案</a>';
				}else return true; // 支援匯出資料
				break;
			case 'optimize':
			case 'check':
			case 'repair':
			default: return false; // 不支援
		}
	}

	/* 匯入資料來源 */
	function dbImport($data){
		$arrData = explode("\r\n", $data);
		$arrData_cnt = count($arrData) - 1; // 最後一個是空的
		$arrTree = array();
		$tree = $logs = $lut = '';
		for($i = 0; $i < $arrData_cnt; $i++){
			$line = explode(',', $arrData[$i], 4); // 切成四段
			$logs .= $line[0].','.$line[1].','.$line[3]."\r\n"; // 重建討論結構
			$lut .= $line[0]."\r\n"; // 重建 LUT 查找表結構
			if($line[1]==0){ // 首篇
				if(!isset($arrTree[$line[0]])) $arrTree[$line[0]] = array($line[0]); // 僅自身一篇
				else array_unshift($arrTree[$line[0]], $line[0]);
				continue;
			}
			if(!isset($arrTree[$line[1]])) $arrTree[$line[1]] = array();
			array_unshift($arrTree[$line[1]], $line[0]);
		}
		foreach($arrTree as $t) $tree .= implode(',', $t)."\r\n"; // 重建樹狀結構
		$chkfile = array($this->logfile, $this->treefile, $this->porderfile);
		foreach($chkfile as $value){
			$fp = fopen($value, 'w');
			stream_set_write_buffer($fp, 0);
			if($value==$this->logfile) fwrite($fp, $logs);
			if($value==$this->treefile) fwrite($fp, $tree);
			if($value==$this->porderfile) fwrite($fp, $lut);
			fclose($fp);
			unset($fp);
			@chmod($value, 0666);
		}
		return true;
	}

	/* 匯出資料來源 */
	function dbExport(){
		if(!$this->prepared) $this->dbPrepare();
		$f = file($this->logfile);
		$data = '';
		foreach($f as $line){
			$line = explode(',', $line, 3); // 分成三段 (最後一段特別長)
			if($line[1]==0 && isset($this->trees[$line[0]])){
				$lastno = array_pop($this->trees[$line[0]]);
				$line2 = $this->fetchPosts($lastno);
				$root = gmdate('Y-m-d H:i:s', substr($line2[0]['tim'], 0, 10)); // UTC 時間
				unset($this->trees[$line[0]]); // 刪除表示已取過
			}else{
				$root = '0';
			}
			$data .= $line[0].','.$line[1].','.$root.','.$line[2];
		}
		return $data;
	}

	/* 文章數目 */
	function postCount($resno=0){
		if(!$this->prepared) $this->dbPrepare();

		return $resno ? ($this->isThread($resno) ? count(@$this->trees[$resno]) : 0) : count($this->porder);
	}

	/* 討論串數目 */
	function threadCount(){
		if(!$this->prepared) $this->dbPrepare();

		return count($this->torder);
	}

	/* 取得最後的文章編號 */
	function getLastPostNo($state){
		if(!$this->prepared) $this->dbPrepare();

		switch($state){
			case 'beforeCommit':
			case 'afterCommit':
				return reset($this->porder);
		}
	}

	/* 輸出文章清單 */
	function fetchPostList($resno=0, $start=0, $amount=0){
		if(!$this->prepared) $this->dbPrepare();

		$plist = array();
		if($resno){
			if($this->isThread($resno)){
				if($start && $amount){
					$plist = array_slice($this->trees[$resno], $start, $amount);
					array_unshift($plist, $resno);
				}
				if(!$start && $amount) $plist = array_slice($this->trees[$resno], 0, $amount);
				if(!$start && !$amount) $plist = $this->trees[$resno];
			}
		}else{
			$plist = $amount ? array_slice($this->porder, $start, $amount) : $this->porder;
		}
		return $plist;
	}

	/* 輸出討論串清單 */
	function fetchThreadList($start=0, $amount=0, $isDESC=false){
		if(!$this->prepared) $this->dbPrepare();
		$tmp_array = $this->torder;
		if($isDESC) rsort($tmp_array); // 按編號遞減排序 (預設為按最後更新時間排序)
		return $amount ? array_slice($tmp_array, $start, $amount) : $tmp_array;
	}

	/* 輸出文章 */
	function fetchPosts($postlist,$fields='*'){
		if(!$this->prepared) $this->dbPrepare();

		return $this->_ArrangeArrayStructure($postlist); // 輸出陣列結構
	}

	/* 刪除舊附件 (輸出附件清單) */
	function delOldAttachments($total_size, $storage_max, $warnOnly=true){
		$FileIO = PMCLibrary::getFileIOInstance();
		if(!$this->prepared) $this->dbPrepare();

		$rpord = $this->porder; sort($rpord); // 由舊排到新 (小->大)
		$arr_warn = $arr_kill = array();
		foreach($rpord as $post){
			$logsarray = $this->_ArrangeArrayStructure($post); // 分析資料為陣列
			$dfile = $logsarray[0]['tim'].$logsarray[0]['ext'];
			$dthumb = $FileIO->resolveThumbName($logsarray[0]['tim']);
			if($FileIO->imageExists($dfile)){ $total_size -= $FileIO->getImageFilesize($dfile) / 1024; $arr_kill[] = $post; $arr_warn[$post] = 1; } // 標記刪除
			if($dthumb && $FileIO->imageExists($dthumb)) $total_size -= $FileIO->getImageFilesize($dthumb) / 1024;
			if($total_size < $storage_max) break;
		}
		return $warnOnly ? $arr_warn : $this->removeAttachments($arr_kill);
	}

	/* 刪除文章 */
	function removePosts($posts){
		if(!$this->prepared) $this->dbPrepare();
		if(count($posts)==0) return array();

		$posts = $this->_includeReplies($posts); // 包含所有回文
		$filelist = $this->removeAttachments($posts); // 欲刪除附件
		$torder_flip = array_flip($this->torder);
		$pcount = count($posts);
		$logsarray = $this->_ArrangeArrayStructure($posts); // 分析資料為陣列
		for($p = 0; $p < $pcount; $p++){
			if(!isset($logsarray[$p])) continue;
			if($logsarray[$p]['resto']==0){ // 討論串頭
				unset($this->trees[$logsarray[$p]['no']]); // 刪除樹狀記錄
				if(array_key_exists($logsarray[$p]['no'], $torder_flip)) unset($this->torder[$torder_flip[$logsarray[$p]['no']]]); // 從討論串首篇陣列中移除
			}else{
				// 從樹狀檔刪除
				if(array_key_exists($logsarray[$p]['resto'], $this->trees)){
					$tr_flip = array_flip($this->trees[$logsarray[$p]['resto']]);
					unset($this->trees[$logsarray[$p]['resto']][$tr_flip[$posts[$p]]]);
				}
			}
			unset($this->logs[$this->LUT[$logsarray[$p]['no']]]);
			if(array_key_exists($logsarray[$p]['no'], $this->LUT)) unset($this->porder[$this->LUT[$logsarray[$p]['no']]]); // 從討論串編號陣列中移除
		}
		$this->LUT = array_flip($this->porder);
		return $filelist;
	}

	/* 刪除附件 (輸出附件清單) */
	function removeAttachments($posts, $recursion = false){
		$FileIO = PMCLibrary::getFileIOInstance();
		if(!$this->prepared) $this->dbPrepare();
		if(count($posts)==0) return array();

		$files = array();
		$logsarray = $this->_ArrangeArrayStructure($posts); // 分析資料為陣列
		$lcount = count($logsarray);
		for($i = 0; $i < $lcount; $i++){
			if($logsarray[$i]['ext']){
				$dfile = $logsarray[$i]['tim'].$logsarray[$i]['ext'];
				$dthumb = $FileIO->resolveThumbName($logsarray[$i]['tim']);
				if($FileIO->imageExists($dfile)) $files[] = $dfile;
				if($dthumb && $FileIO->imageExists($dthumb)) $files[] = $dthumb;
			}
		}
		return $files;
	}

	/* 新增文章/討論串 */
	function addPost($no, $resto, $md5chksum, $category, $tim, $ext, $imgw, $imgh, $imgsize, $tw, $th, $pwd, $now, $name, $email, $sub, $com, $host, $age=false, $status='') {
		if(!$this->prepared) $this->dbPrepare();

		$tline = array($no, $resto, $md5chksum, $category, $tim, $ext, $imgw, $imgh, $imgsize, $tw, $th, $pwd, $now, $name, $email, $sub, $com, $host, $status);
		$tline = array_map(array($this, '_replaceComma'), $tline); // 將資料內的 , 轉換 (Only Log needed)
		array_unshift($this->logs, implode(',', $tline).",\r\n"); // 更新logs
		array_unshift($this->porder, $no); // 更新porder
		$this->LUT = array_flip($this->porder); // 更新LUT

		// 更新torder及trees
		if($resto){
			$this->trees[$resto][] = $no;
			if($age){
				$torder_flip = array_flip($this->torder);
				unset($this->torder[$torder_flip[$resto]]); // 先刪除舊有位置
				array_unshift($this->torder, $resto); // 再移到頂端
			}
		}else{
			$this->trees[$no][0] = $no;
			array_unshift($this->torder, $no);
		}
	}

	/* 檢查是否連續投稿 */
	function isSuccessivePost($lcount, $com, $timestamp, $pass, $passcookie, $host, $isupload){
		if(!$this->prepared) $this->dbPrepare();

		$pcount = $this->postCount();
		$lcount = ($pcount > $lcount) ? $lcount : $pcount;
		for($i = 0; $i < $lcount; $i++){
			$logsarray = $this->_ArrangeArrayStructure($this->porder[$i]); // 分析資料為陣列
			list($lcom, $lhost, $lpwd, $ltime) = array($logsarray[0]['com'], $logsarray[0]['host'], $logsarray[0]['pwd'], substr($logsarray[0]['tim'],0,-3));
			if($host==$lhost || $pass==$lpwd || $passcookie==$lpwd) $pchk = 1;
			else $pchk = 0;
			if($this->ENV['PERIOD.POST'] && $pchk){ // 密碼比對符合且開啟連續投稿時間限制
				if($timestamp - $ltime < $this->ENV['PERIOD.POST']) return true; // 投稿時間相距太短
				if($timestamp - $ltime < $this->ENV['PERIOD.IMAGEPOST'] && $isupload) return true; // 附加圖檔的投稿時間相距太短
				if($com == $lcom && !$isupload) return true; // 內文一樣
			}
		}
		return false;
	}

	/* 檢查是否重複貼圖 */
	function isDuplicateAttachment($lcount, $md5hash){
		$FileIO = PMCLibrary::getFileIOInstance();

		$pcount = $this->postCount();
		$lcount = ($pcount > $lcount) ? $lcount : $pcount;
		for($i = 0; $i < $lcount; $i++){
			$logsarray = $this->_ArrangeArrayStructure($this->porder[$i]); // 分析資料為陣列
			if(!$logsarray[0]['md5chksum']) continue; // 無附加圖檔
			if($logsarray[0]['md5chksum']==$md5hash){
				if($FileIO->imageExists($logsarray[0]['tim'].$logsarray[0]['ext'])) return true; // 存在MD5雜湊相同的檔案
			}
		}
		return false;
	}

	/* 有此討論串? */
	function isThread($no){
		if(!$this->prepared) $this->dbPrepare();

		return isset($this->trees[$no]);
	}

	/* 搜尋文章 */
	function searchPost($keyword,$field,$method){
		if(!$this->prepared) $this->dbPrepare();

		$foundPosts = array();
		$keyword_cnt = count($keyword);
		$pcount = $this->postCount();
		for($i = 0; $i < $pcount; $i++){
			$logsarray = $this->_ArrangeArrayStructure($this->porder[$i]); // 分析資料為陣列
			$found = 0;
			foreach($keyword as $k){
				if(strpos($logsarray[0][$field], $k)!==FALSE) $found++;
				if($method=="OR" && $found) break;
			}
			if($method=="AND" && $found==$keyword_cnt) array_push($foundPosts, $logsarray[0]); // 全部都有找到 (AND交集搜尋)
			elseif($method=="OR" && $found) array_push($foundPosts, $logsarray[0]); // 有找到 (OR聯集搜尋)
		}
		return $foundPosts;
	}

	/* 搜尋類別標籤 */
	function searchCategory($category){
		if(!$this->prepared) $this->dbPrepare();

		$category = strtolower($category);
		$foundPosts = array();
		$pcount = $this->postCount();
		for($i = 0; $i < $pcount; $i++){
			$logsarray = $this->_ArrangeArrayStructure($this->porder[$i]); // 分析資料為陣列
			if(!($ary_category = $logsarray[0]['category'])) continue;
			if(strpos(strtolower($ary_category), '&#44;'.$category.'&#44;')!==false) array_push($foundPosts, $logsarray[0]['no']); // 找到標籤，加入名單
		}
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

		$this->_ArrangeArrayStructure($no); // 將資料變成陣列
		foreach($chk as $c)
			if(isset($newValues[$c]))
				$this->logs[$this->LUT[$no]][$c] = $this->_replaceComma($newValues[$c]); // 修改數值
	}

	/* 設定文章屬性 */
	function setPostStatus($no, $newStatus){
		$this->updatePost($no, array('status' => $newStatus));
	}
}
?>