<?

import("cache");
import("curl");
class ShowScraper {
	private static $instance;
	private $clientSettings = array('client'=>'greenmochi','version'=>1);

	private $download = 1;

	private $scraper;
	private $scrapers = array();

	protected $scrapeShow;
	protected $scrapeShowTitle;

	public static function getInstance($scraper) {
		import( 'scraper.' . $scraper );
		$class = 'Scraper' . $scraper;
		if ( isset(self::$instance) ) return self::$instance;
		else return self::$instance = new $class();
	}

	public function __construct() { }

	public function setScraper($value) {
		$this->scraper = $value;
	}

	public function addScraper($name, $array = array() ) {
		$this->scrapers[ $name ] = $array;
	}

	public function getScraperValue($key) {
		if ( isset($this->scrapers[$this->scraper][$key]) ) return $this->scrapers[$this->scraper][$key];
		else return false;
	}

	public function setShow($show) { $this->scrapeShow = $show; }
	public function getShow() { return $this->scrapeShow; }
	public function setShowTitle($show) { $this->scrapeShowTitle = $show; }
	public function getShowTitle() { return $this->scrapeShowTitle; }

	public function scrape($show) {
		$this->setShowTitle( $show );
		$this->setShow( $show );

		if ( $this->getShow() ) {

			$showinfo = array('episodes' => 0);
			$query = $this->getScraperValue('query');
			foreach ( $this->clientSettings as $key => $value ) {
				$query = str_replace('{' . $key . '}', $value, $query);
			}
			$content = $this->getScrapeInfo( $this->getScraperValue('host'), $query . $this->getShow() );

			$showinfo['aid'] = 0;
			foreach ( $this->getScraperValue('regs') as $reg ) {
				preg_match("/$reg/", $content, $matches);
				for($i=0;$i<=count($matches);$i++) {
					if(isset($matches[$i])) unset($matches[$i]);
				}
				foreach( $matches as $key => $value ) {
					$showinfo[$key] = $value;
				}
			}
			$showinfo['title'] = $this->getShowTitle();
			return $showinfo;
		}
		else return false;
	}

	private function getScrapeInfo($host,$query) {

		$hash = $this->scraper . $this->getShow();
		$cache = new cache($hash);

		if ( $cache->has() ) return $cache->get();
		else {
			$cache->lock();
		
			$content = Curl::sendRequest($host . $query, 'GET', $this->getScraperValue('data'));

			if ( preg_match("/<error>Banned<\/error>/", $content) ) {
				$this->download = 0;
				return false;
			}

			$cache->set($content);

			return $content;

		}
	}

}
