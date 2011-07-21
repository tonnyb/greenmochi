<?php

class DirScan {
	private static $files = array();
	private static $regs;

	public function scan($path) {
		self::$files = array();
		self::scanDir($path);
		sort(self::$files);
		return self::$files;
	}

	private function scanDir($path) {
		self::$regs = getShowRegs();

		if ($handle = opendir($path)) {

		    while (false !== ($file = readdir($handle))) {
				if ( is_dir($path . '/' . $file) ) {
					switch ( $file ) {
						case '.':
						case '..':
						break;
						default:
							self::$files[] = $file;
						break;
					}
				}
		    }

			closedir($handle);
		}

	}

}
