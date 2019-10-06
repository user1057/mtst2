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

// It will display the data that it was received from the form called name
if(isset($_POST['loopWait']) && !empty($_POST['loopWait'])){
	echo "setting1";
	execStatment("UPDATE params SET value = '".strval($_POST['loopWait'])."' WHERE id='loopWait'");
}

if(isset($_POST['stop']) && !empty($_POST['stop'])){
	echo "setting2";
	execStatment("UPDATE params SET value = '".strval($_POST['stop'])."' WHERE id='stop'");
}

if(isset($_POST['periods']) && !empty($_POST['periods'])){
	echo "setting3";
	execStatment("UPDATE params SET value = '".strval($_POST['periods'])."' WHERE id='periods'");
}

if(isset($_POST['offset']) && !empty($_POST['offset'])){
	echo "setting4";
	execStatment("UPDATE params SET value = '".strval($_POST['offset'])."' WHERE id='offset'");
}

if(isset($_POST['waitBeforeTelegramSend']) && !empty($_POST['waitBeforeTelegramSend'])){
	echo "setting5";
	execStatment("UPDATE params SET value = '".strval($_POST['waitBeforeTelegramSend'])."' WHERE id='waitBeforeTelegramSend'");
}

if(isset($_POST['priceDiff']) && !empty($_POST['priceDiff'])){
	echo "setting6";
	execStatment("UPDATE params SET value = '".strval($_POST['priceDiff'])."' WHERE id='priceDiff'");
}

if(isset($_POST['runScriptFor']) && !empty($_POST['runScriptFor'])){
	echo "setting7";
	execStatment("UPDATE params SET value = '".strval($_POST['runScriptFor'])."' WHERE id='runScriptFor'");
}

if(isset($_POST['last_telegram_send']) && !empty($_POST['last_telegram_send'])){
	echo "setting8";
	execStatment("UPDATE params SET value = '".strval($_POST['last_telegram_send'])."' WHERE id='last_telegram_send'");
}

$conn->close();

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
				if($row['id']=="last_telegram_send") $GLOBALS['last_telegram_send']=$row['value'];
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

if(count(file(basename("errors.log")))>20000){
	file_put_contents("errors.log", "");
}
?>
 
<form action="params.php" method="POST">
<table>
<tr><td>loopWait:</td><td><input type="text" name="loopWait" value = "<?php echo ($GLOBALS['loopWait'])?>"></td></tr>
<tr><td>stop:</td><td><input type="text" name="stop" value = "<?php echo ($GLOBALS['stop'])?>"></td></tr>
<tr><td>periods:</td><td><input type="text" name="periods" value = "<?php echo ($GLOBALS['periods'])?>"></td></tr>
<tr><td>offset:</td><td><input type="text" name="offset" value = "<?php echo ($GLOBALS['offset'])?>"></td></tr>
<tr><td>waitBeforeTelegramSend:</td><td><input type="text" name="waitBeforeTelegramSend" value = "<?php echo ($GLOBALS['waitBeforeTelegramSend'])?>"></td></tr>
<tr><td>priceDiff:</td><td><input type="text" name="priceDiff" value = "<?php echo ($GLOBALS['priceDiff'])?>"></td></tr>
<tr><td>runScriptFor:</td><td><input type="text" name="runScriptFor" value = "<?php echo ($GLOBALS['runScriptFor'])?>"></td></tr>
<tr><td>last_telegram_send:</td><td><input type="text" name="last_telegram_send" value = "<?php echo ($GLOBALS['last_telegram_send'])?>"></td></tr>
</table>
<input type="submit" value="Submit">
</form>

<button style="height:100px;width:200px" onclick="window.location.href = 'https://awsh3.000webhostapp.com/stop.php';">stop.php</button>
<button style="height:100px;width:200px" onclick="window.location.href = 'https://awsh3.000webhostapp.com/showlog.php';">showlog.php</button>
<button style="height:100px;width:200px" onclick="window.location.href = 'https://awsh3.000webhostapp.com/params.php';">params.php</button>
<button style="height:100px;width:200px" onclick="window.location.href = 'https://awsh3.000webhostapp.com/menu.php';">menu.php</button>
