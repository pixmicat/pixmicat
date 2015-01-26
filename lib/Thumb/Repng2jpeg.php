<?php
namespace Pixmicat\Thumb;

/**
 * Thumbnail Generate API: Repng2jpeg Wrapper
 *
 * 提供程式便於以 repng2jpeg 生成預覽圖的物件
 *
 * @package PMCLibrary
 * @version $Id$
 * @date $Date$
 */
class Repng2jpeg implements IThumb
{
    private $sourceFile;
    private $sourceWidth;
    private $sourceHeight;
    private $thumbWidth;
    private $thumbHeight;
    private $thumbSetting;
    private $thumbQuality;
    
    private $exec;
    private $sysExec;
    private $supportBmp;
    private $shellEscape;

    public function __construct()
    {
        $this->exec = \realpath('./repng2jpeg'.(\strtoupper(\substr(\PHP_OS, 0, 3))==='WIN' ? '.exe' : ''));
        if (\strtoupper(\substr(\PHP_OS, 0, 3))==='WIN' && \strpos($this->exec, ' ')!==false) {
            $this->shellEscape = 1;
            $this->exec = '"'.$this->exec.'"';
        } elseif (\strtoupper(\substr(\PHP_OS, 0, 3))!='WIN' && \strpos($this->exec, ' ')!==false) {
            $this->shellEscape = 2;
            $this->exec = \str_replace(' ', '\ ', $this->exec);
        }
        if (\function_exists('exec')) {
            \exec('repng2jpeg --version', $status, $retval);
            if ($retval===0) {
                $this->sysExec = true;
                $this->exec = 'repng2jpeg';
            }
            $this->supportBmp = (\strpos(`$this->exec --help`, 'BMP')!==false);
        }
    }

    public function getClass()
    {
        $str = 'repng2jpeg Wrapper';
        if ($this->isWorking()) {
            $str .= ' : '.`$this->exec --version`;
            if ($this->supportBmp) {
                $str .= '(BMP supported)';
            }
            if ($this->sysExec) {
                $str .= '[S]';
            }
        }
        return $str;
    }

    public function isWorking()
    {
        return ($this->sysExec || \file_exists($this->shellUnescape($this->exec)))
            && \function_exists('exec')
            && ($this->sysExec || \strtoupper(\substr(PHP_OS, 0, 3))==='WIN' || \is_executable($this->shellUnescape($this->exec)));
    }

    private function shellUnescape($exec)
    {
        if ($this->shellEscape == 1) {
            return \substr($exec, 1, -1);
        } elseif ($this->shellEscape == 2) {
            return \str_replace('\ ', ' ', $exec);
        } else {
            return $exec;
        }
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
        if ($thumbSetting['Format']=='png') {
            $this->thumbQuality = 'P';
        } elseif ($thumbSetting['Format']=='gif') {
            $this->thumbQuality = 'G';
        }
    }

    public function makeThumbnailtoFile($destFile)
    {
        if (!$this->isWorking()) {
            return false;
        }
        $size = \getimagesize($this->sourceFile);
        switch ($size[2]) {
            case \IMAGETYPE_JPEG:
            case \IMAGETYPE_GIF:
            case \IMAGETYPE_PNG:
                break; // 僅支援此三種格式
            case \IMAGETYPE_BMP:
                if ($this->supportBmp) {
                    break;
                } else {
                    return false;
                }
            default:
                return false;
        }
        $CLI = "$this->exec \"$this->sourceFile\" \"$destFile\" $this->thumbWidth $this->thumbHeight $this->thumbQuality";
        \exec($CLI);
        return true;
    }
}
