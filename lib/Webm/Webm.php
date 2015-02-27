<?php

namespace Pixmicat\Webm;

use Pixmicat\PMCLibrary;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Exception\RuntimeException;

class Webm
{

    const IMAGETYPE_WEBM = 999;

    /**
     * 啟動時先檢查執行檔
     */
    public static function checkEnvironment()
    {
        global $FFMPEG_CONFIGS;

        if (!is_executable($FFMPEG_CONFIGS['ffmpeg.binaries']) || !is_executable($FFMPEG_CONFIGS['ffprobe.binaries'])) {
            if (defined('DEBUG')) {
                return false;
            } else {
                \Pixmicat\error(\Pixmicat\_T('webm_executable_not_found'));
            }
        }
    }

    /**
     * 檢查檔案是否為WebM
     * @param string $filename
     * @return boolean|array
     * @throws \FFMpeg\Exception\InvalidArgumentException
     */
    public static function isWebm($filename)
    {
        try {
            if (is_file($filename)) {
                $ffprobe = self::getFFProbeInstance();

                // check format
                $format = $ffprobe->format($filename);
                if (strstr((string) $format->get('format_name'), 'webm') === false) {
                    return false;
                }

                // extract stream
                $stream = $ffprobe->streams($filename)->first();
                if ($stream === null) {
                    throw new RuntimeException("Can't extract stream from $filename");
                } else {
                    return array(
                        'W' => (int) $stream->get('width'),
                        'H' => (int) $stream->get('height')
                    );
                }
            }
        } catch (RuntimeException $e) {
            self::runtimeException($e);
        }

        return false;
    }

    /**
     * 產生縮圖
     * @global array $THUMB_SETTING
     * @param string $filename
     * @param string $destination
     * @param array $info returned from Webm::isWebm
     * @param integer $W width of thumbnail
     * @param integer $H height of thumbnail
     * @return array
     * @throws \FFMpeg\Exception\InvalidArgumentException
     */
    public static function createThumbnail($filename, $destination, array $info, $W, $H)
    {
        global $THUMB_SETTING;

        try {
            // Extract first frame
            $ffmpeg = self::getFFMpegInstance();
            $video = $ffmpeg->open($filename);
            $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(0))
                    ->save($destination);

            // Create thumbnail of first frame
            $instThumb = PMCLibrary::getThumbInstance();
            $instThumb->setSourceConfig($destination, $info['W'], $info['H']);
            $instThumb->setThumbnailConfig($W, $H, $THUMB_SETTING);
            $instThumb->makeThumbnailtoFile($destination);
        } catch (\FFMpeg\Exception\RuntimeException $e) {
            self::runtimeException($e);
        }
    }

    /**
     * Log and show error message
     * @param RuntimeException $e
     */
    private static function runtimeException(RuntimeException $e)
    {
        PMCLibrary::getLoggerInstance()->error("Message: %s\nTrace:\n%s", $e->getMessage(), $e->getTraceAsString());
        \Pixmicat\error(\Pixmicat\_T('webm_exception'));
    }

    /**
     * @global array $FFMPEG_CONFIGS
     * @return FFMpeg
     */
    private static function getFFMpegInstance()
    {
        global $FFMPEG_CONFIGS;
        return FFMpeg::create($FFMPEG_CONFIGS);
    }

    /**
     * @global array $FFMPEG_CONFIGS
     * @return FFProbe
     */
    private static function getFFProbeInstance()
    {
        global $FFMPEG_CONFIGS;
        return FFProbe::create($FFMPEG_CONFIGS);
    }
}
