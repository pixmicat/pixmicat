<?php
class mod_rss{
	var $FEED_COUNT, $FEED_STATUSFILE, $FEED_CACHEFILE, $BASEDIR;

	function mod_rss(){
		global $PMS;
		$PMS->hookModuleMethod('ModulePage', 'mod_rss'); // 向系統登記模組專屬獨立頁面

		$this->FEED_COUNT = 10; // RSS產生最大篇數
		$this->FEED_STATUSFILE = 'mod_rss.tmp'; // 資料狀態暫存檔 (檢查資料需不需要更新)
		$this->FEED_CACHEFILE = 'rss.xml'; // 資料輸出暫存檔 (靜態快取Feed格式)
		$this->BASEDIR = fullURL(); // 基底URL
	}

	/* Get the name of module */
	function getModuleName(){
		return 'mod_rss : 提供RSS Feed訂閱服務';
	}

	/* Get the module version infomation */
	function getModuleVersionInfo(){
		return 'Pixmicat! RSS Feed Module v070125';
	}

	/* Auto hook to "Head" hookpoint */
	function autoHookHead(&$txt){
		$txt .= '<link rel="alternate" type="application/rss+xml" title="RSS 2.0 Feed" href="'.PHP_SELF.'?mode=module&amp;load=mod_rss" />';
	}

	/* 模組獨立頁面 */
	function ModulePage(){
		global $PIO;

		$PIO->dbPrepare();
		if($this->IsDATAUpdated()) $this->GenerateCache(); // 若資料已更新則也更新RSS Feed快取
		$this->RedirectToCache(); // 重導向到靜態快取
	}

	/* 檢查資料有沒有更新 */
	function IsDATAUpdated(){
		global $PIO;
		if(isset($_GET['force'])) return true; // 強迫更新RSS Feed

		$tmp_fsize = $PIO->getLastPostNo('afterCommit');
		$tmp_ssize = file_exists($this->FEED_STATUSFILE) ? file_get_contents($this->FEED_STATUSFILE) : 0; // 讀取狀態暫存資料
		if($tmp_fsize == $tmp_ssize) return false; // LastNo 相同，沒有更新

		$fp = fopen($this->FEED_STATUSFILE, 'w');
		stream_set_write_buffer($fp, 0); // 立刻寫入不用緩衝
		flock($fp, LOCK_EX); // 鎖定
		fwrite($fp, $tmp_fsize); // 更新
		flock($fp, LOCK_UN); // 解鎖
		fclose($fp);
		@chmod($this->FEED_STATUSFILE, 0666); // 可讀可寫
		return true; // 有更新過
	}

	/* 生成 / 更新靜態快取RSS Feed檔案 */
	function GenerateCache(){
		global $PIO, $FileIO;
		$RFC_timezone = ' '.(TIME_ZONE < 0 ? '-' : '+').substr('0'.abs(TIME_ZONE), -2).'00'; // RFC標準所用之時區格式

		$post = $PIO->fetchPosts($PIO->fetchPostList(0, 0, $this->FEED_COUNT)); // 取出前n筆號碼資料
		$post_count = count($post);
		// RSS Feed內容
		$tmp_c = '<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
<channel>
<title>'.TITLE.'</title>
<link>'.$this->BASEDIR.'</link>
<description>'.TITLE.'</description>
<language>zh-TW</language>
<generator>'.$this->getModuleVersionInfo().'</generator>
';
		for($i = 0; $i < $post_count; $i++){
			$imglink = ''; // 圖檔
			$resto = 0; // 回應
			list($no, $resto, $time, $tw, $th, $tim, $ext, $sub, $com) = array($post[$i]['no'], $post[$i]['resto'], substr($post[$i]['tim'], 0, -3), $post[$i]['tw'], $post[$i]['th'], $post[$i]['tim'], $post[$i]['ext'], $post[$i]['sub'], $post[$i]['com']);

			// 處理資料
			if($ext && $FileIO->imageExists($tim.'s.jpg')) $imglink = '<img src="'.$FileIO->getImageURL($tim.'s.jpg').'" alt="'.$tim.$ext.'" width="'.$tw.'" height="'.$th.'" /><br />';
			$time = gmdate("D, d M Y H:i:s", $time + TIME_ZONE * 60 * 60).$RFC_timezone; // 本地時間RFC標準格式
			$reslink = $this->BASEDIR.PHP_SELF.'?res='.($resto ? $resto : $no); // 回應連結

			$tmp_c .= '<item>
	<title>'.$sub.' ('.$no.')</title>
	<link>'.$reslink.'</link>
	<description>
	<![CDATA[
'.$imglink.$com.'
	]]>
	</description>
	<comments>'.$reslink.'</comments>
	<guid isPermaLink="true">'.$reslink.'#r'.$no.'</guid>
	<pubDate>'.$time.'</pubDate>
</item>
';
		}
		$tmp_c .= '</channel>
</rss>';
		$fp = fopen($this->FEED_CACHEFILE, 'w');
		flock($fp, LOCK_EX); // 鎖定
		fwrite($fp, $tmp_c); // 更新
		flock($fp, LOCK_UN); // 解鎖
		fclose($fp);
		@chmod($this->FEED_CACHEFILE, 0666); // 可讀可寫
	}

	/* 重導向到靜態快取 */
	function RedirectToCache(){
		header('HTTP/1.1 302 Moved Temporarily'); // 暫時性導向
		header('Content-Type: text/xml;charset=utf-8');
		header('Location: '.$this->BASEDIR.$this->FEED_CACHEFILE);
	}
}
?>