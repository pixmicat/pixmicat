<?php
namespace Pixmicat\Pio\Condition;

use Pixmicat\PMCLibrary;

/**
 * 以總文章篇數作為刪除判斷 
 */
class ByPostCountCondition implements IPIOCondition
{
    public static function check($type, $limit)
    {
        $PIO = PMCLibrary::getPIOInstance();
        return $PIO->postCount() >= $limit * ($type == 'predict' ? 0.95 : 1);
    }

    public static function listee($type, $limit)
    {
        $PIO = PMCLibrary::getPIOInstance();
        return $PIO->fetchPostList(0, intval($limit * ($type == 'predict' ? 0.95 : 1)) - 1, $limit);
    }

    public static function info($limit)
    {
        $PIO = PMCLibrary::getPIOInstance();
        return __CLASS__ . ': ' . ($pcnt = $PIO->postCount()) . '/' . $limit .
                sprintf(' (%.2f%%)', ($pcnt / $limit * 100));
    }
}
