<?php
/*
PIO - Pixmicat! data source I/O
Log API
*/

class PIOlog{
	var $logfile, $treefile, $porderfile; // Local Constant
	var $logs, $trees, $LUT, $porder, $torder, $prepared; // Local Global
	var $memcached, $mid;

	function PIOlog($connstr=''){
		$this->logs = $this->trees = $this->LUT = $this->porder = $this->torder = array();
		$this->prepared = 0;
		$this->mid = md5($_SERVER['SCRIPT_FILENAME']); // Unique ID
		$this->memcached = false; // memcached object (null: use, false: don't use)

		if($connstr) $this->dbConnect($connstr);
	}

	/* private 設定 memcached 資料 */
	function _memcacheSet($isAnalysis=true){
		if(!$this->_memcachedEstablish()) return false;
		$this->memcached->set('pmc'.$this->mid.'_isset', true);
		// 是否需要將每行資料分析為陣列
		$this->memcached->set('pmc'.$this->mid.'_logs', ($isAnalysis ? array_map(array($this, '_AnalysisLogs'), $this->logs) : $this->logs));
		$this->memcached->set('pmc'.$this->mid.'_trees', $this->trees);
		$this->memcached->set('pmc'.$this->mid.'_LUT', $this->LUT);
		$this->memcached->set('pmc'.$this->mid.'_porder', $this->porder);
		$this->memcached->set('pmc'.$this->mid.'_torder', $this->torder);
	}

	/* private 取得 memcached 資料 */
	function _memcacheGet(){
		if(!$this->_memcachedEstablish()) return false;
		if($this->memcached->get('pmc'.$this->mid.'_isset')){ // 有資料
			$this->logs = $this->memcached->get('pmc'.$this->mid.'_logs');
			$this->trees = $this->memcached->get('pmc'.$this->mid.'_trees');
			$this->LUT = $this->memcached->get('pmc'.$this->mid.'_LUT');
			$this->porder = $this->memcached->get('pmc'.$this->mid.'_porder');
			$this->torder = $this->memcached->get('pmc'.$this->mid.'_torder');
			return true;
		}else return false;
	}

	/* private 把每一行 Log 解析轉換成陣列資料 */
	function _AnalysisLogs($line){
		$tline = array();
		list($tline['no'], $tline['resto'], $tline['md5chksum'], $tline['category'], $tline['tim'], $tline['ext'], $tline['imgw'], $tline['imgh'], $tline['imgsize'], $tline['tw'], $tline['th'], $tline['pwd'], $tline['now'], $tline['name'], $tline['email'], $tline['sub'], $tline['com'], $tline['host'], $tline['status']) = explode(',', $line);
		return array_reverse($tline);
	}

