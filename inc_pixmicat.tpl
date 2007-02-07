<!--&MAIN-->
<div class="threadpost">
{$IMG_BAR}<!--&IF($IMG_BAR,'<br />','')-->{$IMG_SRC}<input type="checkbox" name="{$NO}" value="delete" /><span class="title">{$SUB}</span>
名稱: <span class="name">{$NAME}</span> [{$NOW}] {$QUOTEBTN}&nbsp;{$REPLYBTN}
<div class="quote">{$COM}</div>
<!--&IF($CATEGORY,'<div class="category">類別: {$CATEGORY}</div>','')-->
{$WARN_OLD}{$WARN_BEKILL}{$WARN_ENDREPLY}{$WARN_HIDEPOST}</div>
<!--/&MAIN-->
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