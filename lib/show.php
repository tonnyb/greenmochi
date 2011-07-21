<?php

import('object');
class Show extends Object {
	private $episodes = array();
	private $episodesHave = 0;

	private $sites = array();

	public function __construct() {
		$this->info['episodes'] = 0;
		$this->info['empty'] = 1;
		$this->info['aid'] = 0;
		$this->info['acid'] = 0;
	}

	public function isEmpty() { return $this->info['empty']; }
	public function setVarname($value) { $this->info['varname'] = $value; }
	public function setPath($value) { 
		//if ( !preg_match("/$\//", $value) ) $value .= "/";
		$this->info['path'] = $value;
	}

	public function setShowInfo($array = array()) {
		foreach ( $array as $key => $value ) {
			if ( !is_int($key) ) $this->info[$key] = $value;
		}
		$this->info['empty'] = 0;
	}
	public function addEpisode($episode) {
		if ( isset($this->sites[$episode->getSite()]) ) $this->sites[$episode->getSite()]++;
		else $this->sites[$episode->getSite()] = 1;

		if ( $episode->getFile() ) $this->episodesHave++;

		$this->episodes[ sprintf('%1d', $episode->getEpisode()) ] = $episode;
	}

	public function getSites() {
		return $this->sites;
	}

	public function getHighestSite() {
		$sites = array_flip($this->sites);
		return( array_shift($sites) );
	}

	public function getEpisodes() { return $this->episodes; }

	public function getEpisode( $episode ) {
		$episode = sprintf("%01d", $episode);
		if ( isset($this->episodes[ $episode ]) ) return $this->episodes[$episode];
		$episode = sprintf("%1d", $episode);
		if ( isset($this->episodes[ $episode ]) ) return $this->episodes[$episode];
		else return false;
	}

	public function getMissingEpisodes( $arr = array() ) {
		if ( isset($arr['airdates']) ) {
			$airdates = $arr['airdates'];
			$maxEp = 0;
			foreach ( $arr['airdates'] as $ep => $airdate ) {
				if ( $ep > $maxEp ) $maxEp = $ep;
			}
			if ( $maxEp > $this->getEpisodeCount() ) $this->info['episodes'] = $maxEp;
		}

		for($e=1;$e<=$this->getEpisodeCount();$e++) {
			$episode = $this->getEpisode($e);

			if ( isset($airdates) && isset($airdates[$e]) ) $airdate = $airdates[$e]['utime'];
			else $airdate = 0;

			if ( !$episode ) {

				$episodeArray = array('episode' => sprintf("%02d", $e), 'site' => $this->getSite(), 'quality' => $this->getQuality());
				//$episode = EpisodeController::newEpisode($this->getShowId(), $episodeArray);
				$episode = new Episode($episodeArray);
				if ( !$episode->getFile() || strtotime($airdate) > date("U") ) {
					//EpisodeController::setWant($this->getShowId(), $episode->getEpisode(), 0);
				}

			}
			if ( $episode->getAirdate() != $airdate ) {
				$episode->setAirdate($airdate);
			}
			$this->addEpisode($episode);

			if ( !$episode->getAirdate() ) {
				$episode->setAirdate($airdate);
			}
			EpisodeController::saveEpisode($this->getShowId(), $episode);
		}
	}

	public function getHash() { return md5($this->getVarName()); }

	public function getFanArt() {
		$image = $this->getFullPath() . "/" . "fanart.jpg";
		if ( file_exists( $image ) ) return $image;
		else return false;
	}

	public function getBanner() {
		$image = $this->getFullPath() . "/" . "folder.jpg";
		if ( file_exists( $image ) ) return $image;
		else return false;
	}

	public function getEpisodeCount() { if ( isset($this->info['episodes']) ) return $this->info['episodes']; }
	public function getEpisodeCountHave() { return $this->episodesHave; }

	//public function getPath() { return $this->info['path']; }
	public function getFullPath() { return $this->getPath(); }

	public function getNextAirDate() {
		foreach ( $this->getEpisodes() as $episode ) {
			if ( $episode->getReleaseDate() ) {
				if ( ( $episode->getReleaseTime() ) > date("U") ) return $episode->getReleaseDate();
			}
		}
		return false;
	}

	public function getEnddateStatus() {
		if ( date("U") > strtotime($this->getEnddate()) && date("U") > strtotime($this->getNextAirDate()) ) return "ended";
		else return "continuing";
	}

}
