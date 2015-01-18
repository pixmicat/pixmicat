<?php
namespace Pixmicat\Pio\Condition;

/**
 * 文章自動刪除機制
 */
class PIOSensor
{
    public static function check($type, array $condobj)
    {
        foreach ($condobj as $i => $j) {
            // 有其中一個需要處理
            if (\call_user_func_array(array($i, 'check'), array($type, $j)) === true) {
                return true;
            }
        }
        return false;
    }

    public static function listee($type, array $condobj)
    {
        $tmparray = array(); // 項目陣列
        foreach ($condobj as $i => $j) {
            // 結果併進 $tmparray
            $tmparray = \array_merge(
                $tmparray,
                \call_user_func_array(array($i, 'listee'), array($type, $j))
            );
        }
        \sort($tmparray); // 由舊排到新 (小到大)
        return \array_unique($tmparray);
    }

    public static function info(array $condobj)
    {
        $sensorinfo = '';
        foreach ($condobj as $i => $j) {
            $sensorinfo .= \call_user_func_array(array($i, 'info'), array($j)) . "\n";
        }
        return $sensorinfo;
    }
}
