<?

import('episodecontroller');
import('episodefactory');
class EpisodeController {

	public static function setWant($showid, $episode, $want = 1) {
		// 0: don't want
		// 1: want
		// 2: snatched
		$db = DB::getInstance();
		$db->fsql('UPDATE episodes SET want = %d WHERE showid = %d AND episode = %d', $want, $showid, $episode);
	}

	public static function saveEpisode($showid, $episode, $force = false) {
		$db = DB::getInstance();
		$exists = $db->fsqlr('SELECT count(id) FROM episodes WHERE showid = %d and episode = %d', $showid, $episode->getEpisode());

		if ( $exists ) {
			if ( $episode->haveChanges() || $force ) {
				$db->fsql('UPDATE episodes SET ' .
				'file = "%s",' .
				'path = "%s",' .
				'quality = "%s",' .
				'airdate = "%s"' .
				' WHERE episode = %d AND showid = %d'
				, $episode->getFile(), $episode->getPath(), $episode->getQuality(), $episode->getAirDate(), $episode->getEpisode(), $showid);
			}
		}
		else {
			$db->fsql( 'INSERT IGNORE INTO episodes (showid, name, episode, site, file, path, quality, want, airdate) VALUES (%d, "%s", %d, "%s", "%s", "%s", "%s", %d, "%s")', $showid, $episode->getName(), $episode->getEpisode(), $episode->getSite(), $episode->getFile(), $episode->getPath(), $episode->getQuality(), 0, $episode->getAirdate());
		}
	}

	public static function newEpisode($showid, $episode) {
		$episode = new Episode($episode);
		self::saveEpisode($showid, $episode);
		return $episode;
	}

}
