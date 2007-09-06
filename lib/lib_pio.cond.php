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

/* 以討論串生存時間作為刪除判斷 */
class ByThreadAliveTimeCondition{
	/*public static */function check($type, $limit){
		global $PIO;
		$oldestThreadNo = $PIO->fetchThreadList($PIO->threadCount() - 1, 1, true); // 最舊討論串編號
		$oldestThread = $PIO->fetchPosts($oldestThreadNo);
		return (time() - substr($oldestThread[0]['tim'], 0, 10) >= 86400 * $limit * ($type=='predict' ? 0.95 : 1));
	}

	/*public static */function listee($type, $limit){
		global $PIO;
		$ThreadNo = $PIO->fetchThreadList(0, 0, true); sort($ThreadNo); // 討論串編號陣列 (由舊到新)
		$NowTime = time();
		$i = 0;
		foreach($ThreadNo as $t){
			$post = $PIO->fetchPosts($t);
			if($NowTime - substr($post[0]['tim'], 0, 10) < 86400 * $limit * ($type=='predict' ? 0.95 : 1)) break; // 時間不符合
			$i++;
		}
		if(count($ThreadNo)===$i){ $i--; } // 保留最新的一篇避免全部刪除
		return array_slice($ThreadNo, 0, $i);
	}
}
?>