	<ul id="subNavMenu">
<?
if ( !isset($_REQUEST["REQUEST"]) ) $_REQUEST["REQUEST"] = "";

switch ( $_REQUEST["REQUEST"] ) {
	case 'config':
		?>
		<li><a href="/Config">General</a></li>
		<li><a href="/Config/Episode">Episodes</a></li>
		<li><a href="/Config/Notifications">Notifications</a></li>
		<li><a href="/Config/Providers">Search</a></li>
		<?
	break;
	case 'show':
		?>
		<li><a href="/addShow.php">Update</a></li>
		<?
	break;
	default:
		?>
		<li><a href="/addshow.php">Add Show</a></li>
		<?
	break;
}
?>
	</ul>
