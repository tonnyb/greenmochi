<?

class telnet {
	private static $instance;

	private $address = '0.0.0.0';
	private $port = 5001;
	private $clients = array();
	private $sock;

	public function getInstance() {
		if ( isset(self::$instance) ) return self::$instance;
		else {
			return self::$instance = new telnet();
		}
	}

	public function __construct() {
		$this->sock = socket_create(AF_INET, SOCK_STREAM, 0);
		socket_bind($this->sock, $this->address, $this->port) or die('Could Not Bind');
		socket_listen($this->sock);
		System_Daemon::info('{appName} Listening to %s:%d', $this->address, $this->port);
	}

	public function input() {
		$this->client = socket_accept($this->sock);
		$input = trim(socket_read($this->client, 1024));
		System_Daemon::info('{appName} accept: ', $input);
		return $input;
	}

	public function send() {
		socket_write($this->client, $values);
	}

	public function close() {
		System_Daemon::info('{appName} Closing connection');
		socket_close($this->sock);
	}

	public function listen() {
		while (true) {
			// Setup clients listen socket for reading
			$read[1] = $this->sock;
			for ($i = 1; $i <= $this->max_clients; $i++)
			{
				if (isset($this->clients[$i]) && $this->clients[$i] != null)
					$read[$i] = $this->clients[$i] ;
			}
			// Set up a blocking call to socket_select()
			$ready = socket_select($read, $write = NULL, $except = NULL, $tv_sec = NULL);
			/* if a new connection is being made add it to the client array */
			if (in_array($this->sock, $read)) {
				for ($i = 1; $i <= $max_clients; $i++)
				{
					if (!isset($this->clients[$i]) || $this->clients[$i] == null) {
						$this->clients[$i] = socket_accept($this->sock);
						break;
					}
					elseif ($i == $max_clients - 1)
						print ("too many clients");
				}
				if (--$ready <= 0)
					continue;
			} // end if in_array
			
			// If a client is trying to write - handle it now
			//for ($i = 1; $i <= $max_clients; $i++) // for each client
			foreach ( $this->clients as $client )
			{
				if ($client == $read)
				{
					$input = socket_read($client, 1024);
					if ($input == null) {
						// Zero length string meaning disconnected
						unset($client);
					}
					$n = trim($input);
					if ($input == 'exit') {
						// requested disconnect
						socket_close($client);
					} elseif ($input) {
						// strip white spaces and write back to user
						$output = ereg_replace("[ \t\n\r]","",$input).chr(0);
						socket_write($client,$output);
					}
				} else {
					// Close the socket
					socket_close($client);
					unset($client);
				}
			}
		} // end while
	}
}
