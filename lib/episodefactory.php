<?

import('episode');
import('factory');
class EpisodeFactory extends Factory {

	public static function getEpisodes($array = array()) {
		$queryAdd = self::parseQuery($array);
		if ( !isset($array['showid']) ) return array();
		else $showid = $array['showid'];

		$episodes = array();
		$db = DB::getInstance();
		$sql = $db->fsql('SELECT * FROM episodes WHERE showid = %d %s', $showid, implode("", $queryAdd));
		while ( $arr = $db->fetchArray($sql) ) {
			$episode = new Episode( $arr );
			$episode->setHaveChanges(0);
			$episodes[$arr['episode']] = $episode;
		}

		return $episodes;
	}

}
