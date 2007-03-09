<?php
include_once('./config.php'); // 引入設定檔
include_once('./lib_language.php'); // 引入語系
header('Content-Type: text/javascript');
?>
var msgs=['<?php echo _T('regist_withoutcomment'); ?>','<?php echo _T('regist_upload_notsupport'); ?>','<?php echo _T('js_convert_sakura'); ?>'];