<?php
/*
FileIO - Satellite 衛星計畫
@Version : 0.2 20061207
*/

class FileIO{
	var $userAgent, $parameter, $index, $modified;

	/* private 測試連線並且初始化遠端衛星主機 */
	function _initSatellite(){
		if(!($fp = @fsockopen($this->parameter[0]['host'], 80))) return false;

		$argument = 'mode=init&key='.$this->parameter[2];
		$out = 'POST '.$this->parameter[0]['path']." HTTP/1.1\r\n";
		$out .= 'Host: '.$this->parameter[0]['host']."\r\n";
		$out .= 'User-Agent: '.$this->userAgent."\r\n";
		$out .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$out .= 'Content-Length: '.strlen($argument)."\r\n\r\n";
		$out .= $argument;
		fwrite($fp, $out);
		$result = fgets($fp, 128); // 取一次足以取到檔頭
		fclose($fp);

		return (strpos($result, '202 Accepted')!==false ? true : false); // 檢查狀態值偵測是否傳輸成功
	}

	/* private 傳送抓取要求到遠端衛星主機上面 */
	function _transloadSatellite($imgname){
		if(!($fp = @fsockopen($this->parameter[0]['host'], 80))) return false;

		$argument = 'mode=transload&key='.$this->parameter[2].'&imgurl='.$this->getImageLocalURL($imgname).'&imgname='.$imgname;
		$out = 'POST '.$this->parameter[0]['path']." HTTP/1.1\r\n";
		$out .= 'Host: '.$this->parameter[0]['host']."\r\n";
		$out .= 'User-Agent: '.$this->userAgent."\r\n";
		$out .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$out .= 'Content-Length: '.strlen($argument)."\r\n\r\n";
		$out .= $argument;
		fwrite($fp, $out);
		$result = fgets($fp, 128); // 取一次足以取到檔頭
		fclose($fp);

		return (strpos($result, '202 Accepted')!==false ? true : false); // 檢查狀態值偵測是否傳輸成功
	}

	/* private 直接傳送檔案到遠端衛星主機上面 */
	function _uploadSatellite($imgname, $imgpath){
		srand((double) microtime()*1000000);
		$boundary = '---------------------'.substr(md5(rand(0,32000)), 0, 10); // 生成分隔線

		$argument = ''; // 資料暫存
		// 一般欄位資料轉換
		$formField = array('mode' => 'upload', 'key' => $this->parameter[2], 'imgname' => $imgname);
		foreach($formField as $ikey => $ival){
			$argument .= "--$boundary\r\n";
			$argument .= "Content-Disposition: form-data; name=\"".$ikey."\"\r\n\r\n";
			$argument .= $ival."\r\n";
			$argument .= "--$boundary\r\n";
		}
		// 上傳檔案欄位資料轉換
		$imginfo = getimagesize($imgpath); // 取得圖檔資訊
		$argument .= "--$boundary\r\n";
		$argument .= 'Content-Disposition: form-data; name="imgfile"; filename="'.$imgname.'"'."\r\n";
		$argument .= 'Content-Type: '.$imginfo['mime']."\r\n\r\n";
		$argument .= join('', file($imgpath))."\r\n";
		$argument .= "--$boundary--\r\n";

		$out = 'POST '.$this->parameter[0]['path']." HTTP/1.1\r\n";
		$out .= 'Host: '.$this->parameter[0]['host']."\r\n";
		$out .= 'User-Agent: '.$this->userAgent."\r\n";
		$out .= "Content-Type: multipart/form-data, boundary=$boundary\r\n";
		$out .= 'Content-Length: '.strlen($argument)."\r\n\r\n";
		$out .= $argument;

		if(!($fp = @fsockopen($this->parameter[0]['host'], 80))) return false;
		fwrite($fp, $out);
		$result = fgets($fp, 128);
		fclose($fp);

		return (strpos($result, '202 Accepted')!==false ? true : false);
	}

