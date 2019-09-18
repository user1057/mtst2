<?php

$ver = SQLite3::version();

echo $ver['versionString'] . "\n";
echo $ver['versionNumber'] . "\n";

var_dump($ver);

$db = new SQLite3(':memory:');

$version = $db->querySingle('SELECT SQLITE_VERSION()||112233');

echo $version . "\n";

?>