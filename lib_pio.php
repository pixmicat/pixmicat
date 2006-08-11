<?php
if(defined('CONNECTION_STRING')){ // 有連線字串
	if(preg_match('/^(.*):\/\//i', CONNECTION_STRING, $backend)){
		define('PIXMICAT_BACKEND',$backend[1]);
	}
}

$pio_file='./pio/pio.'.PIXMICAT_BACKEND.'.php';
if(is_file($pio_file)) include_once($pio_file);

// PIO Class Wrapper
class PIO {
	var $realPIO;

	function PIO($backend,$connstr='') {
		$this->realPIO='PIO'.$backend;
		$this->realPIO=new $this->realPIO($connstr);
	}
	
	/* PIO模組版本 */
	function pioVersion() { return $this->realPIO->pioVersion(); }
	/* 處理連線字串/連接 */
	function dbConnect($connStr) { return $this->realPIO->dbConnect($connStr); }
	/* 初始化 */
	function dbInit() { return $this->realPIO->dbInit(); }
	/* 準備/讀入 */
	function dbPrepare($reload=false,$transaction=true) { return $this->realPIO->dbPrepare($reload,$transaction); }
	/* 提交/儲存 */
	function dbCommit() { return $this->realPIO->dbCommit(); }
	/* 優化資料表 */
	function dbOptimize($doit=false) { return $this->realPIO->dbOptimize($doit); }
	/* 刪除舊文 */
	function delOldPostes() { return $this->realPIO->delOldPostes(); }
	/* 刪除文章 */
	function removePosts($posts) { return $this->realPIO->removePosts($posts); }
	/* 刪除舊附件 (輸出附件清單) */
	function delOldAttachments($total_size,$storage_max,$warnOnly=true) { return $this->realPIO->delOldAttachments($total_size,$storage_max,$warnOnly); }
	/* 刪除附件 (輸出附件清單) */
	function removeAttachments($posts) { return $this->realPIO->removeAttachments($posts); }
	/* 文章數目 */
	function postCount($resno=0) { return $this->realPIO->postCount($resno); }
	/* 討論串數目 */
	function threadCount() { return $this->realPIO->threadCount(); }
	/* 輸出文章清單 */
	function fetchPostList($resno=0,$start=0,$amount=0) { return $this->realPIO->fetchPostList($resno,$start,$amount); }
	/* 輸出討論串清單 */
	function fetchThreadList($start=0,$amount=0) { return $this->realPIO->fetchThreadList($start,$amount); }
	/* 輸出文章 */
	function fetchPosts($postlist) { return $this->realPIO->fetchPosts($postlist); }
	/* 有此討論串? */
	function is_Thread($no) { return $this->realPIO->is_Thread($no); }
	/* 搜尋文章 */
	function searchPost($keyword,$field,$method) { return $this->realPIO->searchPost($keyword,$field,$method); }
	/* 新增文章/討論串 */
	function addPost($no,$resno,$now,$name,$email,$sub,$com,$url,$host,$pass,$ext,$W,$H,$tim,$chk,$age=false) { return $this->realPIO->addPost($no,$resno,$now,$name,$email,$sub,$com,$url,$host,$pass,$ext,$W,$H,$tim,$chk,$age); }
	/* 取得文章屬性 */
	function getPostStatus($status,$statusType) { return $this->realPIO->getPostStatus($status,$statusType); }
	/* 設定文章屬性 */
	function setPostStatus($no, $status, $statusType, $newValue) { return $this->realPIO->setPostStatus($no, $status, $statusType, $newValue); }
	/* 取得最後的文章編號 */
	function getLastPostNo($state) { return $this->realPIO->getLastPostNo($state); }
}

$pio=new PIO(PIXMICAT_BACKEND,(defined('CONNECTION_STRING'))?CONNECTION_STRING:'');
?>