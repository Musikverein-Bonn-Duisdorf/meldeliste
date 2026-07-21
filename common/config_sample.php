<?php
$sql = array(
    'server' => "myserver.com",
    'user' => "username",
    'database' => "database",
    'password' => "password",
);

$dbprefix = "meldeliste_";

$mailconfig = array(
    'server' => "smtp.myprovider.com",
    'user' => "email@myserver.com",
    'password' => "password",
    'port' => 587,
    'from' => "frommail@myserver.com",
    'fromName' => "My Organization",
    'secure' => "tls",
    'subjectprefix' => '[My Organization] ',
);

$conn = mysqli_connect($sql['server'], $sql['user'], $sql['password']) or die (mysqli_error($conn));
global $conn;
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($conn));

$cronID = 'xxxx-xxxx-xxxx-xxxx';

// Dedicated secret for remote HTTP backup (cron.php?cmd=backup). Opt-in: leave empty to keep HTTP backup disabled.
// Generate e.g.: openssl rand -hex 32  (must be at least 32 characters). Do not reuse $cronID.
$backupToken = '';

$googlemapsapi = "xxxx-xxxx-xxxx-xxxx";

global $mailconfig;
global $cronID;
global $backupToken;
global $dbprefix;
global $googlemapsapi;
?>
