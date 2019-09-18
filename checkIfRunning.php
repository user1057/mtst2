<?php
error_reporting(E_ALL); // Error engine - always ON!
ini_set('display_errors', FALSE); // Error display - OFF in production env or real server
ini_set('log_errors', TRUE); // Error logging
ini_set('error_log', 'cronJob.log'); // Logging file
ini_set('log_errors_max_len', 10024); // Logging file size

$id=uniqid();
logToFile("---------");

$servername = "localhost";
$username   = "id8614984_user1";
$password   = "user1";
$dbname     = "id8614984_tradedb2";
$conn=null;
$lastTrasTime=null;
$huobi_opts = array(
	'http' => array(
		'method' => "GET",
		'timeout' => 20
	)
);
$trade_context = stream_context_create($huobi_opts);

if(count(file(basename("cronJob.log")))>8000){
	file_put_contents("cronJob.log", "");
	logToFile("[loop] cronJob.log content deleted");
}

dbconnect();
getLastTrasTime();

$now=time();
$diff=$now-$GLOBALS['lastTrasTime'];


logToFile("Last trans time = ".date("d-m-Y H:i:s.u",$GLOBALS['lastTrasTime'])." (".$GLOBALS['lastTrasTime'].")");
logToFile("Current time = ".date("d-m-Y H:i:s.u",$now)." (".$now.")");
logToFile("Difference is ".strval($diff));

if(($now-$GLOBALS['lastTrasTime'])>60){
	$data = file_get_contents('http://awsh3.000webhostapp.com/mtst2.php', false, $trade_context);
	logToFile("mtst2.php starting, now exiting...(1)");
	dbclose();
	exit;
}else{
	logToFile("Last transaction got in last 60 secs, sleeping 30 secs");
	sleep(30);
	$data = file_get_contents('http://awsh3.000webhostapp.com/mtst2.php', false, $trade_context);
	logToFile("mtst2.php starting, now exiting...(2)");
	dbclose();
	exit;
}

exit;

function dbclose(){
	try{
		if($GLOBALS['conn']!=null){
			$GLOBALS['conn']->close();
			$GLOBALS['conn']=null;
		}
	}catch(Exception $e){
		$GLOBALS['conn']=null;
	}
}

function dbconnect() {
	$GLOBALS['conn'] = null;
	$counter=0;

	logToFile("[dbconnect] Openning connection to db");
	$GLOBALS['conn'] = new mysqli($GLOBALS['servername'], $GLOBALS['username'], $GLOBALS['password'], $GLOBALS['dbname']);	
	while($GLOBALS['conn']->connect_error && $counter<5){
		logToFile("[dbconnect] Trying openning connection to db again in 10 secs...");
		$loopDummyString="";
		for ($x = 0; $x < 10; $x++) {
			$loopDummyString=$loopDummyString.strval($x)." ";
			sleep(1);
		}
		$GLOBALS['conn'] = new mysqli($GLOBALS['servername'], $GLOBALS['username'], $GLOBALS['password'], $GLOBALS['dbname']);	
		if($GLOBALS['conn']->connect_error){
			logToFile("[dbconnect] Error openning connection to db(counter=".strval($counter).")");
		}else{
			logToFile("[dbconnect] Got db connection back(counter=".strval($counter).")");
			break;
		}
		$counter++;
		if($counter>=5){
			logToFile("[dbconnect] Failed openning connection to db after 5 tries...");
			exit;
		}
	}	
}

function execStatment($stmt){
	$result=null;
	$result = $GLOBALS['conn']->query($stmt);
	if($result==false){
		echo("[checkIfRunning.php] execStatment: ".$GLOBALS['conn']->error);
		exit;
	}
	return $result;
}

function getLastTrasTime(){
	$sql = "SELECT UNIX_TIMESTAMP(MAX(timestamp)) as m FROM transHistory";
	$result = execStatment($sql);
	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		$GLOBALS['lastTrasTime'] = $row["m"];
	}else{
		$GLOBALS['lastTrasTime'] = null;
		exit;
	}
}

function logToFile($e){
	error_log($e."(".$GLOBALS['id'].")");
}

?>