<?php
session_write_close();

error_reporting(E_ALL); // Error engine - always ON!
ini_set('display_errors', FALSE); // Error display - OFF in production env or real server
ini_set('log_errors', TRUE); // Error logging
ini_set('error_log', 'errors.log'); // Logging file
ini_set('log_errors_max_len', 10024); // Logging file size
header('Content-type: text/html; charset=utf-8');

$servername = "localhost";
$username   = "id8614984_user1";
$password   = "user1";
$dbname     = "id8614984_tradedb2";
$id=uniqid();
$stop="NO";
$periods = array(1,2,5,8,10,15,20);
$offset = 380;
$waitBeforeTelegramSend=15;
$priceDiff = 100;
$loopWait = 15;
$runScriptFor=1*60*60-60;
$lastScriptStartTime=microtime(TRUE);
$start_time = microtime(TRUE);
$lastTrasTime = null;
$min=0;
$max=0;
$conn=null;

file_put_contents("errors.log", "");

dbconnect("[main]");
setParams("[main]");

//deleteOldTransHistory("[main]");
getLastTrasTime("[main]");
fetchLastTelegramSendTime("[main]");

$now=time();
if(($now-$GLOBALS['lastTrasTime'])<120){
	logToFile("[main] Exiting last transaction in last 2 minutes(".date("d-m-Y H:i:s.u",$GLOBALS['lastTrasTime']).") - IP address: ".get_client_ip());
	exit;
}

logToFile("[main] START");
logToFile("[main] IP address: ".get_client_ip());

//deleteOldDepth("[main]");

$loopCount=0;
while ((microtime(TRUE)-$start_time)<$runScriptFor) {
	if($GLOBALS['stop']=="YES") {logToFile("STOP YES","[loop]"); exit;}
	deleteOldDepth("[main]");
	lastLoopStartTimeUpdate("[loop]");
	getLastTrasTime("[loop]");
	//updateTransHistory("[loop]");
	saveDepth(uniqid(),"[loop]");
	checkForBuyLevel("[loop]");
	dbclose("[loop]");
	logToFile("[loop] Sleeping ".strval($GLOBALS['loopWait'])." seconds");
	$loopDummyString="";
	for ($x = 0; $x < intval($GLOBALS['loopWait']); $x++) {
		$loopDummyString=$loopDummyString.strval($x)." ";
		sleep(1);
	}
	//logToFile("[while loop] Sleep end");
	dbconnect("[loop]");
	$loopCount++;
	setParams("[loop]");
	if(count(file(basename("errors.log")))>8000){
		file_put_contents("errors.log", "");
		logToFile("[loop] errors.log content deleted");
	}
}

logToFile("[main] END");
$conn->close();

