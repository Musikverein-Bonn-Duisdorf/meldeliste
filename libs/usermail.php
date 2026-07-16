<?php
include "PHPMailer/src/PHPMailer.php";
include "PHPMailer/src/SMTP.php";
include "PHPMailer/src/Exception.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Usermail {
    private $_data = array(
        'User' => null,
        'Text' => null,
        'subject' => null,
        'memberonly' => null,
        'sendlink' => null,
        'register' => null,
        'termin' => null,
        'attachments' => false,
        'source' => 'mail',
        'quiet' => false,
    );

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
        case 'source':
        case 'quiet':
            return $this->_data[$key];
        default:
            return null;
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
        case 'source':
            $this->_data[$key] = $val;
            break;
        case 'memberonly':
        case 'sendlink':
        case 'attachments':
        case 'quiet':
            $this->_data[$key] = (bool)$val;
            break;
        default:
            break;
        }
    }

    public function singleUser($userIndex, $subject, $text) {
        $this->User = $userIndex;
        $this->subject = $subject;
        $this->quiet = true;
        if($this->source === null || $this->source === '' || $this->source === 'mail') {
            $this->source = 'single';
        }
        if($userIndex > 0) {
            $this->enqueue($text);
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

    /**
     * Backwards-compatible entry: enqueue into DB queue (no immediate SMTP send).
     */
    public function send($text) {
        return $this->enqueue($text);
    }

    /**
     * Queue an existing draft MailJob (attachments already in job dir).
     * @return int number of outbox rows
     */
    public function enqueueDraft(MailJob $job, $quiet = null) {
        if($quiet !== null) {
            $this->quiet = (bool)$quiet;
        }
        if(!$job->Index || $job->Status !== 'draft') {
            if(!$this->quiet) {
                echo "<div class=\"w3-container ".$GLOBALS['optionsDB']['colorLogError']." w3-mobile\"><h3>Nur Entwürfe können in die Warteschlange gestellt werden.</h3></div>";
            }
            return 0;
        }
        $text = $job->applyGreeting(isset($_SESSION['Vorname']) ? $_SESSION['Vorname'] : '');
        $this->memberonly = (bool)$job->MemberOnly;
        $this->register = (int)$job->Register;
        $this->termin = (int)$job->Termin;
        $this->User = 0;
        $this->subject = (string)$job->Subject;
        $this->source = $job->Source ? (string)$job->Source : 'mail';
        $this->attachments = false;
        return $this->enqueue($text, $job);
    }

    /**
     * Resolve recipients, create/update MailJob + MailOutbox rows.
     * @param string $text body including greeting
     * @param MailJob|null $existingJob draft to promote, or null to create new job
     * @return int number of outbox rows created
     */
    public function enqueue($text, $existingJob = null) {
        $text = (string)$text;
        $this->Text = $text;

        $recipients = $this->resolveRecipients();
        $subjectBase = (string)$this->subject;
        $subjectFull = $GLOBALS['mailconfig']['subjectprefix'].$subjectBase;
        if($this->termin) {
            $Termin = new Termin;
            $Termin->load_by_id($this->termin);
            if($Termin->Index) {
                $subjectFull = $GLOBALS['mailconfig']['subjectprefix']
                    ."[".$Termin->getGermanDate()." ".$Termin->Name."] ".$subjectBase;
            }
        }

        $createdBy = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
        $source = $this->source ? (string)$this->source : 'mail';

        if($existingJob instanceof MailJob && $existingJob->Index) {
            $job = $existingJob;
            $job->Subject = $subjectBase;
            $job->BodyText = $text;
            $job->Source = $source;
            $job->MemberOnly = $this->memberonly ? 1 : 0;
            $job->Register = (int)$this->register;
            $job->Termin = (int)$this->termin;
            $job->ensureAttachmentDir();
        }
        else {
            $job = new MailJob;
            $job->CreatedBy = $createdBy;
            $job->Subject = $subjectBase;
            $job->BodyText = $text;
            $job->Source = $source;
            $job->MemberOnly = $this->memberonly ? 1 : 0;
            $job->Register = (int)$this->register;
            $job->Termin = (int)$this->termin;
            $job->Status = 'queued';
            $job->Total = 0;
            $job->Sent = 0;
            $job->Failed = 0;
            if(!$job->save()) {
                if(!$this->quiet) {
                    echo "<div class=\"w3-container ".$GLOBALS['optionsDB']['colorLogError']." w3-mobile\"><h3>Mail-Job konnte nicht angelegt werden.</h3></div>";
                }
                return 0;
            }
            $job->ensureAttachmentDir();
        }

        $count = 0;
        foreach($recipients as $row) {
            $emails = array();
            if(!empty($row['Email'])) {
                $emails[] = trim($row['Email']);
            }
            if(!empty($row['Email2'])) {
                $emails[] = trim($row['Email2']);
            }
            $emails = array_values(array_unique(array_filter($emails)));
            if(!$emails) {
                continue;
            }

            $bodyStored = "Hallo ".$row['Vorname'].",\n\n".$text;
            $out = new MailOutbox;
            $out->Job = $job->Index;
            $out->User = (int)$row['Index'];
            $out->ToEmail = implode(', ', $emails);
            $out->Subject = $subjectFull;
            $out->BodyText = $bodyStored;
            $out->Status = 'pending';
            $out->Attempts = 0;
            if($out->save()) {
                $count++;
            }
        }

        $job->Total = $count;
        if($count === 0) {
            $job->Status = 'failed';
        }
        else {
            $job->Status = 'queued';
        }
        // Store subject with prefix for history clarity on job
        $job->Subject = $subjectFull;
        $job->save();

        $logentry = new Log;
        if($count > 0) {
            $logentry->email(sprintf(
                "In Warteschlange gestellt | Email-ID: <b>%d</b>, Betreff: <b>%s</b>, Empfänger: <b>%d</b>, Quelle: <b>%s</b>",
                (int)$job->Index,
                htmlspecialchars($subjectFull),
                $count,
                htmlspecialchars($source)
            ));
        }
        else {
            $logentry->warning(sprintf(
                "Warteschlange leer | Email-ID: <b>%d</b>, Betreff: <b>%s</b>, Quelle: <b>%s</b> (keine gültigen Emailadressen)",
                (int)$job->Index,
                htmlspecialchars($subjectFull),
                htmlspecialchars($source)
            ));
        }

        if(!$this->quiet) {
            if($count > 0) {
                echo "<div class=\"w3-container ".$GLOBALS['optionsDB']['colorLogEmail']." w3-mobile\"><h3>".$count." Nachrichten in die Warteschlange gestellt (Email-ID ".$job->Index."). </h3><p>Im Nutzer-Posteingang sofort sichtbar; Versand per PHPMailer asynchron durch den Mail-Queue-Cron.</p></div>";
            }
            else {
                echo "<div class=\"w3-container ".$GLOBALS['optionsDB']['colorLogError']." w3-mobile\"><h3>Keine gültigen Emailadressen gefunden. Kein Versand möglich.</h3></div>";
            }
        }

        return $count;
    }

    /**
     * Send one outbox row via PHPMailer.
     * @return bool
     */
    public function dispatchOne(MailOutbox $outbox) {
        $job = new MailJob;
        $job->load_by_id($outbox->Job);
        if(!$job->Index) {
            $outbox->Status = 'failed';
            $outbox->LastError = 'MailJob fehlt';
            $outbox->Attempts = (int)$outbox->Attempts + 1;
            $outbox->save();
            return false;
        }
        if($job->Status === 'cancelled') {
            $outbox->Status = 'cancelled';
            $outbox->LastError = null;
            $outbox->save();
            return false;
        }

        $user = new User;
        $user->load_by_id($outbox->User);

        $mail = new PHPMailer(true);
        $mail->IsMail();
        $mail->CharSet = 'UTF-8';
        $mail->SMTPDebug = false;
        $mail->setFrom($GLOBALS['mailconfig']['from'], $GLOBALS['mailconfig']['fromName']);
        $mail->IsHTML(true);
        $mail->Subject = $outbox->Subject;

        $style = file_get_contents("styles/w3.css");
        $style .= file_get_contents("styles/w3-colors-highway.css");
        if(is_readable("styles/w3-color-mvd.css")) {
            $style .= file_get_contents("styles/w3-color-mvd.css");
        }
        elseif(is_readable("styles/w3-colors-mvd.css")) {
            $style .= file_get_contents("styles/w3-colors-mvd.css");
        }
        $style .= renderConfigColorCss(false);

        $vorname = $user->Index ? (string)$user->Vorname : '';
        $nachname = $user->Index ? (string)$user->Nachname : '';
        $activeLink = $user->Index ? (string)$user->activeLink : '';
        $anrede = $vorname !== '' ? "Hallo ".$vorname."," : "Hallo,";
        $link = $GLOBALS['optionsDB']['WebSiteURL']."/login.php?alink=".$activeLink;

        // BodyText already contains greeting; strip duplicate greeting for HTML if present
        $plain = (string)$outbox->BodyText;
        $plainForHtml = $plain;
        $prefix = $anrede."\n\n";
        if(strpos($plainForHtml, $prefix) === 0) {
            $plainForHtml = substr($plainForHtml, strlen($prefix));
        }

        $mail->Body = "<html><head><style>".$style."</style></head><body>"
            ."<div class=\"w3-container ".$GLOBALS['optionsDB']['colorTitle']." w3-mobile\"><h1>".$GLOBALS['optionsDB']['WebSiteName']."</h1></div>"
            ."<div class=\"w3-container\"><p>".$anrede."<br /><br />".nl2br($plainForHtml)."</p></div>"
            ."<a class=\"w3-btn w3-mobile ".$GLOBALS['optionsDB']['colorBtnSubmit']." w3-content\" href=\"".$link."\">zu ".genitiv($vorname !== '' ? $vorname : 'deiner')." Meldeliste</a>"
            ."</body></html>";

        $addrs = array_map('trim', explode(',', (string)$outbox->ToEmail));
        $addrs = array_values(array_filter($addrs));
        foreach($addrs as $addr) {
            $mail->addAddress($addr, trim($vorname.' '.$nachname));
        }

        if($job->AttachmentPath && is_dir($job->AttachmentPath)) {
            $files = scandir($job->AttachmentPath);
            if(is_array($files)) {
                foreach($files as $file) {
                    if($file === '.' || $file === '..' || $file === 'README') continue;
                    $full = $job->AttachmentPath.'/'.$file;
                    if(is_file($full)) {
                        $mail->addAttachment($full);
                    }
                }
            }
        }

        $outbox->Attempts = (int)$outbox->Attempts + 1;
        try {
            $mail->Send();
            $outbox->Status = 'sent';
            $outbox->SentAt = date('Y-m-d H:i:s');
            $outbox->LastError = null;
            $outbox->save();

            $logentry = new Log;
            $logentry->email(sprintf(
                "An: %s %s, Betreff: %s",
                $vorname,
                $nachname,
                $outbox->Subject
            ));
            $job->refreshCounts();
            usleep(100000);
            return true;
        }
        catch(Throwable $e) {
            $maxAttempts = isset($GLOBALS['optionsDB']['cronMailQueueMaxAttempts'])
                ? (int)$GLOBALS['optionsDB']['cronMailQueueMaxAttempts']
                : 3;
            $err = $e->getMessage();
            if($mail->ErrorInfo) {
                $err = $mail->ErrorInfo.' | '.$err;
            }
            $outbox->LastError = $err;
            if($outbox->Attempts >= $maxAttempts) {
                $outbox->Status = 'failed';
            }
            else {
                $outbox->Status = 'pending';
            }
            $outbox->save();

            $logentry = new Log;
            $logentry->error(sprintf(
                "Kann Email nicht senden | An: <b>%s %s</b> | Betreff: <b>%s</b> | PHPMailer: <b>%s</b> | Exception: <b>%s</b>",
                htmlspecialchars($vorname),
                htmlspecialchars($nachname),
                htmlspecialchars((string)$outbox->Subject),
                htmlspecialchars((string)$mail->ErrorInfo),
                htmlspecialchars($e->getMessage())
            ));
            try {
                $mail->smtpClose();
            }
            catch(Throwable $ignored) {
            }
            $job->refreshCounts();
            usleep(100000);
            return false;
        }
    }

    /**
     * Process a batch of pending outbox rows.
     * @return array{processed:int,sent:int,failed:int}
     */
    public static function processQueue($batchSize = null, $maxAttempts = null) {
        $batchSize = $batchSize !== null
            ? (int)$batchSize
            : (isset($GLOBALS['optionsDB']['cronMailQueueBatchSize']) ? (int)$GLOBALS['optionsDB']['cronMailQueueBatchSize'] : 20);
        $maxAttempts = $maxAttempts !== null
            ? (int)$maxAttempts
            : (isset($GLOBALS['optionsDB']['cronMailQueueMaxAttempts']) ? (int)$GLOBALS['optionsDB']['cronMailQueueMaxAttempts'] : 3);

        $items = MailOutbox::claimPending($batchSize, $maxAttempts);
        $sent = 0;
        $failed = 0;
        $mailer = new Usermail;
        foreach($items as $item) {
            if($mailer->dispatchOne($item)) {
                $sent++;
            }
            else {
                $failed++;
            }
        }
        return array(
            'processed' => count($items),
            'sent' => $sent,
            'failed' => $failed,
        );
    }

    /**
     * @return array list of user rows with Index, Vorname, Nachname, Email, Email2, activeLink
     */
    protected function resolveRecipients() {
        $rows = array();
        if($this->User > 0) {
            $sql = sprintf(
                "SELECT `Index`, `Vorname`, `Nachname`, `Email`, `Email2`, `activeLink` FROM `%sUser` WHERE `Index` = %d AND `Deleted` != 1;",
                $GLOBALS['dbprefix'],
                (int)$this->User
            );
        }
        elseif($this->termin) {
            $sql = sprintf(
                "SELECT `uIndex` AS `Index`, `Vorname`, `Nachname`, `Email`, `Email2`, `activeLink` FROM `%sMeldungen` INNER JOIN (SELECT `Index` AS `uIndex`, `Vorname`, `activeLink`, `Email`, `Email2`, `Nachname` FROM `%sUser`) `%sUser` ON `uIndex` = `User` WHERE `Termin` = '%d' AND `Wert` != 2;",
                $GLOBALS['dbprefix'],
                $GLOBALS['dbprefix'],
                $GLOBALS['dbprefix'],
                (int)$this->termin
            );
        }
        else {
            $register = '';
            if($this->register > 0) {
                $register = sprintf("AND `Register` = %d", (int)$this->register);
                if($this->memberonly) {
                    $sql = sprintf(
                        "SELECT `Index`, `Vorname`, `Nachname`, `Email`, `Email2`, `activeLink` FROM `%sUser` INNER JOIN (SELECT `Index` AS `iIndex`, `Register` FROM `%sInstrument`) `%sInstrument` ON `iIndex` = `Instrument` WHERE `getMail` = 1 AND `Email` != '' AND `Mitglied` = 1 AND `Deleted` != 1 %s;",
                        $GLOBALS['dbprefix'],
                        $GLOBALS['dbprefix'],
                        $GLOBALS['dbprefix'],
                        $register
                    );
                }
                else {
                    $sql = sprintf(
                        "SELECT `Index`, `Vorname`, `Nachname`, `Email`, `Email2`, `activeLink` FROM `%sUser` INNER JOIN (SELECT `Index` AS `iIndex`, `Register` FROM `%sInstrument`) `%sInstrument` ON `iIndex` = `Instrument` WHERE `getMail` = 1 AND `Email` != '' AND `Deleted` != 1 %s;",
                        $GLOBALS['dbprefix'],
                        $GLOBALS['dbprefix'],
                        $GLOBALS['dbprefix'],
                        $register
                    );
                }
            }
            elseif($this->memberonly) {
                $sql = sprintf(
                    "SELECT `Index`, `Vorname`, `Nachname`, `Email`, `Email2`, `activeLink` FROM `%sUser` WHERE `getMail` = 1 AND `Email` != '' AND `Mitglied` = 1 AND `Deleted` != 1;",
                    $GLOBALS['dbprefix']
                );
            }
            else {
                $sql = sprintf(
                    "SELECT `Index`, `Vorname`, `Nachname`, `Email`, `Email2`, `activeLink` FROM `%sUser` WHERE `getMail` = 1 AND `Email` != '' AND `Deleted` != 1;",
                    $GLOBALS['dbprefix']
                );
            }
        }

        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return $rows;
        while($row = mysqli_fetch_array($dbr)) {
            // Normalize Index for termin join
            if(!isset($row['Index']) && isset($row['uIndex'])) {
                $row['Index'] = $row['uIndex'];
            }
            $rows[] = $row;
        }
        return $rows;
    }
}
?>
