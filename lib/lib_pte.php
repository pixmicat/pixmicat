<?php
/*
Pixmicat! Template-Embedded Library v070606
by: scribe & RT
$Id$
*/

class PTELibrary{
	var $tpl_block, $tpl;

	/* 開啟樣板檔案並取出區塊 */
	function PTELibrary($tplname){
		$this->tpl_block = array();
		$this->tpl = file_get_contents($tplname);
	}

	/* 回傳區塊樣板碼並快取 */
	function _readBlock($blockName){
		if(!isset($this->tpl_block[$blockName])){ // 是否找過
			if(preg_match('/<!--&'.$blockName.'-->(.*)<!--\/&'.$blockName.'-->/smU', $this->tpl, $matches))
				$this->tpl_block[$blockName] = $matches[1]; // 找到了存入陣列快取
			else
				$this->tpl_block[$blockName] = false; // 找過但沒找到
		}
		return $this->tpl_block[$blockName];
	}

	/* 將樣版的標籤取代為正確的字串並傳回 */
	function ParseBlock($blockName, $ary_val){
		if(($tmp_block = $this->_readBlock($blockName))===false) return ""; // 找無
		$tmp_block = $this->EvalFOREACH($tmp_block, $ary_val); // 解析FOREACH敘述
		$tmp_block = $this->EvalIF($tmp_block, $ary_val); // 解析IF敘述
		if(preg_match_all('/<!--&(.*)\/-->/smU', $tmp_block, $matches)){ // 迴遞處理
			$blocksCount=count($matches[1]);
			for($i=0;$i<$blocksCount;$i++)
				$tmp_block = str_replace($matches[0][$i], $this->ParseBlock($matches[1][$i], $ary_val), $tmp_block);
		}
		return @str_replace(@array_keys($ary_val), @array_values($ary_val), $tmp_block);
	}

	/* 解析IF敘述 */
	function EvalIF($tpl, $ary){
		$tmp_tpl = $tpl;
		if(preg_match_all('/<!--&IF\((\$.*),\'(.*)\',\'(.*)\'\)-->/smU', $tmp_tpl, $matches, PREG_SET_ORDER)){
			foreach($matches as $submatches){
				$vari = $submatches[1]; $iftrue = $submatches[2]; $iffalse = $submatches[3];
				if(preg_match('/<!--&(.*)\/-->/smU', $iftrue, $rmatches)) // 迴遞處理
					$iftrue = str_replace($rmatches[0],$this->ParseBlock($rmatches[1],$ary),$iftrue);
				if(preg_match('/<!--&(.*)\/-->/smU', $iffalse, $rmatches)) // 迴遞處理
					$iffalse = str_replace($rmatches[0],$this->ParseBlock($rmatches[1],$ary),$iffalse);
				$tmp_tpl = @str_replace($submatches[0], ($ary['{'.$vari.'}'] ? $iftrue : $iffalse), $tmp_tpl);
			}
		}
		return $tmp_tpl;
	}
	/* 解析FOREACH敘述 */
	function EvalFOREACH($tpl, $ary){
		$tmp_tpl = $tpl;
		if(preg_match_all('/<!--&FOREACH\((\$.*),\'(.*)\'\)-->/smU', $tmp_tpl, $matches, PREG_SET_ORDER)){
			foreach($matches as $submatches){
				$vari = $submatches[1]; $block = $submatches[2];
				
				$foreach_tmp = '';
				if(isset($ary['{'.$vari.'}']) && is_array($ary['{'.$vari.'}']))
					foreach($ary['{'.$vari.'}'] as $eachvar)
						$foreach_tmp .= $this->ParseBlock($block, $eachvar);
				$tmp_tpl = @str_replace($submatches[0], $foreach_tmp, $tmp_tpl);
			}
		}
		return $tmp_tpl;
	}
}
?>