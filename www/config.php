<?php
Template::show('header');

$page = ( getRequest('config') ? strtolower(getRequest('config')) : 'general' );

import('config');
$config = Config::getInstance();

if ( $settings = $config->getConfig($page) ) {

	if ( getRequest('formAction') ) {
		foreach ( (array) $settings as $group => $vars ) {
			foreach ( $vars as $var => $xxx ) {
				if ( getRequest($var) != getRequest($var . "_old") && getRequest($var) != "" ) {
					$config->set($group, $var, getRequest($var));
				}
			}
		}
	}


?>
<form method="post">
<input type="hidden" name="formAction" value="post">
<table>
<?
	foreach ( (array) $settings as $group => $vars ) {
?>
<tr><td colspan="2"> <h2><?=$group?></h2> </td></tr>
<?
		foreach ( $vars as $var => $xxx ) {
			$type = "input";
			if ( preg_match("/pass/", $var) ) $type = "password";
			?>
<tr>
<td> <?=$var?> </td>
<td><input type="hidden" name="<?=$var?>_old" value="<?=$config->get($group, $var)?>" /><input type="<?=$type?>" name="<?=$var?>" value="<?=$config->get($group, $var)?>" /></td>
</tr>
			<?
		}
	}
?>
<tr>
	<td colspan="2"><input type="submit" value="save"></td>
</tr>
</table>
</form>
<?
}

Template::show('footer');
?>
