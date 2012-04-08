<?php
include('./config.php');
include('./lib/fileio/ifs.php');

@unlink(FILEIO_INDEXLOG);
$fio = new IndexFS(FILEIO_INDEXLOG);

$fio->openIndex();
clearstatcache();
$dirs = array(IMG_DIR, THUMB_DIR);
foreach ($dirs as $dir) {
	if ($handle = opendir($dir)) {
		while (false !== ($file = readdir($handle))) {
			if ($file == "." || $file == "..")  continue;
			$fio->addRecord($file,filesize($dir.'/'.$file),'');
		}
	}
}
$fio->saveIndex();
echo "done";
?>