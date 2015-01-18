<?php
namespace Pixmicat\Thumb;

/**
 * 生成預覽圖界面
 */
interface IThumb
{
    /**
     * 取得物件資訊。
     *
     * @return string 物件資訊
     */
    function getClass();

    /**
     * 是否可在此環境下正常工作。
     *
     * @return boolean 是否可工作
     */
    function isWorking();

    /**
     * 設定來源圖參數。
     *
     * @param string $sourceFile 來源圖檔
     * @param int $sourceWidth 來源圖寬
     * @param int $sourceHeight 來源圖高
     */
    function setSourceConfig($sourceFile, $sourceWidth, $sourceHeight);

    /**
     * 設定預覽圖生成參數。
     *
     * @param int $thumbWidth 預覽圖寬
     * @param int $thumbHeight 預覽圖高
     * @param array $thumbSetting 預覽圖其他設定 (例如品質、格式等)
     */
    function setThumbnailConfig($thumbWidth, $thumbHeight, array $thumbSetting);

    /**
     * 產生預覽圖檔案。
     *
     * @param string $destFile 預覽圖檔案路徑
     */
    function makeThumbnailtoFile($destFile);
}
