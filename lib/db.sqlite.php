<?php

class DB {
	private static $instance;
	private $queryExecute;
	private $db;
	private $tables;

	public static function getInstance() {
		if ( System_Daemon::DB() ) return System_Daemon::DB();
		if ( isset(self::$instance) ) return self::$instance;
		else return self::$instance = new DB();
	}

	public function __construct() {
		$this->tables = array('show','episodes','sites','anidb', 'acdb', 'config');
		$this->connect();
	}

	public function connect() {
        $dbpath = BASE_VAR . 'mochi.db';
        if ($this->db = new SQLite3( $dbpath )) {
			$this->createDB();
        }
		else {
			die("Cannot open database");
		}
	}

	private function getTables() {
		return $this->tables;
	}
	private function createDB() {
		foreach ( $this->getTables() as $table ) {
			$this->init( $table );
		}
	}

	private function init( $table ) {
		$q = $this->fsqlr('SELECT count(name) as count FROM sqlite_master WHERE name = "%s"', $table);
		if ( $q == 0 ) {
			switch ( $table ) {
				case 'show':
					$this->sql('CREATE TABLE IF NOT EXISTS shows (showid char(32) , title text, name text, episodes int , path text, site char(20), network char(20), quality char(10), startdate date, enddate date, download int, scan int, unique(name))');
				break;
				case 'episodes':
					$this->sql('CREATE TABLE IF NOT EXISTS episodes (showid char(32), name text, episode int, site char(20), file text, path text, want int, quality char(10), airdate int, unique(showid,episode));
					');
				break;
				case 'sites':
					$this->sql('CREATE TABLE IF NOT EXISTS sites (site char(20) not null, unique(site))');
				break;
				case 'anidb':
					//<aid>|<type>|<language>|<title>
					$this->sql('CREATE TABLE IF NOT EXISTS anidb (aid int, type int, language char(6), title text, cdate int, unique(aid,language,title))');
				break;
				case 'acdb':
					$this->sql('CREATE TABLE IF NOT EXISTS acdb ( id int, title text, cdate int, unique(id) )');
				break;
				case 'config':
					$this->sql('CREATE TABLE IF NOT EXISTS config ( name char(20), var char(20), value char(200), unique(name,var) )');
					$config = Config::getInstance();
					$config->rebuild();
				break;
			}
		}
	}

	public function cleanupTables() {
		foreach ( $this->getTables() as $table ) {
			$this->cleanup($table);
		}
	}

	private function cleanup($table) {
		switch( $table ) {
			case 'show':
				$sql = $this->sql('SELECT showid,path FROM shows');
				while ( $arr = $sql->fetchArray() ) {
					if ( !is_dir( $arr['path'] ) ) {
						System_Daemon::info('---- removing show [' . $arr['showid'] . '] [' . $arr['path'] . '] ++--');
						$this->fsql('DELETE FROM shows WHERE showid = "%s"', $arr['showid']);
					}
				}
			break;
			case 'episodes':
				$sql = $this->sql('SELECT showid,episode,path,file,want FROM episodes');
				while ( $arr = $sql->fetchArray() ) {
					$filepath = $arr['path'] . '/' . $arr['file'];
					if ( $arr['file'] != "" && !file_exists( $filepath ) ) {
						System_Daemon::info('---- removing [' . $arr['showid'] . '] ['. $arr['episode'] . ' ] ' . $arr['file'] . '++--');
						$this->fsql('DELETE FROM episodes where showid = "%s" AND episode = %d', $arr['showid'], $arr['episode']);
					}
					if ( $arr['file'] == "" && $arr['want'] == 2 ) {
						System_Daemon::info('---- reque [' . $arr['showid'] . '] ['. $arr['episode'] . '] ++--');
						$this->fsql('UPDATE episodes SET want = 1 WHERE showid = "%s" AND episode = %d', $arr['showid'], $arr['episode']);
					}
				}
			break;
		}

	}

	public function getQuery() { return $this->queryExecute; }

	private function defineResult( $res ) {
		if ( preg_match("/select /i", $this->getQuery()) ) {
			$res->numRows = 0;
			try {
				if ( method_exists($res, "numColumns") ) {
					$res->query = $this->getQuery();
					if ($res->numColumns() && $res->columnType(0)) {
						// have rows
						$res->numRows = 0;
						while ( $row = $res->fetchArray() ) {
							$res->numRows++;
						}
					}
				}
				else {
					$this->showError();
				}
			}
			catch( Exeption  $e ) {
				$this->showError();
			}
		}
		return $res;
	}

	private function showError() {
		echo("\nError::: " . $this->getQuery() . " \n");
	}

	public function sql($query) {
		$this->queryExecute = $query;
		$this->saveLog($query);
		return $this->res = $this->defineResult($this->db->query( $query ));
	}

	public function sqlr($query) {
		$this->queryExecute = $query;
		$this->saveLog($query);
		return $this->db->querySingle( $query );
	}

	public function sqlra($query) {
		$this->queryExecute = $query;
		return $this->db->querySingle( $query, true );
	}

    public function fsqlr() {
        return $this->sqlr( $this->parseArgs(func_get_args()) );
	}

    public function fsql() {
        $this->res = $this->sql( $this->parseArgs(func_get_args()) );
		return $this->res;
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

		return $query;
	}


	public function __call($name, $arguments) {
		return call_user_func_array(array($this->db, $name), $arguments);
	}

	public function saveLog( $value ) {
		$logFile = BASE_VAR . "db.log";
		$fp = fopen($logFile, 'a');
		fwrite($fp, sprintf("[%15s] %s\n", date("M d H:i:s"), $value ));
		fclose($fp);
	}
}

