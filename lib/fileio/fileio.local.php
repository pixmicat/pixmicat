<?php

/**
 * FileIO Local 本機儲存 API (Without IFS 索引快取)
 *
 * 以本機硬碟空間作為圖檔儲存的方式，並提供一套方法供程式管理圖片
 *
 * 此版還原至舊版 (5th.Release) 的行為，判斷圖檔時仍使用檔案 I/O 來確認，
 * 避免特定環境下 IFS 出現錯誤造成圖檔無法顯示的問題。
 *
 * @package PMCLibrary
 * @version $Id$
 * @date $Date$
 * @since 8th.Release
 */
class FileIOlocal extends AbstractFileIO {
    var $imgPath, $thumbPath;

    public function __construct($parameter, $ENV) {
        parent::__construct();

        $this->imgPath = $ENV['IMG'];
        $this->thumbPath = $ENV['THUMB'];
    }

    public function init() {
        return true;
    }

    public function imageExists($imgname) {
        return file_exists($this->getImagePhysicalPath($imgname));
    }

    public function deleteImage($imgname) {
        if (!is_array($imgname)) {
            $imgname = array($imgname); // 單一名稱參數
        }

        $size = 0;
        $size_perimg = 0;
        foreach ($imgname as $i) {
            $size_perimg = $this->getImageFilesize($i);
            if (unlink($this->getImagePhysicalPath($i))) {
                $size += $size_perimg;
            } 
        }
        return $size;
    }

    private function getImagePhysicalPath($imgname) {
        return (strpos($imgname, 's.') !== false ? $this->thumbPath : $this->imgPath) . $imgname;
    }

    public function uploadImage($imgname, $imgpath, $imgsize) {
        return false;
    }

    public function getImageFilesize($imgname) {
        $size = filesize($this->getImagePhysicalPath($imgname));
        if ($size === false) {
            $size = 0;
        }
        return $size;
    }

    public function getImageURL($imgname) {
        return $this->getImageLocalURL($imgname);
    }

    public function resolveThumbName($thumbPattern) {
        $shortcut = $this->resolveThumbNameShortcut($thumbPattern);
        if ($shortcut !== false) {
            return $shortcut;
        }

        $find = glob($this->thumbPath . $thumbPattern . 's.*');
        return ($find !== false && count($find) != 0) ? basename($find[0]) : false;
    }

    /**
     * 用傳統的 1234567890123s.jpg 規則嘗試尋找預覽圖，運氣好的話只需要找一次。
     *
     * @param string $thumbPattern 預覽圖檔名
     * @return bool 是否找到
     */
    private function resolveThumbNameShortcut($thumbPattern) {
        $shortcutFind = $this->getImagePhysicalPath($thumbPattern . 's.jpg');
        if (file_exists($shortcutFind)) {
            return basename($shortcutFind);
        } else {
            return false;
        }
    }

    protected function getCurrentStorageSizeNoCache() {
        $totalSize = 0;
        $dirs = array(
            new RecursiveDirectoryIterator($this->imgPath),
            new RecursiveDirectoryIterator($this->thumbPath)
        );
        
        foreach ($dirs as $dir) {
            $totalSize += $this->getDirectoryTotalSize($dir);
        }
        return $totalSize;
    }

    private function getDirectoryTotalSize($dirIterator) {
        $dirSize = 0;
        foreach (new RecursiveIteratorIterator($dirIterator) as $file) {
            $dirSize += $file->getSize();
        }
        return $dirSize;
    }
}