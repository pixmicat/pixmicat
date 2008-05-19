<?php
/**
 * Pixmicat! PIO 公用程式 - PIO V2 -> PIO V3 資料格式轉換器
 *
 * 本程式可以將舊版的 PIO V2 結構轉成新的 PIO V3 結構，
 * 主要不同點在於 status 欄位擴充至 VARCHAR(255) 及討論串停止代表旗標參數改變 (T -> _TS_)。
 *
 * @package PMCUtility
 * @version $Id$
 * @date $Date$
 */

include_once('./config.php');
include_once('./lib/lib_pio.php');

$PIO->dbConnect(CONNECTION_STRING);
$PIO->dbPrepare();

switch(PIXMICAT_BACKEND){
	case 'mysql': // MySQL
		/* 修正 status VARCHAR(255), T -> _TS_ */
		$PIO->_mysql_call('ALTER TABLE '.$PIO->tablename.' CHANGE status status VARCHAR(255) NOT NULL');
		$PIO->_mysql_call('UPDATE '.$PIO->tablename.' SET status = "_TS_" WHERE status = "T"');
		break;
	case 'pgsql': // PostgresSQL
		/* 修正 status VARCHAR(255), T -> _TS_ */
		$PIO->_pgsql_call('ALTER TABLE '.$PIO->tablename.' ALTER COLUMN status TYPE VARCHAR(255); UPDATE '.$PIO->tablename.' SET status = "_TS_" WHERE status = "T"');
		break;
	case 'sqlite':
		/* 修正 T -> _TS_ (SQLite VARCHAR 無硬性限制) */
		$PIO->_sqlite_call('UPDATE '.$PIO->tablename.' SET status = "_TS_" WHERE status = "T"');
		break;
	case 'sqlite3':
	case 'log':
	case 'logflockp':
		/* 修正 _THREADSTOP_ -> _TS_ */
		$plist = $PIO->fetchThreadList(0, $PIO->threadCount());
		$post = $PIO->fetchPosts($plist); // 取出資料
		$post_count = count($post);

		for($i = 0; $i < $post_count; $i++){
			$PIO->setPostStatus($post[$i]['no'], str_replace('_THREADSTOP_', '_TS_', $post[$i]['status']));
		}
		break;
	default:
		echo('What backend did you use? Sorry we can\'t fix it now.<br />');
}
$PIO->dbCommit();
echo('PIO V3 Update OK.');
?>