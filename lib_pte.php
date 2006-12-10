<?php
/*
Pixmicat! Template-Embedded Library v060915
by: scribe
*/
class PTELibrary{
	var $tpl_main, $tpl_reply, $tpl_sepa;

	/* 開啟樣板檔案並分出三個區塊 */
	function PTELibrary($tplname){
		$tpl = file_get_contents($tplname);
		// 拆MAIN部分
		if(preg_match("/<!--&MAIN-->(.*)<!--\/&MAIN-->/smU", $tpl, $matches)) $this->tpl_main = $matches[1];
		// 拆REPLY部分
		if(preg_match('/<!--&REPLY-->(.*)<!--\/&REPLY-->/smU', $tpl, $matches)) $this->tpl_reply = $matches[1];
		// 拆SEPARATE部分
		if(preg_match("/<!--&SEPARATE-->(.*)<!--\/&SEPARATE-->/smU", $tpl, $matches)) $this->tpl_sepa = $matches[1];
	}

	/* 將樣版的標籤取代為正確的字串並傳回：MAIN */
	function ReplaceStrings_Main($ary_rpl){
		$tmp_main = $this->EvalIF($this->tpl_main, $ary_rpl); // 解析IF敘述
		return str_replace(array_keys($ary_rpl), array_values($ary_rpl), $tmp_main);
	}

	/* 將樣版的標籤取代為正確的字串並傳回：REPLY */
	function ReplaceStrings_Reply($ary_rpl){
		$tmp_reply = $this->EvalIF($this->tpl_reply, $ary_rpl); // 解析IF敘述
		return str_replace(array_keys($ary_rpl), array_values($ary_rpl), $tmp_reply);
	}

	/* 傳回分隔用樣板原始碼 */
	function ReplaceStrings_Separate(){
		return $this->tpl_sepa;
	}

	/* 解析IF敘述 */
	function EvalIF($tpl, $ary){
		$tmp_tpl = $tpl;
		if(preg_match_all('/<!--&IF\((\$.*),\'(.*)\',\'(.*)\'\)-->/smU', $tmp_tpl, $matches, PREG_SET_ORDER)){
			foreach($matches as $submatches){
				$vari = $submatches[1]; $iftrue = $submatches[2]; $iffalse = $submatches[3];
				$tmp_tpl = str_replace($submatches[0], ($ary['{'.$vari.'}'] ? $iftrue : $iffalse), $tmp_tpl);
			}
		}
		return $tmp_tpl;
	}
}
?>