<?php
include_once('./config.php'); // 引入設定檔
include_once('./lib/lib_language.php'); // 引入語系
header('Content-Type: text/javascript');
?>
var msgs=['<?php echo str_replace('\'', '\\\'', _T('regist_withoutcomment')); ?>','<?php echo str_replace('\'', '\\\'', _T('regist_upload_notsupport')); ?>','<?php echo str_replace('\'', '\\\'', _T('js_convert_sakura')); ?>'];