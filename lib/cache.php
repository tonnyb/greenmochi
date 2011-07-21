<?php

class cache {
	private static $file;
	private static $lockfile;
	private static $oldfile;

	private static $lock;

	private $ttl;

	private $path;
	public function __construct($key) {
 		$this->ttl = (86400 * 2);
		$this->path = BASE_VAR . "cache/";
		if ( !is_dir($this->path) ) mkdir($this->path, 0775);
		$this->cacheKey = $key;
		self::$file = $this->path . $key;
		self::$lockfile = $this->path . $key . ".lock";
		self::$oldfile = $this->path . $key . ".old";
		if ( file_exists(self::$oldfile) ) unlink(self::$oldfile);
	}

	public function has() { 
		if ( file_exists(self::$file) ) $ttltime = filectime(self::$file) + $this->ttl;
		else $ttltime = 0;

		if (
			file_exists(self::$file)
			&& file_get_contents(self::$file) != ""
			&& date("U") < $ttltime
		) return true;
		else return false;
	}
	public function get() { return file_get_contents(self::$file); }
	public function set($value) {
		if ( self::$lock ) {
			file_put_contents(self::$lockfile, $value);
			$this->unlock();
			return true;
		}
		return false;
	}
	public function lock() {
		self::$lock = true;
	}
	private function unlock() {
		self::$lock = false;
		if ( file_exists(self::$file) ) rename(self::$file, self::$oldfile);
		rename(self::$lockfile, self::$file);
	}
}
