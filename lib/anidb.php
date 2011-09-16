<?php

class AniDB {
	private static $check = true;
	public function __construct() { }

	public function checkIntegrity( $update = 0 ) {
		if ( self::$check ) {
			$db = DB::getInstance();

			$res = $db->fsqlr('SELECT max(distinct cdate) AS cdate FROM anidb');
			$maxAge = strtotime("2 weeks ago");

			if ( !$res || $res < $maxAge ) {
				return true;
			}
		}
		return false;
	}

	public function updateDB() {
		self::$check = false;
		$db = DB::getInstance();
		$content = Curl::sendRequest('http://anidb.net/api/animetitles.dat.gz', 'GET', array('gzip' => true));
		$array = explode("\n", $content);
		$i=0;
		$maxItems = 100;
		$count = count($array);
		$db->sql('START TRANSACTION');
		foreach ( $array as $item ) {
			if ( !preg_match("/^#/", $item) ) {
				$arr = explode("|", $item);
				if ( isset($arr[1]) ) {
					$db->fsql('INSERT IGNORE INTO anidb (aid,type,language,title,cdate) VALUES (%d, %d, "%s", "%s", %d)', $arr[0], $arr[1], $arr[2], $arr[3], date("U"));

					if ( $i == $maxItems || $i == $count ) {
						$db->sql('COMMIT');
						if ( $i < $count ) $db->sql('START TRANSACTION');
						$i=0;
					}
					else {
						$i++;
					}
				}
			}
		}

		self::$check = true;
	}

	public function matchShow($show) {
		$db = DB::getInstance();

		/// Try: Exact match
		$res = $this->getDbShow($show);
		if ( $result = $this->matchLikes($show, $res) ) {
			return $result;
		}

		/// Try: Exact match
		$res = $this->getDbShow('The ' . $show);
		if ( $result = $this->matchLikes('The ' . $show, $res) ) {
			return $result;
		}

		/// Try: alike
		$res = $this->getDbShow($show . '%');
		if ( $result = $this->matchLikes($show, $res) ) {
			return $result;
		}

		/// Try: %alike
		$res = $this->getDbShow('%' . $show . '%');
		if ( $db->numRows($res) == 1 ) {
			//System_Daemon::info('! show found [ ' . $this->getShowTitle() . ' ] ');
			return $db->fetchArray($res);
		}

		/// Try: replace ' by `
		if ( preg_match("/'/", $show) ) {
			$replace = str_replace("'", "`", $show);
			$res = $this->getDbShow($replace);
			if ( $db->numRows($res) == 1 ) {
				return $db->fetchArray($res);
			}
		}

		/// Transform: Show - Bla to Show: Bla
		if ( preg_match("/-/", $show) ) {
			$replace = str_replace(" - ", ": ", $show);
			$res = $this->getDbShow($replace);
			if ( $result = $this->matchLikes($replace, $res) ) {
				return $result;
			}

			preg_match("/(.*)(?:[ ]+?)-(?:[ ]+?)(.*)/", $show, $matches);
			if ( !sizeof($matches) ) return false;
			$res = $this->getDbShow($matches[1]);
			if ( $db->numRows($res) != 1 ) {

				$arr = explode(" ", $matches[2]);
				$string = $matches[1] . "%";
				foreach( $arr as $key ) {
					$string .= "%" . $key;
					$res = $this->getDbShow($string);
					if ( $db->numRows($res) == 1 ) {
						//System_Daemon::info('!! show found [ ' . $this->getShowTitle() . ' ] ');
						return $db->fetchArray($res);;
					}
					unset($res);
				}
				
			}
			else return $db->fetchArray($res);

		}

		return false;
	}

	public function getDbShow($show) {
		$db = DB::getInstance();
		$res = $db->fsql('SELECT aid,title FROM anidb WHERE title like "%s" and language in ("en","x-jat","x-unk") group by aid', $show);
		//print_r( $db->getQuery() . "\n");
		return $res;
	}

	public function getMatch($show) {
		$res = $this->getDbShow($show . '%');
	}

	public function matchLikes($title, $res) {
		$db = DB::getInstance();
		while( $arr = $db->fetchArray($res) ) { 
			if ( strtolower($arr['title']) == strtolower($title) ) return $arr;
		}
		return false;
	}

}
