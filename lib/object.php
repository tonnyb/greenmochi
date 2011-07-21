<?

class Object {
	protected $info;
	protected $haveChanges = 0;

	public function haveChanges() {
		return $this->haveChanges;
	}

	public function __call($name, $args) {
		preg_match("/(?<type>(get|set))(?<command>[a-zA-Z]+)/", $name, $match);
		if ( isset($match["type"]) ) {
			$var = strtolower($match["command"]);
			switch ( $match["type"] ) {
				case 'get':
					if ( isset($this->info[$var]) ) return chop($this->info[$var]);
					else return false;
				break;
				case 'set':
					if ( !isset($this->info[$var]) || ( $this->info[$var] != $args[0] && $args[0] && !is_int($var) ) ) {
						$this->info[$var] = $args[0];
						$this->haveChanges = 1;
					}
				break;
			}
		}
		else return false;
	}

}
