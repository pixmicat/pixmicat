<?php
/**
 * FileIO Normal (5th.Release) 本機儲存 API
 *
 * 以本機硬碟空間作為圖檔儲存的方式，並提供一套方法供程式管理圖片
 *
 * 此版還原至舊版 (5th.Release) 的行為，判斷圖檔時仍使用檔案 I/O 來確認，
 * 避免特定環境下 IFS 出現錯誤造成圖檔無法顯示的問題。
 * 但統計圖檔儲存量仍需要 IFS 支援。因此會有部分問題。
 *
 * @package PMCLibrary
 * @version $Id: fileio.normal.php 808 2011-04-15 14:39:47Z scribe $
 * @date $Date: 2011-04-15 22:39:47 +0800 (週五, 15 四月 2011) $
 */

class FileIO{
	var $path, $imgPath, $thumbPath;
	var $IFS;

	/* private 藉由檔名分辨圖檔存放位置 */
	function _getImagePhysicalPath($imgname){
		return (strpos($imgname, 's.') !== false ? $this->thumbPath : $this->imgPath).$imgname;
	}

	/* private 儲存索引檔 */
	function _close(){
		$this->IFS->saveIndex(); // 索引表更新
	}

	function FileIO($parameter='', $ENV){
		require($ENV['IFS.PATH']);
		$this->path = $ENV['PATH'];
		$this->imgPath = $this->path.$ENV['IMG'];
		$this->thumbPath = $this->path.$ENV['THUMB'];
		$this->IFS = new IndexFS($ENV['IFS.LOG']); // IndexFS 物件
		$this->IFS->openIndex();
		register_shutdown_function(array($this, '_close')); // 設定解構元 (PHP 結束前執行)
	}

	function init(){
		return true;
	}

	function imageExists($imgname){
		return file_exists($this->_getImagePhysicalPath($imgname));
	}

	function deleteImage($imgname){
		if(!is_array($imgname))
			$imgname = array($imgname); // 單一名稱參數

		$size = 0; $size_perimg = 0;
		foreach($imgname as $i){
			$size_perimg = $this->getImageFilesize($i);
			// 刪除出現錯誤
			if(!@unlink($this->_getImagePhysicalPath($i))){
				if($this->imageExists($i)) continue; // 無法刪除，檔案存在 (保留索引)
				// 無法刪除，檔案消失 (更新索引)
			}
			$this->IFS->delRecord($i);
			$size += $size_perimg;
		}
		return $size;
	}

	function uploadImage($imgname='', $imgpath='', $imgsize=0){
		if($imgname=='') return true; // 為檔案作索引
		$this->IFS->addRecord($imgname, $imgsize, ''); // 加入索引之中
		return true;
	}

	function getImageFilesize($imgname){
		return @filesize($this->_getImagePhysicalPath($imgname));
	}

	function getImageURL($imgname){
		return $this->getImageLocalURL($imgname);
	}

	function resolveThumbName($thumbPattern){
		$find = glob($this->thumbPath.$thumbPattern.'s.*');
		return ($find !== false && count($find) != 0) ? basename($find[0]) : false;
	}
}
?>