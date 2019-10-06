<?php

error_reporting(E_ALL); // Error engine - always ON!
ini_set('display_errors', TRUE); // Error display - OFF in production env or real server
header('Content-type: text/html; charset=utf-8');

$id=uniqid();
$servername = "localhost";
$username   = "id8614984_user1";
$password   = "user1";
$dbname     = "id8614984_tradedb2";
$conn=null;

dbconnect();

execStatment("UPDATE params SET value = 'YES' WHERE id='stop'");

$conn->close();

function dbconnect() {
	$GLOBALS['conn'] = null;
	$GLOBALS['conn'] = new mysqli($GLOBALS['servername'], $GLOBALS['username'], $GLOBALS['password'], $GLOBALS['dbname']);
	if ($GLOBALS['conn']->connect_error) {
		exit();
	}
}

function execStatment($stmt){
	if ($GLOBALS['conn']->ping()) {
		echo("[params.php] Our connection is ok!");
	} else {
		echo("[params.php] Error: ".$GLOBALS['conn']->error);
		$GLOBALS['conn']->close();
		dbconnect();
	}
	$result=null;
	$result = $GLOBALS['conn']->query($stmt);
	if($result==false){
		echo("[params.php] execStatment: ".$GLOBALS['conn']->error);
	}
	echo("[params.php] execStatment END");
	return $result;
}

?>