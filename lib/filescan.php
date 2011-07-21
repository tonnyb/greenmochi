<?

class FileScan {
	private static $files = array();
	private static $regs;

	public function scan($path) {
		self::$files = array();
		if ( is_dir($path) ) {
			self::scanDir($path);
		}
		return self::$files;
	}

	private function scanDir($path) {
		if ( !preg_match("/$\//", $path) ) $path .= "/";

		if ($handle = opendir($path)) {

		    while (false !== ($file = readdir($handle))) {
				if ( is_dir($path . '/' . $file) ) {
					switch ( $file ) {
						case '.':
						case '..':
						break;
						default:
							if ( !preg_match("/_UNPACK_/", $file) ) {
								self::scanDir( $path . $file );
							}
						break;
					}
				}
				else {

					$fileinfo = pathinfo($path . $file);
					$extensions = (array) getExtensions();
					$episode = "";

					if ( isset($fileinfo['extension']) && in_array($fileinfo["extension"], $extensions) ) {
						// TODO remove unwanted quality crap or something that might fuck parsing of title
						$preFile = str_replace("Blu-Ray", "", $file);

						if ( !preg_match('/-/', $preFile) ) {
							self::$regs = getShowRegs(null, null, 1);
						}
						else {
							self::$regs = getShowRegs(null, null, 2);
						}

						foreach( self::$regs as $reg ) {
							$filename = str_replace("_", " ", $file);
							if ( preg_match("/$reg/", $filename, $matches) ) {
								for($i=0;$i<=count($matches);$i++) {
									if ( isset($matches[$i]) ) unset($matches[$i]);
								}

								$matches["file"] = $file;
								$matches["path"] = $path;
								$matches["extension"] = $fileinfo["extension"];

								if ( isset($matches['site']) && $matches['site'] == "" && isset($matches['site2']) && $matches['site2'] != "" ) {
									$matches['site'] = $matches['site2'];
									unset($matches['site2']);
								}

								if (isset($matches["show"]) ) $matches["show"] = preg_replace("/ -$/", "", $matches["show"]);


								//$episode = new Episode( $matches );
								if ( isset($matches["episode"]) ) {
									$episode = sprintf('%1d', $matches["episode"]);
									self::$files[$episode] = $matches;
								}
								else {
									if ( isset(self::$files[$episode]) ) {
										self::$files[$episode] = array_merge(self::$files[$episode], $matches);
									}
								}
							}
						}

					}
				}
		    }

			closedir($handle);
		}

	}

}
