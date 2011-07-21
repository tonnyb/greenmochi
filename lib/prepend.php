<?php

$path = str_replace("/lib","",__DIR__);
define("BASE_PATH", $path . '/');
define("BASE_LIB", BASE_PATH . 'lib/');
define("BASE_VAR", BASE_PATH . 'var/');
define("BASE_DOC", BASE_PATH . 'www/');
define("BASE_TEMPLATE", BASE_LIB . 'template/');
unset($path);

import('config');
import('regs');
import('extensions');
import('functions');
import('showfactory');

import('template');

function import( $class ) {
	$classFile = BASE_LIB . strtolower($class) . ".php";
	if ( file_exists( $classFile ) ) {
		require_once( $classFile );
	}
}

