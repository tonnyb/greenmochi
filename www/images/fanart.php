<?
$show = ShowFactory::getByShow( getRequest('fanart'), 0 );
if ( $image = $show->getFanArt() ) {
	echo file_get_contents($image);
}
