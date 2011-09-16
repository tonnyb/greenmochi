<?

class Factory {

	public static function parseQuery($array = array(), $return = 'default') {

		$queryAdd = array();

		$queryAdd['default'] = array();
		$queryAdd['head'] = array();

		$showid = false;
		foreach( $array as $key => $value ) {
			if ( $key == "order" ) {
				$order = "ORDER BY " . $value;
				continue;
			}

			if( preg_match("/JOIN (.*?)/s", $key, $matches) ) {
				$queryAdd['head'][] = sprintf(" %s ON %s ", $key, $value);
			}
			else {

				if ( is_array($value) ) {
					$queryAdd['default'][] = sprintf(" AND %s BETWEEN %d AND %d ", $key, $value[0], $value[1]);
				}
				else {
					if ( is_int($value) ) $add = sprintf('%d', $value);
					else $add = sprintf('"%s"', $value);

					$and = "AND";
					$exp = "=";
					if ( preg_match("/\|/", $key) ) {
						$and = "OR";
						$key = str_replace("|", "", $key);
					}
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

					$queryAdd['default'][] = sprintf(" %s %s %s %s ", $and, $key, $exp, $add);
				}

			}
		}

		return $queryAdd[$return];
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
