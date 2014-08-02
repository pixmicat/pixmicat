<?php
/**
 * Pixmicat! interface declarations
 *
 * @package PMCLibrary
 * @version $Id$
 */

/**
 * IPIO
 */
interface IPIO {
	/**
	 * 取得 PIO 模組版本。
	 *
	 * @return string PIO 版本資訊字串
	 */
	public function pioVersion();

	/**
	 * 處理連線字串/連接。
	 *
	 * @param  string $connStr 連線字串
	 */
	public function dbConnect($connStr);

	/**
	 * 資料來源初始化。
	 *
	 * @param  boolean $isAddInitData 是否建立一筆預設資料
	 */
	public function dbInit($isAddInitData = true);

	/**
	 * 連接資料來源並準備使用。
	 *
	 * @param  boolean $reload 是否強制重新連接
	 * @param  boolean $transaction 是否使用交易模式(如果支援的話)
	 */
	public function dbPrepare($reload = false, $transaction = false);

	/**
	 * 提交/儲存。
	 */
	public function dbCommit();

	/**
	 * 維護資料來源的操作。
	 *
	 * @param  string  $action 執行操作
	 * @param  boolean $doit   是否執行
	 * @return boolean         是否支援此操作 ($doit為false時做為查詢之用)
	 */
	public function dbMaintanence($action, $doit = false);

	/**
	 * 自中介格式匯入資料來源。
	 *
	 * @param  string $data 中介檔的檔案全文
	 * @return boolean       操作是否成功
	 */
	public function dbImport($data);

	/**
	 * 匯出資料來源至中介格式。
	 *
	 * @return string 中介檔的檔案全文
	 */
	public function dbExport();

	/**
	 * 取得文章數目。
	 *
	 * @param  integer $resno 討論串文章編號。有指定的話則回傳指定討論串之文章數
	 * @return integer         文章數目
	 */
	public function postCount($resno = 0);

	/**
	 * 取得討論串數目。
	 *
	 * @return integer         討論串數目
	 */
	public function threadCount();

	/**
	 * 取得最後文章編號。
	 *
	 * @param  string $state 取得狀態 'beforeCommit', 'afterCommit'
	 * @return integer        最後文章編號
	 */
	public function getLastPostNo($state);

	/**
	 * 輸出文章清單
	 *
	 * @param  integer $resno  指定編號討論串
	 * @param  integer $start  起始位置
	 * @param  integer $amount 數目
	 * @return array          文章編號陣列
	 */
	public function fetchPostList($resno = 0, $start = 0, $amount = 0);

	/**
	 * 輸出討論串清單
	 *
	 * @param  integer $start  起始位置
	 * @param  integer $amount 數目
	 * @param  boolean $isDESC 是否依編號遞減排序
	 * @return array          文章編號陣列
	 */
	public function fetchThreadList($start = 0, $amount = 0, $isDESC = false);

	/**
	 * 輸出文章
	 *
	 * @param  mixed $postlist 指定文章編號或文章編號陣列
	 * @param  string $fields   選擇輸出的欄位
	 * @return array           文章內容陣列
	 */
	public function fetchPosts($postlist, $fields = '*');

	/**
	 * 刪除舊附件 (輸出附件清單)
	 *
	 * @param  int  $total_size  目前使用容量
	 * @param  int  $storage_max 總容量限制
	 * @param  boolean $warnOnly    是否僅提醒不刪除
	 * @return array               附加圖檔及預覽圖陣列
	 */
	public function delOldAttachments($total_size, $storage_max, $warnOnly = true);

	/**
	 * 刪除文章
	 *
	 * @param  array $posts 刪除之文章編號陣列
	 * @return array        附加圖檔及預覽圖陣列
	 */
	public function removePosts($posts);

	/**
	 * 刪除附件 (輸出附件清單)
	 *
	 * @param  array  $posts     刪除之文章編號陣列
	 * @param  boolean $recursion 是否遞迴尋找相關文章與回應
	 * @return array             附加圖檔及預覽圖陣列
	 */
	public function removeAttachments($posts, $recursion = false);

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
	public function addPost($no, $resto, $md5chksum, $category, $tim, $ext,
		$imgw, $imgh, $imgsize, $tw, $th, $pwd, $now, $name, $email, $sub,
		$com, $host, $age = false, $status = '');

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
	public function isSuccessivePost($lcount, $com, $timestamp, $pass,
		$passcookie, $host, $isupload);

	/**
	 * 檢查是否重複貼圖
	 *
	 * @param  int  $lcount     檢查數目
	 * @param  string  $md5hash MD5
	 * @return boolean          是否為連續貼圖
	 */
	public function isDuplicateAttachment($lcount, $md5hash);

	/**
	 * 有此討論串?
	 *
	 * @param int $no        文章編號
	 * @return boolean     討論串是否存在
	 */
	public function isThread($no);

	/**
	 * 搜尋文章
	 *
	 * @param  array $keyword 關鍵字陣列
	 * @param  string $field   欄位
	 * @param  string $method  搜尋方法
	 * @return array          文章內容陣列
	 */
	public function searchPost($keyword, $field, $method);

	/**
	 * 搜尋類別標籤
	 *
	 * @param  string $category 類別
	 * @return array           此類別之文章編號陣列
	 */
	public function searchCategory($category);

