<?
$show = ShowFactory::getByShow( getRequest('banner'), 0 );
if ( $image = $show->getBanner() ) {
	echo file_get_contents($image);
}
