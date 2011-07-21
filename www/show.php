<?php
Template::show('header');

$show = ShowFactory::getById( getRequest('show') );
if ( getRequest('formAction') ) {

	import('showcontroller');
	if ( getRequest('setActive') != getRequest('oldSetActive') ) {
		$setActive = ( getRequest('setActive') ? 1 : 0 );
		$show->setDownload( $setActive );
		//ShowController::setActive($show->getShowId(), $setActive);	
	}
	if ( getRequest('showSite') != getRequest('oldShowSite') ) {
		$show->setSite( getRequest('showSite') );
	}
	ShowController::saveShow($show, false);
	// Todo ajax stuff
	import('episodecontroller');
	foreach ( $show->getEpisodes() as $episode ) {
		$wantString = 'want' . $episode->getEpisode();
		if ( getRequest($wantString) != getRequest('old'.$wantString) ) {
			if ( getRequest($wantString) ) {
				EpisodeController::setWant($show->getShowId(), $episode->getEpisode());
				$episode->setWant(1);
			}
			else {
				EpisodeController::setWant($show->getShowId(), $episode->getEpisode(), 0);
				$episode->setWant(0);
			}
		}
	}
}

?>

<h1> <?=$show->getTitle()?> </h1>

<div id="showInfo">
<form method="post">
<img src='/images/banner/<?=$show->getTitle()?>' width="400" />
<br />
Show is: <?=($show->getDownload() ? 'Active' : 'Not Active')?> <input type="checkbox" name="setActive" <?=($show->getDownload() ? 'CHECKED' : '')?> /><input type="hidden" name="oldSetActive" value="<?=($show->getDownload() ? 'On' : 'Off' )?>" /> <br />
Site: <input type="text" name="showSite" value="<?=$show->getSite()?>" > <input type="hidden" name="oldShowSite" value="<?=$show->getSite()?>" />

<input type="submit" value="Save">
<input type="hidden" name="formAction" value="show">
</form>

<form method="post" action="/">
<input type="hidden" name="show" value="<?=$show->getShowId()?>">
<input type="hidden" name="deleteShow" value="Delete">
<input type="submit" value="Delete" onClick="if ( !confirm('Are you sure?') ) return false;">
<input type="hidden" name="formAction" value="show">
</form>
</div>

<div id="episodeInfo">
<form method="post">
<table id="showList">
<thead>
<tr>
	<td colspan="4" align="right"><input type="submit" value="save"></td>
</tr>
<tr>
<td>Want</td>
<td>Episode</td>
<td>Airdate</td>
<td>File</td>
</tr>
</thead>
<tfoot>
<tr>
	<td colspan="4" align="right"><input type="submit" value="save"></td>
</tr>
</tfoot>
<?
foreach ( $show->getEpisodes() as $episode ) {
?>
	<tr>
	<td>
		<input type="hidden" name="oldwant<?=$episode->getEpisode()?>" value="<?=( $episode->getWant() ? 'on' : '' ) ?>" />
		<input type="checkbox" name="want<?=$episode->getEpisode()?>" <?=( $episode->getWant() ? 'checked' : '' ) ?> />
	</td>
	<td><?=$episode->getEpisode()?></td>
	<td><?=$episode->getReleaseDate()?></td>
	<td><?=$episode->getFile()?></td>
	</tr>
<?
}
?>
</table>
<input type="hidden" name="formAction" value="post">
</form>
</div>
<?
Template::show('footer');
?>
