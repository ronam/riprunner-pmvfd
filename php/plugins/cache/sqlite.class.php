<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
/* Portions of code used from:
 * 
*  khoaofgod@gmail.com
*  Website: http://www.phpfastcache.com
*  Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
*/
// ==============================================================
namespace riprunner;

if ( !defined('INCLUSION_PERMITTED') ||
( defined('INCLUSION_PERMITTED') && INCLUSION_PERMITTED !== true ) ) {
	die( 'This file must not be invoked directly.' );
}

require_once __RIPRUNNER_ROOT__ . '/plugins/cache/plugin_interfaces.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

class SqliteCachePlugin implements ICachePlugin {

	private $indexing = NULL;
	private $path = null;
	private $max_size = 50; // 50 mb
	private $instant = array();
	private $currentDB = 1;
	
	/*
	 Constructor
	 */
	function __construct() {
		global $log;
		try {
			if($this->isInstalled()) {

				if(!file_exists($this->getCachePath()."/sqlite")) {
					if(!@mkdir($this->getCachePath()."/sqlite",0777)) {
						throw new \Exception("Sqlite cache cannot create temp folder: " . $this->getCachePath()."/sqlite");
					}
				}
				$this->path = $this->getCachePath() . "/sqlite";
								
				$log->trace("Cache plugin init SUCCESS using sqlite on this host!");
			}
			else {
				$log->trace("Cache plugin init FAILED cannot use sqlite on this host!");
			}
		}
		catch(Exception $ex) {
			$log->error("Cache proxy init error [" . $ex->getMessage() . "]");
		}
	}
	
	private function getCachePath() {
		return __RIPRUNNER_ROOT__ . "/temp/cache/";
	}
	
	public function getPluginType() {
		return 'SQLITECACHE';
	}
	
	public function isInstalled() {
		return (extension_loaded('pdo_sqlite'));
	}
	
	public function getItem($keyword) {
		// return null if no caching
		// return value if in caching
		try {
			$stm = $this->db($keyword)->prepare("SELECT * FROM `caching` WHERE `keyword`=:keyword LIMIT 1");
			$stm->execute(array(
					":keyword"  =>  $keyword
			));
			$row = $stm->fetch(\PDO::FETCH_ASSOC);
			
			//echo "SQLITE get #1 " .PHP_EOL;
			//print_r($row);
		} 
		catch(\PDOException $e) {
			$stm = $this->db($keyword,true)->prepare("SELECT * FROM `caching` WHERE `keyword`=:keyword LIMIT 1");
			$stm->execute(array(
					":keyword"  =>  $keyword
			));
			$row = $stm->fetch(\PDO::FETCH_ASSOC);
			
			//echo "SQLITE get #1 " .PHP_EOL;
			//print_r($row);
		}
		if($this->isExpired($row)) {
			//echo "SQLITE get #3 " .PHP_EOL;
			$this->deleteRow($row);
			return null;
		}
		if(isset($row['id'])) {
			//echo "SQLITE get #4 " .PHP_EOL;
			$data = $this->decode($row['object']);
			return $data;
		}
		//echo "SQLITE get #5 " .PHP_EOL;
		return null;
	}

	public function setItem($keyword, $value, $cache_seconds=null) {

		if(isset($cache_seconds) == FALSE) {
			$cache_seconds = 60 * 10;
		}
		
		try {
			echo "SQLITE set #1 " .PHP_EOL;
			$stm = $this->db($keyword)->prepare("INSERT OR REPLACE INTO `caching` (`keyword`,`object`,`exp`) values(:keyword,:object,:exp)");
			$stm->execute(array(
					":keyword"  => $keyword,
					":object"   =>  $this->encode($value),
					":exp"      => @date("U") + (Int)$cache_seconds,
			));
			
			echo "SQLITE set #2 " .PHP_EOL;
		} 
		catch(\PDOException $e) {
			echo "SQLITE set #3 " .PHP_EOL;
			
			$stm = $this->db($keyword,true)->prepare("INSERT OR REPLACE INTO `caching` (`keyword`,`object`,`exp`) values(:keyword,:object,:exp)");
			$stm->execute(array(
					":keyword"  => $keyword,
					":object"   =>  $this->encode($value),
					":exp"      => @date("U") + (Int)$cache_seconds,
			));
		}
	}

