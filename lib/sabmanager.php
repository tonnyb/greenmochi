<?

class SabManager {
	private static $instance;
	private $host;

	public function getInstance() {
		if ( self::$instance ) return self::$instance;
		else return self::$instance = new SabManager();
	}

	public function __construct() {
		$config = Config::getInstance();
		$this->api = $config->get('episode', 'sab_api');
		$this->host = $config->get('episode', 'sab_url');
		$this->query = "/sabnzbd/api/?mode=addurl&cat=Anime&apikey={apikey}&nzbname={nzbname}&name={nzb}";
	}

	private function getHost() {
		return $this->host;
	}

	public function addQue($name, $nzb) {
		$query = $this->query;
		$query = str_replace('{nzbname}', $name, $query);
		$query = str_replace('{nzb}', $nzb, $query);
		$query = str_replace('{apikey}', $this->api, $query);

		import('curl');
		$result = Curl::sendRequest($this->host . $query);
		return $result;

	}
}
