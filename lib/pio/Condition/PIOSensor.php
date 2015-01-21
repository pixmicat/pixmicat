<?php
namespace Pixmicat\Pio\Condition;

/**
 * 文章自動刪除機制
 */
class PIOSensor
{
    public static function check($type, array $condobj)
    {
        foreach ($condobj as $sensor => $parameter) {
            // 有其中一個需要處理
            $result = \call_user_func_array(
                array(self::getSensorClassName($sensor), 'check'),
                array($type, $parameter)
            );
            if ($result === true) {
                return true;
            }
        }
        return false;
    }

    /**
     * 取得 Sensor 類別全名 (因 callable 目前無法套用目前 namespace)
     *
     * @param string $sensorName Sensor 類別名 (不含命名空間)
     * @return string 完整 Sensor 類別名
     */
    private static function getSensorClassName($sensorName) {
        return __NAMESPACE__ . "\\" . $sensorName;
    }

    public static function listee($type, array $condobj)
    {
        $tmparray = array(); // 項目陣列
        foreach ($condobj as $sensor => $parameter) {
            // 結果併進 $tmparray
            $tmparray = \array_merge(
                $tmparray,
                \call_user_func_array(
                    array(self::getSensorClassName($sensor), 'listee'),
                    array($type, $parameter)
                )
            );
        }
        \sort($tmparray); // 由舊排到新 (小到大)
        return \array_unique($tmparray);
    }

    public static function info(array $condobj)
    {
        $sensorinfo = '';
        foreach ($condobj as $sensor => $parameter) {
            $sensorinfo .= \call_user_func_array(
                array(self::getSensorClassName($sensor), 'info'),
                array($parameter)
            ) . "\n";
        }
        return $sensorinfo;
    }
}
