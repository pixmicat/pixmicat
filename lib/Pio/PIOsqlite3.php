<?php
namespace Pixmicat\Pio;

use PDO;
use Pixmicat\PMCLibrary;

/**
 * PIO SQLite3 (PDO) API
 * 提供存取以 SQLite3 資料庫構成的資料結構後端的物件 (需要 PHP 5.1.0 以上並開啟 PDO 功能)
 *
 * @package PMCLibrary
 * @version $Id$
 * @date $Date$
 */
class PIOsqlite3 implements IPIO
{
    private $ENV, $DSN, $tablename; // Local Constant
    /** @var PDO */
    private $con;
    private $prepared, $useTransaction; // Local Global

    public function __construct($connstr = '', $ENV)
    {
        $this->ENV = $ENV;
        $this->prepared = false;
        if ($connstr) {
            $this->dbConnect($connstr);
        }
    }

    /* private 攔截SQL錯誤 */
    private function _error_handler($errtext, $errline)
    {
        $err = sprintf('%s on line %d.', $errtext, $errline);
        if (defined('DEBUG') && DEBUG) {
            $err .= sprintf(PHP_EOL . "Description: %s",
                print_r($this->con->errorInfo(), true));
        }
        throw new \RuntimeException($err);
    }

    /* PIO模組版本 */
    function pioVersion()
    {
        return '0.6 (v20130221)';
    }

    /* 處理連線字串/連接 */
    public function dbConnect($connStr)
    {
        // 格式： sqlite3://資料庫檔案之位置/資料表/
        // 示例： sqlite3://pixmicat.db/imglog/
        // 　　　 sqlite3://:memory:/imglog
        if (preg_match('/^sqlite3:\/\/(.*)\/(.*)\/$/i', $connStr, $linkinfos)) {
            $this->DSN = 'sqlite:' . $linkinfos[1];
            $this->tablename = $linkinfos[2];
        }
    }

