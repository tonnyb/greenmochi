<?php

class DB {
	private static $instance;
	private $queryExecute;
	private $tables;
	private $result;
	private $hash;
	private $db;

	private static $res;

	public static function getInstance( $instance = 1 ) {
		//if ( class_exists('System_Daemon') && System_Daemon::DB() && $instance ) return System_Daemon::DB();
		if ( isset(self::$instance) && $instance ) return self::$instance;
		else return self::$instance = new DB();
	}

	public function __construct() {
		$this->connect();
	}

	private function connect() {
		$server = getConfig('mysql_host');
		$user = getConfig('mysql_user');
		$pass = getConfig('mysql_pass');
		$this->db = getConfig('mysql_db');

		if ( !$server ) die("No mysql_host defined in var/config.php\n");
		if ( !$user ) die("No mysql_user defined in var/config.php\n");
		if ( !$pass ) die("No mysql_pass defined in var/config.php\n");
		if ( !$this->db ) die("No mysql_db defined in var/config.php\n");

		self::$res = @mysql_connect($server, $user, $pass);
		@mysql_select_db($this->db, self::$res);

		if ( mysql_error() ) {
			@mysql_close(self::$res);
			$this->connect();
			sleep(1);
		}

		$this->integrityCheck();
	}

	private function integrityCheck() {
		import("tables.mysql");
	}

	public function getQuery() { return "/* " . $this->hash . " */ " . $this->queryExecute; }

	public function sql($query) {
		$this->queryExecute = $query;
		$this->hash = md5($query);

		$this->result = @mysql_query( $query, self::$res );
		if ( mysql_error() ) {
			switch( mysql_error() ) {
				case 'MySQL server has gone away':
					echo("Reconnecting");
					$this->connect();
					return $this->result = $this->sql($query);
				break;
			}
		}
		return $this->result;
	}

	public function sqlr($query) {
		return @mysql_result($this->sql( $query ), 0);
	}

	public function sqlra($query) {
		return mysql_fetch_array($this->sql( $query ));
	}

    public function fsqlr() {
        return $this->sqlr( $this->parseArgs(func_get_args()) );
	}

    public function fsql() {
        return $this->sql( $this->parseArgs(func_get_args()) );
    }

	public function fetchObject( $result = null ) {
		return mysql_fetch_object( ( $result ? $result : $this->result ) );
	}

	public function fetchArray( $result = null ) {
		return mysql_fetch_array( ( $result ? $result : $this->result ) );	
	}

	public function numRows( $result = null ) {
		return mysql_num_rows( ( $result ? $result : $this->result ) );	
	}

	private function parseArgs($args) {
        // als er een array meegegeven is ipv een aantal scalars, die array gebruiken; als het scalars zijn, er een array van maken :-)
        if(is_array($args[0])){
            $args = $args[0];
        }

        if(is_array($args[0])){
            $array = $args[0];
        }else{
            $array = $args;
        }

        // voor als alleen de values die aan de query zijn meegegeven in een array staan:
        if(isset($args[1])){
            if(is_array($args[1])){
                $array = Array();
                $array[] = $args[0];
                $values = $args[1];

                foreach($values as $value){
                    array_push($array, $value);
                }
            }
        }

        $format = array_shift($array);          // store the query format in $format
        foreach($array as $key => $val) {
            //$array[$key] = $this->db->escapeString($array[$key]);
            $array[$key] = $array[$key];
        }

        $query = @vsprintf($format,$array);

		$this->args = $args;

		return $query;
	}

	public function __call($name, $arguments) {
		return call_user_func_array(array($this->result, $name), $arguments);
	}

	public function getDatabase() {
		return $this->db;
	}

	public function insertId() {
		return mysql_insert_id(self::$res);
	}

}

