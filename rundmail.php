<?php
session_start();
$_SESSION['page']='rundmail';
include "common/header.php";

include "PHPMailer-master/src/PHPMailer.php";
include "PHPMailer-master/src/SMTP.php";
include "PHPMailer-master/src/Exception.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$NTermine = 5;

$mail = new PHPMailer(true);
$mail->IsSMTP();
$mail->CharSet = 'UTF-8';

$str="<div class=\"w3-container w3-dark-gray w3-mobile w3-padding\"><h2>Termin&uuml;bersicht</h2></div><div class=\"w3-container w3-padding\">";
$str = $str.nl2br(htmlspecialchars("Hallo ${Vorname},"));
$str = $str."</div>";
$now = date("Y-m-d");
$end = date("Y-m-d", time()+60*60*24*60);

$sql = sprintf('SELECT `Index` FROM `MVD`.`Termine` WHERE `Datum` >= "%s" AND `Datum` <= "%s" ORDER BY `Datum`, `Uhrzeit`;', $now, $end);
$dbr = mysqli_query($conn, $sql);
while($row = mysqli_fetch_array($dbr)) {
    $M = new Termin;
    $M->load_by_id($row['Index']);
    $str=$str.$M->printMailTableLine()."\n";
}

$mail->Host       = $GLOBALS['mailconfig']['server']; // SMTP server example
$mail->SMTPAuth   = true;                  // enable SMTP authentication
$mail->SMTPDebug  = false;                     // enables SMTP debug information (for testing)
$mail->SMTPSecure = $GLOBALS['mailconfig']['secure'];
$mail->Port       = $GLOBALS['mailconfig']['port'];                    // set the SMTP port for the GMAIL server
$mail->Username   = $GLOBALS['mailconfig']['user']; // SMTP account username example
$mail->Password   = $GLOBALS['mailconfig']['password'];        // SMTP account password example

$mail->setFrom($GLOBALS['mailconfig']['from'], $GLOBALS['mailconfig']['fromName']);
$mail->Subject = "Testmail";
$mail->Body = "<html><head><style>".file_get_contents("styles/w3.css")."</style></head><body><div class=\"w3-container w3-indigo w3-mobile\"><h1>Musikverein Bonn-Duisdorf gegr. 1949 e.V.</h1></div>".$str."</body></html>";
$mail->IsHTML(true);
$mail->addAddress("manuel.schedler@gmx.de", "Manuel Schedler (Schlagwerk)");


echo $str;

$mail->Send();
$mail->clearAddresses();

?>