<?php
/*
FileIO - ImageShack
@Version : 0.2 20061207

使用此功能請遵守 ImageShack 網站的 Terms of Service，並注意以下條約:
Terms specific to the XML API:

    * Website or software must already be developed or have a strategic plan to be developed in the near future.
    * Website or software users must be informed that ImageShack is providing free image hosting.

Free implimentation support is offered to websites that have at least 500 unique visitors per day (users)
or expect to achieve 500 users in the near future. Otherwise, the XML API is offered as is.

使用時請自律將 ImageShack 網站連結置於明顯處，並說明正使用其提供之免費圖檔存放功能。
(http://reg.imageshack.us/content.php?page=linkto 可選擇喜歡方式使用)
*/

class FileIO{
	var $userAgent, $parameter, $index, $modified;

	/* private 傳檔案到 ImageShack 上面 (發送抓取請求) */
	function _transloadImageShack($imgname){
		if(!($fp = @fsockopen('www.imageshack.us', 80))) return false;

		$argument = 'xml=yes&rembar=1&url='.$this->getImageLocalURL($imgname);
		$out = "POST /transload.php HTTP/1.1\r\n";
		$out .= 'Host: www.imageshack.us'."\r\n";
		$out .= 'User-Agent: '.$this->userAgent."\r\n";
		$out .= "Content-Type: application/x-www-form-urlencoded\r\n";
		if($this->parameter[0]) $out .= 'Cookie: myimages='.$this->parameter[0]."\r\n"; // ImageShack Registration Key Cookie
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
					$xmlDatum = explode('<', $xmlDatum);
					if(count($xmlDatum) >= 3) $returnValue[$xmlDatum[1]] = $xmlDatum[2];
				}
			}
			return $returnValue;
		}
	}

	/* private 發出刪除圖片要求 (需填入 Registration Key) */
	function _deleteImageShack($imgURL){
		if(!$this->parameter[0]) return true; // 沒金鑰無法要求刪除故直接略過
		$imgURL = parse_url($imgURL); // 分析 URL 結構準備重組
		if(!($fp = @fsockopen($imgURL['host'], 80))) return false;

		$out = 'GET /delete.php?l='.substr($imgURL['path'], 1).'&c='.$this->parameter[0].'&page=THIS_IS_A_FLAG HTTP/1.1'."\r\n";
		$out .= 'Host: '.$imgURL['host']."\r\n";
		$out .= 'User-Agent: '.$this->userAgent."\r\n\r\n";
		fwrite($fp, $out);

		$result = '';
		while(!feof($fp)){ $result .= fgets($fp, 128); }
		fclose($fp);

		return (strpos($result, 'THIS_IS_A_FLAG')!==false ? true : false); // 偷吃步，偵測page是否為設定的特殊值
	}

	/* private 生成 ImageShack my.php 指向頁面位置 */
	function _myphpImageShack($imgurl, $ishotlink){
		if($ishotlink) return $imgurl; // 直連:直接傳回不需處理
		$imgurl = parse_url($imgurl);
		return 'http://'.$imgurl['host'].'/my.php?image='.basename($imgurl['path']);
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
		$this->userAgent = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)'; // Just for fun ;-)
		$this->parameter = unserialize(FILEIO_PARAMETER); // 將參數重新解析
		$this->modified = false; // 尚未修改索引
		$this->index = false;
		/*
			[0] : ImageShack 註冊金鑰 (即登入頁面 setlogin.php 網址後面附帶一串編碼) * 可不填，但功能會少
			登入後亦可在 http://reg.imageshack.us/content.php?page=register 找到 Your Registration Code
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
				if(!_deleteImageShack($this->index[$i]['imgURL'])) return false; // 送出刪除要求失敗
				unset($this->index[$i]); $this->modified = true; // 自索引中刪除
			}
			return true;
		}
		else{
			if(!_deleteImageShack($this->index[$imgname]['imgURL'])) return false;
			unset($this->index[$imgname]); $this->modified = true;
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
		$ishotlink = false; // 是否使用熱連結直連圖檔位置 (極有可能被 Ban 網域！請慎用)
		return $this->imageExists($imgname) ? (substr($imgname, -5)=='s.jpg' ? $this->index[$imgname]['imgURL'] : $this->_myphpImageShack($this->index[$imgname]['imgURL'], $ishotlink)) : false;
	}
}
?>