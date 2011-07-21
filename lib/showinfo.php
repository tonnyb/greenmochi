<?

class ShowInfo {
	public function getShowInfo($show) {
		import('scraper');
		$scraper = ShowScraper::getInstance('AniDB');
		$scraper->setScraper('anidb');
		$showInfo = (Array) $scraper->scrape( $show );
		return $showInfo;
	}
}

?>
