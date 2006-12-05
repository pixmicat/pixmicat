<?php
/*
FileIO - ImageShack
@Version : 0.2 20061205
*/
class FileIO{
	var $parameter, $index, $modified;

	/* 傳檔案到 ImageShack 上面 */
	function _transloadImageShack($imgname){
		if(!($fp = @fsockopen('www.imageshack.us', 80))){ return false; }

		$argument = 'xml=yes&url='.$this->getImageLocalURL($imgname).($this->parameter[0] ? '&cookie='.$this->parameter[0] : '');
		$out = "POST /transload.php HTTP/1.1\r\n";
		$out .= 'Host: www.imageshack.us'."\r\n";
		$out .= 'User-Agent: Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)'."\r\n";
		$out .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$out .= 'Content-Length: '.strlen($argument)."\r\n\r\n";
		$out .= $argument;
		fwrite($fp, $out);

		$result = '';
		while(!feof($fp)){ $result .= fgets($fp, 128); }
		fclose($fp);

		if(strpos($result, '<'.'?xml version="1.0" encoding="iso-8859-1"?>')===false) return false;
		else{
			$returnValue = array();
			$xmlData = explode("\n", $result);
			foreach($xmlData as $xmlDatum){
				$xmlDatum = trim($xmlDatum);
				if($xmlDatum != '' && !eregi('links', $xmlDatum) && !eregi('xml', $xmlDatum)){
					$xmlDatum = str_replace('>', '<', $xmlDatum);
					list($xmlNull, $xmlName, $xmlValue) = explode('<', $xmlDatum);
					$returnValue[$xmlName] = $xmlValue;
				}
			}
			return $returnValue;
		}
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
			$this->index[$field[0]] = array('imgSize' => $field[1], 'imgURL' => $field[2]);
		}
		unset($indexlog); return true;
	}

	/* private 關閉 FTP 及儲存索引檔 */
	function _setIndex(){
		if($this->modified){ // 如果有修改索引就回存
			$indexlog = '';
			if(count($this->index)) foreach($this->index as $ikey => $ival){ $indexlog .= $ikey."\t\t".$ival['imgSize']."\t\t".$ival['imgURL']."\n"; } // 有資料才跑迴圈
			$fp = fopen(FILEIO_INDEXLOG, 'w');
			fwrite($fp, $indexlog);
			fclose($fp);
		}
	}

	function FileIO(){
		register_shutdown_function(array($this, '_setIndex')); // 設定解構元 (PHP 結束前執行)
		set_time_limit(120); // 執行時間 120 秒 (傳輸過程可能很長)
		$this->parameter = unserialize(FILEIO_PARAMETER); // 將參數重新解析
		$this->modified = false; // 尚未修改索引
		$this->index = false;
		/*
			[0] : ImageShack 註冊金鑰
		*/
	}

	function init(){
		if(!file_exists(FILEIO_INDEXLOG)){ touch(FILEIO_INDEXLOG); chmod(FILEIO_INDEXLOG, 0666); } // 建立索引檔
		return true;
	}

	function imageExists($imgname){
		if(!$this->_getIndex()) return false;
		return isset($this->index[$imgname]);
	}

	function deleteImage($imgname){
		if(!$this->_getIndex()) return false;
		if(is_array($imgname)){
			foreach($imgname as $i){
				unset($this->index[$i]); $this->modified = true; // 自索引中刪除
			}
			return true;
		}
		else{
			$this->modified = true; unset($this->index[$imgname]);
			return true;
		}
	}

	function uploadImage($imgname='', $imgpath='', $imgsize=0){
		if($imgname=='') return true; // 支援上傳方法
		if(substr($imgname, -5)=='s.jpg'){ unlink($imgpath); return true; } // 預覽圖不用上傳，直接刪除
		if(!$this->_getIndex()) return false;
		$result = $this->_transloadImageShack($imgname);
		if($result){
			$this->modified = true;
			$this->index[$imgname] = array('imgSize' => $imgsize, 'imgURL' => $result['image_link']); // 加入索引之中
			$this->index[substr($imgname, 0, 13).'s.jpg'] = array('imgSize' => ceil($imgsize * 0.25), 'imgURL' => $result['thumb_link']); // 加入索引之中
			unlink($imgpath); // 確實上傳後刪除本機暫存
		}
		return $result;
	}

	function getImageFilesize($imgname){
		return $this->imageExists($imgname) ? $this->index[$imgname]['imgSize'] : false;
	}

	function getImageURL($imgname){
		return $this->imageExists($imgname) ? $this->index[$imgname]['imgURL'] : false;
	}
}
?>