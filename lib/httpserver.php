<?
import('template');

class httpdServer extends socketServer {
}

class httpdServerClient extends socketServerClient {
	private $max_total_time = 45;
	private $max_idle_time  = 15;
	private $keep_alive = false;
	private $accepted;
	private $last_action;

	private function handle_request($request) {
		if (!$request['version'] || ($request['version'] != '1.0' && $request['version'] != '1.1')) {
			$output  = '400: Bad request';
		} elseif (!isset($request['method']) || ($request['method'] != 'get' && $request['method'] != 'post')) {
			$output  = '400: Bad request';
		} else {
			// handle request
			if (empty($request['url'])) {
				$request['url'] = '/';
			}
			if ($request['url'] == '/' || $request['url'] == '') {
				$request['url'] = '/index.php';
			}
			$_REQUEST["REQUEST"] = "";
			// parse get params into $params variable
			$params = array();
			if (strpos($request['url'],'?') !== false) {
				$params = substr($request['url'], strpos($request['url'],'?') + 1);
				$params = explode('&', $params);
				foreach($params as $key => $param) {
					$pair = explode('=', $param);
					$params[$pair[0]] = isset($pair[1]) ? $pair[1] : '';
					unset($params[$key]);
				}
				$request['url'] = substr($request['url'], 0, strpos($request['url'], '?'));
			}

			$file = BASE_PATH . 'www' . $request['url'];
			if ( !$output = $this->executeFile($file)) {

				$pages = array(
					'show' => 'Show',
					'calendar' => 'Calendar',
					'config' => 'Config', 
					'addshow' => 'addShow',
					'log' => 'Log',

					'fanart' => 'images\/fanart', 
					'banner' => 'images\/banner', 
					'poster' => 'images\/poster'
				);
				foreach ( $pages as $varKey => $page ) {
					if ( preg_match('/\/(?<request>' . $page . ')(?:\/(?<value>.*))?/i', $request['url'], $match) ) {
						$page = ucfirst($match['request']);
						$_REQUEST["REQUEST"] = $varKey;
						if ( isset($match['value']) ) {
							$value = $match['value'];
							$_REQUEST[$varKey] = urldecode($value);
						}
						break;
					}
				}
				$_REQUEST = array_merge($_REQUEST, $params);
				if ( !$output = $this->executeFile(BASE_PATH . 'www/' . strtolower($page) . '.php') ) {
					//$output  = '<h1>404: Document not found.</h1>';
					$output = $this->executeFile(BASE_TEMPLATE . 'error404.php');
				}

			}

		}

		//array_unshift($header, $output);
		$header = array();
		$header[] = $output;
		return implode($header);
	}

	public function executeFile( $file ) {
		$this->saveLog($file);
		if ( file_exists($file) && is_file($file) ) {
			$fileinfo = pathinfo($file);
			if ( $fileinfo['extension'] == "php" ) {
				$output = phpCall::exec($file);
			}
			else {
				$output  = file_get_contents($file);
			}
			return $output;
		}
		else return false;
	}

	public function saveLog( $file ) {
		$logFile = BASE_VAR . "access.log";
		$fp = fopen($logFile, 'a');
		fwrite($fp, sprintf("[%15s] %s\n", date("M d H:i:s"), $file ));
		fclose($fp);
	}

	public function on_read() {
		$_REQUEST = array();

		$this->last_action = time();
		if ((strpos($this->read_buffer,"\r\n\r\n")) !== FALSE || (strpos($this->read_buffer,"\n\n")) !== FALSE) {

			$request = array();
			$headers = explode("\n", $this->read_buffer);
			$request['uri'] = $headers[0];

			unset($headers[0]);
			while (list(, $line) = each($headers)) {
				$line = trim($line);
				if ($line != '') {
					$pos  = strpos($line, ':');
					$type = substr($line,0, $pos);
					$val  = trim(substr($line, $pos + 1));
					$request[strtolower($type)] = strtolower($val);
				}
			}

			$uri                = $request['uri'];
			$request['method']  = strtolower(substr($uri, 0, strpos($uri, ' ')));
			$request['version'] = substr($uri, strpos($uri, 'HTTP/') + 5, 3);
			$uri                = substr($uri, strlen($request['method']) + 1);
			$request['url']     = substr($uri, 0, strpos($uri, ' '));
			foreach ($request as $type => $val) {
				if ($type == 'connection' && $val == 'keep-alive') {
					$this->keep_alive = false;
				}
			}

			if ($request['method'] == "post") {
				if (preg_match('/\r{0,1}\n\r{0,1}\n/', $this->read_buffer)) {
					//NB, only first header, doesnt handle multiple headers!
					$postArr = preg_split('/\r{0,1}\n\r{0,1}\n/', $this->read_buffer, 2);
					foreach( explode("&", $postArr[1]) as $varString ) {
						if ( preg_match("/=/", $varString) ) {
							list($key, $value) =  explode("=", $varString);
							$_REQUEST[$key] = urldecode($value);
						}
					}
				}
			}

			$this->write($this->handle_request($request));
			$this->read_buffer  = '';
		}
	}

	public function on_connect() {
		//echo "[httpServerClient] accepted connection from {$this->remote_address}\n";
		$this->accepted    = time();
		$this->last_action = $this->accepted;
	}

	public function on_disconnect() {
		//echo "[httpServerClient] {$this->remote_address} disconnected\n";
	}

	public function on_write() {
		if (strlen($this->write_buffer) == 0 && !$this->keep_alive) {
			$this->disconnected = true;
			$this->on_disconnect();
			$this->close();
		}
	}

	public function on_timer() {
		$idle_time  = time() - $this->last_action;
		$total_time = time() - $this->accepted;
		if ($total_time > $this->max_total_time || $idle_time > $this->max_idle_time) {
			echo "[httpServerClient] Client keep-alive time exceeded ({$this->remote_address})\n";
			$this->close();
		}
	}
}

class phpCall {
	static public function exec($file) {
		//echo( memory_get_usage() . "\n" );
		try {
			ob_start();
			require($file);
			$content = ob_get_clean();
		}
		catch ( Exception $e ) {
			echo("WTF ERROR IN JE CODE NOOB!");
		}
		return $content;
	}
}

