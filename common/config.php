<?php
$config = array(
	'server' => "manuelschedler.de",
	'user' => "MVD",
	'database' => "MVD",
	'password' => "1949eV",
);

$conn = mysqli_connect($config['server'], $config['user'], $config['password']) or die (mysqli_error($conn));
global $conn;
?>
