<?php
include "PHPMailer-master/src/PHPMailer.php";
include "PHPMailer-master/src/SMTP.php";
include "PHPMailer-master/src/Exception.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Usermail {
    private $_data = array('User' => null, 'Text' => null, 'subject' => null, 'memberonly' => null, 'sendlink' => null, 'register' => null);
    public function __get($key) {
        switch($key) {
        case 'User':
        case 'subject':
        case 'memberonly':
        case 'sendlink':
        case 'register':
        case 'Text':
            return $this->_data[$key];
            break;
        default:
            break;
        }
    }
    public function __set($key, $val) {
        switch($key) {
        case 'User':
        case 'register':
            $this->_data[$key] = (int)$val;
            break;
        case 'Text':
            $this->_data[$key] = $val;
            break;
        case 'subject':
            $this->_data[$key] = $val;
            break;
        case 'memberonly':
        case 'sendlink':
            $this->_data[$key] = (bool)$val;
            break;
        default:
            break;
        }	
    }
    public function singleUser($userIndex, $subject, $text) {
        $this->User = $userIndex;
        $this->subject = $subject;
        if($userIndex > 0) {
            $this->send($text);
        }
    }
    public function subject($subject) {
        $this->subject = $subject;
    }
    public function memberonly($val) {
        $this->memberonly = $val;
    }
    public function register($val) {
        $this->register = $val;
    }
    public function sendlink($val) {
        $this->sendlink = $val;
    }
    public function send($text) {
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
        $mail->IsHTML(true);

        $mail->Subject = $GLOBALS['mailconfig']['subjectprefix'].$this->subject;
        $style=file_get_contents("styles/w3.css");
        $style=$style.file_get_contents("styles/w3-colors-highway.css");
        $register = '';
        if($this->User > 0) {
            $sql = sprintf("SELECT * FROM `%sUser` WHERE `Index` = %d;",
            $GLOBALS['dbprefix'],
            $this->User);
        }
        else {
            if($this->register > 0) {
                $register = sprintf("AND `Register` = %d", $this->register);
            }
            if($this->memberonly) {
                $sql = sprintf("SELECT * FROM `%sUser` INNER JOIN (SELECT `Index` AS `iIndex`, `Register` FROM `%sInstrument`) `%sInstrument` ON `iIndex` = `Instrument` WHERE `getMail` = 1 AND `Email` != '' AND `Mitglied` = 1 %s;",
                $GLOBALS['dbprefix'],
                $GLOBALS['dbprefix'],
                $GLOBALS['dbprefix'],
                $register);
            }
            else {
                $sql = sprintf("SELECT * FROM `%sUser` INNER JOIN (SELECT `Index` AS `iIndex`, `Register` FROM `%sInstrument`) `%sInstrument` ON `iIndex` = `Instrument` WHERE `getMail` = 1 AND `Email` != '' %s;",
                $GLOBALS['dbprefix'],
                $GLOBALS['dbprefix'],
                $GLOBALS['dbprefix'],
                $register);
            }
        }
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $i=0;
        while($row = mysqli_fetch_array($dbr)) {
            $anrede = "Hallo ".$row['Vorname'].",";
            $link= $GLOBALS['site']['WebSiteURL']."/login.php?alink=".$row['activeLink'];

            $mail->Body = "<html><head><style>".$style."</style></head><body><div class=\"w3-container ".$GLOBALS['commonColors']['Title']." w3-mobile\"><h1>".$GLOBALS['site']['WebSiteName']."</h1></div><div class=\"w3-container\"><p>".$anrede."<br /><br />".nl2br($text)."</p></div><a class=\"w3-btn w3-mobile ".$GLOBALS['commonColors']['submit']." w3-content\" href=\"".$link."\">zu ".genitiv($row['Vorname'])." Meldeliste</a></body></html>";

            $mail->addAddress($row['Email'], $row['Vorname']." ".$row['Nachname']);
        
            $mail->Send();
            $mail->clearAddresses();
            $logentry = new Log;
            $logmessage = sprintf("An: %s %s, Betreff: %s, Text: %s",
            $row['Vorname'],
            $row['Nachname'],
            $this->subject,
            $text
            );
            $logentry->email($logmessage);
            $i++;
        }
        echo "<div class=\"w3-container ".$GLOBALS['commonColors']['mailSentMsg']." w3-mobile\"><h3>Es wurden ".$i." Emails versandt.</h3></div>";
    }
}