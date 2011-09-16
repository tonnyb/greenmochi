<?

import('episodemanager');
class TaskEpisodeManager extends EpisodeManager {

	public function run() {
		parent::run();
		sleep(1);
		$config = Config::getInstance();
		$scanOnStart = true;
		$scanStart = date("U");
		$scanTime = ( $config->get('search', 'search_frequency') * 60 );
		$nextScan = $scanStart + $scanTime;

		while( $this->pid ) {
			if ( $scanOnStart || date("U") > $nextScan ) {
				$this->runManager();
				$nextScan = date("U") + $scanTime;
				$scanOnStart = false;
				$scanTime = ( $config->get('search', 'search_frequency') * 60 );
			}
			$this->iterate(2);
		}

	}

}

