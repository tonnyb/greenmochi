<?
$show = ShowFactory::getByShow( getRequest('poster'), 0 );
if ( $image = $show->getFanArt() ) {
	echo file_get_contents($image);
}
