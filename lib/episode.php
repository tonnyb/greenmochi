<?php

import('object');
class Episode extends Object {
	public function __construct($info) {
		$this->setFile($info);
	}

	public function getShowHash() {
		return md5( chop($this->info['show']) );
	}

	public function getSite() {
		return strtolower($this->info['site']);
	}

	public function getReleaseDate() { 
		return date("d-m-Y H:i", $this->getReleaseTime());
	}

	public function getReleaseTime() {
		if ( $this->getAirdate() ) {
			return $releaseDate = $this->getAirdate() + ( 86400 * 1 );
		}
		return strtotime("Next Month");
	}

	public function setHaveChanges( $value = 1 ) {
		$this->haveChanges = $value;
	}

	public function setFile($info) {
		if ( is_array($info) ) {
			foreach( $info as $key => $value ) {
				//$this->info[$key] = chop($value);
				$func = "set" . $key;
				$this->$func(chop($value));
			}
			$path = $this->getPath() . $this->getFile();
			if ( $path && $this->getFullPath() == "" ) {
				$this->setFullPath( $this->getPath() . $this->getFile() );
			}
		}
		else $this->info['file'] = $info;
	}

	public function getEpisode() {
		return sprintf('%02d', $this->info['episode']);
	}

}