	/* private 建立 memcached 實體 */
	function _memcachedEstablish(){
		if(!extension_loaded('memcache')) return ($this->memcached = false);
		if(is_null($this->memcached)){
			$this->memcached = new Memcache;
			if(!$this->memcached->pconnect('localhost')) return ($this->memcached = false);
			return true;
		}
		return ($this->memcached===false) ? false : true;
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
			if(!is_array($this->logs[$this->LUT[$i]])){ // 進行分析轉換
				$line = $this->logs[$this->LUT[$i]];
				if($line=='') continue;
				$tline = array();
				list($tline['no'], $tline['resto'], $tline['md5chksum'], $tline['category'], $tline['tim'], $tline['ext'], $tline['imgw'], $tline['imgh'], $tline['imgsize'], $tline['tw'], $tline['th'], $tline['pwd'], $tline['now'], $tline['name'], $tline['email'], $tline['sub'], $tline['com'], $tline['host'], $tline['status']) = explode(',', $line);
				$this->logs[$this->LUT[$i]] = array_reverse($tline); // list()是由右至左代入的
			}
			$posts[] = $this->logs[$this->LUT[$i]];
		}
		return $posts;
	}

	/* PIO模組版本 */
	function pioVersion(){
		return '0.4alpha with memcached (b20070201)';
	}

	/* 處理連線字串/連接 */
	function dbConnect($connStr){
		if(preg_match('/^log:\/\/(.*)\:(.*)\/$/i', $connStr, $linkinfos)){
			$this->logfile = $linkinfos[1]; // 投稿文字記錄檔檔名
			$this->treefile = $linkinfos[2]; // 樹狀結構記錄檔檔名
			$this->porderfile = 'lutcache.dat'; // LUT索引查找表檔案
		}
	}

	/* 初始化 */
	function dbInit(){
		$chkfile = array($this->logfile, $this->treefile, $this->porderfile);
		// 自動建置
		foreach($chkfile as $value){
			if(!is_file($value)){ // 檔案不存在
				$fp = fopen($value, 'w');
				stream_set_write_buffer($fp, 0);
				if($value==$this->logfile) fwrite($fp, '1,0,,,0,,0,0,,0,0,,05/01/01(六)00:00,無名氏,,無標題,無內文,,,');  // For Pixmicat!-PIO [Structure V2]
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
		if($this->_memcacheGet()){ $this->prepared = 1; return true; } // 如果 memcache 有快取則直接使用

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
		$this->_memcacheSet(); // 把目前資料設定到 memcached 內
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
		$this->_memcacheSet(false); // 更新快取 (不需要再分析)

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

	/* 優化資料表 */
	function dbOptimize($doit=false){
		return false; // 不支援
	}

	/* 匯入資料來源 */
	function dbImport(){
	
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
				$root = strftime('%Y-%m-%d %H:%M:%S', substr($line2[0]['tim'], 0, 10) + TIME_ZONE * 3600);
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
	function fetchThreadList($start=0, $amount=0){
		if(!$this->prepared) $this->dbPrepare();

		return $amount ? array_slice($this->torder, $start, $amount) : $this->torder;
	}

	/* 輸出文章 */
	function fetchPosts($postlist){
		if(!$this->prepared) $this->dbPrepare();

		return $this->_ArrangeArrayStructure($postlist); // 輸出陣列結構
	}

	/* 刪除舊文 */
	function delOldPostes(){
		if(!$this->prepared) $this->dbPrepare();

		$delPosts = @array_slice($this->porder, LOG_MAX - 1); // 截出舊文編號陣列
		if(count($delPosts)) return $this->removePosts($delPosts);
		else return false;
	}

	/* 刪除舊附件 (輸出附件清單) */
	function delOldAttachments($total_size, $storage_max, $warnOnly=true){
		global $FileIO;
		if(!$this->prepared) $this->dbPrepare();

		$rpord = $this->porder; sort($rpord); // 由舊排到新 (小->大)
		$arr_warn = $arr_kill = array();
		foreach($rpord as $post){
			$logsarray = $this->_ArrangeArrayStructure($post); // 分析資料為陣列
			if($FileIO->imageExists($logsarray[0]['tim'].$logsarray[0]['ext'])){ $total_size -= $FileIO->getImageFilesize($logsarray[0]['tim'].$logsarray[0]['ext']) / 1024; $arr_kill[] = $post; $arr_warn[$post] = 1; } // 標記刪除
			if($FileIO->imageExists($logsarray[0]['tim'].'s.jpg')) $total_size -= $FileIO->getImageFilesize($logsarray[0]['tim'].'s.jpg') / 1024;
			if($total_size < $storage_max) break;
		}
		return $warnOnly ? $arr_warn : $this->removeAttachments($arr_kill);
	}

	/* 刪除文章 */
	function removePosts($posts){
		if(!$this->prepared) $this->dbPrepare();

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
	function removeAttachments($posts){
		global $FileIO;
		if(!$this->prepared) $this->dbPrepare();

		$files = array();
		$logsarray = $this->_ArrangeArrayStructure($posts); // 分析資料為陣列
		$lcount = count($logsarray);
		for($i = 0; $i < $lcount; $i++){
			if($logsarray[$i]['ext']){
				if($FileIO->imageExists($logsarray[$i]['tim'].$logsarray[$i]['ext'])) $files[] = $logsarray[$i]['tim'].$logsarray[$i]['ext'];
				if($FileIO->imageExists($logsarray[$i]['tim'].'s.jpg')) $files[] = $logsarray[$i]['tim'].'s.jpg';
			}
		}
		return $files;
	}

	/* 新增文章/討論串 */
	function addPost($no, $resto, $md5chksum, $category, $tim, $ext, $imgw, $imgh, $imgsize, $tw, $th, $pwd, $now, $name, $email, $sub, $com, $host, $age=false) {
		if(!$this->prepared) $this->dbPrepare();

		$tline = array($no, $resto, $md5chksum, $category, $tim, $ext, $imgw, $imgh, $imgsize, $tw, $th, $pwd, $now, $name, $email, $sub, $com, $host, '');
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
			if(RENZOKU && $pchk){ // 密碼比對符合且開啟連續投稿時間限制
				if($timestamp - $ltime < RENZOKU) return true; // 投稿時間相距太短
				if($timestamp - $ltime < RENZOKU2 && $isupload) return true; // 附加圖檔的投稿時間相距太短
				if($com == $lcom && !$isupload) return true; // 內文一樣
			}
		}
		return false;
	}

	/* 檢查是否重複貼圖 */
	function isDuplicateAttechment($lcount, $md5hash){
		global $FileIO;

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

	/* 設定文章屬性 */
	function setPostStatus($no, $status, $statusType, $newValue){
		if(!$this->prepared) $this->dbPrepare();

		$scount = count($no);
		for($i = 0; $i < $scount; $i++){
			$statusType[$i] = explode(',', $statusType[$i]);
			$newValue[$i] = explode(',', $newValue[$i]);
			$st_count = count($statusType[$i]);
			for($j = 0; $j < $st_count; $j++){
				switch($statusType[$i][$j]){
					case 'TS': // 討論串鎖定
						if(strpos($status[$i], 'T')!==false && $newValue[$i][$j]==0)
							$status[$i] = str_replace('T', '', $status[$i]); // 討論串解除鎖定
						elseif(strpos($status[$i], 'T')===false && $newValue[$i][$j]==1)
							$status[$i] .= 'T'; // 討論串鎖定
						break;
					default:
				}
			}
			$this->_ArrangeArrayStructure($no[$i]); // 將資料變成陣列
			$this->logs[$this->LUT[$no[$i]]]['status'] = $status[$i]; // 修改狀態
		}
	}
}
?>