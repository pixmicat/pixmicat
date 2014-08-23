<?php
/**
 * Pixmicat! PIO 公用程式 - 檢查伺服器執行環境支援
 * - PIO: SQLite 2, PDO SQLite, PostgreSQL, MySQL, MySQL Improved
 * - Thumbnail: GD, Imagick, MagickWand, ImageMagick, repng2jpeg
 *
 * 本公用程式可為您檢查伺服器支援的項目，讓您選擇最適合的 PIO 資料來源後端和預覽圖生成物件。
 * - 伺服器資訊: 得知伺服器的基本資訊，如伺服器版本、PHP 版本等
 * - PIO 檢查: 在設定檔的 CONNECTION_STRING 可以使用的後端檢查，除了 Log 外您還可以使用更穩定的 SQL
 * - 預覽圖生成檢查: 檢查您的伺服器是否支援各類預覽圖生成，並提供多種支援供您選擇
 * - ImageMagick 和 repng2jpeg 支援檢查: 協助您選擇最適用的 repng2jpeg 執行檔和找出 ImageMagick convert 程式路徑
 * 
 * Original source: SUGA <http://sugachan.dip.jp/> @ 2004/10/16
 * Custom: SakaQ <http://www.punyu.net/> @ 2004/11/22
 * FIXED: scribe <http://scribe.chkkk.idv.tw> @ 2005/07/09
 * Rewrite: Pixmicat! Development Team <http://pixmicat.openfoundry.org/>
 *
 * @package PMCUtility
 * @version $Id$
 * @date $Date$
 */

class CheckEnvironment{
	/* 尋找檔案路徑 */
	function _findfile($filename, $isWin=false){
		if(@is_file("./$filename")){ return "./$filename"; }
		$ary = explode(PATH_SEPARATOR, getenv('PATH'));
		if(!$isWin){
			$ary = array_merge($ary, explode(':',
			':/bin'.
			':/usr/bin'.
			':/usr/ucb'.
			':/etc'.
			':/lib'.
			':/usr/etc'.
			':/usr/lib'.
			':/usr/local/bin'.
			':/usr/local/X11R6/bin'.
			':/usr/local/bin/mh'.
			':/usr/local/lib'.
			':/usr/local/lib/mh'.
			':/usr/local/sbin'.
			':/usr/local/libexec'.
			':/usr/local/canna/bin'.
			':'.ini_get('safe_mode_include_dir')));
		}
		foreach($ary as $value){
			if(@is_file($value.DIRECTORY_SEPARATOR.$filename)){ return ($value.DIRECTORY_SEPARATOR.$filename); }
			if(@realpath($value.DIRECTORY_SEPARATOR.$filename)){ return ($value.DIRECTORY_SEPARATOR.$filename); } // smbfs workaround, from http://php.net/file-exists#82269
		}
		return false;
	}

