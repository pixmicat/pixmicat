<?php
namespace Pixmicat\FileIo;

/**
 * FileIO Interface.
 */
interface IFileIO
{
    /**
     * 建置初始化。通常在安裝時做一次即可。
     */
    function init();

    /**
     * 圖檔是否存在。
     *
     * @param string $imgname 圖檔名稱
     * @return bool 是否存在
     */
    function imageExists($imgname);

    /**
     * 刪除圖片。
     *
     * @param string $imgname 圖檔名稱
     */
    function deleteImage($imgname);

    /**
     * 上傳圖片。
     *
     * @param string $imgname 圖檔名稱
     * @param string $imgpath 圖檔路徑
     * @param int $imgsize 圖檔檔案大小 (byte)
     */
    function uploadImage($imgname, $imgpath, $imgsize);

    /**
     * 取得圖檔檔案大小。
     *
     * @param string $imgname 圖檔名稱
     * @return mixed 檔案大小 (byte) 或 0 (失敗時)
     */
    function getImageFilesize($imgname);

    /**
     * 　取得圖檔的 URL 以便 &lt;img&gt; 標籤顯示圖片。
     *
     * @param string $imgname 圖檔名稱
     * @return string 圖檔 URL
     */
    function getImageURL($imgname);

    /**
     * 取得預覽圖檔名。
     *
     * @param string $thumbPattern 預覽圖檔名格式
     * @return string 預覽圖檔名
     */
    function resolveThumbName($thumbPattern);

    /**
     * 回傳目前總檔案大小 (單位 KB)
     *
     * @return int 目前總檔案大小
     */
    function getCurrentStorageSize();

    /**
     * 更新總檔案大小數值
     *
     * @param int $delta 本次更動檔案大小，作為差異修改之用 (單位 byte)
     * @return int 目前容量大小 (單位 byte)
     */
    function updateStorageSize($delta = 0);
}
