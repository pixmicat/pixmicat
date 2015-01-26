<?php
namespace Pixmicat\Pio\Condition;

/**
 * IPIOCondition
 */
interface IPIOCondition
{
    /**
     * 檢查是否需要進行檢查步驟。
     *
     * @param  string $type  目前模式 ("predict" 預知提醒、"delete" 真正刪除)
     * @param  mixed  $limit 判斷機制上限參數
     * @return boolean       是否需要進行進一步檢查
     */
    static function check($type, $limit);

    /**
     * 列出需要刪除的文章編號列表。
     *
     * @param  string $type  目前模式 ("predict" 預知提醒、"delete" 真正刪除)
     * @param  mixed  $limit 判斷機制上限參數
     * @return array         文章編號列表陣列
     */
    static function listee($type, $limit);

    /**
     * 輸出 Condition 物件資訊。
     *
     * @param  mixed  $limit 判斷機制上限參數
     * @return string        物件資訊文字
     */
    static function info($limit);
}