	/**
	 * 取得文章狀態
	 *
	 * @param  string $status 旗標狀態
	 * @return FlagHelper         旗標狀態修改物件
	 */
	public function getPostStatus($status);

	/**
	 * 更新文章
	 *
	 * @param int $no        文章編號
	 * @param array $newValues 新欄位值陣列
	 */
	public function updatePost($no, $newValues);

	/**
	 * 設定文章屬性
	 *
	 * @param int $no        文章編號
	 */
	public function setPostStatus($no, $newStatus);
}

/**
 * IFileIO
 */
interface IFileIO {
    /**
     * 建置初始化。通常在安裝時做一次即可。
     */
    function init();

    /**
     * 圖檔是否存在。
     *
     * @param string $imgname 圖檔名稱
     * @return bool 是否存在
     */
    function imageExists($imgname);

    /**
     * 刪除圖片。
     *
     * @param string $imgname 圖檔名稱
     */
    function deleteImage($imgname);

    /**
     * 上傳圖片。
     *
     * @param string $imgname 圖檔名稱
     * @param string $imgpath 圖檔路徑
     * @param int $imgsize 圖檔檔案大小 (byte)
     */
    function uploadImage($imgname, $imgpath, $imgsize);

    /**
     * 取得圖檔檔案大小。
     *
     * @param string $imgname 圖檔名稱
     * @return mixed 檔案大小 (byte) 或 0 (失敗時)
     */
    function getImageFilesize($imgname);

    /**
     *　取得圖檔的 URL 以便 &lt;img&gt; 標籤顯示圖片。
     *
     * @param string $imgname 圖檔名稱
     * @return string 圖檔 URL
     */
    function getImageURL($imgname);

    /**
     * 取得預覽圖檔名。
     *
     * @param string $thumbPattern 預覽圖檔名格式
     * @return string 預覽圖檔名
     */
    function resolveThumbName($thumbPattern);

    /**
     * 回傳目前總檔案大小 (單位 KB)
     *
     * @return int 目前總檔案大小
     */
    function getCurrentStorageSize();

    /**
     * 更新總檔案大小數值
     *
     * @param int $delta 本次更動檔案大小，作為差異修改之用 (單位 byte)
     * @return int 目前容量大小 (單位 byte)
     */
    function updateStorageSize($delta = 0);
}

/**
 * IPIOCondition
 */
interface IPIOCondition {
	/**
	 * 檢查是否需要進行檢查步驟。
	 *
	 * @param  string $type  目前模式 ("predict" 預知提醒、"delete" 真正刪除)
	 * @param  mixed  $limit 判斷機制上限參數
	 * @return boolean       是否需要進行進一步檢查
	 */
	public static function check($type, $limit);

	/**
	 * 列出需要刪除的文章編號列表。
	 *
	 * @param  string $type  目前模式 ("predict" 預知提醒、"delete" 真正刪除)
	 * @param  mixed  $limit 判斷機制上限參數
	 * @return array         文章編號列表陣列
	 */
	public static function listee($type, $limit);

	/**
	 * 輸出 Condition 物件資訊。
	 *
	 * @param  mixed  $limit 判斷機制上限參數
	 * @return string        物件資訊文字
	 */
	public static function info($limit);
}

/**
 * ILogger
 */
interface ILogger {
	/**
	 * 建構元。
	 *
	 * @param string $logName Logger 名稱
	 * @param string $logFile 記錄檔案位置
	 */
	public function __construct($logName, $logFile);
	/**
	 * 檢查是否 logger 要記錄 DEBUG 等級。
	 *
	 * @return boolean 要記錄 DEBUG 等級與否
	 */
	public function isDebugEnabled();

	/**
	 * 檢查是否 logger 要記錄 INFO 等級。
	 *
	 * @return boolean 要記錄 INFO 等級與否
	 */
	public function isInfoEnabled();

	/**
	 * 檢查是否 logger 要記錄 ERROR 等級。
	 *
	 * @return boolean 要記錄 ERROR 等級與否
	 */
	public function isErrorEnabled();

	/**
	 * 以 DEBUG 等級記錄訊息。
	 *
	 * @param string $format 格式化訊息內容
	 * @param mixed $varargs 參數
	 */
	public function debug($format, $varargs = '');

	/**
	 * 以 INFO 等級記錄訊息。
	 *
	 * @param string $format 格式化訊息內容
	 * @param mixed $varargs 參數
	 */
	public function info($format, $varargs = '');

	/**
	 * 以 ERROR 等級記錄訊息。
	 *
	 * @param string $format 格式化訊息內容
	 * @param mixed $varargs 參數
	 */
	public function error($format, $varargs = '');
}

/**
 * MethodInterceptor (AOP Around Advice)
 */
interface MethodInterceptor {
	/**
	 * 代理呼叫方法。
	 *
	 * @param  array  $callable 要被呼叫的方法
	 * @param  array  $args     方法傳遞的參數
	 * @return mixed            方法執行的結果
	 */
	public function invoke(array $callable, array $args);
}

/**
 * IModule
 */
interface IModule {
	/**
	 * 回傳模組名稱方法
	 *
	 * @return string 模組名稱。建議回傳格式: mod_xxx : 簡短註解
	 */
	public function getModuleName();

	/**
	 * 回傳模組版本號方法
	 *
	 * @return string 模組版本號
	 */
	public function getModuleVersionInfo();
}