function get_client_ip() {
    $ipaddress = '';
    if (getenv('HTTP_CLIENT_IP'))
        $ipaddress = getenv('HTTP_CLIENT_IP');
    else if(getenv('HTTP_X_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    else if(getenv('HTTP_X_FORWARDED'))
        $ipaddress = getenv('HTTP_X_FORWARDED');
    else if(getenv('HTTP_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_FORWARDED_FOR');
    else if(getenv('HTTP_FORWARDED'))
       $ipaddress = getenv('HTTP_FORWARDED');
    else if(getenv('REMOTE_ADDR'))
        $ipaddress = getenv('REMOTE_ADDR');
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

function setParams($loc){
	$result=execStatment("SELECT id,value FROM params",$loc."[setParams]");
	try{
		$s="";
		for ($x = 0; $x < $result->num_rows; $x++) {
			$row = $result->fetch_assoc();
			//$s=$s.strval($row["id"])."=".strval($row["value"]).";";
			if($row["id"]=="stop") $GLOBALS['stop']=$row['value'];
			if($row["id"]=="periods") $GLOBALS['periods']=array_map('intval',explode(",",$row['value']));
			if($row["id"]=="offset") $GLOBALS['offset']=intval($row['value']);
			if($row["id"]=="waitBeforeTelegramSend") $GLOBALS['waitBeforeTelegramSend']=intval($row['value']);
			if($row["id"]=="priceDiff") {
				$GLOBALS['priceDiff']=intval($row['value']);
				$s=$s.strval($row["id"])."=".strval($row["value"]).";";
			}
			if($row["id"]=="loopWait"){
				$GLOBALS['loopWait']=intval($row['value']);
				$s=$s.strval($row["id"])."=".strval($row["value"]).";";
			}
			if($row["id"]=="runScriptFor") $GLOBALS['runScriptFor']=intval($row['value']);
			if($row["id"]=="lastScriptStartTime") $GLOBALS['lastScriptStartTime']=floatval($row['value']);
			if($row["id"]=="last_telegram_send") $GLOBALS['last_telegram_send']=floatval($row['value']);
			if($row["id"]=="minimum") {
				$GLOBALS['min']=floatval($row['value']);
				$s=$s.strval($row["id"])."=".strval($row["value"]).";";
			}
			if($row["id"]=="maximum") {
				$GLOBALS['max']=floatval($row['value']);
				$s=$s.strval($row["id"])."=".strval($row["value"]).";";
			}
		}
		logToFile($loc."[setParams] ".$s);
	}catch(Exception $e){
		logToFile($loc."[setParams] "."Greška kod obrade parametara: ".$e);
		exit();
	}
}

function checkForStep($loc){
	if($GLOBALS['stop']=="YES") {logToFile($loc."[checkForStep] STOP YES"); exit;}
	$minmax = lastXseconds($loc."[checkForStep]");
	if($minmax != null){
		for($x = 0; $x < count($minmax); $x++) {
			$diff=$minmax[$x][2]-$minmax[$x][1];
			logToFile($loc."[checkForStep] difference is ".strval($diff).", min ".strval($minmax[$x][1]).", max ".strval($minmax[$x][2]).", last ".strval($minmax[$x][3])." minutes");
			if($diff>$GLOBALS['priceDiff']) {
				if(abs($minmax[$x][1]-$GLOBALS['min'])>3 || abs($minmax[$x][2]-$GLOBALS['max'])>3){
					$lastMins = strval($minmax[$x][3])."min";
					$uid=uniqid();
					logToFile($loc."[checkForStep] Sending alert...");
					telegramMsg($lastMins." | diff:".strval($diff)." | min:".strval($minmax[$x][1])."  MAX:".strval($minmax[$x][2])."\n".$uid,$loc."[checkForStep]");
					updateMinMaxPrices($minmax[$x][1],$minmax[$x][2],$loc."[checkForStep]");
					saveDepth($uid,$loc."[checkForStep]");
					break;
				}else{
					logToFile($loc."[checkForStep] Small difference(".strval($diff).", param ".strval($GLOBALS['priceDiff']).", dev 3) in recent changes in min(".strval($minmax[$x][1]).", global ".strval($GLOBALS['min']).") or max(".strval($minmax[$x][2]).", global ".strval($GLOBALS['max']).")");
				}	
				break;
			}else{
				if(abs($minmax[$x][1]-$GLOBALS['min'])>50 || abs($minmax[$x][2]-$GLOBALS['max'])>50){
					$lastMins = "Updating min & max\nOld min ".strval($GLOBALS['min'])." max ".strval($GLOBALS['max']);
					logToFile($loc."[checkForStep] Updating min&max");
					telegramMsg($lastMins."\nmin: ".strval($minmax[$x][1])."\nmax: ".strval($minmax[$x][2]),$loc."[checkForStep]");					
					updateMinMaxPrices($minmax[$x][1],$minmax[$x][2],$loc."[checkForStep]");
				}
			}				
		}
	}
}


function checkForBuyLevel($loc){
	if($GLOBALS['stop']=="YES") {logToFile($loc."[checkForBuyLevel] STOP YES"); exit;}
	$minmax = lastXsecondsOfDepth($loc."[checkForBuyLevel]");
	if($minmax != null){
		for($x = 0; $x < count($minmax); $x++) {
			$diff=$minmax[$x][2]-$minmax[$x][1];
			logToFile($loc."[checkForBuyLevel] difference is ".strval($diff).", min ".strval($minmax[$x][1]).", max ".strval($minmax[$x][2]).", last ".strval($minmax[$x][3])." minutes");
			if($diff>$GLOBALS['priceDiff']) {
				if(abs($minmax[$x][1]-$GLOBALS['min'])>3 || abs($minmax[$x][2]-$GLOBALS['max'])>3){
					$lastMins = strval($minmax[$x][3])."min";
					$uid=uniqid();
					logToFile($loc."[checkForBuyLevel] Sending alert...");
					telegramMsg($lastMins." | diff:".strval($diff)." | min:".strval($minmax[$x][1])."  MAX:".strval($minmax[$x][2])."\n".$uid,$loc."[checkForBuyLevel]");
					updateMinMaxPrices($minmax[$x][1],$minmax[$x][2],$loc."[checkForBuyLevel]");
					saveDepth($uid,$loc."[checkForBuyLevel]");
					break;
				}else{
					logToFile($loc."[checkForBuyLevel] Small changes in min(".strval($minmax[$x][1]).", global ".strval($GLOBALS['min']).") or max(".strval($minmax[$x][2]).", global ".strval($GLOBALS['max']).") TRIGGERED DIFFERENCE(".strval($diff).", trigger diff ".strval($GLOBALS['priceDiff']).", dev 3) in recent");
				}	
				break;
			}else{
				if(abs($minmax[$x][1]-$GLOBALS['min'])>100 || abs($minmax[$x][2]-$GLOBALS['max'])>100){
					$lastMins = "Updating min & max\nOld min ".strval($GLOBALS['min'])." max ".strval($GLOBALS['max']);
					logToFile($loc."[checkForBuyLevel] Updating min&max");
					//telegramMsg($lastMins."\nmin: ".strval($minmax[$x][1])."\nmax: ".strval($minmax[$x][2]),$loc."[checkForBuyLevel]");					
					updateMinMaxPrices($minmax[$x][1],$minmax[$x][2],$loc."[checkForBuyLevel]");
				}
			}				
		}
	}
}


function lastXsecondsOfDepth($loc){
	if($GLOBALS['stop']=="YES") {logToFile($loc."[lastXsecondsOfDepth] STOP YES"); exit;}
	//1 min
	$sql = 			   "SELECT 1 AS num, MIN(price) AS mini, MAX(price) AS maxi, 1 AS mins FROM depthHistory WHERE subid='0' AND timestamp >=FROM_UNIXTIME(".microtime(true).") - INTERVAL 60 SECOND";
	//2 min
	$sql = $sql." UNION SELECT 2 AS num, MIN(price) AS mini, MAX(price) AS maxi, 2 AS mins FROM depthHistory WHERE subid='0' AND timestamp >=FROM_UNIXTIME(".microtime(true).") - INTERVAL 120 SECOND";
	//3 min
	$sql = $sql." UNION SELECT 3 AS num, MIN(price) AS mini, MAX(price) AS maxi, 3 AS mins FROM depthHistory WHERE subid='0' AND timestamp >=FROM_UNIXTIME(".microtime(true).") - INTERVAL 180 SECOND";
	$sql = $sql." UNION SELECT 5 AS num, MIN(price) AS mini, MAX(price) AS maxi, 5 AS mins FROM depthHistory WHERE subid='0' AND timestamp >=FROM_UNIXTIME(".microtime(true).") - INTERVAL 350 SECOND";
	$sql = $sql." UNION SELECT 7 AS num, MIN(price) AS mini, MAX(price) AS maxi, 7 AS mins FROM depthHistory WHERE subid='0' AND timestamp >=FROM_UNIXTIME(".microtime(true).") - INTERVAL 420 SECOND";
	$sql = $sql." UNION SELECT 11 AS num, MIN(price) AS mini, MAX(price) AS maxi, 11 AS mins FROM depthHistory WHERE subid='0' AND timestamp >=FROM_UNIXTIME(".microtime(true).") - INTERVAL 660 SECOND";
	$sql = $sql." UNION SELECT 15 AS num, MIN(price) AS mini, MAX(price) AS maxi, 15 AS mins FROM depthHistory WHERE subid='0' AND timestamp >=FROM_UNIXTIME(".microtime(true).") - INTERVAL 900 SECOND";
	//5 min
	//$sql = $sql." UNION SELECT 4 AS num, MIN(price) AS mini, MAX(price) AS maxi, 5 AS mins FROM transHistory WHERE timestamp >=NOW() - INTERVAL 680 SECOND";
	//7 min
	//$sql = $sql." UNION SELECT 5 AS num, MIN(price) AS mini, MAX(price) AS maxi, 7 AS mins FROM transHistory WHERE timestamp >=NOW() - INTERVAL 800 SECOND";
	//10 min
	//$sql = $sql." UNION SELECT 6 AS num, MIN(price) AS mini, MAX(price) AS maxi, 10 AS mins FROM transHistory WHERE timestamp >=NOW() - INTERVAL 980 SECOND";
	//15 min
	//$sql = $sql." UNION SELECT 7 AS num, MIN(price) AS mini, MAX(price) AS maxi, 15 AS mins FROM transHistory WHERE timestamp >=NOW() - INTERVAL 1280 SECOND";
	
	//logToFile($loc."[lastXsecondsOfDepth] ".$sql);
	$result = execStatment($sql,$loc."[lastXsecondsOfDepth]");
	$res=[];
	if ($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {
			array_push($res,array($row["num"], $row["mini"] , $row["maxi"], $row["mins"]));
		}
		return $res;
	}else{
		logToFile($loc."[lastXsecondsOfDepth] no minmax data fetched");
		return null;
	}
}






function deleteOldDepth($loc){
	if($GLOBALS['stop']=="YES") {logToFile($loc."[deleteOldDepth] STOP YES"); exit;}
	logToFile($loc."[deleteOldDepth]");
	$sql = "DELETE FROM depthHistory WHERE timestamp <=FROM_UNIXTIME(".microtime(TRUE).") - INTERVAL 240 MINUTE";
	execStatment($sql,$loc."[deleteOldDepth]");	
}

function saveDepth($uid,$loc){
	if($GLOBALS['stop']=="YES") {logToFile($loc."[saveDepft] STOP YES"); exit;}
	if($GLOBALS['lastTrasTime']==null) getLastTrasTime($loc."[saveDepth]");
	//logToFile($loc."[saveDepth] START");
	
    $huobi_opts = array(
        'http' => array(
            'method' => "GET",
			'timeout' => 20,
			'header'=> "Host: api.huobi.pro\r\n"
        )
    );
	$trade_context = stream_context_create($huobi_opts);
	$data=false;
	try{
		$data = file_get_contents('http://104.16.233.188/market/depth?symbol=datxbtc&type=step0', false, $trade_context);
		if ($data !== false) {
			$data = json_decode($data, true, 512, JSON_BIGINT_AS_STRING);
			$sql = "INSERT INTO depthHistory (amount, price, id, subid, timestamp) ";
			$cnt=0;
			$first=true;
			for ($x = 0; $x < count($data['tick']['bids']); $x++) {
				if($cnt>5) break;
				$price=$data['tick']['bids'][$x][0]* 10000000000;
				$amount=$data['tick']['bids'][$x][1];
				if($amount>1){				
					if($first==true){
						$sql=$sql."VALUES ({$amount}, {$price}, '".$uid."', ".strval($cnt).", FROM_UNIXTIME(".microtime(TRUE)."))";
						$first=false;
					}else{
						$sql=$sql.", ({$amount}, {$price}, '".$uid."', ".strval($cnt).", FROM_UNIXTIME(".microtime(TRUE)."))";
					}				
					$cnt++;
				}
			}
			//logToFile($loc."[saveDepth] ".$sql);
			if($cnt>0){
				$result=execStatment($sql,$loc."[saveDepth]");
				if($result==false){
					logToFile($loc."[saveDepth] ".$GLOBALS['conn']->error);
				}	
				logToFile($loc."[saveDepth] depth saved");
			}else{
				logToFile($loc."[saveDepth] depth EMPTY amount <= 1");
			}
		}		
	} catch (Exception $e) {
		logToFile($loc."[saveDepth] ".$e);
	}
	
	//logToFile($loc."[saveDepth] END");
}

function updateMinMaxPrices($min_,$max_,$loc){
	if($GLOBALS['stop']=="YES") {logToFile($loc."[updateMinMaxPrices] STOP YES"); exit;}
	logToFile($loc."[updateMinMaxPrices] START");
	execStatment("UPDATE params SET value = '".strval($min_)."' WHERE id='minimum'",$loc."[updateMinMaxPrices]");
	execStatment("UPDATE params SET value = '".strval($max_)."' WHERE id='maximum'",$loc."[updateMinMaxPrices]");
	$GLOBALS['min']=$min_;
	$GLOBALS['max']=$max_;
	//logToFile("updateMinMaxPrices END");	
}

function telegramMsg($text,$loc){
	if($GLOBALS['stop']=="YES") {logToFile($loc."[telegramMsg] STOP YES"); exit;}
	//logToFile("telegramMsg START");
	$t = microtime(TRUE);
	if(($t-$GLOBALS['lastTelegramSendTime'])>$GLOBALS['waitBeforeTelegramSend']){
		$text = $text."\n"."(".date("d-m-Y H:i:s").")";
		$text = urlencode($text);
		logToFile($loc."[telegramMsg] sending telegram: {$text}");
		logToFile($loc."[telegramMsg] now: ".$t." last send ".$GLOBALS['lastTelegramSendTime']);
		$telegram_opts      = array(
			'http' => array(
				'method' => "GET",
				'header' => "Accept-language: en\r\n" . "Cookie: foo=bar\r\n"
			)
		);    
		$telegram_context = stream_context_create($telegram_opts);
		try{
			$telegram_handler = file_get_contents('https://api.telegram.org/bot755582695:AAEBcMVt8piJHKn6XCm-QENSa2rLCBk24dQ/sendMessage?chat_id=@channelll111&text='.$text, false, $telegram_context);
		} catch (Exception $e) {
			logToFile($loc."[telegramMsg] telegram_handler".$e);
		}
		updateLastTelegramSendTime($loc."[telegramMsg]");
		logToFile($loc."[telegramMsg] telegram sent");
	}else{
		logToFile($loc."[telegramMsg] telegramMsg send too soon, not sent");
	}
	//logToFile("telegramMsg END");
}

function dbclose($loc){
	try{
		logToFile($loc."[dbclose] Closing connection");
		//logToFile("conn type: ".gettype($GLOBALS['conn']));
		if($GLOBALS['conn']!=null){
			$GLOBALS['conn']->close();
			$GLOBALS['conn']=null;
		}
	}catch(Exception $e){
		logToFile($loc."[dbclose] Handled error when closing connection: ".$e."(".mysqli_connect_error().")");
		$GLOBALS['conn']=null;
	}
}

function dbconnect($loc) {
	dbclose($loc."[dbconnect]");
	if($GLOBALS['stop']=="YES") {logToFile($loc."[dbconnect] STOP YES"); exit;}
	logToFile($loc."[dbconnect] Openning connection to db");
	 
	$counter=0;
	$GLOBALS['conn'] = new mysqli($GLOBALS['servername'], $GLOBALS['username'], $GLOBALS['password'], $GLOBALS['dbname']);	
	while($GLOBALS['conn']->connect_error && $counter<5){
		logToFile($loc."[dbconnect] Trying openning connection to db again in 10 secs...");
		$loopDummyString="";
		for ($x = 0; $x < 10; $x++) {
			$loopDummyString=$loopDummyString.strval($x)." ";
			sleep(1);
		}
		$GLOBALS['conn'] = new mysqli($GLOBALS['servername'], $GLOBALS['username'], $GLOBALS['password'], $GLOBALS['dbname']);	
		if($GLOBALS['conn']->connect_error){
			logToFile($loc."[dbconnect] Error openning connection to db(counter=".strval($counter).")");
		}else{
			logToFile($loc."[dbconnect] Got db connection back(counter=".strval($counter).")");
			break;
		}
		$counter++;
		if($counter>=5){
			logToFile($loc."[dbconnect] Failed openning connection to db after 5 tries...");
			exit;
		}
	}	
}

function updateTransHistory($loc){
	if($GLOBALS['stop']=="YES") {logToFile($loc."[updateTransHistory] STOP YES"); exit;}
	logToFile($loc."[updateTransHistory] START");
	if($GLOBALS['lastTrasTime']==null) getLastTrasTime($loc."[updateTransHistory]");
	
    $huobi_opts = array(
        'http' => array(
            'method' => "GET",
			'timeout' => 20,
			'header'=> "Host: api.huobi.pro\r\n"
        )
    );
	$trade_context = stream_context_create($huobi_opts);
	$data=false;
	try{
		//api.huobi.pro
		//104.16.233.188
		$data = file_get_contents('http://104.16.233.188/market/history/trade?symbol=datxbtc&size=20', false, $trade_context);
		$counter=0;
		while($data===false && $counter<5){
			logToFile($loc."[updateTransHistory] Error getting transactions, trying again in 10 secs...");
			sleep(10);
			$data = file_get_contents('http://104.16.233.188/market/history/trade?symbol=datxbtc&size=20', false, $trade_context);
			if($data===false){
				logToFile($loc."[updateTransHistory] Error getting transactions");
			}else{
				logToFile($loc."[updateTransHistory] Got connection back(counter=".strval($counter).")");
				break;
			}
			$counter++;
			if($counter>=5){
				logToFile($loc."[updateTransHistory] Exiting after 5 tries...");
				exit;
			}
		}
	} catch (Exception $e) {
		logToFile($loc."[updateTransHistory] ".$e);
	}
	
	if ($data !== false) {
		$data = json_decode($data, true, 512, JSON_BIGINT_AS_STRING);
		$sql = "INSERT INTO transHistory (amount, price, buy_sell, transid, timestamp) ";
		$first = true;
		$counter = 0;
		$block=0;
		for ($x = 0; $x < count($data['data']); $x++) {
			for ($y = 0; $y < count($data['data'][$x]['data']); $y++) {	
				$ctstmp = $data['data'][$x]['data'][$y]['ts']/1000;
				$cp = $data['data'][$x]['data'][$y]['price']* 10000000000;
				$cd = $data['data'][$x]['data'][$y]['direction'];
				$d=b'0';
				$amt = $data['data'][$x]['data'][$y]['amount'];
				if($cd=="sell"){
					$d=b'1';
				}
				$ctid = $data['data'][$x]['data'][$y]['id'];
				if($ctstmp>$GLOBALS['lastTrasTime']){
					$counter++;
					if($first==true){
						$sql=$sql."VALUES ({$amt}, {$cp}, {$d}, {$ctid}, FROM_UNIXTIME(".$ctstmp."))";
						$first=false;
					}else{
						$sql=$sql.", ({$amt}, {$cp}, {$d}, {$ctid}, FROM_UNIXTIME(".$ctstmp."))";
					}
				}
			}
		}
		if($counter>0){
			$result=execStatment($sql,$loc."[updateTransHistory]");
			if($result==false){
				logToFile($loc."[updateTransHistory] ".$GLOBALS['conn']->error);
			}	
		}
		logToFile($loc."[updateTransHistory] history fetched. {$counter} new transactions");
	}
	//logToFile("[updateTransHistory] END");
}

function lastXseconds($loc){
	if($GLOBALS['stop']=="YES") {logToFile($loc."[lastXseconds] STOP YES"); exit;}
	//1 min
	$sql = "SELECT 1 AS num, MIN(price) AS mini, MAX(price) AS maxi, 1 AS mins FROM transHistory WHERE timestamp >=NOW() - INTERVAL 440 SECOND";
	//2 min
	$sql = $sql." UNION SELECT 2 AS num, MIN(price) AS mini, MAX(price) AS maxi, 2 AS mins FROM transHistory WHERE timestamp >=NOW() - INTERVAL 500 SECOND";
	//3 min
	$sql = $sql." UNION SELECT 3 AS num, MIN(price) AS mini, MAX(price) AS maxi, 3 AS mins FROM transHistory WHERE timestamp >=NOW() - INTERVAL 560 SECOND";
	//5 min
	//$sql = $sql." UNION SELECT 4 AS num, MIN(price) AS mini, MAX(price) AS maxi, 5 AS mins FROM transHistory WHERE timestamp >=NOW() - INTERVAL 680 SECOND";
	//7 min
	//$sql = $sql." UNION SELECT 5 AS num, MIN(price) AS mini, MAX(price) AS maxi, 7 AS mins FROM transHistory WHERE timestamp >=NOW() - INTERVAL 800 SECOND";
	//10 min
	//$sql = $sql." UNION SELECT 6 AS num, MIN(price) AS mini, MAX(price) AS maxi, 10 AS mins FROM transHistory WHERE timestamp >=NOW() - INTERVAL 980 SECOND";
	//15 min
	//$sql = $sql." UNION SELECT 7 AS num, MIN(price) AS mini, MAX(price) AS maxi, 15 AS mins FROM transHistory WHERE timestamp >=NOW() - INTERVAL 1280 SECOND";
	

	$result = execStatment($sql,$loc."[lastXseconds]");
	$res=[];
	if ($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {
			array_push($res,array($row["num"], $row["mini"] , $row["maxi"], $row["mins"]));
		}
		return $res;
	}else{
		logToFile($loc."[lastXseconds] no minmax data fetched");
		return null;
	}
	//logToFile("lastXseconds END");
}

function fetchLastTelegramSendTime($loc){
	if($GLOBALS['stop']=="YES") {logToFile($loc."[fetchLastTelegramSendTime] STOP YES"); exit;}
	//logToFile("fetchLastTelegramSendTime START");
	$GLOBALS['lastTelegramSendTime'] = null;
	$sql = "SELECT value as ts FROM params WHERE id='last_telegram_send'";
	$result = execStatment($sql,$loc."[fetchLastTelegramSendTime]");
	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		$ltst=floatval($row["ts"]);
		logToFile($loc."[fetchLastTelegramSendTime] Last telegram send time: ".date("d-m-Y H:i:s.u",$ltst)."(".$ltst.")");
		$GLOBALS['lastTelegramSendTime'] = $ltst;
	}else{
		$GLOBALS['lastTelegramSendTime'] = null;
	}
	//logToFile("fetchLastTelegramSendTime END");
}

function updateLastTelegramSendTime($loc){
	if($GLOBALS['stop']=="YES") {logToFile($loc."[updateLastTelegramSendTime] STOP YES"); exit;}
	logToFile($loc."[updateLastTelegramSendTime] updateLastTelegramSendTime");
	$GLOBALS['lastTelegramSendTime'] = microtime(TRUE);
	$sql = "UPDATE params SET value = '".strval($GLOBALS['lastTelegramSendTime'])."' WHERE id='last_telegram_send'";
    execStatment($sql,$loc."[updateLastTelegramSendTime]");
}

function getLastTrasTime($loc){
	if($GLOBALS['stop']=="YES") {logToFile($loc."[getLastTrasTime] STOP YES"); exit;}
	//logToFile("getLastTrasTime START");
	$sql = "SELECT UNIX_TIMESTAMP(MAX(timestamp)) as m FROM depthHistory";
	$result = execStatment($sql,$loc."[getLastTrasTime]");
	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		$GLOBALS['lastTrasTime'] = $row["m"];
		logToFile($loc."[getLastTrasTime] Last trans time = ".date("d-m-Y H:i:s.u",$GLOBALS['lastTrasTime'])." (".$GLOBALS['lastTrasTime'].")");
	}else{
		$GLOBALS['lastTrasTime'] = null;
	}
	//logToFile("getLastTrasTime END");
}

function deleteOldTransHistory($loc){
	if($GLOBALS['stop']=="YES") {logToFile($loc."[deleteOldTransHistory] STOP YES"); exit;}
	logToFile($loc."[deleteOldTransHistory]");
	$sql = "DELETE FROM transHistory WHERE timestamp <=NOW() - INTERVAL 126 MINUTE";
	execStatment($sql,$loc."[deleteOldTransHistory]");
}

function lastLoopStartTimeUpdate($loc){
	if($GLOBALS['stop']=="YES") {logToFile($loc."[lastLoopStartTimeUpdate] STOP YES"); exit;}
	logToFile($loc."[lastLoopStartTimeUpdate]");
	$sql="UPDATE params set value='".strval(microtime(TRUE))."' where id='lastLoopStartTime'";
	execStatment($sql,$loc."[lastLoopStartTimeUpdate]");	
}

function execStatment($stmt,$loc){
	if($GLOBALS['stop']=="YES") {logToFile($loc."[execStatment] STOP YES"); exit;}
	/*
	if ($GLOBALS['conn']->ping()) {
		//logToFile("Our connection is ok!");
	} else {
		logToFile("Error(ping): ". $GLOBALS['conn']->error);
		$GLOBALS['conn']->close();
		dbconnect();
	}
	*/
	$result=null;
	try{
		$result = $GLOBALS['conn']->query($stmt);
		if($result==false){
			logToFile($loc."[execStatment] Exception(1) in execStatment while executing statement: ".$stmt);
			logToFile($loc."[execStatment] Trying(1) to connect and resend staement after 10 secs...");
			sleep(10);
			dbconnect($loc."[execStatment]");
			$result = $GLOBALS['conn']->query($stmt);
			if($result==false){
				logToFile($loc."[execStatment] Error(1): ".$GLOBALS['conn']->error);
				logToFile($loc."[execStatment] Exiting(1)...");
				exit();
			}
		}
	}catch(Exception $e){
		logToFile($loc."[execStatment] Exception(2) in execStatment while executing statement: ".$stmt);
		logToFile($loc."[execStatment] Exception(2) e: ".$e);
		
		logToFile($loc."[execStatment] Trying(2) to connect and resend staement, waiting 10 secs...");
		sleep(10);
		dbconnect($loc."[execStatment]");
		$result = $GLOBALS['conn']->query($stmt);
		if($result==false){
			logToFile($loc."[execStatment] Error(2): ".$GLOBALS['conn']->error);
			logToFile($loc."[execStatment] Exiting(2)...");
			exit();
		}		
	}
	//logToFile("execStatment END");
	return $result;
}

function logToFile($e){
	if($GLOBALS['stop']=="YES"){
		error_log("[logToFile] STOP YES(".$GLOBALS['id'].")");
		exit;
	}
	
	/*
	try{
		error_log($e."(".$GLOBALS['id'].")");
	}catch(Exception $e){
		file_put_contents("errors.log", "");
		error_log($e."(".$GLOBALS['id'].")");
	}	
	*/
	
	error_log($e."(".$GLOBALS['id'].")");
}

function miscInsert($id,$loc){
	if($GLOBALS['stop']=="YES") {logToFile($loc."[miscInsert] STOP YES"); exit;}
	$sql = "INSERT INTO misc(id, timestamp) VALUES('".$id."',FROM_UNIXTIME(".microtime(TRUE)."))";
    $result=execStatment($sql,$loc."[miscInsert]");
}
?>