	/* private 發出刪除圖片要求 */
	function _deleteSatellite($imgname){
		if(!($fp = @fsockopen($this->parameter[0]['host'], 80))) return false;

		$argument = 'mode=delete&key='.$this->parameter[2].'&imgname='.$imgname;
		$out = 'POST '.$this->parameter[0]['path']." HTTP/1.1\r\n";
		$out .= 'Host: '.$this->parameter[0]['host']."\r\n";
		$out .= 'User-Agent: '.$this->userAgent."\r\n";
		$out .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$out .= 'Content-Length: '.strlen($argument)."\r\n\r\n";
		$out .= $argument;
		fwrite($fp, $out);
		$result = fgets($fp, 128);
		fclose($fp);

		return (strpos($result, '202 Accepted')!==false ? true : false);
	}

	/* private 解析索引檔 */
	function _getIndex(){
		if(!file_exists(FILEIO_INDEXLOG)){ $this->init(); return false; }
		if($this->index!==false || filesize(FILEIO_INDEXLOG)==0) return true;
		$indexlog = file(FILEIO_INDEXLOG); $indexlog_count = count($indexlog); // 讀入索引檔並計算目前筆數
		$this->index = array(); // 把 index 從 false 換成 array() 表示已讀過
		for($i = 0; $i < $indexlog_count; $i++){
			if(!($trimline = rtrim($indexlog[$i]))) continue; // 本行無意義
			$field = explode("\t\t", $trimline);
			$this->index[$field[0]] = $field[1];
		}
		unset($indexlog); return true;
	}

	/* private 關閉 FTP 及儲存索引檔 */
	function _setIndex(){
		if($this->modified){ // 如果有修改索引就回存
			$indexlog = '';
			if(count($this->index)) foreach($this->index as $ikey => $ival){ $indexlog .= $ikey."\t\t".$ival."\n"; } // 有資料才跑迴圈
			$fp = fopen(FILEIO_INDEXLOG, 'w');
			fwrite($fp, $indexlog);
			fclose($fp);
		}
	}

	function FileIO(){
		register_shutdown_function(array($this, '_setIndex')); // 設定解構元 (PHP 結束前執行)
		set_time_limit(120); // 執行時間 120 秒 (傳輸過程可能很長)
		$this->userAgent = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)'; // Just for fun ;-)
		$this->parameter = unserialize(FILEIO_PARAMETER); // 將參數重新解析
		$this->parameter[0] = parse_url($this->parameter[0]); // URL 位置拆解
		$this->modified = false; // 尚未修改索引
		$this->index = false;
		/*
			[0] : 衛星程式遠端 URL 位置
			[1] : 是否使用 Transload 方式要求衛星程式抓取圖檔 (true:是　false:否，使用傳統 HTTP 上傳)
			[2] : 傳輸認證金鑰
			[3] : 遠端目錄對應 URL
		*/
	}

	function init(){
		if(!file_exists(FILEIO_INDEXLOG)){ touch(FILEIO_INDEXLOG); chmod(FILEIO_INDEXLOG, 0666); } // 建立索引檔
		return $this->_initSatellite();
	}

	function imageExists($imgname){
		if(!$this->_getIndex()) return false;
		return isset($this->index[$imgname]);
	}

	function deleteImage($imgname){
		if(!$this->_getIndex()) return false;
		if(is_array($imgname)){
			foreach($imgname as $i){
				if(!$this->_deleteSatellite($i)) return false;
				unset($this->index[$i]); $this->modified = true; // 自索引中刪除
			}
			return true;
		}
		else{
			$result = $this->_deleteSatellite($imgname);
			if($result){ unset($this->index[$imgname]); $this->modified = true; }
			return $result;
		}
	}

	function uploadImage($imgname='', $imgpath='', $imgsize=0){
		if($imgname=='') return true; // 支援上傳方法
		if(!$this->_getIndex()) return false;
		$result = $this->parameter[1] ? $this->_transloadSatellite($imgname) : $this->_uploadSatellite($imgname, $imgpath); // 選擇傳輸方法
		if($result){
			$this->modified = true;
			$this->index[$imgname] = $imgsize; // 加入索引之中
			unlink($imgpath); // 確實上傳後刪除本機暫存
		}
		return $result;
	}

	function getImageFilesize($imgname){
		return $this->imageExists($imgname) ? $this->index[$imgname] : false;
	}

	function getImageURL($imgname){
		return $this->imageExists($imgname) ? $this->parameter[3].$imgname : false;
	}
}
?>