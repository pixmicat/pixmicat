<?php
/*
FileIO - FTP
@Version : 0.2 20061205
*/

class FileIO{
	var $conn, $parameter, $index, $modified;

	/* private 登入 FTP */
	function _ftp_login(){
		if($this->conn) return true;
		$this->conn = ftp_connect($this->parameter[0], $this->parameter[1]);
		if($result = @ftp_login($this->conn, $this->parameter[2], $this->parameter[3])){
			if($this->parameter[4]=='PASV') ftp_pasv($this->conn, true); // 被動模式
			ftp_set_option($this->conn, FTP_TIMEOUT_SEC, 120); // 延長 Timeout 至 120 秒
			@ftp_chdir($this->conn, $this->parameter[5]);
		}
		return $result;
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
	function _ftp_close_and_setIndex(){
		if($this->conn) ftp_close($this->conn); // 有開啟 FTP 連線則關閉
		if($this->modified){ // 如果有修改索引就回存
			$indexlog = '';
			if(count($this->index)) foreach($this->index as $ikey => $ival){ $indexlog .= $ikey."\t\t".$ival."\n"; } // 有資料才跑迴圈
			$fp = fopen(FILEIO_INDEXLOG, 'w');
			fwrite($fp, $indexlog);
			fclose($fp);
		}
	}

	function FileIO(){
		register_shutdown_function(array($this, '_ftp_close_and_setIndex')); // 設定解構元 (PHP 結束前執行)
		set_time_limit(120); // 執行時間 120 秒 (FTP 傳輸過程可能很長)
		$this->parameter = unserialize(FILEIO_PARAMETER); // 將參數重新解析
		$this->modified = false; // 尚未修改索引
		$this->index = false;
		/*
			[0] : FTP 伺服器位置
			[1] : FTP 伺服器埠號
			[2] : FTP 使用者帳號
			[3] : FTP 使用者密碼
			[4] : 是否使用被動模式？ (PASV: 使用, NOPASV: 不使用)
			[5] : FTP 預設工作目錄
			[6] : 工作目錄對應 URL
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
		if(!$this->_getIndex() || !$this->_ftp_login()) return false;
		if(is_array($imgname)){
			foreach($imgname as $i){
				if(!ftp_delete($this->conn, $i)) return false;
				else{ unset($this->index[$i]); $this->modified = true; } // 自索引中刪除
			}
			return true;
		}
		else{
			$result = ftp_delete($this->conn, $imgname);
			if($result){ unset($this->index[$imgname]); $this->modified = true; }
			return $result;
		}
	}

	function uploadImage($imgname='', $imgpath='', $imgsize=0){
		if($imgname=='') return true; // 支援上傳方法
		if(!$this->_getIndex() || !$this->_ftp_login()) return false;
		$result = ftp_put($this->conn, $imgname, $imgpath, FTP_BINARY);
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
		return $this->imageExists($imgname) ? $this->parameter[6].$imgname : false;
	}
}
?>