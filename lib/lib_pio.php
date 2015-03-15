<?php
/*
PIO - Pixmicat! data source I/O
*/

// 協助設定 status 旗標的類別
class FlagHelper{
	var $_status;

	function FlagHelper($status=''){
		$this->_write($status);
	}

	function _write($status=''){
		$this->_status = $status;
	}

	function toString(){
		return $this->_status;
	}

	function get($flag){
		$result = preg_match('/_('.$flag.'(\:(.*))*)_/U', $this->toString(), $match);
		return $result ? $match[1] : false;
	}

	function exists($flag){
		return $this->get($flag) !== false;
	}

	function value($flag){
		$wholeflag = $this->get($flag);
		if($scount = substr_count($wholeflag, ':')){
			$wholeflag = preg_replace('/^'.$flag.'\:/', '', $wholeflag);
			return ($scount > 1 ? explode(':', $wholeflag) : $wholeflag);
		}else return $wholeflag !== false;
	}

	function add($flag, $value=null){
		return $this->update($flag, $value);
	}

	function update($flag, $value=null){
		if($value===null){
			$ifexist = $this->get($flag);
			if($ifexist !== $flag) $this->_write($this->toString()."_${flag}_");
		}else{
			if(is_array($value)) $value = $this->join($value); // Array Flatten
			$ifexist = $this->get($flag);
			if($ifexist !== $flag.':'.$value){
				if($ifexist) $this->_write($this->replace($ifexist, "$flag:$value")); // 已立flag，不同值
				else $this->_write($this->toString()."_$flag:${value}_"); // 無flag
			}
		}
		return $this;
	}

	function replace($from, $to){
		return str_replace("_${from}_", "_${to}_", $this->toString());
	}

	function remove($flag){
		$wholeflag = $this->get($flag);
		$this->_write(str_replace("_${wholeflag}_", '', $this->toString()));
		return $this;
	}

	function toggle($flag){
		return ($this->get($flag) ? $this->remove($flag) : $this->add($flag));
	}

	function offsetValue($flag, $d=0){
		$v = intval($this->value($flag));
		return $this->update($flag, $v + $d);
	}

	function plus($flag){ return $this->offsetValue($flag, 1); }
	function minus($flag){ return $this->offsetValue($flag, -1); }

	function join(){
		$arg = func_get_args();
		$newval = array();
		foreach($arg as $a){
			if(is_array($a)) array_push($newval, implode(':', $a));
			else array_push($newval, $a);
		}
		return implode(':', $newval);
	}

	public function __toString() {
		return sprintf('%s {status = %s}', __CLASS__, $this->toString());
	}
}

// 文章自動刪除機制
include(ROOTPATH.'lib/lib_pio.cond.php');
class PIOSensor{
	public static function check($type, array $condobj){
		foreach($condobj as $i => $j){
			// 有其中一個需要處理
			if(call_user_func_array(array($i, 'check'), array($type, $j))===true) return true;
		}
		return false;
	}

	public static function listee($type, array $condobj){
		$tmparray = array(); // 項目陣列
		foreach($condobj as $i => $j){
			// 結果併進 $tmparray
			$tmparray = array_merge($tmparray, call_user_func_array(array($i, 'listee'), array($type, $j)));
		}
		sort($tmparray); // 由舊排到新 (小到大)
		return array_unique($tmparray);
	}

	public static function info(array $condobj){
		$sensorinfo='';
		foreach($condobj as $i => $j){
			$sensorinfo .= call_user_func_array(array($i, 'info'), array($j))."\n";
		}
		return $sensorinfo;
	}
}

// 分析連線字串
if(preg_match('/^(.*):\/\//i', CONNECTION_STRING, $backend)) define('PIXMICAT_BACKEND', $backend[1]);

// 引入必要函式庫
$PIOEnv = array( // PIO 環境常數
	'BOARD' => STORAGE_PATH,
	'LUTCACHE' => 'lutcache.dat',
	'NONAME' => DEFAULT_NONAME,
	'NOTITLE' => DEFAULT_NOTITLE,
	'NOCOMMENT' => DEFAULT_NOCOMMENT,
	'PERIOD.POST' => RENZOKU,
	'PERIOD.IMAGEPOST' => RENZOKU2
);

$pio_file = ROOTPATH.'lib/pio/pio.'.PIXMICAT_BACKEND.'.php';
if(is_file($pio_file)) include($pio_file);
?>