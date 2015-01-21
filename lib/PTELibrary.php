<?php
namespace Pixmicat;

/**
 * Pixmicat! Template-Embedded Library
 * 
 * @author Roy Tam
 * @author Scribe Huang
 * @deprecated 應該改用第三方樣板庫
 */
class PTELibrary
{
    /** @var array 樣板快取 */
    private $tpl_block;
    /** @var string 樣板內容 */
    private $tpl;

    /**
     * 開啟樣板檔案並取出區塊 
     * 
     * @param string $tplname 樣板路徑
     */
    public function __construct($tplname)
    {
        $this->tpl_block = array();
        $this->tpl = \file_get_contents($tplname);
    }

    /**
     * 回傳去除前後空格的區塊樣板碼
     */
    public function BlockValue($blockName)
    {
        return \trim($this->readBlock($blockName));
    }
    
    /**
     * 回傳區塊樣板碼並快取
     */
    private function readBlock($blockName)
    {
        // 是否找過
        if (!isset($this->tpl_block[$blockName])) {
            if (\preg_match('/<!--&'.$blockName.'-->(.*)<!--\/&'.$blockName.'-->/smU', $this->tpl, $matches)) {
                // 找到了存入陣列快取
                $this->tpl_block[$blockName] = $matches[1];
            } 
            else {
                // 找過但沒找到
                $this->tpl_block[$blockName] = false;
            } 
        }
        return $this->tpl_block[$blockName];
    }

    /**
     * 將樣版的標籤取代為正確的字串並傳回
     */
    public function ParseBlock($blockName, $ary_val)
    {
        if (($tmp_block = $this->readBlock($blockName))===false) {
            return "";
        } // 找無
        foreach ($ary_val as $akey=>$aval) {
            $ary_val[$akey] = \str_replace('{$', '{'.\chr(1).'$', $ary_val[$akey]);
        }
        $tmp_block = $this->evalForEach($tmp_block, $ary_val); // 解析FOREACH敘述
        $tmp_block = $this->evalIf($tmp_block, $ary_val); // 解析IF敘述
        $tmp_block = $this->evalInclude($tmp_block, $ary_val); // 解析引用
        return \str_replace(
            '{'.\chr(1).'$',
            '{$',
            \str_replace(\array_keys($ary_val), \array_values($ary_val), $tmp_block)
        );
    }

    /**
     * 解析IF敘述
     */
    private function evalIf($tpl, $ary)
    {
        $tmp_tpl = $tpl;
        if (\preg_match_all('/<!--&IF\(([\$&].*),\'(.*)\',\'(.*)\'\)-->/smU', $tmp_tpl, $matches, \PREG_SET_ORDER)) {
            foreach ($matches as $submatches) {
                $isblock = \substr($submatches[1], 0, 1) == "&";
                $vari = \substr($submatches[1], 1);
                $iftrue = $submatches[2];
                $iffalse = $submatches[3];
                $key = '{$'.$vari.'}';
                $tmp_tpl = \str_replace(
                    $submatches[0],
                    (
                        ($isblock
                            ? $this->BlockValue($vari)
                            : (array_key_exists($key, $ary) && $ary[$key] == true)
                        )
                            ? $this->evalInclude($iftrue, $ary)
                            : $this->evalInclude($iffalse, $ary)
                    ),
                    $tmp_tpl
                );
            }
        }
        return $tmp_tpl;
    }

    /**
     * 解析FOREACH敘述
     */
    private function evalForEach($tpl, $ary)
    {
        $tmp_tpl = $tpl;
        if (\preg_match_all('/<!--&FOREACH\((\$.*),\'(.*)\'\)-->/smU', $tmp_tpl, $matches, \PREG_SET_ORDER)) {
            foreach ($matches as $submatches) {
                $vari = $submatches[1];
                $block = $submatches[2];
                
                $foreach_tmp = '';
                $key = '{'.$vari.'}';
                if (isset($ary[$key]) && is_array($ary[$key])) {
                    foreach ($ary[$key] as $eachvar) {
                        $foreach_tmp .= $this->ParseBlock($block, $eachvar);
                    }
                }
                $tmp_tpl = \str_replace($submatches[0], $foreach_tmp, $tmp_tpl);
            }
        }
        return $tmp_tpl;
    }

    /**
     * 解析區塊引用
     */
    private function evalInclude($tpl, $ary)
    {
        $tmp_tpl = $tpl;
        if (\preg_match_all('/<!--&(.*)\/-->/smU', $tmp_tpl, $matches, \PREG_SET_ORDER)) {
            foreach ($matches as $submatches) {
                $tmp_tpl = \str_replace($submatches[0], $this->ParseBlock($submatches[1], $ary), $tmp_tpl);
            }
        }
        return $tmp_tpl;
    }
}
