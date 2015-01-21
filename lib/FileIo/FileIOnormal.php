<?php
namespace Pixmicat\FileIo;

/**
 * FileIO Normal 本機儲存 API (With IFS 索引快取)
 *
 * 以本機硬碟空間作為圖檔儲存的方式，並提供一套方法供程式管理圖片
 *
 * @package PMCLibrary
 * @version $Id$
 * @date $Date$
 */
class FileIOnormal extends AbstractIfsFileIO
{
    private $imgPath;
    private $thumbPath;

    public function __construct($parameter, $ENV)
    {
        parent::__construct($parameter, $ENV);

        $this->imgPath = $ENV['IMG'];
        $this->thumbPath = $ENV['THUMB'];
    }

    public function init()
    {
        return true;
    }

    public function deleteImage($imgname)
    {
        if (!\is_array($imgname)) {
            $imgname = array($imgname); // 單一名稱參數
        }

        $size = 0;
        $size_perimg = 0;
        foreach ($imgname as $i) {
            $size_perimg = $this->getImageFilesize($i);
            // 刪除出現錯誤
            if (!\unlink($this->getImagePhysicalPath($i))) {
                if ($this->imageExists($i)) {
                    continue; // 無法刪除，檔案存在 (保留索引)
                }
                // 無法刪除，檔案消失 (更新索引)
            }
            $this->IFS->delRecord($i);
            $size += $size_perimg;
        }
        return $size;
    }

    /**
     * 取得圖檔的真實位置。
     */
    private function getImagePhysicalPath($imgname)
    {
        return (\strpos($imgname, 's.') !== false ? $this->thumbPath : $this->imgPath) . $imgname;
    }

    public function uploadImage($imgname, $imgpath, $imgsize)
    {
        $this->IFS->addRecord($imgname, $imgsize, ''); // 加入索引之中
    }

    public function getImageURL($imgname)
    {
        return $this->getImageLocalURL($imgname);
    }
}
