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
	var $sourceFile, $sourceWidth, $sourceHeight, $thumbWidth, $thumbHeight, $thumbSetting, $thumbQuality;

	function ThumbWrapper($sourceFile='', $sourceWidth=0, $sourceHeight=0){
		$this->sourceFile = $sourceFile;
		$this->sourceWidth = $sourceWidth;
		$this->sourceHeight = $sourceHeight;
	}

	function _GetLeftShiftCount($dwVal,$len=4) {
		$nCount = 0;
		for ($i=0; $i<$len * 8; $i++) {
			if ($dwVal & 1) $nCount++;
			$dwVal >>= 1;
		}
		return (8 - $nCount);
	}
	function _GetRightShiftCount($dwVal,$len=4) {
		for ($i=0; $i<$len * 8; $i++) {
			if ($dwVal & 1) return $i;
			$dwVal >>= 1;
		}
		return -1;
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
		if ($BMP['colors'] <=256 || $BMP['compression'] == 3) {
			if($BMP['compression'] == 3) // BI_BITFIELDS
				$PALETTE = unpack('V3', fread($f1,12));
			else
		 		$PALETTE = unpack('V'.$BMP['colors'], fread($f1,$BMP['colors']*4));
		}
		if($BMP['compression'] == 3) // BI_BITFIELDS
			$mask = array(array($PALETTE[1],$PALETTE[2],$PALETTE[3]),
				array($this->_GetRightShiftCount($PALETTE[1]),$this->_GetRightShiftCount($PALETTE[2]),$this->_GetRightShiftCount($PALETTE[3])),
				array($this->_GetLeftShiftCount($PALETTE[1]),$this->_GetLeftShiftCount($PALETTE[2]),$this->_GetLeftShiftCount($PALETTE[3])));
		else {
			if($BMP['colors'] == 65536) $mask = array(array(0x7C00,0x3E0,0x1F),array(10,5,0),array(3,3,3));
		}

		// 第四步：關閉檔案，變換每一個畫素
		$IMG = fread($f1, $BMP['size_bitmap']);
		fclose($f1);
		$VIDE = chr(0);

		$res = ImageCreateTrueColor($BMP['width'], $BMP['height']);
		$P = 0;
		$Y = $BMP['height'] - 1;

		if($BMP['compression'] == 1 && $BMP['colors'] == 256) { // BI_RLE8
			$imgDataLen=strlen($IMG);
			$RLEData='';
			while(true) {
				$prefix=ord(substr($IMG, floor($P++), 1));
				$suffix=ord(substr($IMG, floor($P++), 1));

				if(($prefix==0)&&($suffix==1)) break; // end of RLE stream
				if($P>=$imgDataLen) break;

				while(!(($prefix==0)&&($suffix==0))){ // ! end of RLE line
					if($prefix==0){ // Command
						$RLEData.=substr($IMG, floor($P), $suffix);
						$P+=$suffix;
						if($P%2==1) $P++;
					} elseif($prefix>0){ // Repeat
						for($r=0;$r<$prefix;$r++)
							$RLEData.=chr($suffix);
					}
					$prefix=ord(substr($IMG, floor($P++), 1));
					$suffix=ord(substr($IMG, floor($P++), 1));
				}
				for($X=0;$X<strlen($RLEData);$X++) // Write
					ImageSetPixel($res, $X, $Y, $PALETTE[ord($RLEData[$X])+1]);
				$RLEData='';
				$Y--;
			}
		} elseif($BMP['compression'] == 2 && $BMP['colors'] == 16) { // BI_RLE4
			$imgDataLen=strlen($IMG);
			$RLEData='';
			while(true) {
				$prefix=ord(substr($IMG, floor($P++), 1));
				$suffix=ord(substr($IMG, floor($P++), 1));

				if(($prefix==0)&&($suffix==1)) break; // end of RLE stream
				if($P>=$imgDataLen) break;

				while(!(($prefix==0)&&($suffix==0))){ // ! end of RLE line
					if($prefix==0){ // Command
						for($h=0;$h<$suffix;$h++) {
							$COLOR = ord(substr($IMG, floor($P), 1));
							$RLEData.=($h%2==0)?chr($COLOR >> 4):chr($COLOR & 0x0F);
							$P += $BMP['bytes_per_pixel'];
						}
						$P=ceil($P);
						if($P%2==1) $P++;
					} elseif($prefix>0){ // Repeat
						for($r=0;$r<$prefix;$r++)
							$RLEData.=($r%2==0)?chr($suffix >> 4):chr($suffix & 0x0F);
					}
					$prefix=ord(substr($IMG, floor($P++), 1));
					$suffix=ord(substr($IMG, floor($P++), 1));
				}

				for($X=0;$X<strlen($RLEData);$X++) // Write
					ImageSetPixel($res, $X, $Y, $PALETTE[ord($RLEData[$X])+1]);
				$RLEData='';
				$Y--;

			}
		} else {
			while($Y >= 0){
				$X = 0;
				while($X < $BMP['width']){
					switch($BMP['bits_per_pixel']){
						case 32: 
							$COLOR = unpack('V', substr($IMG, $P, 4));
							$COLOR[1] &= 0xFFFFFF; // 不處理Alpha
							break;
						case 24: $COLOR = unpack('V', substr($IMG, $P, 3).$VIDE); break;
						case 16:
							$COLOR = unpack("v",substr($IMG,$P,2));
							$COLOR[1] = (((($COLOR[1] & $mask[0][0])>>$mask[1][0])<<$mask[2][0])<<16) |
								(((($COLOR[1] & $mask[0][1])>>$mask[1][1])<<$mask[2][1])<<8) |
								((($COLOR[1] & $mask[0][2])>>$mask[1][2])<<$mask[2][2]);
							break;
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
					if($BMP['bits_per_pixel']<16) $COLOR[1] = $PALETTE[$COLOR[1]+1];
					ImageSetPixel($res, $X, $Y, $COLOR[1]);
					$X++;
					$P += $BMP['bytes_per_pixel'];
				}
				$Y--;
				$P += $BMP['decal'];
			}
		}

		// 終章：回傳新圖像
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

	function setThumbnailConfig($thumbWidth, $thumbHeight, $thumbSetting){
		$this->thumbWidth = $thumbWidth;
		$this->thumbHeight = $thumbHeight;
		$this->thumbSetting = $thumbSetting;
		$this->thumbQuality = $thumbSetting['Quality'];
	}

	function makeThumbnailtoFile($destFile){
		if(!$this->isWorking()) return false;
		$size = getimagesize($this->sourceFile);
		switch($size[2]){
			case IMAGETYPE_JPEG:
				$im_in = @ImageCreateFromJPEG($this->sourceFile); break;
			case IMAGETYPE_GIF:
				$im_in = @ImageCreateFromGIF($this->sourceFile); break;
			case IMAGETYPE_PNG:
				$im_in = @ImageCreateFromPNG($this->sourceFile); break;
			case IMAGETYPE_BMP:
				$im_in = $this->_ImageCreateFromBMP($this->sourceFile); break;
			default:
				return false;
		}
		if(!$im_in) return false;
		$im_out = ImageCreateTrueColor($this->thumbWidth, $this->thumbHeight);
		ImageCopyResampled($im_out, $im_in, 0, 0, 0, 0, $this->thumbWidth, $this->thumbHeight, $this->sourceWidth, $this->sourceHeight);
		switch(strtolower($this->thumbSetting['Format'])){
			case 'png':
				ImagePNG($im_out, $destFile, $this->thumbQuality);
				break;
			case 'gif':
				ImageGIF($im_out, $destFile);
				break;
			case 'jpg':
			case 'jpeg':
			default:
				ImageJPEG($im_out, $destFile, $this->thumbQuality);
				break;
		}
		ImageDestroy($im_in); ImageDestroy($im_out);
		return true;
	}
}
?>