	public function deleteItem($keyword) {
		$stm = $this->db($keyword)->prepare("DELETE FROM `caching` WHERE (`keyword`=:keyword) OR (`exp` <= :U)");
		$stm->execute(array(
				":keyword"   => $keyword,
				":U"    =>  @date("U"),
		));		
	}
	
	
	private function db($keyword, $reset = false) {
		/*
		 * Default is fastcache
		 */
		$instant = $this->indexing($keyword);
		/*
		 * init instant
		*/
		if(!isset($this->instant[$instant])) {
			// check DB Files ready or not
			$createTable = false;
			if(!file_exists($this->path."/db".$instant) || $reset == true) {
				$createTable = true;
			}
			$PDO = new \PDO("sqlite:".$this->path."/db".$instant);
			$PDO->setAttribute(\PDO::ATTR_ERRMODE,
					\PDO::ERRMODE_EXCEPTION);
			if($createTable == true) {
				$this->initDB($PDO);
			}
			$this->instant[$instant] = $PDO;
			unset($PDO);
		}
		return $this->instant[$instant];
	}	
	
	/*
	 * INIT Instant DB
	 * Return Database of Keyword
	 */
	private function indexing($keyword) {
		if($this->indexing == NULL) {
			$createTable = false;
			if(!file_exists($this->path."/indexing")) {
				$createTable = true;
			}
			$PDO = new \PDO("sqlite:".$this->path."/indexing");
			$PDO->setAttribute(\PDO::ATTR_ERRMODE,
					\PDO::ERRMODE_EXCEPTION);
			if($createTable == true) {
				$this->initIndexing($PDO);
			}
			$this->indexing = $PDO;
			unset($PDO);
			$stm = $this->indexing->prepare("SELECT MAX(`db`) as `db` FROM `balancing`");
			$stm->execute();
			$row = $stm->fetch(\PDO::FETCH_ASSOC);
			if(!isset($row['db'])) {
				$db = 1;
			} elseif($row['db'] <=1 ) {
				$db = 1;
			} else {
				$db = $row['db'];
			}
			// check file size
			$size = file_exists($this->path."/db".$db) ? filesize($this->path."/db".$db) : 1;
			$size = round($size / 1024 / 1024,1);
			if($size > $this->max_size) {
				$db = $db + 1;
			}
			$this->currentDB = $db;
		}	
	}
	
	/*
	 * INIT NEW DB
	 */
	private function initDB(\PDO $db) {
		$db->exec('drop table if exists "caching"');
		$db->exec('CREATE TABLE "caching" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "keyword" VARCHAR UNIQUE, "object" BLOB, "exp" INTEGER)');
		$db->exec('CREATE UNIQUE INDEX "cleaup" ON "caching" ("keyword","exp")');
		$db->exec('CREATE INDEX "exp" ON "caching" ("exp")');
		$db->exec('CREATE UNIQUE INDEX "keyword" ON "caching" ("keyword")');
	}
	/*
	 * INIT Indexing DB
	 */
	private function initIndexing(\PDO $db) {
		// delete everything before reset indexing
		$dir = opendir($this->path);
		while($file = readdir($dir)) {
			if($file != "." && $file!=".." && $file != "indexing" && $file!="dbfastcache") {
				@unlink($this->path."/".$file);
			}
		}
		$db->exec('drop table if exists "balancing"');
		$db->exec('CREATE TABLE "balancing" ("keyword" VARCHAR PRIMARY KEY NOT NULL UNIQUE, "db" INTEGER)');
		$db->exec('CREATE INDEX "db" ON "balancing" ("db")');
		$db->exec('CREATE UNIQUE INDEX "lookup" ON "balacing" ("keyword")');
	}	
	
	private function isExpired($row) {
		if(isset($row['exp']) && @date("U") >= $row['exp']) {
			return true;
		}
		return false;
	}
	private function deleteRow($row) {
		$stm = $this->db($row['keyword'])->prepare("DELETE FROM `caching` WHERE (`id`=:id) OR (`exp` <= :U) ");
		$stm->execute(array(
				":id"   => $row['id'],
				":U"    =>  @date("U"),
		));
	}	
	
	/*
	* Object for Files & SQLite
	*/
	private function encode($data) {
		return serialize($data);
	}
	private function decode($value) {
		$x = @unserialize($value);
		if($x == false) {
			return $value;
		} 
		else {
			return $x;
		}
	}	
}