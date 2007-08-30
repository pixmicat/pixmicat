<?php
/**
 * PIO Condition Object
 *
 * 判斷文章是否符合刪除條件並列出刪除編號
 * 
 * @package PMCLibrary
 * @version $Id$
 * @date $Date$
 */

/* 以總文章篇數作為刪除判斷 */
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

/* 以總討論串數作為刪除判斷 */
class ByThreadCountCondition{
	/*public static */function check($type, $limit){
		global $PIO;
		return $PIO->threadCount() >= ($type=='predict' ? $limit * 0.95 : 1);
	}

	/*public static */function listee($type, $limit){
		global $PIO;
		return $PIO->fetchThreadList(intval($limit * ($type=='predict' ? 0.95 : 1)), $limit);
	}
}
?>