<?php
namespace Pixmicat\FileIo;

use Pixmicat\Logger\ILogger;
use Pixmicat\PMCLibrary;

/**
 * 抽象 FileIO，預先實作好本地圖檔相關方法。
 */
abstract class AbstractFileIO implements IFileIO
{
    /** @var ILogger */
    protected $LOG;

    /**
     * 伺服器絕對位置
     *
     * @var string
     */
    private $absoluteUrl;

    /**
     * 圖檔總容量快取檔案位置
     *
     * @var string
     */
    private $cacheFile;

    public function __construct()
    {
        $this->LOG = PMCLibrary::getLoggerInstance('AbstractFileIO');
        $this->absoluteUrl = $this->getAbsoluteUrl();
        $this->cacheFile = $this->getCacheFile();
    }

    private function getAbsoluteUrl()
    {
        $phpSelf = $_SERVER['PHP_SELF'];
        return sprintf(
            '//%s%s', $_SERVER['HTTP_HOST'], substr($phpSelf, 0, strpos($phpSelf, PHP_SELF))
        );
    }

    private function getCacheFile()
    {
        return \STORAGE_PATH . 'sizecache.dat';
    }

    protected function getImageLocalURL($imgname)
    {
        return $this->absoluteUrl .
            (\strpos($imgname, 's.') !== false
                ? \basename(\THUMB_DIR)
                : \basename(\IMG_DIR)) .
            '/' . $imgname;
    }

    protected function remoteImageExists($img)
    {
        try {
            $result = \file_get_contents($img, false, null, 0, 1);
        } catch (\Exception $ignored) {
            $this->LOG->error("remoteImageExists -> file_get_contents failed");
            return false;
        }

        return ($result !== false);
    }

    public function getCurrentStorageSize()
    {
        $size = 0;
        if (!\is_file($this->cacheFile)) {
            $size = $this->updateStorageSize();
        } else {
            $size = \file_get_contents($this->cacheFile);
        }
        return \intval($size / 1024);
    }

    public function updateStorageSize($delta = 0)
    {
        if (!\is_file($this->cacheFile)) {
            $sizeNow = $this->getCurrentStorageSizeNoCache();
        } else {
            $sizeNow = \file_get_contents($this->cacheFile) + $delta;
            if ($delta == 0) {
                return $sizeNow;
            }
        }

        $this->writeToCache($sizeNow);
        return $sizeNow;
    }

    /**
     * 不依賴圖檔總容量快取檔案，取得目前圖檔所占空間 (單位 byte)
     *
     * @return int 圖檔所占空間
     */
    protected abstract function getCurrentStorageSizeNoCache();

    /**
     * 寫入到圖檔總容量快取檔案。
     *
     * @param int $sizeNow
     */
    private function writeToCache($sizeNow)
    {
        \file_put_contents(
            $this->cacheFile, $sizeNow, \LOCK_EX
        );
        \chmod($this->cacheFile, 0666);
    }

}
