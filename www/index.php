<?
require_once(__DIR__ . '/../lib/prepend.php');

import('showfactory');
$shows = ShowFactory::getShows(array('order' => 'name'));

if ( getRequest('formAction') ) {
	if ( getRequest('deleteShow') ) {
		ShowController::removeShow( getRequest('show') );
	}
}

Template::show('header');
?>

<table id="showList">
<thead>
<tr>
<th width="20">&nbsp;</th>
<th align="left">Show</th>
<th>Next Ep</th>
<th>Network</th>
<th>Site</th>
<th>Quality</th>
<th>Downloads</th>
<th>Active</th>
<th>Status</th>
</tr>
</thead>

<tfoot>
	<tr>
	<th rowspan="1" colspan="6"></th>
	</tr>
	</tfoot>
<tbody>
<?

foreach ( $shows as $show ) {
	$title = $show->getTitle() ? $show->getTitle() : $show->getVarName();
?>
<tr>
	<td> &nbsp; </td>
	<td class="show"><a href="/Show/<?=$show->getShowId()?>"><?=$title?></a></td>
	<td align="center"><?=$show->getNextAirDate() ?></td>
	<td align="center"><?=$show->getNetwork()?></td>
	<td align="center"><?=$show->getSite()?></td>
	<td align="center"><?=$show->getQuality()?></td>
	<td align="center">[ <?=$show->getEpisodeCountHave() ?>/<?=$show->getEpisodeCount()?> ] </td>
	<td align="center"><? echo( $show->getDownload() ? 'YES' : 'NO' ) ?> </td>
	<td align="center"><? echo(ucfirst($show->getEnddateStatus())) ?> </td>
</tr>
<?
}
?>
</tbody>
</table>
<?
Template::show('footer');
?>
