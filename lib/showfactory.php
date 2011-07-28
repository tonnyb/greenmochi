<?

import('db');
import('show');
import('episodefactory');
import('factory');
class ShowFactory extends Factory {

	public static function getByShow($name, $episodes = 1) {
		$db = DB::getInstance();

		$result = $db->fsqlr('SELECT showid FROM shows WHERE name like "%s" or title like "%s"', $name, $name);
		if ( $result ) {
			$show = self::getById($result, $episodes);
		}
		else {
			$show = new Show();
			$show->setVarname($name);
		}
		return $show;
	}

	public static function getbyId($id, $episodes = 1) {
		$db = DB::getInstance();
		$result = $db->fsqlr("SELECT count(showid) FROM shows WHERE showid = %d", $id);
		if ( $result ) {

			$result = $db->sqlra("SELECT *,name as varname FROM shows WHERE showid = " . $id);
			$show = new Show();
			$show->setShowInfo( $result );

			if ( $episodes ) {
				$episodes = EpisodeFactory::getEpisodes(array('showid' => $id));
				foreach ( $episodes as $episode ) {
					$show->addEpisode( $episode );
				}
			}

		}
		else $show = new Show();
		return $show;
	}

	public static function getShows($array = array()) {
		$db = DB::getInstance();
		$queryAdd = self::parseQuery($array);
		$queryHead = self::parseQuery($array, 'head');
		$shows = array();
		$qstring = implode($queryAdd);
		$qhstring = implode($queryHead);
		$sql = $db->fsql("SELECT shows.showid FROM shows %s where shows.episodes != 0 %s", $qhstring, $qstring);
		while( $result = $db->fetchArray($sql) ) {
			$showid = $result['showid'];
			$shows[$showid] = self::getById($showid);
		}

		return($shows);
	}

	public function getShowByEpisode($array = array()) {
		$queryAdd = self::parseQuery($array);
		$shows = array();
		$qstring = implode($queryAdd);
		$sql = $db->fsql("SELECT showid FROM shows JOIN episodes ON shows.showid = episodes.showid WHERE shows.episodes != 0 %s", $qstring);
		while( $result = $db->fetchArray($sql) ) {
			$showid = $result['showid'];
			$shows[$showid] = self::getById($showid);

		}
		return $shows;
	}

}
