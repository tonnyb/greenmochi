<?

import('episodemanager');
class TaskBacklog extends EpisodeManager {
	public function run() {
		parent::run();
		$config = Config::getInstance();
		$scanOnStart = true;
		$scanStart = date("U");
		$scanTime = ( $config->get('search', 'search_frequency_backlog') * 60 );
		$nextScan = $scanStart + $scanTime;

		while( $this->pid ) {
			if ( $scanOnStart || date("U") > $nextScan ) {
				if ( !AniDB::checkIntegrity() ) {
					$this->runManager('backlog');
					$nextScan = date("U") + $scanTime;
					$scanOnStart = false;
					$scanTime = ( $config->get('search', 'search_frequency_backlog') * 60 );
				}
			}
			$this->iterate(2);
		}

	}
}

?>
