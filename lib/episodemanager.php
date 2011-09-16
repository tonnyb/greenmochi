<?

import('task');
import('showfactory');
import('showcontroller');
import('episodefactory');
import('episodecontroller');
import('nzbscraper');
import('sabmanager');
import('anidb');
class EpisodeManager extends Task {

	protected function runManager( $method = 'new' ) {
		$db = DB::getInstance();

		$airdateFrom = strtotime("23 days ago");
		$airdateTo = strtotime("9 hours");

		switch ( $method ) {
			case 'backlog':
				$shows = ShowFactory::getShows(array('shows.download' => 1, 'shows.scan' => 0, 'JOIN episodes' => 'shows.showid = episodes.showid', 'episodes.want' => 1));
			break;
			case 'new':
			default:
				$shows = ShowFactory::getShows(array('download' => 1, 'scan' => 0, '>enddate' => date('Y-m-d', $airdateTo), '|enddate' => 0));
			break;
		}

		$wantlist = array();
		$found = array();
		foreach ( $shows as $show ) {
			$items1 = $items2 = array();

			$this->logInfo('Seeing if we need new episodes for ' . $show->getVarName());

			$site = ( $show->getSite() ? $show->getSite() : $show->getHighestSite() );
			$quality = ( $show->getQuality() ? $show->getQuality() : '720p' );

			switch ( $method ) {
				case 'backlog':
					$episodes = EpisodeFactory::getEpisodes(array('showid' => $show->getShowId(), 'want' => 1, 'file' => ''));
				break;
				case 'new':
				default:
					$episodes = EpisodeFactory::getEpisodes(array('showid' => $show->getShowId(), 'airdate' => array($airdateFrom, $airdateTo), '!want' => 2, 'file' => ''));
				break;
			}
			//$episodes = array_merge($newepisodes, $wantedepisodes);

			if ( count($episodes) != 0 ) {

				// Quality Check
				$items1 = $this->searchItem($show->getVarName(), $site, '1080p');
				if ( empty($items1) ) {
					$items1 = $this->searchItem($show->getVarName(), $site, $quality);
					if ( empty($items1) ) {
						$items1 = $this->searchItem($show->getVarName(), $site, null);
					}
				}
				if ( $show->getVarName() != $show->getTitle() ) {
					$items2 = $this->searchItem($show->getTitle(), $site, $quality);
				}

				$items = array_merge($items1, $items2);

				$this->logInfo('RSS-SEARCH-RESULT:: Found %d items', count($items));

				foreach ( $episodes as $episode ) {
					$found[] = $this->needEpisode($show, $episode, $items);
				}

				sleep(2);
			}
			else {
				$this->logInfo('::No episodes wanted');
			}

			//usleep(100000);
		}

		$sab = SabManager::getInstance();
		foreach ( $found as $item ) {
			if ( $item ) {
				$this->logInfo('Snatched: ' . $item["name"]);
				$sab->addQue($item["name"], $item["link"]);
				EpisodeController::setWant($item['show'], $item['episode'], 2);
			}
		}
		
	}


	protected function needEpisode($show, $episode, $items) {
		$epreg = '(?:[ \-_.]+)' . 
			'(?:[sS](?<season>[0-9]+))?' . 
			'(?:[eE][pP](?:isode)?)?' . 
			'(?<episode>[0-9]+\.[0-9]+|[0-9]+)';

		$titles = $this->getParsedTitles($show);
		$showTitles = "(" . implode("|", $titles) . ")";

		$showreg = getShowRegs($showTitles, $epreg);
		$regTitle = $showreg[0];
		//$showreg = getShowRegs($show->getVarName(), $epreg);
		//$regVar = $showreg[0];

		$site = ( $show->getSite() ? $show->getSite() : $show->getHighestSite() );

		if ( !$episode->getFile() ) {

			$this->logInfo('NZB-SEARCH:: Need Episode %d [ %s ]', $episode->getEpisode(), date('Y-m-d', $episode->getReleaseTime()));

			foreach ( $items as $item ) {
				$matches = nzbScraper::parseItem($item, $regTitle);

				$showTitle = chop(strtolower($matches["show"]));

				if ( !$this->parseTitle($showTitle, $titles) ) continue;
				//echo( "[" . $showTitle . "]\n" );
				if ( $episode->getEpisode() != $matches["episode"] ) continue;
				//echo( "[" . $matches["episode"] . "]\n" );
				if ( strtolower($site) != strtolower($matches["site"]) ) continue;
				//echo( "SS:" . $matches["site"] . "\n" );

				//if ( $show->getQuality() != $matches["quality"] ) continue;
				$item["name"] = str_replace(" ", "_", $show->getVarName()). "_" . $episode->getEpisode() . "_" . $site;
				$item["episode"] = $episode->getEpisode();
				$item["show"] = $show->getShowId();
				$this->logInfo('Found NZB candidate for: [%s] [%s]', $show->getVarName(), $episode->getEpisode() );

				return $item;
			}

		}
		return false;
	}

	protected function parseTitle($itemTitle, $titles) {
		$title = strtolower($itemTitle);
		foreach ( $titles as $title ) {
			if ( strtolower($title) == $itemTitle ) return true;
		}
		return false;
	}

	protected function getParsedTitles($show) {
		$titles[] = strtolower($show->getTitle());
		$titles[] = strtolower($show->getVarName());
		if ( preg_match("/-/", $show->getTitle()) ) {
			$preTitle = str_replace("- ", "", strtolower($show->getTitle()));
			if ( !in_array($preTitle, $titles) ) $titles[] = $preTitle;
		}
		if ( preg_match("/:/", $show->getTitle()) ) {
			$preTitle = str_replace(": ", " ", strtolower($show->getTitle()));
			if ( !in_array($preTitle, $titles) ) $titles[] = $preTitle;
		}
		if ( preg_match("/-/", $show->getVarName()) ) {
			$preTitle = str_replace("- ", "", strtolower($show->getVarName()));
			if ( !in_array($preTitle, $titles) ) $titles[] = $preTitle;
		}
		if ( preg_match("/:/", $show->getVarName()) ) {
			$preTitle = str_replace(": ", " ", strtolower($show->getVarName()));
			if ( !in_array($preTitle, $titles) ) $titles[] = $preTitle;
		}

		return $titles;
	}

	public function searchItem($title, $site, $quality) {
		$nzb = nzbScraper::getInstance('NZBIndex');
		$nzb->setScraper('nzbindex');

		$search = str_replace(" ", "+", $title);
		$arr = array(
			$search,
			$site,
			$quality,
		);
		$string = implode("+", $arr);
		$this->logInfo('RSS-SEARCH :: ' . $string);
		return $nzb->scrape( $string );
	}

}
