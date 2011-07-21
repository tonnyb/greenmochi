<?php
require_once(__DIR__ . '/../lib/prepend.php');
Template::show('header');

?>
<form method="post">
<?

$config = Config::getInstance();
if ( getRequest('formPost') ) {

	if ( getRequest('name') != "" && !getRequest("showName") ) {
		
		$db = DB::getInstance();
		import('anidb');
		$anidb = new AniDB();
		if ( getRequest('name') == "OTHER" ) $name = getRequest('otherName');
		else $name = getRequest('name');
		$result = $anidb->matchShow($name);
		echo('<input type="radio" name="showName" value="' . $result['title'] . '" /> <input type="hidden" name="name" value="' . $name . '" />' . $result['title'] . ' <br />');

		import("nzbscraper");
		$nzb = nzbScraper::getInstance("NZBIndex");
		$nzb->setScraper('nzbindex');
		$items = $nzb->scrape($result['title']);

		$showreg = getShowRegs($result['title'], null, 1);
		$sites = array();

		$regTitle = $showreg[0];

		foreach ( $items as $item ) {
			//preg_match("/$regs[0]/", $item['title'], $matches);
			$matches = nzbScraper::parseItem($item, $regTitle);
			if ( isset($matches['site']) && $matches['site'] ) {
				$site = strtolower($matches['site']);
				$sites[$site][] = 1;
			}
		}
		echo('Site: <select name="showSite">');
		foreach ( $sites as $site => $c ) {
			echo('<option value="' . $site . '">' . $site . ' (' . count($c) . ')');
		}
		echo('</select>');

	}
	if ( getRequest('showName') ) {
		echo("Added");
		$path = $config->get('location','path1');
		$fullPath = $path . getRequest('name');

		$show = ShowFactory::getByShow( getRequest('showName') );
		$show->setVarname( getRequest('name') );

		import('showinfo');
		$showInfo = (array) ShowInfo::getShowInfo( $show->getVarName() );
		$show->setShowInfo( $showInfo );
		$show->setPath( $fullPath );
		$show->setSite( getRequest('showSite') );

		import('showcontroller');
		ShowController::saveShow($show, false);
		ShowController::setRescan( $show->getShowId() );

		/*
		print "<pre>";
		print_r($showInfo);
		print_r($show);
		*/
	}
	
}

else {

?>
<br />
<?
import('dirscan');
$dirs = DirScan::scan( $config->get('location', 'path1') );

foreach ( $dirs as $name ) {
	?>
<input type="radio" name="name" value="<?=$name?>" /> <?=$name?> <br />
	<?
}

?>
<input type="radio" name="name" value="OTHER" /> <input type="text" name="otherName" value="" /> <br />
<?

}
?>
<br />
<input type="submit" value="save">
<input type="hidden" name="formPost" value="true">
</form>
<?

Template::show('footer');
?>
