<?php

class Template {

	public static function show($file) {
		include( BASE_TEMPLATE . $file . ".php" );
	}

}
