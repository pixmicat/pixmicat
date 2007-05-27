<!--&HEADER1--><?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="zh-tw">
<head>
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="Sat, 1 Jan 2000 00:00:00 GMT" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Content-Language" content="zh-tw" />
<title>{$TITLE}</title>
<link rel="stylesheet" type="text/css" href="mainstyle.css" />
<!--/&HEADER1-->

<!--&HEADER2-->
<script type="text/javascript" src="mainscript.lang.php"></script>
<script type="text/javascript" src="mainscript.js"></script>
<!--[if IE]><script type="text/javascript" src="iedivfix.js"></script><![endif]-->
<script type="text/javascript">
// <![CDATA[
var ext="{$ALLOW_UPLOAD_EXT}".toUpperCase().split("|");
// ]]>
</script>
<!--/&HEADER2-->

<!--&TOPLINKS-->
<div id="toplink">
{$HOME} {$SEARCH} {$HOOKLINKS} {$TOP_LINKS} {$STATUS} {$ADMIN} {$REFRESH}
</div>
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
<input type="hidden" name="mode" value="regist" />
<input type="hidden" name="MAX_FILE_SIZE" value="{$MAX_FILE_SIZE}" />
<input type="hidden" name="upfile_path" value="" />
<!--&IF($RESTO,'{$RESTO}','')-->
<div style="text-align: center;">
<table cellpadding="1" cellspacing="1" id="postform_tbl" style="margin: 0px auto; text-align: left;">
<tr><td class="Form_bg"><b>{$FORM_NAME_TEXT}</b></td><td>{$FORM_NAME_FIELD}</td></tr>
<tr><td class="Form_bg"><b>{$FORM_EMAIL_TEXT}</b></td><td>{$FORM_EMAIL_FIELD}</td></tr>
<tr><td class="Form_bg"><b>{$FORM_TOPIC_TEXT}</b></td><td>{$FORM_TOPIC_FIELD}{$FORM_SUBMIT}</td></tr>
<tr><td class="Form_bg"><b>{$FORM_COMMENT_TEXT}</b></td><td>{$FORM_COMMENT_FIELD}</td></tr>
<!--&IF($FORM_ATTECHMENT_FIELD,'<tr><td class="Form_bg"><b>{$FORM_ATTECHMENT_TEXT}</b></td><td>{$FORM_ATTECHMENT_FIELD}[{$FORM_NOATTECHMENT_FIELD}<label for="noimg">{$FORM_NOATTECHMENT_TEXT}</label>]','')-->
<!--&IF($FORM_CONTPOST_FIELD,'[{$FORM_CONTPOST_FIELD}<label for="up_series">{$FORM_CONTPOST_TEXT}</label>]','')-->
<!--&IF($FORM_ATTECHMENT_FIELD,'</td></tr>','')-->
<!--&IF($FORM_CATEGORY_FIELD,'<tr><td class="Form_bg"><b>{$FORM_CATEGORY_TEXT}</b></td><td>{$FORM_CATEGORY_FIELD}<small>{$FORM_CATEGORY_NOTICE}</small></td></tr>','')-->
<tr><td class="Form_bg"><b>{$FORM_DELETE_PASSWORD_TEXT}</b></td><td>{$FORM_DELETE_PASSWORD_FIELD}<small>{$FORM_DELETE_PASSWORD_NOTICE}</small></td></tr>
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
<div class="threadpost">
{$IMG_BAR}<!--&IF($IMG_BAR,'<br />','')-->{$IMG_SRC}<input type="checkbox" name="{$NO}" value="delete" /><span class="title">{$SUB}</span>
名稱: <span class="name">{$NAME}</span> [{$NOW}] {$QUOTEBTN}&nbsp;{$REPLYBTN}
<div class="quote">{$COM}</div>
<!--&IF($CATEGORY,'<div class="category">類別: {$CATEGORY}</div>','')-->
{$WARN_OLD}{$WARN_BEKILL}{$WARN_ENDREPLY}{$WARN_HIDEPOST}</div>
<!--/&THREAD-->

<!--&REPLY-->
<div class="reply" id="r{$NO}">
<input type="checkbox" name="{$NO}" value="delete" /><span class="title">{$SUB}</span> 名稱: <span class="name">{$NAME}</span> [{$NOW}] {$QUOTEBTN} &nbsp;<!--&IF($IMG_BAR,'<br />&nbsp;','')-->{$IMG_BAR} {$IMG_SRC}
<div class="quote">{$COM}</div>
<!--&IF($CATEGORY,'<div class="category">類別: {$CATEGORY}</div>','')-->
{$WARN_BEKILL}</div>
<!--/&REPLY-->

<!--&SEPARATE-->
<hr />
<!--/&SEPARATE-->

<!--&DELFORM-->
<div id="del">
<table style="float: right;">
<tr><td align="center" style="white-space: nowrap;">
{$DEL_HEAD_TEXT}[{$DEL_IMG_ONLY_FIELD}<label for="onlyimgdel">'{$DEL_IMG_ONLY_TEXT}</label>]<br />
{$DEL_PASS_TEXT}{$DEL_PASS_FIELD}{$DEL_SUBMIT_BTN}
</td></tr>
</table>
</div>
<!--/&DELFORM-->

<!--&MAIN-->
<div id="contents">
<form action="{$SELF}" method="POST">
<div id="threads">
{$THREADFRONT}
{$THREADS}
{$THREADREAR}
</div>
<!--&DELFORM/-->
<script type="text/javascript">l2();</script>
</form>
{$PAGENAV}
</div>
<!--/&MAIN-->
