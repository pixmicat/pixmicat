<?php
namespace Pixmicat\Thumb;

/**
 * Thumbnail Generate API: Imagick Wrapper
 *
 * 提供程式便於以 Imagick (Imagick Image Library) 生成預覽圖的物件
 *
 * @package PMCLibrary
 * @version $Id$
 * @date $Date$
 */
class Imagick implements IThumb
{
    private $sourceFile;
    private $sourceWidth;
    private $sourceHeight;
    private $thumbWidth;
    private $thumbHeight;
    private $thumbSetting;
    private $thumbQuality;

    public function getClass()
    {
        $str = 'Imagick Wrapper';
        if ($this->isWorking()) {
            $a = new \Imagick();
            $b = $a->getVersion();
            $b = $b['versionString'];
            $str .= ' : ' . \str_replace(\strrchr($b, ' '), '', $b);
            unset($a);
            unset($b);
        }
        return $str;
    }

    public function isWorking()
    {
        return \extension_loaded('imagick') && \class_exists('Imagick');
    }

    public function setSourceConfig($sourceFile, $sourceWidth, $sourceHeight)
    {
        $this->sourceFile = $sourceFile;
        $this->sourceWidth = $sourceWidth;
        $this->sourceHeight = $sourceHeight;
    }

    public function setThumbnailConfig($thumbWidth, $thumbHeight, array $thumbSetting)
    {
        $this->thumbWidth = $thumbWidth;
        $this->thumbHeight = $thumbHeight;
        $this->thumbSetting = $thumbSetting;
        $this->thumbQuality = $thumbSetting['Quality'];
    }

    public function makeThumbnailtoFile($destFile)
    {
        $returnVal = false;
        if (!$this->isWorking()) {
            return false;
        }
        $image = new \Imagick($this->sourceFile);
        $image->setCompressionQuality($this->thumbQuality);
        $image->thumbnailImage($this->thumbWidth, $this->thumbHeight);
        $returnVal = $image->writeImage($destFile);
        unset($image);
        return $returnVal;
    }
}
