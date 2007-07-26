<?php
/**
 * Thumbnail Generate API: GD Wrapper
 *
 * 提供程式便於以 GD Library 生成預覽圖的物件
 *
 * @package PMCLibrary
 * @version $Id$
 * @date $Date$
 */

class ThumbWrapper{
	var $sourceFile, $sourceWidth, $sourceHeight, $thumbWidth, $thumbHeight, $thumbQuality;

	function ThumbWrapper($sourceFile='', $sourceWidth=0, $sourceHeight=0){
		$this->sourceFile = $sourceFile;
		$this->sourceWidth = $sourceWidth;
		$this->sourceHeight = $sourceHeight;
	}

	/* ImageCreateFromBMP : 讓GD可處理BMP圖檔
	此為修改後最適化版本。原出處：http://www.php.net/imagecreate#53879
	原作宣告：
	*****************************
	Function: ImageCreateFromBMP
	Author:	DHKold
	Contact: admin@dhkold.com
	Date: The 15th of June 2005
	Version: 2.0B
	*****************************/
	function _ImageCreateFromBMP($filename){
		// 序章：以二進位模式開啟檔案流
		if(!$f1 = fopen($filename, 'rb')) return FALSE;

		// 第一步：讀取BMP檔頭
		$FILE = unpack('vfile_type/Vfile_size/Vreserved/Vbitmap_offset', fread($f1, 14));
		if($FILE['file_type']!=19778) return FALSE; // BM

		// 第二步：讀取BMP資訊
		// 僅支援BITMAPINFOHEADER，不支援BITMAPV4HEADER及BITMAPV5HEADER
		$BMP = unpack('Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel/Vcompression/Vsize_bitmap/Vhoriz_resolution/Vvert_resolution/Vcolors_used/Vcolors_important', fread($f1, 40));
		$BMP['colors'] = pow(2, $BMP['bits_per_pixel']);
		if($BMP['size_bitmap']==0) $BMP['size_bitmap'] = $FILE['file_size'] - $FILE['bitmap_offset'];
		$BMP['bytes_per_pixel'] = $BMP['bits_per_pixel'] / 8;
		$BMP['decal'] = ($BMP['width'] * $BMP['bytes_per_pixel'] / 4);
		$BMP['decal'] -= floor($BMP['width'] * $BMP['bytes_per_pixel'] / 4);
		$BMP['decal'] = 4 - (4 * $BMP['decal']);
		if($BMP['decal']==4) $BMP['decal'] = 0;

		// 第三步：讀取色盤資訊
		$PALETTE = array();
		if($BMP['colors'] < 16777216) $PALETTE = unpack('V'.$BMP['colors'], fread($f1, $BMP['colors'] * 4));

		// 第四步：變換每一個畫素
		// 尚不支援32bit, 32bit with BITFIELDS, 8bit with RLE8, 4bit with RLE4等格式
		$IMG = fread($f1, $BMP['size_bitmap']);
		$VIDE = chr(0);

		$res = ImageCreateTrueColor($BMP['width'], $BMP['height']);
		$P = 0;
		$Y = $BMP['height'] - 1;
		while($Y >= 0){
			$X = 0;
			while($X < $BMP['width']){
				switch($BMP['bits_per_pixel']){
					case 24: $COLOR = unpack('V', substr($IMG, $P, 3).$VIDE); break;
					case 16: $COLOR = unpack('n', substr($IMG, $P, 2)); break;
					case 8:	$COLOR = unpack('n', $VIDE.substr($IMG, $P, 1)); break;
					case 4:
						$COLOR = unpack('n', $VIDE.substr($IMG, floor($P), 1));
						if(($P*2)%2==0) $COLOR[1] = ($COLOR[1] >> 4);
						else $COLOR[1] = ($COLOR[1] & 0x0F);
						break;
					case 1:
						$COLOR = unpack('n', $VIDE.substr($IMG, floor($P), 1));
						switch(($P * 8) % 8){
							case 0: $COLOR[1] = $COLOR[1] >> 7; break;
							case 1: $COLOR[1] = ($COLOR[1] & 0x40) >> 6; break;
							case 2: $COLOR[1] = ($COLOR[1] & 0x20) >> 5; break;
							case 3: $COLOR[1] = ($COLOR[1] & 0x10) >> 4; break;
							case 4: $COLOR[1] = ($COLOR[1] & 0x8) >> 3; break;
							case 5: $COLOR[1] = ($COLOR[1] & 0x4) >> 2; break;
							case 6: $COLOR[1] = ($COLOR[1] & 0x2) >> 1; break;
							case 7: $COLOR[1] = ($COLOR[1] & 0x1);
						}
						break;
					default:
						return FALSE;
				}
				if($BMP['bits_per_pixel']!=24) $COLOR[1] = $PALETTE[$COLOR[1]+1];
				ImageSetPixel($res, $X, $Y, $COLOR[1]);
				$X++;
				$P += $BMP['bytes_per_pixel'];
			}
			$Y--;
			$P += $BMP['decal'];
		}

		// 終章：關閉檔案，回傳新圖像
		fclose($f1);
		return $res;
	}

	function getClass(){
		$str = 'GD Wrapper';
		if($this->isWorking()){
			$a = gd_info();	$str .= ' : '.$a['GD Version'];
		}
		return $str;
	}

	function isWorking(){
		return extension_loaded('gd') && function_exists('ImageCreateTrueColor') && function_exists('ImageCopyResampled');
	}

	function setThumbnailConfig($thumbWidth, $thumbHeight, $thumbQuality=50){
		$this->thumbWidth = $thumbWidth;
		$this->thumbHeight = $thumbHeight;
		$this->thumbQuality = $thumbQuality;
	}

	function makeThumbnailtoFile($destFile){
		if(!$this->isWorking()) return false;
		switch(strtolower(strrchr($destFile, '.'))){ // 取出副檔名
			case '.jpg':
				$im_in = @ImageCreateFromJPEG($this->sourceFile); break;
			case '.gif':
				$im_in = @ImageCreateFromGIF($this->sourceFile); break;
			case '.png':
				$im_in = @ImageCreateFromPNG($this->sourceFile); break;
			case '.bmp':
				$im_in = $this->_ImageCreateFromBMP($this->sourceFile); break;
			default:
				return false;
		}
		if(!$im_in) return false;
		$im_out = ImageCreateTrueColor($this->thumbWidth, $this->thumbHeight);
		ImageCopyResampled($im_out, $im_in, 0, 0, 0, 0, $this->thumbWidth, $this->thumbHeight, $this->sourceWidth, $this->sourceHeight);
		ImageJPEG($im_out, $destFile, $this->thumbQuality);
		ImageDestroy($im_in); ImageDestroy($im_out);
		return true;
	}
}
?>