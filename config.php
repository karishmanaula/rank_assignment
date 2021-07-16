<?php 

	/**
	 * Variable will set debugging true or false
	 * Possible balues => true, false
	 * setting true will show all errors
	 */
	$GLOBALS['debug'] = true;

	$GLOBALS['env'] = 'localhost';
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

	
	if( $GLOBALS['env'] == 'localhost' ) {
		############ LOCAL DB SERVER #############
		$GLOBALS['db_host'] = "127.0.0.1";
		$GLOBALS['db_user'] = "root";
		$GLOBALS['db_pass'] = "";
		$GLOBALS['db_name'] = "leaderboard";
	} else {
		echo 'credentials not set Error:000000000x1';
		return false;
	}
	$GLOBALS['db_opt'] = array(
	    PDO::ATTR_EMULATE_PREPARES   => false, // turn off emulation mode for "real" prepared statements
	    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // turn on errors in the form of exceptions
	    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // make the default fetch be an associative array
	    PDO::MYSQL_ATTR_FOUND_ROWS   => true, // 
	);



	global $db;
	date_default_timezone_set('Asia/Kolkata');
	$db = mysqli_connect($GLOBALS['db_host'], $GLOBALS['db_user'], $GLOBALS['db_pass']);
	mysqli_select_db($db, $GLOBALS['db_name']);


	// session_start();
?>
