<?php

/**
 * FileIO FTP 遠端儲存 API
 *
 * 以遠端硬碟空間作為圖檔儲存的方式 (以 FTP 存取)，並提供一套方法供程式管理圖片
 *
 * @package PMCLibrary
 * @version $Id$
 * @date $Date$
 */
class FileIOftp extends AbstractIfsFileIO {

    var $conn, $parameter, $thumbLocalPath;

    /* private 登入 FTP */
    private function _ftp_login() {
        if ($this->conn) {
            return true;
        }
        $this->conn = ftp_connect($this->parameter[0], $this->parameter[1]);
        if ($result = @ftp_login($this->conn, $this->parameter[2], $this->parameter[3])) {
            if ($this->parameter[4] == 'PASV') {
                ftp_pasv($this->conn, true);
            } // 被動模式
            ftp_set_option($this->conn, FTP_TIMEOUT_SEC, 120); // 延長 Timeout 至 120 秒
            @ftp_chdir($this->conn, $this->parameter[5]);
        }
        return $result;
    }

    /**
     * 關閉 FTP 及儲存索引檔
     */
    private function _ftp_close() {
        if ($this->conn) {
            ftp_close($this->conn);
        }
    }

    public function __construct($parameter, $ENV) {
        parent::__construct($parameter, $ENV);

        register_shutdown_function(array($this, '_ftp_close')); // 設定解構元 (PHP 結束前執行)
        set_time_limit(120); // 執行時間 120 秒 (FTP 傳輸過程可能很長)
        $this->thumbLocalPath = $ENV['THUMB']; // 預覽圖本機位置
        $this->parameter = $parameter;
        /*
          [0] : FTP 伺服器位置
          [1] : FTP 伺服器埠號
          [2] : FTP 使用者帳號
          [3] : FTP 使用者密碼
          [4] : 是否使用被動模式？ (PASV: 使用, NOPASV: 不使用)
          [5] : FTP 預設工作目錄
          [6] : 工作目錄對應 URL
          [7] : 預覽圖是否上傳至遠端 (true: 是, false: 否，使用本機檔案)
         */
    }

    public function init() {
        return true;
    }

    public function deleteImage($imgname) {
        if (!$this->_ftp_login()) {
            return 0;
        }
        if (!is_array($imgname)) {
            $imgname = array($imgname);
        } // 單一名稱參數

        $size = 0;
        $size_perimg = 0;
        foreach ($imgname as $i) {
            $size_perimg = $this->getImageFilesize($i);
            if (!$this->parameter[7] && strpos($i, 's.') !== false) {
                @unlink($this->thumbLocalPath . $i);
            } else {
                if (!ftp_delete($this->conn, $i)) {
                    if ($this->remoteImageExists($this->parameter[6] . $i))
                        continue; // 無法刪除，檔案存在 (保留索引)

// 無法刪除，檔案消失 (更新索引)
                }
            }
            $this->IFS->delRecord($i); // 自索引中刪除
            $size += $size_perimg;
        }
        return $size;
    }

    public function uploadImage($imgname, $imgpath, $imgsize) {
        if (!$this->parameter[7] && strpos($imgname, 's.') !== false) {
            $this->IFS->addRecord($imgname, $imgsize, ''); // 加入索引之中
            return true; // 不處理預覽圖
        }
        if (!$this->_ftp_login()) {
            return false;
        }
        $result = ftp_put($this->conn, $imgname, $imgpath, FTP_BINARY);
        if ($result) {
            $this->IFS->addRecord($imgname, $imgsize, ''); // 加入索引之中
            unlink($imgpath); // 確實上傳後刪除本機暫存
        }
        return $result;
    }

    public function getImageURL($imgname) {
        if (!$this->parameter[7] && strpos($imgname, 's.') !== false) {
            return $this->getImageLocalURL($imgname);
        }
        return $this->IFS->beRecord($imgname) ? $this->parameter[6] . $imgname : false;
    }
}