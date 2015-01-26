<?php
namespace Pixmicat\Pio\Condition;

use Pixmicat\PMCLibrary;

/**
 * 以討論串生存時間作為刪除判斷
 */
class ByThreadAliveTimeCondition implements IPIOCondition
{
    public static function check($type, $limit)
    {
        $PIO = PMCLibrary::getPIOInstance();
        // 最舊討論串編號
        $oldestThreadNo = $PIO->fetchThreadList($PIO->threadCount() - 1, 1, true);
        $oldestThread = $PIO->fetchPosts($oldestThreadNo);
        return (\time() - \substr($oldestThread[0]['tim'], 0, 10) >= 86400 *
                $limit * ($type == 'predict' ? 0.95 : 1));
    }

    public static function listee($type, $limit)
    {
        $PIO = PMCLibrary::getPIOInstance();
        // 討論串編號陣列 (由舊到新)
        $ThreadNo = $PIO->fetchThreadList(0, 0, true);
        \sort($ThreadNo);
        $NowTime = \time();
        $i = 0;
        foreach ($ThreadNo as $t) {
            $post = $PIO->fetchPosts($t);
            if ($NowTime - \substr($post[0]['tim'], 0, 10) < 86400 * $limit *
                    ($type == 'predict' ? 0.95 : 1))
                break; // 時間不符合
            $i++;
        }
        if (\count($ThreadNo) === $i) {
            $i--;
        } // 保留最新的一篇避免全部刪除
        return \array_slice($ThreadNo, 0, $i);
    }

    public static function info($limit)
    {
        return __CLASS__ . ": $limit day(s)";
    }
}
