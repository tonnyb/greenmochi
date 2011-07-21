<?

class ShowStats {
	public static function getActiveShows() {
		$db = DB::getInstance();
		return $db->fsqlr('SELECT count(showId) as num FROM shows WHERE enddate = ""');
	}
	public static function getShows() {
		$db = DB::getInstance();
		return $db->fsqlr('SELECT count(showId) as num FROM shows');
	}
	public static function getEpisodes() {
		$db = DB::getInstance();
		return $db->fsqlr('SELECT count(showId) as num FROM episodes WHERE file != ""');
	}
	public static function getTotalEpisodes() {
		$db = DB::getInstance();
		return $db->fsqlr('SELECT sum(episodes) as num FROM shows');
	}
}
