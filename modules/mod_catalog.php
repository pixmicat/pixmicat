<?php
/*
mod_catalog : 以相簿模式列出圖檔方便瀏覽及抓取
By: scribe (Adopted from Pixmicat!-Festival)
*/

class mod_catalog{
	var $CATALOG_NUMBER;

	function mod_catalog(){
		global $PMS;
		$PMS->hookModuleMethod('ModulePage', 'mod_catalog'); // 向系統登記模組專屬獨立頁面

		$this->CATALOG_NUMBER = 50; // 相簿模式一頁最多顯示個數 (視文章是否有貼圖而有實際變動)
	}

	/* Get the name of module */
	function getModuleName(){
		return 'mod_catalog : 以相簿模式列出圖檔方便瀏覽及抓取';
	}

	/* Get the module version infomation */
	function getModuleVersionInfo(){
		return 'Pixmicat! Catalog Module v070126';
	}

	/* 自動掛載：樣式表 */
	function autoHookHead(&$style){
		$style .= '<style type="text/css">
div.list { float: left; margin: 5px; width: 125px; height: 125px; } /* (相簿模式) div 框格設定 */
</style>
';
	}

	/* 自動掛載：頂部連結列 */
	function autoHookToplink(&$linkbar){
		$linkbar .= '[<a href="'.PHP_SELF.'?mode=module&amp;load=mod_catalog">相簿模式</a>]'."\n";
	}

	/* 模組獨立頁面 */
	function ModulePage(){
		global $PIO, $FileIO;

		$thisPage = PHP_SELF.'?mode=module&amp;load=mod_catalog'; // 基底位置
		$dat = ''; // HTML Buffer
		$listMax = $PIO->postCount(); // 文章總筆數
		$pageMax = ceil($listMax / $this->CATALOG_NUMBER) - 1; // 分頁最大編號
		$page = isset($_GET['page']) ? intval($_GET['page']) : 0; // 目前所在分頁頁數
		if($page < 0 || $page > $pageMax) exit('Page out of range.'); // $page 超過範圍
		$plist = $PIO->fetchPostList(0, $this->CATALOG_NUMBER * $page, $this->CATALOG_NUMBER); // 取得定位正確的 N 筆資料號碼
		$post = $PIO->fetchPosts($plist); // 取出資料
		$post_count = count($post);

		head($dat);
		$dat .= '<div id="contents">
[<a href="'.PHP_SELF2.'?'.time().'">回到版面</a>]
<div class="bar_reply">相簿模式</div>
';
		// 逐步取資料
		for($i = 0; $i < $post_count; $i++){
			list($imgw, $imgh, $tw, $th, $tim, $ext) = array($post[$i]['imgw'], $post[$i]['imgh'],$post[$i]['tw'], $post[$i]['th'], $post[$i]['tim'], $post[$i]['ext']);
			if($FileIO->imageExists($tim.$ext)){
				$dat .= '<div class="list"><a href="'.$FileIO->getImageURL($tim.$ext).'" rel="_blank"><img src="'.$FileIO->getImageURL($tim.'s.jpg').'" style="'.$this->OptimizeImageWH($tw, $th).'" title="'.$imgw.'x'.$imgh.'" alt="'.$tim.$ext.'" /></a></div>'."\n";
			}
		}

		$dat .= '</div>

<hr />

<div id="page_switch">
<table border="1" style="float: left;"><tr>
';
		if($page) $dat .= '<td><a href="'.$thisPage.'&amp;page='.($page - 1).'">上一頁</a></td>';
		else $dat .= '<td style="white-space: nowrap;">第一頁</td>';
		$dat .= '<td>';
		for($i = 0; $i <= $pageMax; $i++){
			if($i==$page) $dat .= '[<b>'.$i.'</b>] ';
			else $dat .= '[<a href="'.$thisPage.'&amp;page='.$i.'">'.$i.'</a>] ';
		}
		$dat .= '</td>';
		if($page < $pageMax) $dat .= '<td><a href="'.$thisPage.'&amp;page='.($page + 1).'">下一頁</a></td>';
		else $dat .= '<td style="white-space: nowrap;">最後一頁</td>';
		$dat .= '
</tr></table>
</div>

';
		foot($dat);
		echo $dat;
	}

	/* 最佳化圖顯示尺寸 */
	function OptimizeImageWH($w, $h){
		if($w > MAX_RW || $h > MAX_RH){
			$W2 = MAX_RW / $w; $H2 = MAX_RH / $h;
			$tkey = ($W2 < $H2) ? $W2 : $H2;
			$w = ceil($w * $tkey); $h = ceil($h * $tkey);
		}
		return 'width: '.$w.'px; height: '.$h.'px;';
	}
}
?>