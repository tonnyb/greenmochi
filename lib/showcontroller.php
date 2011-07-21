<?

import('showfactory');
import('episodecontroller');
import('db');
class ShowController {

	public static function setRescan($showid, $scan = 1) {
		$db = DB::getInstance();
		$db->fsql('UPDATE shows SET scan = %d WHERE showid = %d', $scan, $showid);
	}

	public static function saveShow($show, $episodes = true, $force = false) {

		if ( $show->getVarName() == "" ) return false;

		$db = DB::getInstance();

		$exists = $db->fsqlr('SELECT count(showId) as num FROM shows WHERE name = "%s"', $show->getVarName());
		$db->sql('START TRANSACTION');
		if ( $exists ) {
			# UPDATE
			$db->fsql('UPDATE shows SET ' .
			'aid = %d,'.
			'acid = %d,'.
			'episodes = %d,'.
			'enddate = "%s",'.
			'changedate = "%s",'.
			'download = %d'.
			' WHERE showId = %d',
			$show->getAid(),
			$show->getAcid(),
			$show->getEpisodeCount(),
			$show->getEndDate(),
			date('Y-m-d'),
			$show->getDownload(),
			$show->getShowId());
		}
		else {
			$db->fsql('INSERT IGNORE INTO shows ' .
			'(title, name, episodes, path, site, network, startdate, enddate, download, scan, aid, acid) ' .
			' VALUES ' . 
			'("%s", "%s", %d, "%s", "%s", "%s", "%s", "%s", %d, 0, %d, %d) ' , 
			$show->getTitle(), $show->getVarName(), $show->getEpisodeCount(), $show->getFullPath(), $show->getSite(), $show->getNetwork(), $show->getStartDate(), $show->getEndDate(), $show->getDownload(), $show->getAid(), $show->getAcid()
			);

			$show->setShowId( $db->insertId() );
		}

		foreach ( $show->getSites() as $site => $x ) {
			if ( $site ) $db->fsql('REPLACE INTO sites (site) VALUES ("%s")', strtolower($site));
		}

		if ( $episodes ) {
			foreach ( $show->getEpisodes() as $episode ) {
				if ( $episode->haveChanges() || $force ) EpisodeController::saveEpisode($show->getShowId(), $episode, $force);
			}
		}
		$db->sql('COMMIT');

	}

	public static function setActive($showid, $download = 1) {
		$db = DB::getInstance();
		$db->fsql('UPDATE shows SET download = %d WHERE showid = %d', $download, $showid);
	}

	public static function removeShow($showid) {
		$db = DB::getInstance();
		$db->fsql('DELETE FROM shows WHERE showid = %d', $showid);
		$db->fsql('DELETE FROM episodes WHERE showid = %d', $showid);
	}

}
