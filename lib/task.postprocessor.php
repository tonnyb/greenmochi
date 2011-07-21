<?php

import('task');
import('filescan');
import('scraper');
import('episode');
import('show');

import('notify.notifo');

import('showfactory');
import('showcontroller');
import('episodefactory');
import('episodecontroller');
import('airdatescraper');

class TaskPostProcessor extends Task {
	private $watchdir;

	private $notify;
	private $config;

	private $remove = array();
	private $noremove = array();

	private $timebeforeremove = 120;

	private $extensions = "avi|mkv|mpeg|mpg|wmv|ts";

	public function run() {
		parent::run();
		$this->config = Config::getInstance();
		$this->notify = Notifo::getInstance('Notifo', $this->config->get('notifo','notifo_user'), $this->config->get('notifo','notifo_pass'));

		import('showscan');
		while( $this->pid ) {
			$this->rescanShows();

			$this->scanWatchDir();
			$this->iterate(5);
		}

	}

	public function rescanShows() {
		$shows = ShowFactory::getShows(array('scan' => 1));
		foreach ( $shows as $show ) {
			$this->logInfo('Rescan issued for Show [%s]', $show->getTitle());
			$this->scanShow( $show->getPath(), $show );
			ShowController::setRescan($show->getShowId(), 0);
			$this->iterate(1);
		}
	}

	public function scanShow($path, $show) {
		if ( $show->isEmpty() ) {
			$show->setVarname( $showName );
			$show->setPath( $path );
		}
		// Todo getShowInfo when import folder
		//$show->setShowInfo( (array) $this->getShowInfo( $show->getVarName() ) );
		$files = (array) FileScan::scan($path);

		$ads = new AirdateScraper();
		$this->logInfo('Fetching airdates by Varname or Title for %s', $show->getVarName());
		$airdates = (array) $ads->getByTitle( $show->getTitle(), $show->getVarName() );
		$show->getMissingEpisodes($airdates);
		$showinfo = array();
		foreach ( $airdates as $date ) {
			$showinfo["network"] = $date['network'];
		}

		$show->setShowInfo($showinfo);

		foreach ( $show->getEpisodes() as $episode ) {
			$e = sprintf('%1d', $episode->getEpisode());
			if ( isset($files[$e]) ) {
				$episode->setFile($files[$e]);
			}
			if ( isset($airdates[$e]) ) {
				$airdate = $airdates[$e];
				$episode->setAirdate( $airdate['utime'] );
			}
			$show->addEpisode( $episode, true );
		}

		ShowController::saveShow($show, true, true);
	}

	private function hasValidFiles($path) {
		$this->remove[$path] = $path;
		if ($handle = opendir($path)) {
			while (false !== ($file = readdir($handle))) {
				if ( is_dir($path . '/' . $file) ) {
					if ( $file == "." || $file == ".." ) continue;
					else {
						$this->hasValidFiles($path . '/' . $file);
					}
				}
				else {
					$stat = stat($path);
					preg_match("/\.(" . $this->extensions . "|part|rar)/i", $file, $match);
					if ( preg_match("/\.(" . $this->extensions . "|part|rar)/i", $file) || ( $stat["ctime"] + $this->timebeforeremove ) < date("U")) {
						$this->noremove[$path] = $path;
					}
				}
			}
			closedir($handle);
		}
	}

	public function scanWatchDir() {
		if ( $watchDir = $this->config->get('processing', 'watchdir') ) {

			$newfiles = array();
			$newfiles = FileScan::scan($watchDir);

			$rescan = array();
			foreach ( $newfiles as $file ) {
				$episode = new Episode($file);
				$show = ShowFactory::getByShow( '%' . str_replace(" ", "%", $episode->getShow()) );
				if ( !$show->isEmpty() ) {
					$this->logInfo("WATCHDIR: Found new Episode [ %s ] for Show [ %s ]", $episode->getEpisode(), $episode->getShow());
					EpisodeController::setWant($show->getShowId(), $episode->getEpisode(), 0);
					if ( file_exists($episode->getFullPath()) ) {
						// Move file to show path
						if ( !is_dir($show->getFullPath()) ) {
							mkdir($show->getFullPath(), 0777);
						}

						$newFile = $show->getFullPath() . '/' . str_replace("_", " ", $episode->getFile());
						rename($episode->getFullPath(), $newFile);
						chmod($newFile, 0777);

						if ( !file_exists($episode->getFullPath()) ) {
							// Issue rescan show
							$rescan[$show->getShowId()] = $show->getShowId();
							// Send Notification
							$this->notify->sendNotification(array('title' => 'mochi', 'msg' => $show->getTitle() . ' ' . $episode->getEpisode()));
						}
						else {
							$this->logInfo("WATCHDIR: Error file could not be removed");
						}
					}
				}
			}

			foreach ( $rescan as $showid ) {
				ShowController::setRescan($showid);
			}
			/* TODO:: Fix cleanup
			$noremove = array();
			$remove = array();

			if ( $cleanup = 1 ) {

				foreach ( $watchDir as $path ) {
					$this->noremove[$path] = $path;
					$this->hasValidFiles( $path );
				}

				foreach ( $this->remove as $path ) {

					if( !in_array($path, $this->noremove) ) {
						//echo("Removing $path\n");
						//exec("rm -rf $path");
						unset($this->remove[$path]);
					}

				}
			}
			*/
		}
	}
}
