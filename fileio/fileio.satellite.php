<?php
/*
FileIO - Satellite 衛星計畫
@Version : 0.2 20061212
*/

class FileIO{
	var $userAgent, $parameter;

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

	/* private 儲存索引檔 */
	function _setIndex(){
		global $IFS;
		$IFS->saveIndex(); // 索引表更新
	}

	function FileIO($parameter){
		global $IFS;
		$IFS->openIndex();
		register_shutdown_function(array($this, '_setIndex')); // 設定解構元 (PHP 結束前執行)
		set_time_limit(120); // 執行時間 120 秒 (傳輸過程可能很長)
		$this->userAgent = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)'; // Just for fun ;-)
		$this->parameter = unserialize($parameter); // 將參數重新解析
		$this->parameter[0] = parse_url($this->parameter[0]); // URL 位置拆解
		/*
			[0] : 衛星程式遠端 URL 位置
			[1] : 是否使用 Transload 方式要求衛星程式抓取圖檔 (true:是　false:否，使用傳統 HTTP 上傳)
			[2] : 傳輸認證金鑰
			[3] : 遠端目錄對應 URL
		*/
	}

	function init(){
		return $this->_initSatellite();
	}

	function imageExists($imgname){
		global $IFS;
		return $IFS->beRecord($imgname);
	}

	function deleteImage($imgname){
		global $IFS;
		if(is_array($imgname)){
			foreach($imgname as $i){
				if(!$this->_deleteSatellite($i)) return false;
				$IFS->delRecord($i); // 自索引中刪除
			}
			return true;
		}
		else{
			if($result = $this->_deleteSatellite($imgname)) $IFS->delRecord($imgname);
			return $result;
		}
	}

	function uploadImage($imgname='', $imgpath='', $imgsize=0){
		global $IFS;
		if($imgname=='') return true; // 支援上傳方法
		$result = $this->parameter[1] ? $this->_transloadSatellite($imgname) : $this->_uploadSatellite($imgname, $imgpath); // 選擇傳輸方法
		if($result){
			$IFS->addRecord($imgname, $imgsize, ''); // 加入索引之中
			unlink($imgpath); // 確實上傳後刪除本機暫存
		}
		return $result;
	}

	function getImageFilesize($imgname){
		global $IFS;
		if($rc = $IFS->getRecord($imgname)) return $rc['imgSize'];
		return false;
	}

	function getImageURL($imgname){
		global $IFS;
		return $IFS->beRecord($imgname) ? $this->parameter[3].$imgname : false;
	}
}
?>