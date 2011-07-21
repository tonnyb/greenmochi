<?

class Factory {

	public static function parseQuery($array = array()) {

		$queryAdd = array();
		$showid = false;
		foreach( $array as $key => $value ) {
			if ( $key == "order" ) {
				$order = "ORDER BY " . $value;
				continue;
			}

			if ( is_array($value) ) {
				$queryAdd[] = sprintf(" AND %s BETWEEN %d AND %d ", $key, $value[0], $value[1]);
			}
			else {
				if ( is_int($value) ) $add = sprintf('%d', $value);
				else $add = sprintf('"%s"', $value);

				$exp = "=";
				if ( preg_match("/\!/", $key) ) {
					$exp = "!=";
					$key = str_replace("!", "", $key);
				}
				if ( preg_match("/>/", $key) ) {
					$exp = ">=";
					$key = str_replace(">", "", $key);
				}
				if ( preg_match("/</", $key) ) {
					$exp = "<=";
					$key = str_replace("<", "", $key);
				}

				$queryAdd[] = sprintf(" AND %s %s %s ", $key, $exp, $add);
			}
		}

		return $queryAdd;
	}

}
/*
		$queryAdd = array();
		$order = "";
		foreach( $array as $key => $value ) {
			if ( $key == "order" ) {
				$order = "ORDER BY " . $value;
				continue;
			}
			if ( is_int($value) ) $add = sprintf('%d', $value);
			else $add = sprintf('"%s"', $value);
			$queryAdd[] = sprintf(" AND %s = %s ", $key, $add);
		}

		$queryAdd[] = $order;
		return $queryAdd;
	}
*/

?>
