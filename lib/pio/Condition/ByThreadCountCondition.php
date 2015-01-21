<?php
namespace Pixmicat\Pio\Condition;

use Pixmicat\PMCLibrary;

/**
 * 以總討論串數作為刪除判斷 
 */
class ByThreadCountCondition implements IPIOCondition
{
    public static function check($type, $limit)
    {
        $PIO = PMCLibrary::getPIOInstance();
        return $PIO->threadCount() >= ($type == 'predict' ? $limit * 0.95 : 1);
    }

    public static function listee($type, $limit)
    {
        $PIO = PMCLibrary::getPIOInstance();
        return $PIO->fetchThreadList(
                        \intval($limit * ($type == 'predict' ? 0.95 : 1)), $limit);
    }

    public static function info($limit)
    {
        $PIO = PMCLibrary::getPIOInstance();
        return __CLASS__ . ': ' . ($tcnt = $PIO->threadCount()) . '/' . $limit .
                \sprintf(' (%.2f%%)', ($tcnt / $limit * 100));
    }
}
