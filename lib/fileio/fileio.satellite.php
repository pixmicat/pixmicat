<?php

/**
 * FileIO Satellite 衛星計畫後端
 *
 * 搭配 satellite.php/pl 利用遠端空間管理圖檔
 *
 * @package PMCLibrary
 * @version $Id$
 * @date $Date$
 * @deprecated
 */
class FileIOsatellite extends AbstractIfsFileIO {

    var $userAgent, $parameter, $thumbLocalPath;

    /**
     * 測試連線並且初始化遠端衛星主機
     */
    public function init() {
        if (!($fp = @fsockopen($this->parameter[0]['host'], 80))) {
            return false;
        }

        $argument = 'mode=init&key=' . $this->parameter[2];
        $out = 'POST ' . $this->parameter[0]['path'] . " HTTP/1.1\r\n";
        $out .= 'Host: ' . $this->parameter[0]['host'] . "\r\n";
        $out .= 'User-Agent: ' . $this->userAgent . "\r\n";
        $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $out .= 'Content-Length: ' . strlen($argument) . "\r\n\r\n";
        $out .= $argument;
        fwrite($fp, $out);
        $result = fgets($fp, 128); // 取一次足以取到檔頭
        fclose($fp);

        return (strpos($result, '202 Accepted') !== false); // 檢查狀態值偵測是否傳輸成功
    }

    /**
     * 傳送抓取要求到遠端衛星主機上面
     */
    private function _transloadSatellite($imgname) {
        if (!($fp = @fsockopen($this->parameter[0]['host'], 80)))
            return false;
        $argument = 'mode=transload&key=' . $this->parameter[2] . '&imgurl=http:' . $this->getImageLocalURL($imgname) . '&imgname=' . $imgname;
        $out = 'POST ' . $this->parameter[0]['path'] . " HTTP/1.1\r\n";
        $out .= 'Host: ' . $this->parameter[0]['host'] . "\r\n";
        $out .= 'User-Agent: ' . $this->userAgent . "\r\n";
        $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $out .= 'Content-Length: ' . strlen($argument) . "\r\n\r\n";
        $out .= $argument;
        fwrite($fp, $out);
        $result = fgets($fp, 128); // 取一次足以取到檔頭
        fclose($fp);

        return (strpos($result, '202 Accepted') !== false); // 檢查狀態值偵測是否傳輸成功
    }

    /**
     * 直接傳送檔案到遠端衛星主機上面
     */
    private function _uploadSatellite($imgname, $imgpath) {
        srand((double) microtime() * 1000000);
        $boundary = '---------------------' . substr(md5(rand(0, 32000)), 0, 10); // 生成分隔線

        $argument = ''; // 資料暫存
        // 一般欄位資料轉換
        $formField = array('mode' => 'upload', 'key' => $this->parameter[2], 'imgname' => $imgname);
        foreach ($formField as $ikey => $ival) {
            $argument .= "--$boundary\r\n";
            $argument .= "Content-Disposition: form-data; name=\"" . $ikey . "\"\r\n\r\n";
            $argument .= $ival . "\r\n";
            $argument .= "--$boundary\r\n";
        }
        // 上傳檔案欄位資料轉換
        $imginfo = getimagesize($imgpath); // 取得圖檔資訊
        $argument .= "--$boundary\r\n";
        $argument .= 'Content-Disposition: form-data; name="imgfile"; filename="' . $imgname . '"' . "\r\n";
        $argument .= 'Content-Type: ' . $imginfo['mime'] . "\r\n\r\n";
        $argument .= join('', file($imgpath)) . "\r\n";
        $argument .= "--$boundary--\r\n";

        $out = 'POST ' . $this->parameter[0]['path'] . " HTTP/1.1\r\n";
        $out .= 'Host: ' . $this->parameter[0]['host'] . "\r\n";
        $out .= 'User-Agent: ' . $this->userAgent . "\r\n";
        $out .= "Content-Type: multipart/form-data, boundary=$boundary\r\n";
        $out .= 'Content-Length: ' . strlen($argument) . "\r\n\r\n";
        $out .= $argument;

        if (!($fp = @fsockopen($this->parameter[0]['host'], 80))) {
            return false;
        }
        fwrite($fp, $out);
        $result = fgets($fp, 128);
        fclose($fp);

        return (strpos($result, '202 Accepted') !== false);
    }

    /**
     * 發出刪除圖片要求
     */
    private function _deleteSatellite($imgname) {
        if (!($fp = @fsockopen($this->parameter[0]['host'], 80))) {
            return false;
        }

        $argument = 'mode=delete&key=' . $this->parameter[2] . '&imgname=' . $imgname;
        $out = 'POST ' . $this->parameter[0]['path'] . " HTTP/1.1\r\n";
        $out .= 'Host: ' . $this->parameter[0]['host'] . "\r\n";
        $out .= 'User-Agent: ' . $this->userAgent . "\r\n";
        $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $out .= 'Content-Length: ' . strlen($argument) . "\r\n\r\n";
        $out .= $argument;
        fwrite($fp, $out);
        $result = fgets($fp, 128);
        fclose($fp);

        return (strpos($result, '202 Accepted') !== false);
    }

    public function __construct($parameter, $ENV) {
        parent::__construct($parameter, $ENV);

        set_time_limit(120); // 執行時間 120 秒 (傳輸過程可能很長)
        $this->userAgent = 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1)'; // Just for fun ;-)
        $this->thumbLocalPath = $ENV['THUMB']; // 預覽圖本機位置
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

    public function deleteImage($imgname) {
        if (!is_array($imgname))
            $imgname = array($imgname); // 單一名稱參數

        $size = 0;
        $size_perimg = 0;
        foreach ($imgname as $i) {
            $size_perimg = $this->getImageFilesize($i);
            if (!$this->parameter[4] && strpos($i, 's.') !== false) {
                @unlink($this->thumbLocalPath . $i);
            } else {
                // 刪除出現錯誤
                if (!$this->_deleteSatellite($i)) {
                    if ($this->remoteImageExists($this->parameter[3] . $i))
                        continue; // 無法刪除，檔案存在 (保留索引)

// 無法刪除，檔案消失 (更新索引)
                }
            }
            $this->IFS->delRecord($i);
            $size += $size_perimg;
        }
        return $size;
    }

    public function uploadImage($imgname, $imgpath, $imgsize) {
        if (!$this->parameter[4] && strpos($imgname, 's.') !== false) {
            $this->IFS->addRecord($imgname, $imgsize, '');
            return true; // 不處理預覽圖
        }
        $result = $this->parameter[1]
                ? $this->_transloadSatellite($imgname)
                : $this->_uploadSatellite($imgname, $imgpath);
        if ($result) {
            $this->IFS->addRecord($imgname, $imgsize, ''); // 加入索引之中
            unlink($imgpath); // 確實上傳後刪除本機暫存
        }
        return $result;
    }

    public function getImageURL($imgname) {
        if (!$this->parameter[4] && strpos($imgname, 's.') !== false) {
            return $this->getImageLocalURL($imgname);
        }
        return $this->IFS->beRecord($imgname) ? $this->parameter[3] . $imgname : false;
    }
}
