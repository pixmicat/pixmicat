<?php
/**
 * FileIO Satellite 衛星計畫後端
 *
 * 搭配 satellite.php/pl 利用遠端空間管理圖檔
 *
 * @package PMCLibrary
 * @version $Id$
 * @date $Date$
 */

class FileIO{
	var $userAgent, $parameter, $thumbLocalPath;
	var $IFS;

	/* private 搜尋預覽圖檔之完整檔名 */
	function _resolveThumbName($thumbPattern){
		if(!$this->parameter[4]){ // 預覽圖在本機
			$find = glob($this->thumbLocalPath.$thumbPattern.'s.*');
			return ($find !== false && count($find) != 0)
				? basename($find[0]) : false;
		}else{ // 預覽圖在網路
			return $this->IFS->findThumbName($thumbPattern);
		}
	}

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
		$this->IFS->saveIndex(); // 索引表更新
	}

	function FileIO($parameter, $ENV){
		require($ENV['IFS.PATH']);
		$this->IFS = new IndexFS($ENV['IFS.LOG']); // IndexFS 物件
		$this->IFS->openIndex();
		register_shutdown_function(array($this, '_setIndex')); // 設定解構元 (PHP 結束前執行)
		set_time_limit(120); // 執行時間 120 秒 (傳輸過程可能很長)
		$this->userAgent = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)'; // Just for fun ;-)
		$this->thumbLocalPath = $ENV['PATH'].$ENV['THUMB']; // 預覽圖本機位置
		$this->parameter = $parameter; // 將參數重新解析
		$this->parameter[0] = parse_url($this->parameter[0]); // URL 位置拆解
		/*
			[0] : 衛星程式遠端 URL 位置
			[1] : 是否使用 Transload 方式要求衛星程式抓取圖檔 (true:是　false:否，使用傳統 HTTP 上傳)
			[2] : 傳輸認證金鑰
			[3] : 遠端目錄對應 URL
			[4] : 預覽圖是否上傳至遠端 (true: 是, false: 否，使用本機檔案)
		*/
	}

	function init(){
		return $this->_initSatellite();
	}

	function imageExists($imgname){
		if(!$this->parameter[4] && strpos($imgname, 's.') !== false) return file_exists($this->thumbLocalPath.$imgname);
		return $this->IFS->beRecord($imgname);
	}

	function deleteImage($imgname){
		if(!is_array($imgname))
			$imgname = array($imgname); // 單一名稱參數

		$size = 0; $size_perimg = 0;
		foreach($imgname as $i){
			$size_perimg = $this->getImageFilesize($i);
			if(!$this->parameter[4] && strpos($i, 's.') !== false){
				@unlink($this->thumbLocalPath.$i);
			}else{
				// 刪除出現錯誤
				if(!$this->_deleteSatellite($i)){
					if($this->remoteImageExists($this->parameter[3].$i)) continue; // 無法刪除，檔案存在 (保留索引)
					// 無法刪除，檔案消失 (更新索引)
				}
				$this->IFS->delRecord($i);
			}
			$size += $size_perimg;
		}
		return $size;
	}

	function uploadImage($imgname='', $imgpath='', $imgsize=0){
		if($imgname=='') return true; // 支援上傳方法
		if(!$this->parameter[4] && strpos($imgname, 's.') !== false) return false; // 不處理預覽圖
		$result = $this->parameter[1] ? $this->_transloadSatellite($imgname) : $this->_uploadSatellite($imgname, $imgpath); // 選擇傳輸方法
		if($result){
			$this->IFS->addRecord($imgname, $imgsize, ''); // 加入索引之中
			unlink($imgpath); // 確實上傳後刪除本機暫存
		}
		return $result;
	}

	function getImageFilesize($imgname){
		if(!$this->parameter[4] && strpos($imgname, 's.') !== false) return @filesize($this->thumbLocalPath.$imgname);
		if($rc = $this->IFS->getRecord($imgname)) return $rc['imgSize'];
		return false;
	}

	function getImageURL($imgname){
		if(!$this->parameter[4] && strpos($imgname, 's.') !== false) return $this->getImageLocalURL($imgname);
		return $this->IFS->beRecord($imgname) ? $this->parameter[3].$imgname : false;
	}
}
?>