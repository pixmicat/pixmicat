<?php
/**
 * PIO Condition Object
 *
 * ”»Ð•¶Í¥”Û•„‡™ˆœžŠŒ•À—ño™ˆœ•Òåj
 * 
 * @package PMCLibrary
 * @version $Id$
 * @date $Date$
 */

/* ˆÈã`•¶Í•ÑÉìˆ×™ˆœ”»Ð */
class ByPostCountCondition{
	/*public static */function check($type, $limit){
		global $PIO;
		return $PIO->postCount() >= $limit * ($type=='predict' ? 0.95 : 1);
	}

	/*public static */function listee($type, $limit){
		global $PIO;
		return $PIO->fetchPostList(0, intval($limit * ($type=='predict' ? 0.95 : 1)) - 1, $limit);
	}
}
?>