	/* 檢查適用環境之 repng2jpeg 種類 */
	function checkRepng2jpegRecommended(){
		$os = PHP_OS;
		$msg = ''; // HTML 文字流
		if(!function_exists('exec')){ return 'This server has disabled the exec() function. So repng2jpeg can\'t be used.'; } // 封鎖 exec 功能
		if(stristr($os, 'Linux')){ // 系統為 Linux
			$libjpeg = $this->_findfile('libjpeg.so.62'); $libpng = $this->_findfile('libpng.so.2'); $libz = $this->_findfile('libz.so.1');
			$libm = $this->_findfile('libm.so.6'); $libc = $this->_findfile('libc.so.6'); $ldlinux = $this->_findfile("ld-linux.so.2");
			$msg .= '- libjpeg  -> '.($libjpeg ? $libjpeg : 'Not Found')."\n";
			$msg .= '- libpng   -> '.($libpng ? $libpng : 'Not Found')."\n";
			$msg .= '- libz     -> '.($libz ? $libz : 'Not Found')."\n";
			$msg .= '- libm     -> '.($libm ? $libm : 'Not Found')."\n";
			$msg .= '- libc     -> '.($libc ? $libc : 'Not Found')."\n";
			$msg .= '- ldlinux  -> '.($ldlinux ? $ldlinux : 'Not Found')."\n";

			if($libjpeg && $libpng && $libz && $libm && $libc && $ldlinux){
				$msg .= 'You can use repng2jpeg (i386_linux_dynamic).';
				return $msg;
			}
			if($libz && $libm && $libc && $ldlinux){
				$msg .= 'You can use repng2jpeg (i386_linux_standard).';
				return $msg;
			}
			$msg .= 'You can use repng2jpeg (i386_linux_static).';
			return $msg;
		}elseif(stristr($os, 'FreeBSD')){ // 系統為 FreeBSD
			$libjpeg = $this->_findfile('libjpeg.so.9'); $libpng = $this->_findfile('libpng.so.5'); $libz = $this->_findfile('libz.so.2');
			$libm = $this->_findfile('libm.so.2'); $libc = $this->_findfile('libc.so.4');
			$msg .= '- libjpeg  -> '.($libjpeg ? $libjpeg : 'Not Found')."\n";
			$msg .= '- libpng   -> '.($libpng ? $libpng : 'Not Found')."\n";
			$msg .= '- libz     -> '.($libz ? $libz : 'Not Found')."\n";
			$msg .= '- libm     -> '.($libm ? $libm : 'Not Found')."\n";
			$msg .= '- libc     -> '.($libc ? $libc : 'Not Found')."\n";

			if($libjpeg && $libpng && $libz && $libm && $libc){
				$msg .= 'You can use repng2jpeg (i386_freebsd4_dynamic).';
				return $msg;
			}
			if($libz && $libm && $libc){
				$msg .= 'You can use repng2jpeg (386_freebsd4_standard).';
				return $msg;
			}
			$msg .= 'You can use repng2jpeg (i386_freebsd4_static).';
			return $msg;
		}elseif(stristr($os, 'Solaris')){ // 系統為 Solaris
			$libc = $this->_findfile('libc.so.1'); $libdl = $this->_findfile('libdl.so.1');
			$msg .= '- libc     -> '.($libc ? $libc : 'Not Found')."\n";
			$msg .= '- libdl    -> '.($libdl ? $libdl : 'Not Found')."\n";

			if($libc && $libdl){
				$msg .= 'You can use repng2jpeg (i386_solaris_standard).';
				return $msg;
			}
			$msg .= 'You can use repng2jpeg (i386_solaris_static).';
			return $msg;
		}elseif(stristr($os, 'Win')){ // 系統為 Windows
			$msvcrt = $this->_findfile('msvcrt.dll', 1);
			$msg .= '- msvcrt.dll -> '.($msvcrt ? $msvcrt : 'Not Found')."\n";

			if($msvcrt){
				$msg .= 'You can use repng2jpeg (i386_win32).';
			}else{
				$msg .= 'You can\'t use repng2jpeg (i386_win32). Try others.';
			}
			return $msg;
		}else{ return 'You can\'t use repng2jpeg on this platform because no suitable binary can be used.'; } // 無法支援的系統
	}

	/* 檢查 repng2jpeg 可用性 */
	function checkRepng2jpeg(){
		$_exec = realpath('./repng2jpeg'.(strtoupper(substr(PHP_OS, 0, 3))==='WIN' ? '.exe' : ''));
		if(function_exists('exec') && file_exists($_exec) && (strtoupper(substr(PHP_OS, 0, 3))==='WIN' || is_executable($_exec))){
			return `$_exec --version`;
		}else{ return false; }
	}

	/* 檢查 GD 可用性 */
	function checkGD(){
		if(extension_loaded('gd') && function_exists('ImageCreateTrueColor') && function_exists('ImageCopyResampled')){
			$a = gd_info();	return $a['GD Version'];
		}else{ return false; }
	}

	/* 檢查 Imagick 可用性 */
	function checkImagick(){
		if(extension_loaded('imagick') && class_exists('Imagick')){
			$a = new Imagick(); $b = $a->getVersion(); $b = $b['versionString'];
			unset($a);
			return $b;
		}else{ return false; }
	}

