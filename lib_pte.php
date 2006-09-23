<?php
/**
 * Pixmicat! Template-Embedded Library
 *
 * 分析樣板檔案(*.tpl)並載入後，排版資料輸出
 *
 * <code>
 * $PTE = new PTELibrary('inc_pixmicat.tpl');
 * echo $PTE->ReplaceStrings_Main(array('{NO}'=>$no, ...));
 * </code>
 *
 * @package PTELibrary
 * @version v060923
 * @author scribe
 */
class PTELibrary{
	var $tpl_style, $tpl_main, $tpl_reply, $tpl_sepa;

	/*
	 * 開啟樣板檔案並分出四個區塊
	 * @param string 樣板檔案檔名
	 */
	function PTELibrary($tplname){
		$tpl = file_get_contents($tplname);
		// 拆STYLE部分
		if(preg_match("/<!--&STYLE-->(.*)<!--\/&STYLE-->/smU", $tpl, $matches)) $this->tpl_style = $matches[1];
		// 拆MAIN部分
		if(preg_match("/<!--&MAIN-->(.*)<!--\/&MAIN-->/smU", $tpl, $matches)) $this->tpl_main = $matches[1];
		// 拆REPLY部分
		if(preg_match('/<!--&REPLY-->(.*)<!--\/&REPLY-->/smU', $tpl, $matches)) $this->tpl_reply = $matches[1];
		// 拆SEPARATE部分
		if(preg_match("/<!--&SEPARATE-->(.*)<!--\/&SEPARATE-->/smU", $tpl, $matches)) $this->tpl_sepa = $matches[1];
	}

	/*
	 * 傳回樣板樣式設計區塊原始碼
	 * @return string 樣板樣式設計區塊原始碼
	 */
	function ReplaceStrings_Style(){
		return trim($this->tpl_style);
	}

	/*
	 * 將樣版的標籤取代為正確的字串並傳回：MAIN
	 * @param array 各樣板標籤對應之變數值
	 * @return string 分析取代後之排版資料
	 */
	function ReplaceStrings_Main($ary_rpl){
		$tmp_main = $this->EvalIF($this->tpl_main, $ary_rpl); // 解析IF敘述
		return str_replace(array_keys($ary_rpl), array_values($ary_rpl), $tmp_main);
	}

	/*
	 * 將樣版的標籤取代為正確的字串並傳回：REPLY
	 * @param array 各樣板標籤對應之變數值
	 * @return string 分析取代後之排版資料
	 */
	function ReplaceStrings_Reply($ary_rpl){
		$tmp_reply = $this->EvalIF($this->tpl_reply, $ary_rpl); // 解析IF敘述
		return str_replace(array_keys($ary_rpl), array_values($ary_rpl), $tmp_reply);
	}

	/*
	 * 傳回分隔用樣板原始碼
	 * @return string 分隔討論串之原始碼
	 */
	function ReplaceStrings_Separate(){
		return $this->tpl_sepa;
	}

	/*
	 * 解析IF敘述
	 * @param string 樣板區塊字串
	 * @return string 解析IF敘述後之新樣板區塊字串
	 * @access private
	 */
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