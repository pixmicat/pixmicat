<?php
/**
 * PIO Condition Object
 *
 * 判斷文章是否符合刪除條件並列出刪除編號
 *
 * @package PMCLibrary
 * @version $Id$
 */

/* 以總文章篇數作為刪除判斷 */
class ByPostCountCondition implements IPIOCondition {
	public static function check($type, $limit){
		$PIO = PMCLibrary::getPIOInstance();
		return $PIO->postCount() >= $limit * ($type=='predict' ? 0.95 : 1);
	}

	public static function listee($type, $limit){
		$PIO = PMCLibrary::getPIOInstance();
		return $PIO->fetchPostList(0,
			intval($limit * ($type=='predict' ? 0.95 : 1)) - 1, $limit);
	}

	public static function info($limit){
		$PIO = PMCLibrary::getPIOInstance();
		return __CLASS__.': '.($pcnt=$PIO->postCount()).'/'.$limit.
			sprintf(' (%.2f%%)',($pcnt/$limit*100));
	}
}

/* 以總討論串數作為刪除判斷 */
class ByThreadCountCondition implements IPIOCondition {
	public static function check($type, $limit){
		$PIO = PMCLibrary::getPIOInstance();
		return $PIO->threadCount() >= ($type=='predict' ? $limit * 0.95 : 1);
	}

	public static function listee($type, $limit){
		$PIO = PMCLibrary::getPIOInstance();
		return $PIO->fetchThreadList(
			intval($limit * ($type=='predict' ? 0.95 : 1)), $limit);
	}

	public static function info($limit){
		$PIO = PMCLibrary::getPIOInstance();
		return __CLASS__.': '.($tcnt=$PIO->threadCount()).'/'.$limit.
			sprintf(' (%.2f%%)',($tcnt/$limit*100));
	}
}

/* 以討論串生存時間作為刪除判斷 */
class ByThreadAliveTimeCondition implements IPIOCondition {
	public static function check($type, $limit){
		$PIO = PMCLibrary::getPIOInstance();
		// 最舊討論串編號
		$oldestThreadNo = $PIO->fetchThreadList($PIO->threadCount() - 1, 1, true);
		$oldestThread = $PIO->fetchPosts($oldestThreadNo);
		return (time() - substr($oldestThread[0]['tim'], 0, 10) >= 86400 *
			$limit * ($type=='predict' ? 0.95 : 1));
	}

	public static function listee($type, $limit){
		$PIO = PMCLibrary::getPIOInstance();
		// 討論串編號陣列 (由舊到新)
		$ThreadNo = $PIO->fetchThreadList(0, 0, true); sort($ThreadNo);
		$NowTime = time();
		$i = 0;
		foreach($ThreadNo as $t){
			$post = $PIO->fetchPosts($t);
			if($NowTime - substr($post[0]['tim'], 0, 10) < 86400 * $limit *
				($type=='predict' ? 0.95 : 1)) break; // 時間不符合
			$i++;
		}
		if(count($ThreadNo)===$i){ $i--; } // 保留最新的一篇避免全部刪除
		return array_slice($ThreadNo, 0, $i);
	}

	public static function info($limit){
		return __CLASS__.": $limit day(s)";
	}
}