	/* 檢查 MagickWand 可用性 */
	function checkMagickWand(){
		if(extension_loaded('magickwand') && function_exists('MagickThumbnailImage')){
			$a = MagickGetVersion(); $b = $a[0];
			unset($a);
			return $b;
		}else{ return false; }
	}

	/* 檢查 ImageMagick 可用性 */
	function checkImageMagick(){
		if(!function_exists('exec')) return false;
		$_exec = 'convert'.(strtoupper(substr(PHP_OS, 0, 3))==='WIN' ? '.exe' : '');
		if($newexec = $this->_findfile($_exec)){ $_exec = $newexec; } // ImageMagick "convert" Binary Location

		@exec("\"$_exec\" -version", $status, $retval);
		// 可能呼叫成 Windows 內建的 convert.exe 導致無輸出
		if(count($status) != 0){
			$a = null;
			if(preg_match('/^Version: (.*)/', $status[0], $a)){
				return $a[1]."\n\t\t".'- Location guessed: '.$_exec;
			}
		}
		return false;
	}

	/* 檢查 MySQL 可用性 */
	function checkPIOMySQL(){
		return (extension_loaded('mysql') && function_exists('mysql_connect'));
	}

	/* 檢查 MySQLi 可用性 */
	function checkPIOMySQLi(){
		return (extension_loaded('mysqli') && class_exists('mysqli'));
	}

	/* 檢查 SQLite 可用性 */
	function checkPIOSQLite(){
		return (extension_loaded('sqlite') && function_exists('sqlite_popen'));
	}

	/* 檢查 PostgreSQL 可用性 */
	function checkPIOPostgreSQL(){
		return (extension_loaded('pgsql') && function_exists('pg_pconnect'));
	}

	/* 檢查 PDO SQLite3 可用性 */
	function checkPIOPDOSQLite3(){
		return (class_exists('PDO') && extension_loaded('pdo_sqlite'));
	}

	/* 回傳伺服器資訊 */
	function getServerInfo(){
		$msg = "\t".'PHP: '.phpversion()."\n";
		$msg .= "\t\t".'- upload_max_filesize: '.ini_get('upload_max_filesize')."\n";
		$msg .= "\t\t".'- post_max_size: '.ini_get('post_max_size')."\n";
		$msg .= "\t\t".'- disable_functions: '.ini_get('disable_functions')."\n";
		$msg .= "\t".'HTTPd: '.($_SERVER['SERVER_SOFTWARE'] ? $_SERVER['SERVER_SOFTWARE'] : getenv('SERVER_SOFTWARE'))."\n";
		$msg .= "\t".'OS: '.php_uname('s').' '.php_uname('r').' '.php_uname('v');
		return $msg;
	}
}

$objChk = new CheckEnvironment();
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>CheckEnvironment</title>
</head>
<body>
<pre>
Server Infomation:

<?php echo $objChk->getServerInfo(); ?> 

PIO Check:

	SQLite Support: <?php echo $objChk->checkPIOSQLite(); ?> 
	MySQL Support: <?php echo $objChk->checkPIOMySQL(); ?> 
	MySQL Improved Support: <?php echo $objChk->checkPIOMySQLi(); ?> 
	PDO SQLite3 Support: <?php echo $objChk->checkPIOPDOSQLite3(); ?> 
	PostgreSQL Support: <?php echo $objChk->checkPIOPostgreSQL(); ?> 

Thumbnail Generator Check:

	GD Support: <?php echo $objChk->checkGD(); ?> 
	Imagick Support: <?php echo $objChk->checkImagick(); ?> 
	MagickWand Support: <?php echo $objChk->checkMagickWand(); ?> 
	ImageMagick Support: <?php echo $objChk->checkImageMagick(); ?> 
	repng2jpeg Support: <?php echo $objChk->checkRepng2jpeg(); ?> 

Check suitable repng2jpeg:

<?php echo $objChk->checkRepng2jpegRecommended(); ?>

</pre>
</body>
</html>