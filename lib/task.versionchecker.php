<?

import('task');
import('showinfo');
import('showcontroller');
class TaskVersionChecker extends Task {
	public function run() {
		parent::run();
		$scanOnStart = true;
		$scanStart = date("U");
		$scanTime = ( 24 * 60 ) * 5;
		$nextScan = $scanStart + $scanTime;

		import('anidb');
		import('airdatescraper');

		while( $this->pid ) {

			if ( AniDB::checkIntegrity() ) {
				$this->logInfo('Updating AniDB');
				AniDB::updateDB();
			}
			if ( AirdateScraper::checkIntegrity() ) {
				$this->logInfo('Updating AnimeCalendar');
				AirdateScraper::updateDB();
			}

			// Updates Shows
			$shows = ShowFactory::getShows(array('download' => 1, 'enddate' => 0, '<changedate' => date('Y-m-d', strtotime('1 day ago'))));
			foreach ( $shows as $show ) {
				$this->logInfo('Updating %s', $show->getName());
				$showInfo = (Array) ShowInfo::getShowInfo( $show->getName() );
				$show->setShowInfo( $showInfo );

				$air = new AirdateScraper();
				$this->logInfo('Checking airdates for ' . $show->getVarName());
				$airdates = (array) $air->getByTitle( $show->getTitle(), $show->getVarName() );

				$show->getMissingEpisodes(array('airdates' => $airdates));
				ShowController::saveShow($show, true, true);
				sleep(2);
			}

			$nextScan = date("U") + $scanTime;
			$scanOnStart = false;
			$scanTime = ( 24 * 60 ) * 5;

			$this->iterate(2);
		}

	}
}

?>
