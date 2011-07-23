<?

import('db');
import('curl');
class AirdateScraper {
	private static $check = true;

	public function __construct() { }

	public function checkIntegrity() {
		if ( self::$check ) {
			$db = DB::getInstance();
			$result = $db->fsqlr('SELECT max(distinct cdate) AS cdate FROM acdb');
			$maxAge = strtotime("2 weeks ago");
			if ( !$result || $result < $maxAge ) {
				return true;
			}
		}
		return false;
	}

	public function updateDB() {
		self::$check = false;
		$content = Curl::sendRequest("http://animecalendar.net/shows/list/all");
		//$content = file_get_contents( BASE_PATH . "temp");

		$db = DB::getInstance(1);
		$db->sql('START TRANSACTION');
		preg_match_all("/<a href=\"\/show\/([0-9]+)\/(.*)\">(.*)<\/a>/", $content, $matches);
		$maxItems = 500;
		$i=0;
		$items = array();
		foreach ( $matches[1] as $key => $id ) {
			$titles = $matches[3];
			$names = $matches[2];
			
			$name = $names[$key];
			$title = $titles[$key];

			if ( $i == $maxItems ) {
				$db->fsql('INSERT IGNORE INTO acdb (id,title,cdate) VALUES %s', implode(',', $items));
				$i=0;
				$items = array();
			}

			$items[] = sprintf('(%d, "%s", %d)', $id, $title, date("U"));
			$i++;
		}

		if ( count($items) != 0 ) {
			$db->fsql('INSERT IGNORE INTO acdb (id,title,cdate) VALUES %s', implode(',', $items));
			$i=0;
			$items = array();
		}
		$db->sql('COMMIT');
		self::$check = true;
	}

	private function getReplaces( $value ) {
		$db = DB::getInstance();
		if ( $result = $db->fsqlr('SELECT id FROM acdb WHERE title like "%s"', $value . '%') ) return $result;
		if ( preg_match("/II/", $value) ) {
			if ( $result = $db->fsqlr('SELECT id FROM acdb WHERE title like "%s"', str_replace("II", "2", $value) . '%') ) return $result;
		}
		if ( preg_match("/2/", $value) ) {
			if ( $result = $db->fsqlr('SELECT id FROM acdb WHERE title like "%s"', str_replace("2", "II", $value) . '%') ) return $result;
		}
		return false;
	}

	public function getIdByTitle( $title, $varname ) {
		if ( !$title || $title == "%" ) return false;

		if ( $result = $this->getReplaces($title) ) return $result;

		$replace = str_replace(" ", "%", $title);
		if ( $result = $this->getReplaces($replace) ) return $result;

		$replace = str_replace("2nd Season", "2", $title);
		if ( $result = $this->getReplaces($replace) ) return $result;

		$replace = preg_replace("/[aeiou]/s", "%", $title);
		if ( $result = $this->getReplaces($replace) ) return $result;

		$replace = preg_replace("/[^a-zA-Z0-9\-\_\!\s]/", "%", $title);
		if ( $result = $this->getReplaces($replace) ) return $result;


		if ( !$varname && $title == $varname ) return false;

		//Varname
		if ( $result = $this->getReplaces($varname) ) return $result;

		$replace = str_replace(" ", "%", $varname);
		if ( $result = $this->getReplaces($replace) ) return $result;

		$replace = str_replace("2nd Season", "2", $varname);
		if ( $result = $this->getReplaces($replace) ) return $result;

		$replace = preg_replace("/[aeiou]/s", "%", $varname);
		if ( $result = $this->getReplaces($replace) ) return $result;

		$replace = preg_replace("/[^a-zA-Z0-9\-\_\!\s]/", "%", $varname);
		if ( $result = $this->getReplaces($replace) ) return $result;

		return false;
	}

	public function getByTitle( $title , $varname = null ) {
		$id = $this->getIdByTitle( $title, $varname );
		if ( $id ) {
			return $this->getAirDates($id);
		}
	}

	public function getAirDates( $id ) {
		$url = sprintf("http://animecalendar.net/ajax/episodes/%d/0/0", $id);
		import('cache');

		$cache = new cache("acdb".$id);
		if ( !$cache->has() ) {
			$cache->lock();
			$content = Curl::sendRequest($url);
			$cache->set($content);
		}
		else $content = $cache->get();

		preg_match_all("/Ep: (.*)/", $content, $matches);
		$episodes = array();
		foreach ( $matches[1] as $mcontent ) {
			preg_match_all("/<strong>((?:(?!<\/strong>).)*)/", $mcontent, $match);
			$result = $match[1];
			$episode = $result[0];
			$time = $result[1];
			$network = $result[2];
			$date = $result[3];
			$episodes[$episode]["episode"] = $episode;
			$episodes[$episode]["time"] = $time;
			$episodes[$episode]["network"] = $network;
			$episodes[$episode]["date"] = $date;
			$episodes[$episode]["utime"] = strtotime($date . ' ' . $time);
		}

		return $episodes;
	}

}
