<?php

function signalHandler($signal) {
    if ($signal === SIGTERM) {
        System_Daemon::warning('I received the termination signal. ' . $signal);
        // Execute some final code
		//$telnet = telnet::getInstance();
		//$telnet->close();
        // and be sure to:
        System_Daemon::stop();
    }
}

function getRequest($var, $specialchars = 1) {
	$array = $_REQUEST;

	if ( isset($array[$var]) ) {
		return $specialchars ? htmlspecialchars( $array[$var] ) : $array[$var];
	}
	return false;
}

function getConfig($var) {
	require( BASE_VAR . "config.php" );
	if ( isset($_CONFIG[$var]) ) {
		return $_CONFIG[$var];
	}
	return false;
}
