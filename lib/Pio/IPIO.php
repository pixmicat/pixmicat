<?php
namespace Pixmicat\Pio;

/**
 * PIO Interface.
 */
interface IPIO
{
    /**
     * 取得 PIO 模組版本。
     *
     * @return string PIO 版本資訊字串
     */
    function pioVersion();

    /**
     * 處理連線字串/連接。
     *
     * @param  string $connStr 連線字串
     */
    function dbConnect($connStr);

    /**
     * 資料來源初始化。
     *
     * @param  boolean $isAddInitData 是否建立一筆預設資料
     */
    function dbInit($isAddInitData = true);

    /**
     * 連接資料來源並準備使用。
     *
     * @param  boolean $reload 是否強制重新連接
     * @param  boolean $transaction 是否使用交易模式(如果支援的話)
     */
    function dbPrepare($reload = false, $transaction = false);

    /**
     * 提交/儲存。
     */
    function dbCommit();

    /**
     * 維護資料來源的操作。
     *
     * @param  string  $action 執行操作
     * @param  boolean $doit   是否執行
     * @return boolean         是否支援此操作 ($doit為false時做為查詢之用)
     */
    function dbMaintanence($action, $doit = false);

    /**
     * 自中介格式匯入資料來源。
     *
     * @param  string $data 中介檔的檔案全文
     * @return boolean       操作是否成功
     */
    function dbImport($data);

    /**
     * 匯出資料來源至中介格式。
     *
     * @return string 中介檔的檔案全文
     */
    function dbExport();

    /**
     * 取得文章數目。
     *
     * @param  integer $resno 討論串文章編號。有指定的話則回傳指定討論串之文章數
     * @return integer         文章數目
     */
    function postCount($resno = 0);

    /**
     * 取得討論串數目。
     *
     * @return integer         討論串數目
     */
    function threadCount();

    /**
     * 取得最後文章編號。
     *
     * @param  string $state 取得狀態 'beforeCommit', 'afterCommit'
     * @return integer        最後文章編號
     */
    function getLastPostNo($state);

    /**
     * 輸出文章清單
     *
     * @param  integer $resno  指定編號討論串
     * @param  integer $start  起始位置
     * @param  integer $amount 數目
     * @return array          文章編號陣列
     */
    function fetchPostList($resno = 0, $start = 0, $amount = 0);

    /**
     * 輸出討論串清單
     *
     * @param  integer $start  起始位置
     * @param  integer $amount 數目
     * @param  boolean $isDESC 是否依編號遞減排序
     * @return array          文章編號陣列
     */
    function fetchThreadList($start = 0, $amount = 0, $isDESC = false);

    /**
     * 輸出文章
     *
     * @param  mixed $postlist 指定文章編號或文章編號陣列
     * @param  string $fields   選擇輸出的欄位
     * @return array           文章內容陣列
     */
    function fetchPosts($postlist, $fields = '*');

    /**
     * 刪除舊附件 (輸出附件清單)
     *
     * @param  int  $total_size  目前使用容量
     * @param  int  $storage_max 總容量限制
     * @param  boolean $warnOnly    是否僅提醒不刪除
     * @return array               附加圖檔及預覽圖陣列
     */
    function delOldAttachments($total_size, $storage_max, $warnOnly = true);

    /**
     * 刪除文章
     *
     * @param  array $posts 刪除之文章編號陣列
     * @return array        附加圖檔及預覽圖陣列
     */
    function removePosts($posts);

    /**
     * 刪除附件 (輸出附件清單)
     *
     * @param  array  $posts     刪除之文章編號陣列
     * @param  boolean $recursion 是否遞迴尋找相關文章與回應
     * @return array             附加圖檔及預覽圖陣列
     */
    function removeAttachments($posts, $recursion = false);

    /**
     * 新增文章/討論串
     *
     * @param int $no        文章編號
     * @param int  $resto     回應編號
     * @param string  $md5chksum 附加圖MD5
     * @param string  $category  類別
     * @param string  $tim       時間戳
     * @param string  $ext       附加圖副檔名
     * @param int  $imgw      附加圖寬
     * @param int  $imgh      附加圖高
     * @param string  $imgsize   附加圖大小
     * @param int  $tw        預覽圖寬
     * @param int  $th        預覽圖高
     * @param string  $pwd       密碼
     * @param string  $now       發文時間字串
     * @param string  $name      名稱
     * @param string  $email     電子郵件
     * @param string  $sub       標題
     * @param string  $com       內文
     * @param string  $host      主機名稱
     * @param boolean $age       是否推文
     * @param string  $status    狀態旗標
     */
    function addPost($no, $resto, $md5chksum, $category, $tim, $ext, $imgw, $imgh, $imgsize, $tw, $th, $pwd, $now, $name, $email, $sub, $com, $host, $age = false, $status = '');

    /**
     * 檢查是否連續投稿
     *
     * @param  int  $lcount     檢查數目
     * @param  string  $com        內文
     * @param  int  $timestamp  發文時間戳
     * @param  string  $pass       密碼
     * @param  string  $passcookie Cookie 密碼
     * @param  string  $host       主機名稱
     * @param  boolean  $isupload   是否上傳附加圖檔
     * @return boolean             是否為連續投稿
     */
    function isSuccessivePost($lcount, $com, $timestamp, $pass, $passcookie, $host, $isupload);

    /**
     * 檢查是否重複貼圖
     *
     * @param  int  $lcount     檢查數目
     * @param  string  $md5hash MD5
     * @return boolean          是否為連續貼圖
     */
    function isDuplicateAttachment($lcount, $md5hash);

    /**
     * 有此討論串?
     *
     * @param int $no        文章編號
     * @return boolean     討論串是否存在
     */
    function isThread($no);

    /**
     * 搜尋文章
     *
     * @param  array $keyword 關鍵字陣列
     * @param  string $field   欄位
     * @param  string $method  搜尋方法
     * @return array          文章內容陣列
     */
    function searchPost($keyword, $field, $method);

    /**
     * 搜尋類別標籤
     *
     * @param  string $category 類別
     * @return array           此類別之文章編號陣列
     */
    function searchCategory($category);

    /**
     * 取得文章狀態
     *
     * @param  string $status 旗標狀態
     * @return FlagHelper         旗標狀態修改物件
     */
    function getPostStatus($status);

    /**
     * 更新文章
     *
     * @param int $no        文章編號
     * @param array $newValues 新欄位值陣列
     */
    function updatePost($no, $newValues);

    /**
     * 設定文章屬性
     *
     * @param int $no 文章編號
     * @param string $newStatus 文章屬性
     */
    function setPostStatus($no, $newStatus);
}
