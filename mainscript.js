var preset_sensor=0;var previous_replyhlno=0;var msgs=['在沒有附加檔案的情況下，請寫入內文','附加檔案為系統不支援的格式','偵測到您有輸入櫻花日文假名的可能性，將自動為您轉換'];var arrtbl_1=[63223,12353,82],arrtbl_2=[63306,12449,85],arrtbl_3=[63486,12535,4],arrtbl_4=[[63216,63219],[12293,12540],1],arrtbl_5=[[63210,63212],[12541,12445],1];
/* 讀取Cookies值 */
function getCookie(key){var tmp1=' '+document.cookie+';',tmp2;var xx1=0,xx2=0,xx3;var len=tmp1.length;while(xx1<len){xx2=tmp1.indexOf(';',xx1);tmp2=tmp1.substring(xx1+1,xx2);xx3=tmp2.indexOf('=');if(tmp2.substring(0,xx3)==key){return unescape(tmp2.substring(xx3+1,xx2-xx1-1));}
xx1=xx2+1;}
return'';}

/* 寫入Cookies值 */
function setCookie(name,value){var exp=new Date();exp.setTime(exp.getTime()+1000*60*60*24*7);document.cookie=name+'='+escape(value)+'; expires='+exp.toGMTString();}

/* 取出Cookies的值並填入表單 */
function l(e){var P=getCookie('pwdc'),N=getCookie('namec'),E=getCookie('emailc');var d=document;var forms_length=d.forms.length;for(var i=0;i<forms_length;i++){if(d.forms[i].pwd){d.forms[i].pwd.value=P;}
if(d.forms[i].name){d.forms[i].name.value=N;}
if(d.forms[i].email){d.forms[i].email.value=E;}}}

/* 前端檢查表單機制 */
function c(){var df=document.forms[0];try{check_sakura('com');check_sakura('name');check_sakura('sub');if(df.upfile){if(!df.upfile.value&&!df.com.value){alert(msgs[0]);return false;}
if(df.upfile.value){var ext_allowed=0,ext_length=ext.length;for(var j=0;j<ext_length;j++){if(df.upfile.value.substr(df.upfile.value.length-3,3).toUpperCase()==ext[j]){ext_allowed=1;break;}}
if(!ext_allowed){alert(msgs[1]);return false;}
if(window.clipboardData){df.upfile_path.value=df.upfile.value;}}}else{if(!df.com.value){alert(msgs[0]);return false;}}
df.sendbtn.disabled=true;}catch(e){}
if(df.name.value){setCookie('namec',df.name.value);}}

/* 動態改變超連結的視窗目標 */
function fixalllinks(){if(!document.getElementsByTagName){return;}
var anchors=document.getElementsByTagName('a');var anchors_length=anchors.length;for(var i=0;i<anchors_length;i++){var anchor=anchors[i];if(anchor.getAttribute('href')&&anchor.getAttribute('rel')=='_blank'){anchor.target='_blank';}
if(anchor.getAttribute('href')&&anchor.getAttribute('rel')=='_top'){anchor.target='_top';}}}

/* 顯示/隱藏發文表單 */
function showform(){var d=document;d.getElementById('postform').className='';d.getElementById('postform_tbl').className='';d.getElementById('hide').className='show';d.getElementById('show').className='hide';}
function hideform(){var d=document;d.getElementById('postform').className='hide_btn';d.getElementById('postform_tbl').className='hide';d.getElementById('hide').className='hide';d.getElementById('show').className='show';}

/* 內文引用編號 */
function quote(text){try{document.forms[0].com.focus();}catch(e){}
document.forms[0].com.value+='>>No.'+text+"\r\n";}

/* 回應背景標亮 / 取消 */
function replyhl(id,isrecover){var rpydiv=document.getElementById('r'+id);if(rpydiv){if(isrecover){rpydiv.className=rpydiv.className.replace(' reply_hl','');}else{if(previous_replyhlno){replyhl(previous_replyhlno,true);}previous_replyhlno=id;rpydiv.className+=' reply_hl';}}}

/* 檢查使用者是否輸入了Unicode使用者造字區字集 (多為櫻花日文假名) */
function check_sakura(field){var tar_obj=document.forms[0][field];var checktext=escape(tar_obj.value).toLowerCase();var regular_exp=/%uf(6[ef]|7[0-9a-f]|80)[0-9a-f]/;if(checktext.match(regular_exp)){alert(msgs[2]);replace_sakura(tar_obj);}}

/* 將Unicode使用者造字區字集之日文對應至Unicode日文假名區 */
function replace_sakura(tar_obj){var temp=tar_obj.value;var i=0;for(i=0;i<=arrtbl_1[2];i++){temp=ReplaceDX(temp,arrtbl_1[0]+i,arrtbl_1[1]+i);}
for(i=0;i<=arrtbl_2[2];i++){temp=ReplaceDX(temp,arrtbl_2[0]+i,arrtbl_2[1]+i);}
for(i=0;i<=arrtbl_3[2];i++){temp=ReplaceDX(temp,arrtbl_3[0]+i,arrtbl_3[1]+i);}
for(i=0;i<=arrtbl_4[2];i++){temp=ReplaceDX(temp,arrtbl_4[0][i],arrtbl_4[1][i]);}
for(i=0;i<=arrtbl_5[2];i++){temp=ReplaceDX(temp,arrtbl_5[0][0]+i,arrtbl_5[1][0]+i);temp=ReplaceDX(temp,arrtbl_5[0][1]+i,arrtbl_5[1][1]+i);}
tar_obj.value=temp;}

/* 強力字元取代函式 */
function ReplaceDX(txt,target,retxt){if(txt.indexOf(String.fromCharCode(target))!=-1){txt=txt.split(String.fromCharCode(target));txt=txt.join(String.fromCharCode(retxt));}
return txt;}

/* 載入後執行的函式 */
function preset(){if(preset_sensor){return;}
preset_sensor++;fixalllinks();var url=location.href;if(url.indexOf('?res=')){if(url.match(/#[rq]([0-9]+)$/)){replyhl(RegExp.$1);}
if(url.match(/#q([0-9]+)$/)){quote(RegExp.$1);}}}

window.onload = preset;