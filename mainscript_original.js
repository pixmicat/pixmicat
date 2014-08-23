// ==ClosureCompiler==
// @compilation_level SIMPLE_OPTIMIZATIONS
// @output_file_name mainscript.js
// ==/ClosureCompiler==
/*jslint browser: true, devel: true, undef: true, eqeqeq: true, regexp: true, newcap: true, immed: true */
/*global window, msgs, ext */

var previous_replyhlno = 0;
var arrPresetFunc = [];
var arrSakuraTbl = [[63223, 12353, 82], [63306, 12449, 85], [63486, 12535, 4]]; // Big5-Sakura to Unicode Table
var arrSakuraTblsp = [[63216, 63219, 63210, 63211, 63212, 63213], [12293, 12540, 12541, 12542, 12445, 14446]]; // Special Characters

/* getElementById shortcut */
function $g(i){ return document.getElementById(i); }

/* 讀取Cookies值 */
function getCookie(key){
	var tmp1, tmp2, xx1 = 0, xx2 = 0, xx3;
	tmp1 = ' '+document.cookie+';';
	var len = tmp1.length;
	while(xx1 < len){
		xx2 = tmp1.indexOf(';', xx1);
		tmp2 = tmp1.substring(xx1 + 1, xx2);
		xx3 = tmp2.indexOf('=');
		if(tmp2.substring(0, xx3)===key){ return window.unescape(tmp2.substring(xx3 + 1, xx2 - xx1 - 1)); }
		xx1 = xx2 + 1;
	}
	return '';
}

/* 寫入Cookies值 */
function setCookie(name, value){
	var exp = new Date();
	exp.setTime(exp.getTime() + 86400000 * 7);
	document.cookie = name+'='+window.escape(value)+'; expires='+exp.toGMTString();
}

/* 將Unicode使用者造字區字集之日文對應至Unicode日文假名區 */
function replace_sakura(tar_obj){
	var temp = tar_obj.value, i = 0, p = 0;
	var Tblcount = arrSakuraTbl.length, Tbl;
	for(i = 0; i < Tblcount; i++){ // 處理假名部分
		Tbl = arrSakuraTbl[i];
		for(p = 0; p <= Tbl[2]; p++){ temp = temp.replace(new RegExp(String.fromCharCode(Tbl[0] + p), 'g'), String.fromCharCode(Tbl[1] + p)); }
	}
	Tblcount = arrSakuraTblsp[0].legnth;
	for(i = 0; i < Tblcount; i++){ // 處理符號部分
		Tbl = arrSakuraTblsp;
		temp = temp.replace(new RegExp(String.fromCharCode(Tbl[0][i]), 'g'), String.fromCharCode(Tbl[1][i]));
	}
	tar_obj.value = temp;
}

/* 檢查使用者是否輸入了Unicode使用者造字區字集 (多為櫻花日文假名) */
function check_sakura(field){
	var tar_obj = $g(field);
	var checktext = window.escape(tar_obj.value).toLowerCase(); // %uxxxx形式 (全部小寫)
	var regular_exp = /%uf(6[ef]|7[0-9a-f]|80)[0-9a-f]/; // U+F6Ex～F80x為櫻花日文的概略位置 (比對時用小寫)
	if(checktext.match(regular_exp)!==null){
		alert(msgs[2]);
		replace_sakura(tar_obj); // 代轉
	}
}

/* 取出Cookies的值並填入表單 */
function l1(){
	var N = getCookie('namec'), E = getCookie('emailc'), obj;
	if((obj = $g('fname'))){ obj.value = N; }
	if((obj = $g('femail'))){ obj.value = E; }
}

/* 填入表單密碼 */
function l2(){
	var P = getCookie('pwdc'), d = document, forms_length = d.forms.length;
	for(var i = 0; i < forms_length; i++){
		if(d.forms[i].pwd){ d.forms[i].pwd.value = P; }
	}
}

/* 前端檢查表單機制 */
function c(){
	var upfilevalue, j, ext_allowed, ext_length;
	try{
		if(!$g('fupfile')){ return true; }
		upfilevalue = $g('fupfile').value;
		if(!upfilevalue && !$g('fcom').value){ alert(msgs[0]); return false; }
		if(upfilevalue){
			ext_allowed = 0; ext_length = ext.length;
			for(j = 0; j < ext_length; j++){
				if(upfilevalue.substr(upfilevalue.lastIndexOf('.')+1).toUpperCase()===ext[j]){
					ext_allowed = 1;
					break;
				}
			}
			if(!ext_allowed){ alert(msgs[1]); return false; }
		}
		check_sakura('fcom'); check_sakura('fname'); check_sakura('fsub'); // 檢查櫻花日文
		if(window.clipboardData){ document.forms[0].upfile_path.value = upfilevalue; } // IE的Senddata為完整路徑名稱
		document.forms[0].sendbtn.disabled = true;
	}catch(e){  }
	if($g('fname').value){ setCookie('namec', $g('fname').value); } // Cookies寫入名稱
}

/* 顯示發文表單 */
function showform(){
	$g("postform").className = '';
	$g("postform_tbl").className = '';
	$g("hide").className = 'show';
	$g("show").className = 'hide';
}

/* 隱藏發文表單 */
function hideform(){
	$g("postform").className = 'hide_btn';
	$g("postform_tbl").className = 'hide';
	$g("hide").className = 'hide';
	$g("show").className = 'show';
}

/* 內文引用編號 */
function quote(text){
	try{
		$g('fcom').focus();
		$g('fcom').value += '>>No.' + text + "\r\n";
	}catch(e){  }
}

/* 回應背景標亮 / 取消 */
function replyhl(id, isRecover){
	var rpydiv = $g('r'+id);
	if(rpydiv){
		if(isRecover){
			rpydiv.className = rpydiv.className.replace(' reply_hl', '');
		}else{
			if(previous_replyhlno){ replyhl(previous_replyhlno, true); }
			previous_replyhlno = id;
			rpydiv.className += ' reply_hl';
		}
	}
}

/* 掛載當執行 preset() 後跟著執行的函式 */
function hookPresetFunction(func){
	if(typeof func === 'function'){ arrPresetFunc.push(func); }
}

/* 載入後執行的函式 */
function preset(){
	var i, l = arrPresetFunc.length, f;

	for(i = 0; i < l; i++){ f = arrPresetFunc[i]; if(typeof f==='function'){ f(); } }
	var url = location.href;
	if(url.indexOf('?res=')){
		if(url.match(/#[rq]([0-9]+)$/)){ replyhl(RegExp.$1, false); } // 回應標亮
		if(url.match(/#q([0-9]+)$/)){ quote(RegExp.$1); } // 回應引用
	}
}