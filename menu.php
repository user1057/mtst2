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
getParams();
$buys=array();
$sells=array();
getChartData();

$conn->close();
function getChartData(){
	$result=execStatment("SELECT price,buy_sell,UNIX_TIMESTAMP(timestamp) as timestamp FROM transHistory where buy_sell=0 and timestamp>=NOW() - INTERVAL 60 MINUTE");
	if($result==true){
		for ($x = 0; $x < $result->num_rows; $x++) {
			$row = $result->fetch_assoc();
			$temp=array($row['timestamp'],$row['price']);
			array_push($GLOBALS['buys'],$temp);
			$GLOBALS['buysEnc']=json_encode($GLOBALS['buys']);
		}
	}
	
	$result=execStatment("SELECT price,buy_sell,UNIX_TIMESTAMP(timestamp) as timestamp FROM transHistory where buy_sell=1 and timestamp>=NOW() - INTERVAL 60 MINUTE");
	if($result==true){
		for ($x = 0; $x < $result->num_rows; $x++) {
			$row = $result->fetch_assoc();
			$temp=array($row['timestamp'],$row['price']);
			array_push($GLOBALS['sells'],$temp);
			$GLOBALS['sellsEnc']=json_encode($GLOBALS['sells']);
		}
	}	
}

function getParams(){
	$result=execStatment("SELECT id,value FROM params");
	if($result==true){
		try{
			echo("\n"."<br>");
			for ($x = 0; $x < $result->num_rows; $x++) {
				$row = $result->fetch_assoc();
				if($row['id']=="loopWait") $GLOBALS['loopWait']=$row['value'];
				if($row['id']=="stop") $GLOBALS['stop']=$row['value'];
				if($row['id']=="periods") $GLOBALS['periods']=$row['value'];
				if($row['id']=="offset") $GLOBALS['offset']=$row['value'];
				if($row['id']=="waitBeforeTelegramSend") $GLOBALS['waitBeforeTelegramSend']=$row['value'];
				if($row['id']=="priceDiff") $GLOBALS['priceDiff']=$row['value'];
				if($row['id']=="runScriptFor") $GLOBALS['runScriptFor']=$row['value'];
				if($row['id']=="last_telegram_send"){
					$GLOBALS['last_telegram_send']=$row['value'];
					echo("Parametar: last_telegram_send = ".date("d-m-Y H:i:s.u",floatval($row['value'])));
					echo("\n"."<br>");
				}
				if($row['id']=="lastLoopStartTime"){
					$GLOBALS['lastLoopStartTime']=$row['value'];
					echo("Parametar: lastLoopStartTime = ".date("d-m-Y H:i:s.u",floatval($row['value'])));
					echo("\n"."<br>");
				}				
				echo("Parametar: ".$row["id"]." = ".$row["value"]);
				echo("\n"."<br>");
			}
		}catch(Exception $e){
			logToFile("Greška kod obrade parametara: ".$e);
			exit();
		}
		
	}else{
		logToFile("Greška kod dohvata parametara");
		exit();
	}
}
 
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
<br>

<script>
function stopscript(){
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
	location.reload();
  };
  xhttp.open("GET", "stop.php", true);
  xhttp.send();
}

function startscript(){
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
	location.reload();
  };
  xhttp.open("GET", "start.php", true);
  xhttp.send();
}

function runscript(){
  var xhttp = new XMLHttpRequest();
  xhttp.open("GET", "mtst2.php", true);
  xhttp.send();
  alert("Script started");
}
 </script>
  
<button style="height:100px;width:200px" onclick="stopscript()">stop.php</button>
<button style="height:100px;width:200px" onclick="startscript()">start.php</button>
<button style="height:100px;width:200px" onclick="runscript()">mtst2.php</button>
<button style="height:100px;width:200px" onclick="window.location.href = 'https://awsh3.000webhostapp.com/menu.php';">Refresh</button>
<br>
<button style="height:100px;width:200px" onclick="window.location.href = 'https://awsh3.000webhostapp.com/showlog.php';">showlog.php</button>
<button style="height:100px;width:200px" onclick="window.location.href = 'https://awsh3.000webhostapp.com/params.php';">params.php</button>

<div class="ct-chart ct-perfect-fourth"></div>
