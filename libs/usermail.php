<?php
include "PHPMailer/src/PHPMailer.php";
include "PHPMailer/src/SMTP.php";
include "PHPMailer/src/Exception.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Usermail {
    private $_data = array('User' => null, 'Text' => null, 'subject' => null, 'memberonly' => null, 'sendlink' => null, 'register' => null, 'termin' => null, 'attachments' => false);
    public function __get($key) {
        switch($key) {
        case 'User':
        case 'subject':
        case 'memberonly':
        case 'termin':
        case 'sendlink':
        case 'register':
        case 'Text':
        case 'attachments':
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
        case 'termin':
            $this->_data[$key] = (int)$val;
            break;
        case 'Text':
        case 'subject':
            $this->_data[$key] = $val;
            break;
        case 'memberonly':
        case 'sendlink':
        case 'attachments':
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
    public function termin($val) {
        $this->termin = $val;
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
		$mail->SMTPKeepAlive = true;
		$mail->Timeout = 15;
        $mail->CharSet = 'UTF-8';
        
        $mail->Host       = gethostbyname($GLOBALS['mailconfig']['server']);
		// --> DEBUG ONLY
		//$mail->SMTPDebug = 2;
		//$mail->Debugoutput = 'error_log';
        $mail->SMTPDebug  = false;                     // enables SMTP debug information (for testing)
		// <-- DEBUG ONLY
        $mail->SMTPAuth   = true;                  // enable SMTP authentication
        $mail->SMTPSecure = $GLOBALS['mailconfig']['secure'];
        $mail->Port       = $GLOBALS['mailconfig']['port'];                    // set the SMTP port for the GMAIL server
        $mail->Username   = $GLOBALS['mailconfig']['user']; // SMTP account username example
        $mail->Password   = $GLOBALS['mailconfig']['password'];        // SMTP account password example
        $mail->setFrom($GLOBALS['mailconfig']['from'], $GLOBALS['mailconfig']['fromName']);
        $mail->IsHTML(true);

        if($this->attachments) {
            $files = scandir("uploads/");
            foreach($files as $file) {
                if($file == "." || $file == ".." || $file == "README") continue;
                $mail->addAttachment("uploads/".$file);
            }
        }

        $mail->Subject = $GLOBALS['mailconfig']['subjectprefix'].$this->subject;
        $style=file_get_contents("styles/w3.css");
        $style=$style.file_get_contents("styles/w3-colors-highway.css");
        $register = '';
        if($this->User > 0) {
            $sql = sprintf("SELECT * FROM `%sUser` WHERE `Index` = %d AND `Deleted` != 1;",
            $GLOBALS['dbprefix'],
            $this->User);
        }
        else {
            if($this->termin) {
                $sql = sprintf("SELECT * FROM `%sMeldungen` INNER JOIN (SELECT `Index` AS `uIndex`, `Vorname`, `activeLink`, `Email`, `Email2`, `Nachname` FROM `%sUser`) `%sUser` ON `uIndex` = `User` WHERE `Termin` = '%d' AND `Wert` != 2;",
                $GLOBALS['dbprefix'],
                $GLOBALS['dbprefix'],
                $GLOBALS['dbprefix'],
                $this->termin
                );

                $Termin = new Termin;
                $Termin->load_by_id($this->termin);
                $mail->Subject = $GLOBALS['mailconfig']['subjectprefix']."[".$Termin->getGermanDate()." ".$Termin->Name."] ".$this->subject;
            }
            else {
                if($this->register > 0) {
                    $register = sprintf("AND `Register` = %d", $this->register);
                    if($this->memberonly) {
                        $sql = sprintf("SELECT * FROM `%sUser` INNER JOIN (SELECT `Index` AS `iIndex`, `Register` FROM `%sInstrument`) `%sInstrument` ON `iIndex` = `Instrument` WHERE `getMail` = 1 AND `Email` != '' AND `Mitglied` = 1 AND `Deleted` != 1 %s;",
                        $GLOBALS['dbprefix'],
                        $GLOBALS['dbprefix'],
                        $GLOBALS['dbprefix'],
                        $register);
                    }
                    else {
                        $sql = sprintf("SELECT * FROM `%sUser` INNER JOIN (SELECT `Index` AS `iIndex`, `Register` FROM `%sInstrument`) `%sInstrument` ON `iIndex` = `Instrument` WHERE `getMail` = 1 AND `Email` != '' AND `Deleted` != 1 %s;",
                        $GLOBALS['dbprefix'],
                        $GLOBALS['dbprefix'],
                        $GLOBALS['dbprefix'],
                        $register);
                    }
                }
                else {
                    if($this->memberonly) {
                        $sql = sprintf("SELECT * FROM `%sUser` WHERE `getMail` = 1 AND `Email` != '' AND `Mitglied` = 1 AND `Deleted` != 1;",
                        $GLOBALS['dbprefix']
                        );
                    }
                    else {
                        $sql = sprintf("SELECT * FROM `%sUser` WHERE `getMail` = 1 AND `Email` != '' AND `Deleted` != 1;",
                        $GLOBALS['dbprefix']
                        );
                    }
                }
            }
        }
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $i=0;
        while($row = mysqli_fetch_array($dbr)) {
            $anrede = "Hallo ".$row['Vorname'].",";
            $link= $GLOBALS['optionsDB']['WebSiteURL']."/login.php?alink=".$row['activeLink'];

            $mail->clearAllRecipients();
            $mail->Body = "<html><head><style>".$style."</style></head><body><div class=\"w3-container ".$GLOBALS['optionsDB']['colorTitle']." w3-mobile\"><h1>".$GLOBALS['optionsDB']['WebSiteName']."</h1></div><div class=\"w3-container\"><p>".$anrede."<br /><br />".nl2br($text)."</p></div><a class=\"w3-btn w3-mobile ".$GLOBALS['optionsDB']['colorBtnSubmit']." w3-content\" href=\"".$link."\">zu ".genitiv($row['Vorname'])." Meldeliste</a></body></html>";

            if($row['Email']) {
                $mail->addAddress($row['Email'], $row['Vorname']." ".$row['Nachname']);
                if($row['Email2']) {
                    $mail->addAddress($row['Email2'], $row['Vorname']." ".$row['Nachname']);
                }
		try {
                $mail->Send();
            $logentry = new Log;
            $logmessage = sprintf("An: %s %s, Betreff: %s",
            $row['Vorname'],
            $row['Nachname'],
            $this->subject
            );
            $logentry->email($logmessage);
		} catch (Exception $e) {
		      $logentry = new Log;

		      $logmessage = sprintf("Kann Email nicht senden | An: %s %s | Betreff: %s | PHPMailer: %s",
        $row['Vorname'],
        $row['Nachname'],
        $this->subject,
        $mail->ErrorInfo
    );

    $logentry->error($logmessage);
		}
            }
			usleep(100000); // 100 ms Pause
            $i++;
        }
		$mail->smtpClose();
        echo "<div class=\"w3-container ".$GLOBALS['optionsDB']['colorLogEmail']." w3-mobile\"><h3>Es wurden ".$i." Emails versandt.</h3></div>";
    }
}
