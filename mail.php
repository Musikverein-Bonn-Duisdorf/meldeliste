<?php
session_start();
$_SESSION['userid'] = 1;
include "common/header.php";

include "PHPMailer-master/src/PHPMailer.php";
include "PHPMailer-master/src/SMTP.php";
include "PHPMailer-master/src/Exception.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
$mail->IsSMTP();
$mail->CharSet = 'UTF-8';

$mail->Host       = $GLOBALS['mailconfig']['server']; // SMTP server example
$mail->SMTPAuth   = true;                  // enable SMTP authentication
$mail->SMTPDebug  = false;                     // enables SMTP debug information (for testing)
$mail->SMTPSecure = $GLOBALS['mailconfig']['secure'];
$mail->Port       = $GLOBALS['mailconfig']['port'];                    // set the SMTP port for the GMAIL server
$mail->Username   = $GLOBALS['mailconfig']['user']; // SMTP account username example
$mail->Password   = $GLOBALS['mailconfig']['password'];        // SMTP account password example

$mail->setFrom($GLOBALS['mailconfig']['from'], $GLOBALS['mailconfig']['fromName']);
$mail->Subject = "Testmail";
$mail->Body = "<html><head><link rel=\"stylesheet\" href=\"https://www.w3schools.com/w3css/4/w3.css\"></head><body><div class=\"w3-container w3-red w3-mobile\"><h1>Warum</h1></div><div class=\"w3-container w3-blue\">warum?</div></body></html>";
$mail->IsHTML(true);
$mail->addAddress("manuel.schedler@gmx.de", "Manuel Schedler (Schlagwerk)");

$mail->Send();
?>