<?

import('db');
class ScraperAniDB extends ShowScraper {

	public function __construct() {
		$this->setScraper('anidb');
		$scraper = array(
			'host' => 'http://api.anidb.net:9001/',
			'query' => 'httpapi?request=anime&client={client}&clientver={version}&protover=1&aid=',
			'regs' => array(
				"<anime id=\"(?<aid>[0-9]+)\"",
				"<episodecount>(?<episodes>.*?)<\/episodecount>",
				"<startdate>(?<startdate>.*?)<\/startdate>",
				"<enddate>(?<enddate>.*?)<\/enddate>",
			),
			'follow' => array(
				'The document has moved <a href=\"(.*)\">here',
				'number\">1.*<a href=\"(.*)\"><img src.*number\">2',
			),
			'data' => array('gzip' => true),
		);
		$this->addScraper('anidb', $scraper);
	}

	public function setShow($show) {
		import('anidb');
		$anidb = new AniDB();
		$result = $anidb->matchShow($show);
		parent::setShowTitle($result['title']);
		parent::setShow($result['aid']);
	}

}
