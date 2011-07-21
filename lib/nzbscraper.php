<?

import('curl');
class nzbScraper {
	private static $instance;
	private $clientSettings = array();

	private $scraper;
	private $scrapers = array();

	public static function getInstance($scraper) {
		import( 'nzbscraper.' . $scraper );
		$class = 'nzbScraper' . $scraper;
		if ( isset(self::$instance) ) return self::$instance;
		else return self::$instance = new $class();
	}

	public function setScraper($value) {
		$this->scraper = strtolower($value);
	}

	public function addScraper($name, $array = array() ) {
		$this->scrapers[ strtolower($name) ] = $array;
	}

	public function getScraperValue($key) {
		if ( isset($this->scrapers[$this->scraper][$key]) ) return $this->scrapers[$this->scraper][$key];
		else return false;
	}

	public function scrape($string) {
		$string = str_replace(" ", "+", $string);
		$items = array();
		$query = $this->getScraperValue('query');
		foreach ( $this->clientSettings as $key => $value ) {
			$query = str_replace('{' . $key . '}', $value, $query);
		}

		$content = $this->getScrapeInfo( $this->getScraperValue('host'), $query . $string );

		if ( preg_match_all("/^<item>(?<item>(?:(?!<\/item>).)*)/ms", $content, $matches) ) {
			foreach ( $matches['item'] as $item ) {

				foreach ( $this->getScraperValue('regs') as $reg ) {
					preg_match("/$reg/", $item, $matches);
					for($i=0;$i<=count($matches);$i++) {
						if(isset($matches[$i])) unset($matches[$i]);
					}
					foreach( $matches as $key => $value ) {
						$file[$key] = $value;
					}
				}
				$items[] = $file;
			}

		}

		return $items;
	}

	private function getScrapeInfo($host,$query) {
		$content = Curl::sendRequest($host.$query);
		return $content;
	}

	public function parseItem($item, $regTitle) {
		$title = str_replace("_", " ", $item["title"]);
		$title = htmlspecialchars_decode($title);
		$title = preg_replace('/^.*?"/', '"', $title);

		preg_match('/"' . $regTitle . '.*"/i', $title, $matches);
		//if ( count($matches) == 0 ) preg_match('/"' . $regTitle . '.*"/', $title, $matches);
		if ( count($matches) == 0 ) return false;

		if ( !isset($matches['site']) && $matches['site'] == "" && isset($matches['site2']) && $matches['site2'] != "" ) {
			$matches['site'] = $matches['site2'];
			unset($matches['site2']);
		}

		if ( !isset($matches["show"]) ) false;

		return $matches;
	}

}

class nzbScraperNZBIndex extends nzbScraper {
	public function __construct() {
		$scraper = array(
			//?q=Infinite+Stratos+Ayako+09+720&sort=agedesc&minsize=10&max=100&more=1
			//<enclosure url="http://www.nzbindex.com/download/35865730/AySePo-2011-03-04T160629-0126-Ayako-Infinite-Stratos-IS-09-H264720p.mkv.NFO.nzb" length="506825735" type="text/xml" />
			'host' => 'http://www.nzbindex.com/rss/',
			'query' => '?sort=agedesc&minsize=100&max=100&more=1&q=',
			'regs' => array(
				'<title>(?<title>.*)<\/title>',
				'<enclosure url=\"(?<link>.*)\" length=\"(?<size>.*)\" type=\"text\/xml\" \/>',
			),
		);

		$this->addScraper('NZBIndex', $scraper);
	}
}

