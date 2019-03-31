<?php
$sql = array(
	'server' => "manuelschedler.de",
	'user' => "MVD",
	'database' => "MVD",
	'password' => "1949eV",
);

$mailconfig = array(
       'server' => "mail.gmx.net",
       'user' => "manuel.schedler@gmx.de",
       'password' => "U,e-Ir.c{F3wi]<QOO[]",
       'port' => 465,
       'from' => "manuel.schedler@gmx.de",
       'fromName' => "Manuel Schedler",
       'secure' => "ssl",
);
global $mailconfig;

$conn = mysqli_connect($sql['server'], $sql['user'], $sql['password']) or die (mysqli_error($conn));
global $conn;
?>
