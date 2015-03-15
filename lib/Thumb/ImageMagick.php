<?php
namespace Pixmicat\Thumb;

/**
 * Thumbnail Generate API: ImageMagick Wrapper
 *
 * 提供程式便於以 ImageMagick 命令列生成預覽圖的物件
 *
 * @package PMCLibrary
 * @version $Id$
 * @date $Date$
 */
class ImageMagick implements IThumb
{
    private $sourceFile;
    private $sourceWidth;
    private $sourceHeight;
    private $thumbWidth;
    private $thumbHeight;
    private $thumbSetting;
    private $thumbQuality;
    private $exec;

    public function __construct()
    {
        $this->exec = 'convert'; // ImageMagick "convert" Binary Location
    }

    public function getClass()
    {
        $str = 'ImageMagick Wrapper';
        if ($this->isWorking()) {
            $a = null;
            \preg_match('/^Version: ImageMagick (.*?) [hf]/', `$this->exec -version`, $a);
            $str .= ' : ' . $a[1];
            unset($a);
        }
        return $str;
    }

    public function isWorking()
    {
        if (!\function_exists('exec')) {
            return false;
        }

        \exec("$this->exec -version", $status, $retval);
        return ($retval === 0);
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
        if (!$this->isWorking()) {
            return false;
        }

        $CLI = "$this->exec -thumbnail {$this->thumbWidth}x{$this->thumbHeight} -quality $this->thumbQuality -flatten \"$this->sourceFile\" \"$destFile\"";
        \exec($CLI);
        return true;
    }
}
