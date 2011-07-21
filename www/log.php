<?
Template::show('header');


$file = array_reverse( file( BASE_VAR . 'greenmochi.log' ) );
$limit = 50;

echo("<pre>");
for ($i = 0; $i <= $limit; $i++ ){
	echo $file[$i];
}
echo("</pre>");

Template::show('footer');
?>
