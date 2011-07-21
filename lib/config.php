<?

class Config {
	private static $instance;
	private static $settings;

	public function getInstance() {
		if ( isset(self::$instance) ) return self::$instance;
		else return self::$instance = new Config();
	}

	public function __construct() {
		/*
		$this->reload();
		$this->lastReload = date("U");
		*/
	}

	private function reload() {
		/*
		$db = DB::getInstance();
		$res = $db->fsql("SELECT * FROM config");
		if ( $res->numRows == 0 ) {
			$this->rebuild();
			$res = $db->fsql("SELECT * FROM config");
		}
		while( $arr = $res->fetchArray() ) {
			$this->settings[ $arr['name'] ][ $arr['var'] ] = $arr['value'];
		}
		*/
	}

	public function sanityCheck() {
		/*
		$db = DB::getInstance();
		$db->connect();
		$this->reload();	
		$nextLoad = $this->lastReload + 60;
		if ( $nextLoad < date("U") ) {
			$this->save();
			$this->reload();	
			$this->lastReload = date("U");
		}
		*/
	}

	public function rebuild() {
		foreach ( $this->getDefaultSettings() as $main => $groups ) {
			foreach ( $groups as $group => $vars ) {
				foreach ( $vars as $var => $value ) {
					$this->set($group, $var, $value);
				}
			}
		}
	}

	public function set($group, $var, $value) {
		//$this->settings[ $group ][ $var ] = $value;
		$this->saveOption($group, $var, $value);
	}

	public function get($group, $var) {
		$db = DB::getInstance();
		$result = $db->fsqlr('SELECT value FROM config WHERE name = "%s" and var = "%s"', $group, $var);
		if ( $result ) return $result;
		$settings = $this->getDefaultSettings();
		foreach ( $settings as $groupname => $gs ) {
			if ( !isset($gs[$group]) ) continue;
			if ( isset($gs[$group][$var]) ) {
				$result = $gs[$group][$var];
				//$this->saveOption($group, $var, $result);
				return $result;
			}
		}
	}

	private function saveOption($group,$var, $value) {
		$db = DB::getInstance();
		$db->fsql('INSERT INTO config (name,var,value) VALUES ("%s", "%s", "%s") ON DUPLICATE KEY UPDATE value = "%s"', $group, $var, $value, $value);
	}

	private function save() {
		#foreach ( $this->settings as $group => $vars ) {
		#	foreach ( $vars as $var => $value ) {
		#		$this->saveOption($group, $var);
		#	}
		#}
	}

	public function getConfig( $var ) {
		$configs = $this->getDefaultSettings();
		if ( isset($configs[$var]) ) return $configs[$var];
		else return false;
	}

	public function getDefaultSettings() {
		if ( self::$settings ) return self::$settings;
		self::$settings = array(
			'general' => array(
				'webinterface' => array(
					'http_port' => '8082',
					'http_user' => 'admin',
					'http_pass' => 'admin',
				),
			),
			'episode' => array(
				'search' => array(
					'search_frequency' => 60,
					'usenet_retention' => 400,
				),
				'episode' => array(
					'result_method' => 'sab', // sab / dir
					'dir_blackhole' => BASE_VAR . 'tmp',
					'sab_url' => 'http://localhost:8080/',
					'sab_user' => 'admin',
					'sab_pass' => 'admin',
					'sab_api' => 'HASH1337',
					'sab_category' => 'anime',
				),
				'processing' => array(
					'watchdir' => BASE_VAR . 'tmp',
					'scan_n_process' => '',
				),
			),
			'notifications' => array(
				'xbmc' => array(
					'xbmc_enabled' => 0,
					'xbmc_notify_snatch' => 0,
					'xbmc_notify_download' => 0,
					'xbmc_update_library' => 0,
					'xbmc_ip' => '0.0.0.0:8080',
					'xbmc_user' => 'xbmc',
					'xbmc_pass' => 'xbmc',
				),
				'notifo' => array(
					'notifo_enabled' => 0,
					'notifo_notify_snatch' => 0,
					'notifo_notify_download' => 0,
					'notifo_user' => '',
					'notifo_pass' => '',
				),
			),
		);
		return self::$settings;
	}
}
