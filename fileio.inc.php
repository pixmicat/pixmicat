<?php
if(USE_FTP==1)
	include_once('./fileio.ftp.php');
else
	include_once('./fileio.normal.php');
?>