<!--&THEMENAME-->futaba Theme<!--/&THEMENAME-->
<!--&THEMEVER-->v20140603<!--/&THEMEVER-->
<!--&THEMEAUTHOR-->Pixmicat! Development Team<!--/&THEMEAUTHOR-->
<!--&HEADER--><!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=yes" />
<title>{$TITLE}</title>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap-theme.min.css">
<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
<style type="text/css">
body{background-color: #FFE;}	
	a:link { color: #00E; } /* 正常連結樣式 */
	a:hover { color: #D00; } /* hover時連結樣式 */
	a:visited { color: #00E; } /* 已拜訪連結樣式 */
	a.qlink { text-decoration: none; } /* 引用用連結樣式 */
	small { font-size: 0.8em; } /* 小字樣式(eg.[以預覽圖顯示]) */
	hr { clear: left; } /* 分隔線樣式 */
	img { border: 0; } /* 圖片顯示樣式 */
	h1 { color: #800000; text-align: center; margin: 0 auto; } /* 網頁主標題樣式 */
	hr.top { width: 90%; height: 1px; } /* 主標題下分隔線樣式 */

	.Form_bg { background: #EA8; } /* 送出表單左方欄位之底色 */
	.hide_btn { float:right;display:flex; width: 4em; height: 1.25em; overflow: hidden; text-align: center; background: #F0E0D6; } /* 表單收縮按鈕樣式 */
	.show { width:6em;color: #00E; }
	.hide { display: none; }
	#postinfo { font-size: 0.8em; } /* 上傳說明樣式 */
	form { padding: 0; margin: 0; } /* 修正表單標籤造成的排版問題 */
	
	.threadpost {  } /* 討論串首篇樣式 */
	.reply { display: table; margin: 0.5ex 1em 0 1em; background: #F0E0D6; } /* 討論串回應樣式 */
	.replywrap { display: table-cell; } /* 解決 IE8+ 無法選取討論串回應內文用 */
	.reply_hl { background: #F0D5B7; }  /* 討論串回應背景標亮樣式 */
	.name { color: #117743; font-weight: bold; } /* 文章張貼者名稱樣式 */
	.admin_cap { color: #0000FF; } /* 管理員キャップ樣式設定 */
	.img { float: left; margin: 1ex 2ex; } /* 討論串圖片顯示樣式 */
	.title { color: #CC1105; font-size: 1.125em; font-weight: bold; } /* 討論串標題樣式 */
	.nor { font-weight: normal; } /* Trip取消粗體用 */
	.quote { margin: 1em 2em; } /* 討論串內文縮排樣式 */
	.resquote { color: #789922; } /* 標註引用回文顏色 */
	.category { font-size: 0.8em; color: gray; } /* 類別標籤顯示樣式 */

	.warn_txt { color: #F00000; font-weight: bold; } /* 討論串狀態警告文字(eg.文章即將被刪除) */
	.warn_txt2 { color: #707070; } /* 討論串狀態提示文字(eg.回應幾篇被隱藏) */
	#footer { text-align: center; clear: both; } /* 頁尾樣式 */
	.bar_reply { background: #E04000; color: #FFF; font-weight: bold; text-align: center; } /* 回應模式樣式標題列 */
	.bar_admin { background: #E08000; color: #FFF; font-weight: bold; text-align: center; } /* 管理模式樣式標題列 */
	.ListRow1_bg { background: #D6D6F6; } /* 管理模式欄位背景顏色1(輪替出現) */
	.ListRow2_bg { background: #F6F6F6; } /* 管理模式欄位背景顏色2(輪替出現) */

</style>
<!-- <link rel="stylesheet" type="text/css" href="mainstyle.css" />  -->
<!--/&HEADER-->

<!--&JSHEADER-->
<script type="text/javascript">
// <![CDATA[
var msgs=['{$JS_REGIST_WITHOUTCOMMENT}','{$JS_REGIST_UPLOAD_NOTSUPPORT}','{$JS_CONVERT_SAKURA}'];
var ext="{$ALLOW_UPLOAD_EXT}".toUpperCase().split("|");
// ]]>
</script>
<script type="text/javascript" src="mainscript.js"></script>
<!--[if lt IE 8]><script type="text/javascript" src="iedivfix.js"></script><![endif]-->
<!--/&JSHEADER-->

<!--&TOPLINKS-->
<nav class="nav nav-tabs" role="navigation" id="toplink">
{$HOOKLINKS} {$TOP_LINKS} {$STATUS} {$ADMIN} {$REFRESH} {$SEARCH} {$HOME} 
</nav>
<!--/&TOPLINKS-->

<!--&BODYHEAD-->
<body>

<div id="header"> 
<!--&TOPLINKS/-->
<br />
<h1>{$TITLE}</h1>
<hr class="top" />
</div>
<!--/&BODYHEAD-->

<!--&POSTFORM-->
<form action="{$SELF}" method="post" enctype="multipart/form-data" onsubmit="return c();" id="postform_main">
<div id="postform">
<!--&IF($FORMTOP,'{$FORMTOP}','')-->
<input type="hidden" name="mode" value="{$MODE}" />
<input type="hidden" name="MAX_FILE_SIZE" value="{$MAX_FILE_SIZE}" />
<input type="hidden" name="upfile_path" value="" />
<!--&IF($RESTO,'{$RESTO}','')-->
<div style="text-align: center;">
<table id="postform_tbl" style="padding: 10px;border-spacing; 10px; margin: 0px auto; text-align: left;">
<tr><td class="Form_bg"><b>{$FORM_NAME_TEXT}</b></td><td>{$FORM_NAME_FIELD}</td></tr>
<tr><td class="Form_bg"><b>{$FORM_EMAIL_TEXT}</b></td><td>{$FORM_EMAIL_FIELD}</td></tr>
<tr><td class="Form_bg"><b>{$FORM_TOPIC_TEXT}</b></td><td>{$FORM_TOPIC_FIELD}{$FORM_SUBMIT}</td></tr>
<tr><td class="Form_bg"><b>{$FORM_COMMENT_TEXT}</b></td><td>{$FORM_COMMENT_FIELD}</td></tr>
<!--&IF($FORM_ATTECHMENT_FIELD,'<tr><td class="Form_bg"><b>{$FORM_ATTECHMENT_TEXT}</b></td><td>{$FORM_ATTECHMENT_FIELD}[{$FORM_NOATTECHMENT_FIELD}<label for="noimg">{$FORM_NOATTECHMENT_TEXT}</label>]','')-->
<!--&IF($FORM_CONTPOST_FIELD,'[{$FORM_CONTPOST_FIELD}<label for="up_series">{$FORM_CONTPOST_TEXT}</label>]','')-->
<!--&IF($FORM_ATTECHMENT_FIELD,'</td></tr>','')-->
<!--&IF($FORM_CATEGORY_FIELD,'<tr><td class="Form_bg"><b>{$FORM_CATEGORY_TEXT}</b></td><td>{$FORM_CATEGORY_FIELD}<small>{$FORM_CATEGORY_NOTICE}</small></td></tr>','')-->
<tr><td class="Form_bg"><b>{$FORM_DELETE_PASSWORD_TEXT}</b></td><td>{$FORM_DELETE_PASSWORD_FIELD}<small>{$FORM_DELETE_PASSWORD_NOTICE}</small></td></tr>
{$FORM_EXTRA_COLUMN}
<tr><td colspan="2">
<div id="postinfo">
<ul>{$FORM_NOTICE}
<!--&IF($FORM_NOTICE_STORAGE_LIMIT,'{$FORM_NOTICE_STORAGE_LIMIT}','')-->
{$HOOKPOSTINFO}
{$ADDITION_INFO}
</ul>
<noscript><div>{$FORM_NOTICE_NOSCRIPT}</div></noscript>
</div>
</td></tr>
</table>
</div>
<script type="text/javascript">l1();</script>
<hr />
</div>
</form>
<!--&IF($FORMBOTTOM,'{$FORMBOTTOM}','')-->
<!--/&POSTFORM-->

<!--&FOOTER-->
<div id="footer">
{$FOOTER}
<script type="text/javascript">preset();</script>
</div>

</body>
</html>
<!--/&FOOTER-->

<!--&ERROR-->
<div id="error">
<div style="text-align: center; font-size: 1.5em; font-weight: bold;">
<span style="color: red;">{$MESG}</span><p />
<a href="{$SELF2}">{$RETURN_TEXT}</a>　<a href="javascript:history.back();">{$BACK_TEXT}</a>
</div>
<hr />
</div>
<!--/&ERROR-->


<!--&THREAD-->
<div class="threadpost" id="r{$NO}">
{$IMG_BAR}<!--&IF($IMG_BAR,'<br />','')-->{$IMG_SRC}<input type="checkbox" name="{$NO}" value="delete" /><span class="title">{$SUB}</span>
{$NAME_TEXT}<span class="name">{$NAME}</span> [{$NOW}] {$QUOTEBTN}&#160;{$REPLYBTN}
<div class="quote">{$COM}</div>
<!--&IF($CATEGORY,'<div class="category">{$CATEGORY_TEXT}{$CATEGORY}</div>','')-->
{$WARN_OLD}{$WARN_BEKILL}{$WARN_ENDREPLY}{$WARN_HIDEPOST}</div>
<!--/&THREAD-->

<!--&REPLY-->
<div class="reply" id="r{$NO}"><div class="replywrap">
<input type="checkbox" name="{$NO}" value="delete" /><span class="title">{$SUB}</span> {$NAME_TEXT}<span class="name">{$NAME}</span> [{$NOW}] {$QUOTEBTN} &#160;<!--&IF($IMG_BAR,'<br />&#160;','')-->{$IMG_BAR} {$IMG_SRC}
<div class="quote">{$COM}</div>
<!--&IF($CATEGORY,'<div class="category">{$CATEGORY_TEXT}{$CATEGORY}</div>','')-->
{$WARN_BEKILL}</div></div>
<!--/&REPLY-->

<!--&SEARCHRESULT-->
<div class="threadpost">
<span class="title">{$SUB}</span>
{$NAME_TEXT}<span class="name">{$NAME}</span> [{$NOW}] No.{$NO}
<div class="quote">{$COM}</div>
<!--&IF($CATEGORY,'<div class="category">{$CATEGORY_TEXT}{$CATEGORY}</div>','')-->
</div>
<!--&REALSEPARATE/-->
<!--/&SEARCHRESULT-->

<!--&THREADSEPARATE-->
<hr />
<!--/&THREADSEPARATE-->

<!--&REALSEPARATE-->
<hr />
<!--/&REALSEPARATE-->

<!--&DELFORM-->
<div id="del">
<table style="float: right;">
<tr><td style="text-align:center;white-space: nowrap;">
{$DEL_HEAD_TEXT}[{$DEL_IMG_ONLY_FIELD}<label for="onlyimgdel">{$DEL_IMG_ONLY_TEXT}</label>]<br />
{$DEL_PASS_TEXT}{$DEL_PASS_FIELD}{$DEL_SUBMIT_BTN}
</td></tr>
</table>
</div>
<!--/&DELFORM-->

<!--&MAIN-->
<div id="contents">
{$THREADFRONT}
<form action="{$SELF}" method="post">
<div id="threads" class="autopagerize_page_element">
{$THREADS}
</div>
{$THREADREAR}
<!--&DELFORM/-->
<script type="text/javascript">l2();</script>
</form>
<div class="pagination pagination-large pagination-centered">
{$PAGENAV}
</div>
<!--/&MAIN-->