    /* 初始化 */
    public function dbInit($isAddInitData = true)
    {
        $this->dbPrepare();
        $nline = $this->con->query('SELECT COUNT(name) FROM sqlite_master WHERE name LIKE "' . $this->tablename . '"')->fetch();
        if ($nline[0] === '0') { // 資料表不存在
            $result = 'CREATE TABLE ' . $this->tablename . ' (
	"no" INTEGER  NOT NULL PRIMARY KEY,
	"resto" INTEGER  NOT NULL,
	"root" TIMESTAMP DEFAULT \'0\' NOT NULL,
	"time" INTEGER  NOT NULL,
	"md5chksum" VARCHAR(32)  NOT NULL,
	"category" VARCHAR(255)  NOT NULL,
	"tim" INTEGER  NOT NULL,
	"ext" VARCHAR(4)  NOT NULL,
	"imgw" INTEGER  NOT NULL,
	"imgh" INTEGER  NOT NULL,
	"imgsize" VARCHAR(10)  NOT NULL,
	"tw" INTEGER  NOT NULL,
	"th" INTEGER  NOT NULL,
	"pwd" VARCHAR(8)  NOT NULL,
	"now" VARCHAR(255)  NOT NULL,
	"name" VARCHAR(255)  NOT NULL,
	"email" VARCHAR(255)  NOT NULL,
	"sub" VARCHAR(255)  NOT NULL,
	"com" TEXT  NOT NULL,
	"host" VARCHAR(255)  NOT NULL,
	"status" VARCHAR(255)  NOT NULL
	);'; // PIO Structure V3
            $idx = array('resto', 'root', 'time');
            foreach ($idx as $x) {
                $result .= 'CREATE INDEX IDX_' . $this->tablename . '_' . $x . ' ON ' . $this->tablename . '(' . $x . ');';
            }
            $result .= 'CREATE INDEX IDX_' . $this->tablename . '_resto_no ON ' . $this->tablename . '(resto,no);';
            if ($isAddInitData) {
                $result .= 'INSERT INTO ' . $this->tablename . ' (resto,root,time,md5chksum,category,tim,ext,imgw,imgh,imgsize,tw,th,pwd,now,name,email,sub,com,host,status) VALUES (0, datetime("now"), 1111111111, "", "", 1111111111111, "", 0, 0, "", 0, 0, "", "05/01/01(六)00:00", "' . $this->ENV['NONAME'] . '", "", "' . $this->ENV['NOTITLE'] . '", "' . $this->ENV['NOCOMMENT'] . '", "", "");';
            }
            $this->con->exec($result);
            $this->dbCommit();
        }
    }

    /* 準備/讀入 */
    public function dbPrepare($reload = false, $transaction = false)
    {
        if ($this->prepared) {
            return true;
        }

        ($this->con = new PDO($this->DSN)) or $this->_error_handler('Open database failed',
            __LINE__);
        $this->useTransaction = $transaction;
        if ($transaction) {
            @$this->con->beginTransaction();
        } // 啟動交易性能模式

        $this->prepared = true;
    }

    /* 提交/儲存 */
    public function dbCommit()
    {
        if (!$this->prepared) {
            return false;
        }
        if ($this->useTransaction) {
            @$this->con->commit();
        } // 交易性能模式提交
    }

    /* 資料表維護 */
    public function dbMaintanence($action, $doit = false)
    {
        switch ($action) {
            case 'optimize':
                if ($doit) {
                    $this->dbPrepare(false);
                    if ($this->con->exec('VACUUM ' . $this->tablename) !== false) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return true;
                } // 支援最佳化資料表
                break;
            case 'export':
                if ($doit) {
                    $this->dbPrepare(false);
                    $gp = gzopen('piodata.log.gz', 'w9');
                    gzwrite($gp, $this->dbExport());
                    gzclose($gp);

                    return '<a href="piodata.log.gz">下載 piodata.log.gz 中介檔案</a>';
                } else {
                    return true;
                } // 支援匯出資料
                break;
            case 'check':
            case 'repair':
            default:
                return false; // 不支援
        }
    }

    /* 匯入資料來源 */
    public function dbImport($data)
    {
        $this->dbInit(false); // 僅新增結構不新增資料
        $data = \explode("\r\n", $data);
        $data_count = \count($data) - 1;
        $replaceComma = \create_function('$txt', 'return str_replace("&#44;", ",", $txt);');
        $SQL = 'INSERT INTO ' . $this->tablename . ' (no,resto,root,time,md5chksum,category,tim,ext,imgw,imgh,imgsize,tw,th,pwd,now,name,email,sub,com,host,status) VALUES '
            . '(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $PDOStmt = $this->con->prepare($SQL);
        for ($i = 0; $i < $data_count; $i++) {
            $line = \array_map($replaceComma, \explode(',', $data[$i])); // 取代 &#44; 為 ,
            $tim = \substr($line[5], 0, 10);
            $PDOStmt->bindValue(1, $line[0], PDO::PARAM_INT);
            $PDOStmt->bindValue(2, $line[1], PDO::PARAM_INT);
            $PDOStmt->bindValue(3, $line[2], PDO::PARAM_STR);
            $PDOStmt->bindValue(4, $tim, PDO::PARAM_INT);
            $PDOStmt->bindValue(5, $line[3], PDO::PARAM_STR);
            $PDOStmt->bindValue(6, $line[4], PDO::PARAM_STR);
            $PDOStmt->bindValue(7, $line[5],
                PDO::PARAM_STR); // 13-digit BIGINT workground //refix at 201406
            $PDOStmt->bindValue(8, $line[6], PDO::PARAM_STR);
            $PDOStmt->bindValue(9, $line[7], PDO::PARAM_INT);
            $PDOStmt->bindValue(10, $line[8], PDO::PARAM_INT);
            $PDOStmt->bindValue(11, $line[9], PDO::PARAM_STR);
            $PDOStmt->bindValue(12, $line[10], PDO::PARAM_INT);
            $PDOStmt->bindValue(13, $line[11], PDO::PARAM_INT);
            $PDOStmt->bindValue(14, $line[12], PDO::PARAM_STR);
            $PDOStmt->bindValue(15, $line[13], PDO::PARAM_STR);
            $PDOStmt->bindValue(16, $line[14], PDO::PARAM_STR);
            $PDOStmt->bindValue(17, $line[15], PDO::PARAM_STR);
            $PDOStmt->bindValue(18, $line[16], PDO::PARAM_STR);
            $PDOStmt->bindValue(19, $line[17], PDO::PARAM_STR);
            $PDOStmt->bindValue(20, $line[18], PDO::PARAM_STR);
            $PDOStmt->bindValue(21, $line[19], PDO::PARAM_STR);
            $PDOStmt->execute() or $this->_error_handler('Insert a new post failed', __LINE__);
        }
        $this->dbCommit(); // 送交
        return true;
    }

    /* 匯出資料來源 */
    public function dbExport()
    {
        if (!$this->prepared) {
            $this->dbPrepare();
        }
        $line = $this->con->query('SELECT no,resto,root,md5chksum,category,tim,ext,imgw,imgh,imgsize,tw,th,pwd,now,name,email,sub,com,host,status FROM ' . $this->tablename . ' ORDER BY no DESC');
        $data = '';
        $replaceComma = \create_function('$txt', 'return str_replace(",", "&#44;", $txt);');
        while ($row = $line->fetch(PDO::FETCH_ASSOC)) {
            $row = \array_map($replaceComma, $row); // 取代 , 為 &#44;
            $data .= \implode(',', $row) . ",\r\n";
        }

        return $data;
    }

    /* 文章數目 */
    public function postCount($resno = 0)
    {
        if (!$this->prepared) {
            $this->dbPrepare();
        }

        if ($resno) { // 一討論串文章總數目
            $line = $this->con->query('SELECT COUNT(no) FROM ' . $this->tablename . ' WHERE resto = ' . intval($resno))->fetch();
            $countline = $line[0] + 1;
        } else { // 文章總數目
            $line = $this->con->query('SELECT COUNT(no) FROM ' . $this->tablename)->fetch();
            $countline = $line[0];
        }

        return $countline;
    }

    /* 討論串數目 */
    public function threadCount()
    {
        if (!$this->prepared) {
            $this->dbPrepare();
        }

        $tree = $this->con->query('SELECT COUNT(no) FROM ' . $this->tablename . ' WHERE resto = 0')->fetch();

        return $tree[0]; // 討論串目前數目
    }

    /* 取得最後文章編號 */
    public function getLastPostNo($state)
    {
        if (!$this->prepared) {
            $this->dbPrepare();
        }

        if ($state == 'afterCommit') { // 送出後的最後文章編號
            $lastno = $this->con->query('SELECT MAX(no) FROM ' . $this->tablename)->fetch();

            return $lastno[0];
        } else {
            return 0;
        } // 其他狀態沒用
    }

    /* 輸出文章清單 */
    public function fetchPostList($resno = 0, $start = 0, $amount = 0)
    {
        if (!$this->prepared) {
            $this->dbPrepare();
        }

        $resno = intval($resno);
        if ($resno) { // 輸出討論串的結構 (含自己, EX : 1,2,3,4,5,6)
            $tmpSQL = 'SELECT no FROM ' . $this->tablename . ' WHERE no = ' . $resno . ' OR resto = ' . $resno . ' ORDER BY no';
        } else { // 輸出所有文章編號，新的在前
            $tmpSQL = 'SELECT no FROM ' . $this->tablename . ' ORDER BY no DESC';
            $start = intval($start);
            $amount = intval($amount);
            if ($amount) {
                $tmpSQL .= " LIMIT {$start}, {$amount}";
            } // 指定數量
        }

        return $this->con->query($tmpSQL)->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /* 輸出討論串清單 */
    public function fetchThreadList($start = 0, $amount = 0, $isDESC = false)
    {
        if (!$this->prepared) {
            $this->dbPrepare();
        }

        $tmpSQL = 'SELECT no FROM ' . $this->tablename . ' WHERE resto = 0 ORDER BY ' . ($isDESC ? 'no' : 'root') . ' DESC';
        $start = intval($start);
        $amount = intval($amount);
        if ($amount) {
            $tmpSQL .= " LIMIT {$start}, {$amount}";
        } // 指定數量
        return $this->con->query($tmpSQL)->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /* 輸出文章 */
    public function fetchPosts($postlist, $fields = '*')
    {
        if (!$this->prepared) {
            $this->dbPrepare();
        }

        if (is_array($postlist)) { // 取多串
            $postlist = array_filter($postlist, "is_numeric");
            if (count($postlist) == 0) {
                return array();
            }
            $params = str_repeat('?,', count($postlist) - 1) . '?';
            $tmpSQL = "SELECT $fields FROM {$this->tablename} WHERE no IN ($params) ORDER BY no";
            if (count($postlist) > 1) {
                if ($postlist[0] > $postlist[1]) {
                    $tmpSQL .= ' DESC';
                }
            } // 由大排到小

            $sth = $this->con->prepare($tmpSQL);
            $sth->execute($postlist);
        } else {
            $tmpSQL = "SELECT $fields FROM {$this->tablename} WHERE no = ?"; // 取單串
            $sth = $this->con->prepare($tmpSQL);
            $sth->bindValue(1, $postlist, PDO::PARAM_INT);
            $sth->execute();
        }

        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    /* 刪除舊附件 (輸出附件清單) */
    public function delOldAttachments($total_size, $storage_max, $warnOnly = true)
    {
        $FileIO = PMCLibrary::getFileIOInstance();
        if (!$this->prepared) {
            $this->dbPrepare();
        }

        $arr_warn = $arr_kill = array(); // 警告 / 即將被刪除標記
        ($result = $this->con->query('SELECT no,ext,tim FROM ' . $this->tablename . ' WHERE ext <> "" ORDER BY no')) or $this->_error_handler('Get the old post failed',
            __LINE__);
        while (list($dno, $dext, $dtim) = $result->fetch(PDO::FETCH_NUM)) {
            $dfile = $dtim . $dext;
            $dthumb = $FileIO->resolveThumbName($dtim);
            if ($FileIO->imageExists($dfile)) {
                $total_size -= $FileIO->getImageFilesize($dfile) / 1024;
                $arr_kill[] = $dno;
                $arr_warn[$dno] = 1;
            } // 標記刪除
            if ($dthumb && $FileIO->imageExists($dthumb)) {
                $total_size -= $FileIO->getImageFilesize($dthumb) / 1024;
            }
            if ($total_size < $storage_max) {
                break;
            }
        }

        return $warnOnly ? $arr_warn : $this->removeAttachments($arr_kill);
    }

    /* 刪除文章 */
    public function removePosts($posts)
    {
        if (!$this->prepared) {
            $this->dbPrepare();
        }
        $posts = array_filter($posts, "is_numeric");
        if (count($posts) == 0) {
            return array();
        }

        $files = $this->removeAttachments($posts, true); // 先遞迴取得刪除文章及其回應附件清單
        $params = str_repeat('?,', count($posts) - 1) . '?';
        $sth = $this->con->prepare("DELETE FROM {$this->tablename} WHERE no IN ($params) OR resto IN($params)");
        if (!$sth->execute($posts)) {
            $this->_error_handler('Delete old posts and replies failed', __LINE__);
        }

        return $files;
    }

    /* 刪除附件 (輸出附件清單) */
    public function removeAttachments($posts, $recursion = false)
    {
        $FileIO = PMCLibrary::getFileIOInstance();
        if (!$this->prepared) {
            $this->dbPrepare();
        }
        $posts = array_filter($posts, "is_numeric");
        if (count($posts) == 0) {
            return array();
        }

        $files = array();
        $params = str_repeat('?,', count($posts) - 1) . '?';
        if ($recursion) {
            // 遞迴取出 (含回應附件)
            $tmpSQL = "SELECT ext,tim FROM {$this->tablename} WHERE (no IN ($params) OR resto IN($params)) AND ext <> ''";
        } else {
            // 只有指定的編號
            $tmpSQL = "SELECT ext,tim FROM {$this->tablename} WHERE no IN ($params) AND ext <> ''";
        }

        $sth = $this->con->prepare($tmpSQL);
        $sth->execute($posts) or $this->_error_handler('Get attachments of the post failed',
            __LINE__);
        while (list($dext, $dtim) = $sth->fetch(PDO::FETCH_NUM)) {
            $dfile = $dtim . $dext;
            $dthumb = $FileIO->resolveThumbName($dtim);
            if ($FileIO->imageExists($dfile)) {
                $files[] = $dfile;
            }
            if ($dthumb && $FileIO->imageExists($dthumb)) {
                $files[] = $dthumb;
            }
        }

        return $files;
    }

    /* 新增文章/討論串 */
    public function addPost(
        $no,
        $resto,
        $md5chksum,
        $category,
        $tim,
        $ext,
        $imgw,
        $imgh,
        $imgsize,
        $tw,
        $th,
        $pwd,
        $now,
        $name,
        $email,
        $sub,
        $com,
        $host,
        $age = false,
        $status = ''
    ) {
        if (!$this->prepared) {
            $this->dbPrepare();
        }

        $time = (int)substr($tim, 0, -3); // 13位數的數字串是檔名，10位數的才是時間數值
        $updatetime = gmdate('Y-m-d H:i:s'); // 更動時間 (UTC)
        if ($resto) { // 新增回應
            $root = '0';
            if ($age) { // 推文
                $result = $this->con->prepare('UPDATE ' . $this->tablename . ' SET root = :now WHERE no = :resto');
                $result->execute(array(
                    ':now' => $updatetime,
                    ':resto' => $resto
                )) or $this->_error_handler('Push the post failed', __LINE__);
            }
        } else {
            $root = $updatetime;
        } // 新增討論串, 討論串最後被更新時間

        $SQL = 'INSERT INTO ' . $this->tablename . ' (resto,root,time,md5chksum,category,tim,ext,imgw,imgh,imgsize,tw,th,pwd,now,name,email,sub,com,host,status) VALUES '
            . '(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $PDOStmt = $this->con->prepare($SQL);
        $PDOStmt->bindValue(1, $resto, PDO::PARAM_INT);
        $PDOStmt->bindValue(2, $root, PDO::PARAM_STR);
        $PDOStmt->bindValue(3, $time, PDO::PARAM_INT);
        $PDOStmt->bindValue(4, $md5chksum, PDO::PARAM_STR);
        $PDOStmt->bindValue(5, $category, PDO::PARAM_STR);
        $PDOStmt->bindValue(6, $tim, PDO::PARAM_STR); // 13-digit BIGINT workground//refix at 201406
        $PDOStmt->bindValue(7, $ext, PDO::PARAM_STR);
        $PDOStmt->bindValue(8, $imgw, PDO::PARAM_INT);
        $PDOStmt->bindValue(9, $imgh, PDO::PARAM_INT);
        $PDOStmt->bindValue(10, $imgsize, PDO::PARAM_STR);
        $PDOStmt->bindValue(11, $tw, PDO::PARAM_INT);
        $PDOStmt->bindValue(12, $th, PDO::PARAM_INT);
        $PDOStmt->bindValue(13, $pwd, PDO::PARAM_STR);
        $PDOStmt->bindValue(14, $now, PDO::PARAM_STR);
        $PDOStmt->bindValue(15, $name, PDO::PARAM_STR);
        $PDOStmt->bindValue(16, $email, PDO::PARAM_STR);
        $PDOStmt->bindValue(17, $sub, PDO::PARAM_STR);
        $PDOStmt->bindValue(18, $com, PDO::PARAM_STR);
        $PDOStmt->bindValue(19, $host, PDO::PARAM_STR);
        $PDOStmt->bindValue(20, $status, PDO::PARAM_STR);
        $PDOStmt->execute() or $this->_error_handler('Insert a new post failed', __LINE__);
    }

    /* 檢查是否連續投稿 */
    public function isSuccessivePost(
        $lcount,
        $com,
        $timestamp,
        $pass,
        $passcookie,
        $host,
        $isupload
    ) {
        $FileIO = PMCLibrary::getFileIOInstance();
        if (!$this->prepared) {
            $this->dbPrepare();
        }

        if (!$this->ENV['PERIOD.POST']) {
            return false;
        } // 關閉連續投稿檢查
        $timestamp = intval($timestamp);
        $tmpSQL = 'SELECT pwd,host FROM ' . $this->tablename . ' WHERE time > ' . ($timestamp - (int)$this->ENV['PERIOD.POST']); // 一般投稿時間檢查
        if ($isupload) {
            $tmpSQL .= ' OR time > ' . ($timestamp - (int)$this->ENV['PERIOD.IMAGEPOST']);
        } // 附加圖檔的投稿時間檢查 (與下者兩者擇一)
        else {
            $tmpSQL .= " OR md5(com) = '" . md5($com) . "'";
        } // 內文一樣的檢查 (與上者兩者擇一)
        $this->con->sqliteCreateFunction('md5', 'md5', 1); // Register MD5 function
        ($result = $this->con->query($tmpSQL)) or $this->_error_handler('Get the post to check the succession failed',
            __LINE__);
        while (list($lpwd, $lhost) = $result->fetch(PDO::FETCH_NUM)) {
            // 判斷為同一人發文且符合連續投稿條件
            if ($host == $lhost || $pass == $lpwd || $passcookie == $lpwd) {
                return true;
            }
        }

        return false;
    }

    /* 檢查是否重複貼圖 */
    public function isDuplicateAttachment($lcount, $md5hash)
    {
        $FileIO = PMCLibrary::getFileIOInstance();
        if (!$this->prepared) {
            $this->dbPrepare();
        }

        ($result = $this->con->query('SELECT tim,ext FROM ' . $this->tablename . ' WHERE ext <> "" AND md5chksum = "' . $md5hash . '" ORDER BY no DESC'))
        or $this->_error_handler('Get the post to check the duplicate attachment failed', __LINE__);
        while (list($ltim, $lext) = $result->fetch(PDO::FETCH_NUM)) {
            if ($FileIO->imageExists($ltim . $lext)) {
                return true;
            } // 有相同檔案
        }

        return false;
    }

    /* 有此討論串? */
    public function isThread($no)
    {
        if (!$this->prepared) {
            $this->dbPrepare();
        }

        $result = $this->con->query('SELECT no FROM ' . $this->tablename . ' WHERE no = ' . intval($no) . ' AND resto = 0');

        return $result->fetch() ? true : false;
    }

    /* 搜尋文章 */
    public function searchPost($keyword, $field, $method)
    {
        if (!$this->prepared) {
            $this->dbPrepare();
        }

        if (!in_array($field, array('com', 'name', 'sub', 'no'))) {
            $field = 'com';
        }
        if (!in_array($method, array('AND', 'OR'))) {
            $method = 'AND';
        }

        $keyword_cnt = count($keyword);
        $SearchQuery = 'SELECT * FROM ' . $this->tablename . " WHERE {$field} LIKE " . $this->con->quote('%' . $keyword[0] . '%') . "";
        if ($keyword_cnt > 1) {
            for ($i = 1; $i < $keyword_cnt; $i++) {
                $SearchQuery .= " {$method} {$field} LIKE " . $this->con->quote('%' . $keyword[$i] . '%');
            }
        } // 多重字串交集 / 聯集搜尋
        $SearchQuery .= ' ORDER BY no DESC'; // 按照號碼大小排序
        ($line = $this->con->query($SearchQuery)) or $this->_error_handler('Search the post failed',
            __LINE__);

        return $line->fetchAll(PDO::FETCH_ASSOC);
    }

    /* 搜尋類別標籤 */
    public function searchCategory($category)
    {
        if (!$this->prepared) {
            $this->dbPrepare();
        }

        $result = $this->con->prepare('SELECT no FROM ' . $this->tablename . ' WHERE lower(category) LIKE :category ORDER BY no DESC');
        $result->execute(array(':category' => '%,' . strtolower($category) . ',%'));

        return $result->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /* 取得文章屬性 */
    public function getPostStatus($status)
    {
        return new FlagHelper($status); // 回傳 FlagHelper 物件
    }

    /* 更新文章 */
    public function updatePost($no, $newValues)
    {
        if (!$this->prepared) {
            $this->dbPrepare();
        }

        $no = intval($no);
        $chk = array(
            'resto',
            'md5chksum',
            'category',
            'tim',
            'ext',
            'imgw',
            'imgh',
            'imgsize',
            'tw',
            'th',
            'pwd',
            'now',
            'name',
            'email',
            'sub',
            'com',
            'host',
            'status'
        );
        foreach ($chk as $c) {
            if (isset($newValues[$c])) {
                if (!$this->con->exec('UPDATE ' . $this->tablename . " SET $c = " . $this->con->quote($newValues[$c]) . ' WHERE no = ' . $no)) {
                    $this->_error_handler('Update the field of the post failed', __LINE__);
                } // 更新討論串屬性
            }
        }
    }

    /* 設定文章屬性 */
    public function setPostStatus($no, $newStatus)
    {
        $this->updatePost($no, array('status' => $newStatus));
    }
}
