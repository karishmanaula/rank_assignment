<?php 

	/**
	 * Variable will set debugging true or false
	 * Possible balues => true, false
	 * setting true will show all errors
	 */
	$GLOBALS['debug'] = false;

	if( $GLOBALS['debug'] == true ) {
		ini_set("display_errors", "on");
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		error_reporting(E_ALL);
	} else {
		ini_set("display_errors", "off");
		ini_set('display_errors', 0);
		ini_set('display_startup_errors', 0);
		error_reporting(0);
	}

	$GLOBALS['db_host'] = "127.0.0.1";
	$GLOBALS['db_user'] = "root";
	$GLOBALS['db_pass'] = "";
	$GLOBALS['db_name'] = "leaderboard";

?>
