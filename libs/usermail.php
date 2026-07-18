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
        'recipientSpec' => null,
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
        case 'recipientSpec':
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
        case 'recipientSpec':
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
        $this->recipientSpec = $job->getRecipientSpecArray();
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

            if(function_exists('mailBodyLooksLikeHtml') && mailBodyLooksLikeHtml($text)) {
                $anredeHtml = '<p>Hallo '.htmlspecialchars((string)$row['Vorname'], ENT_QUOTES, 'UTF-8').',</p>';
                $bodyStored = $anredeHtml.(function_exists('sanitizeMailHtml') ? sanitizeMailHtml($text) : $text);
            }
            else {
                $bodyStored = "Hallo ".$row['Vorname'].",\n\n".$text;
            }
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
                echo "<div class=\"w3-container ".$GLOBALS['optionsDB']['colorLogEmail']." w3-mobile\"><h3>".$count." Nachrichten in die Warteschlange gestellt (Email-ID ".$job->Index."). </h3></div>";
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
            $logentry = new Log;
            $logentry->error(sprintf(
                'Email-Versand fehlgeschlagen | Outbox-ID: <b>%d</b>, Job fehlt, An: <b>%s</b>, Betreff: <b>%s</b>',
                (int)$outbox->Index,
                htmlspecialchars((string)$outbox->ToEmail),
                htmlspecialchars((string)$outbox->Subject)
            ));
            return false;
        }
        if($job->Status === 'cancelled') {
            $outbox->Status = 'cancelled';
            $outbox->LastError = null;
            $outbox->save();
            $logentry = new Log;
            $logentry->warning(sprintf(
                'Email-Versand übersprungen (Job abgebrochen) | Outbox-ID: <b>%d</b>, Email-ID: <b>%d</b>, An: <b>%s</b>',
                (int)$outbox->Index,
                (int)$job->Index,
                htmlspecialchars((string)$outbox->ToEmail)
            ));
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

        $style = '';
        foreach(array('styles/w3.css', 'styles/w3-colors-highway.css', 'styles/w3-color-mvd.css', 'styles/w3-colors-mvd.css') as $cssFile) {
            if(is_readable($cssFile)) {
                $style .= file_get_contents($cssFile);
            }
        }
        if(function_exists('renderConfigColorCss')) {
            $style .= renderConfigColorCss(false);
        }

        $vorname = $user->Index ? (string)$user->Vorname : '';
        $nachname = $user->Index ? (string)$user->Nachname : '';
        $activeLink = $user->Index ? (string)$user->activeLink : '';
        $anrede = $vorname !== '' ? "Hallo ".$vorname."," : "Hallo,";
        $link = $GLOBALS['optionsDB']['WebSiteURL']."/login.php?alink=".$activeLink;

        // BodyText already contains greeting; strip duplicate greeting for HTML if present
        $bodyInner = function_exists('stripMailBodyGreeting')
            ? stripMailBodyGreeting((string)$outbox->BodyText, $vorname)
            : (string)$outbox->BodyText;
        $bodyHtml = function_exists('formatMailBodyForEmail')
            ? formatMailBodyForEmail($bodyInner)
            : nl2br($bodyInner);
        $mail->AltBody = trim(html_entity_decode(strip_tags(str_replace(array('<br>', '<br/>', '<br />', '</p>'), array("\n", "\n", "\n", "\n\n"), $anrede."\n\n".$bodyInner)), ENT_QUOTES, 'UTF-8'));

        $mail->Body = "<html><head><style>".$style."</style></head><body>"
            ."<div class=\"w3-container ".$GLOBALS['optionsDB']['colorTitle']." w3-mobile\"><h1>".$GLOBALS['optionsDB']['WebSiteName']."</h1></div>"
            ."<div class=\"w3-container\"><p>".htmlspecialchars($anrede, ENT_QUOTES, 'UTF-8')."</p>".$bodyHtml."</div>"
            ."<a class=\"w3-btn w3-mobile ".$GLOBALS['optionsDB']['colorBtnSubmit']." w3-content\" href=\"".$link."\">zu ".genitiv($vorname !== '' ? $vorname : 'deiner')." Meldeliste</a>"
            ."</body></html>";

        $addrs = array_map('trim', explode(',', (string)$outbox->ToEmail));
        $addrs = array_values(array_filter($addrs));
        if(!$addrs) {
            $outbox->Status = 'failed';
            $outbox->LastError = 'Keine gültige Empfängeradresse';
            $outbox->Attempts = (int)$outbox->Attempts + 1;
            $outbox->save();
            $logentry = new Log;
            $logentry->error(sprintf(
                'Email-Versand fehlgeschlagen | Outbox-ID: <b>%d</b>, Email-ID: <b>%d</b>, User: <b>%s %s</b> — keine gültige Empfängeradresse',
                (int)$outbox->Index,
                (int)$job->Index,
                htmlspecialchars($vorname),
                htmlspecialchars($nachname)
            ));
            $job->refreshCounts();
            return false;
        }
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
        $maxAttempts = isset($GLOBALS['optionsDB']['cronMailQueueMaxAttempts'])
            ? (int)$GLOBALS['optionsDB']['cronMailQueueMaxAttempts']
            : 3;

        // Still claimed by this worker?
        $chk = sprintf(
            'SELECT `Status` FROM `%sMailOutbox` WHERE `Index` = %d;',
            $GLOBALS['dbprefix'],
            (int)$outbox->Index
        );
        $chkR = mysqli_query($GLOBALS['conn'], $chk);
        $chkRow = $chkR ? mysqli_fetch_assoc($chkR) : null;
        if(!$chkRow || $chkRow['Status'] !== 'sending') {
            $logentry = new Log;
            $logentry->warning(sprintf(
                'Email-Versand abgebrochen (Status nicht mehr sending) | Outbox-ID: <b>%d</b>, Status: <b>%s</b>',
                (int)$outbox->Index,
                htmlspecialchars($chkRow ? (string)$chkRow['Status'] : '—')
            ));
            return false;
        }

        try {
            $mail->Send();
            $mark = sprintf(
                'UPDATE `%sMailOutbox` SET `Status` = "sent", `SentAt` = NOW(), `LastError` = NULL, `LockedAt` = NULL, `Attempts` = %d WHERE `Index` = %d AND `Status` = "sending";',
                $GLOBALS['dbprefix'],
                (int)$outbox->Attempts,
                (int)$outbox->Index
            );
            $markOk = mysqli_query($GLOBALS['conn'], $mark);
            if(!$markOk || mysqli_affected_rows($GLOBALS['conn']) !== 1) {
                $logentry = new Log;
                $logentry->warning(sprintf(
                    'Email möglicherweise doppelt vermieden | Outbox-ID: <b>%d</b>, Email-ID: <b>%d</b>, An: <b>%s</b> — Status war nicht mehr sending nach Send()',
                    (int)$outbox->Index,
                    (int)$job->Index,
                    htmlspecialchars(implode(', ', $addrs))
                ));
                $job->refreshCounts();
                return false;
            }
            $outbox->Status = 'sent';
            $outbox->SentAt = date('Y-m-d H:i:s');
            $outbox->LastError = null;

            $logentry = new Log;
            $logentry->email(sprintf(
                'Email versendet | Outbox-ID: <b>%d</b>, Email-ID: <b>%d</b>, An: <b>%s %s</b> &lt;%s&gt;, Betreff: <b>%s</b>',
                (int)$outbox->Index,
                (int)$job->Index,
                htmlspecialchars($vorname),
                htmlspecialchars($nachname),
                htmlspecialchars(implode(', ', $addrs)),
                htmlspecialchars((string)$outbox->Subject)
            ));
            $job->refreshCounts();
            usleep(100000);
            return true;
        }
        catch(Throwable $e) {
            $err = $e->getMessage();
            if($mail->ErrorInfo) {
                $err = $mail->ErrorInfo.' | '.$err;
            }
            $outbox->LastError = $err;
            $final = $outbox->Attempts >= $maxAttempts;
            if($final) {
                $outbox->Status = 'failed';
            }
            else {
                $outbox->Status = 'pending';
            }
            $outbox->LockedAt = null;
            $outbox->save();

            $logentry = new Log;
            $msg = sprintf(
                'Email-Versand %s | Outbox-ID: <b>%d</b>, Email-ID: <b>%d</b>, An: <b>%s %s</b> &lt;%s&gt;, Betreff: <b>%s</b>, Versuch: <b>%d</b>/%d, PHPMailer: <b>%s</b>, Exception: <b>%s</b>',
                $final ? 'endgültig fehlgeschlagen' : 'fehlgeschlagen (wird erneut versucht)',
                (int)$outbox->Index,
                (int)$job->Index,
                htmlspecialchars($vorname),
                htmlspecialchars($nachname),
                htmlspecialchars(implode(', ', $addrs)),
                htmlspecialchars((string)$outbox->Subject),
                (int)$outbox->Attempts,
                $maxAttempts,
                htmlspecialchars((string)$mail->ErrorInfo),
                htmlspecialchars($e->getMessage())
            );
            if($final) {
                $logentry->error($msg);
            }
            else {
                $logentry->warning($msg);
            }
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
     * File lock (reliable on shared hosting) + stale LockedAt reclaim.
     * @return array{processed:int,sent:int,failed:int,reclaimed:int,batchSize:int,skipped:bool}
     */
    public static function processQueue($batchSize = null, $maxAttempts = null) {
        $batchSize = $batchSize !== null
            ? (int)$batchSize
            : (isset($GLOBALS['optionsDB']['cronMailQueueBatchSize']) ? (int)$GLOBALS['optionsDB']['cronMailQueueBatchSize'] : 20);
        $maxAttempts = $maxAttempts !== null
            ? (int)$maxAttempts
            : (isset($GLOBALS['optionsDB']['cronMailQueueMaxAttempts']) ? (int)$GLOBALS['optionsDB']['cronMailQueueMaxAttempts'] : 3);

        $empty = array(
            'processed' => 0,
            'sent' => 0,
            'failed' => 0,
            'reclaimed' => 0,
            'batchSize' => $batchSize,
            'skipped' => false,
        );

        $lockPath = dirname(__DIR__).'/uploads/.mail_queue.lock';
        if(!is_dir(dirname($lockPath))) {
            @mkdir(dirname($lockPath), 0755, true);
        }
        $lockFp = @fopen($lockPath, 'c+');
        if(!$lockFp || !flock($lockFp, LOCK_EX | LOCK_NB)) {
            if($lockFp) {
                fclose($lockFp);
            }
            $logentry = new Log;
            $logentry->info('Mail-Queue Lauf übersprungen (Datei-Lock aktiv — anderer Worker)');
            $empty['skipped'] = true;
            return $empty;
        }

        try {
            // Only reclaim rows stuck longer than 5 minutes (never steal an active send).
            $reclaimed = MailOutbox::reclaimStuckSending(5);
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

            $result = array(
                'processed' => count($items),
                'sent' => $sent,
                'failed' => $failed,
                'reclaimed' => $reclaimed,
                'batchSize' => $batchSize,
                'skipped' => false,
            );

            if($reclaimed > 0 || count($items) > 0) {
                $logentry = new Log;
                $summary = sprintf(
                    'Mail-Queue Lauf | Batch: <b>%d</b>, beansprucht: <b>%d</b>, gesendet: <b>%d</b>, fehlgeschlagen/übersprungen: <b>%d</b>, hängende sending zurückgeholt: <b>%d</b>',
                    $batchSize,
                    count($items),
                    $sent,
                    $failed,
                    $reclaimed
                );
                if($failed > 0) {
                    $logentry->warning($summary);
                }
                else {
                    $logentry->info($summary);
                }
            }

            return $result;
        }
        finally {
            flock($lockFp, LOCK_UN);
            fclose($lockFp);
        }
    }

    /**
     * Close the HTTP response (e.g. after a redirect), then process one queue batch.
     * Used so Absenden can start SMTP without delaying the overview reload (MELD-66).
     */
    public static function finishResponseThenProcessQueue() {
        ignore_user_abort(true);
        if(function_exists('session_write_close')) {
            session_write_close();
        }

        if(function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        else {
            if(!headers_sent()) {
                header('Content-Length: 0');
                header('Connection: close');
            }
            while(ob_get_level() > 0) {
                ob_end_flush();
            }
            flush();
        }

        try {
            self::processQueue();
        }
        catch(Throwable $e) {
            $logentry = new Log;
            $logentry->error(sprintf(
                'Mail-Queue Sofortlauf fehlgeschlagen | Exception: <b>%s</b>',
                htmlspecialchars($e->getMessage())
            ));
        }
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
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
            sqlerror();
            if(!$dbr) return $rows;
            while($row = mysqli_fetch_array($dbr)) {
                $rows[] = $row;
            }
            return $rows;
        }
        if($this->termin) {
            $sql = sprintf(
                "SELECT `uIndex` AS `Index`, `Vorname`, `Nachname`, `Email`, `Email2`, `activeLink` FROM `%sMeldungen` INNER JOIN (SELECT `Index` AS `uIndex`, `Vorname`, `activeLink`, `Email`, `Email2`, `Nachname` FROM `%sUser`) `%sUser` ON `uIndex` = `User` WHERE `Termin` = '%d' AND `Wert` != 2;",
                $GLOBALS['dbprefix'],
                $GLOBALS['dbprefix'],
                $GLOBALS['dbprefix'],
                (int)$this->termin
            );
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
            sqlerror();
            if(!$dbr) return $rows;
            while($row = mysqli_fetch_array($dbr)) {
                if(!isset($row['Index']) && isset($row['uIndex'])) {
                    $row['Index'] = $row['uIndex'];
                }
                $rows[] = $row;
            }
            return $rows;
        }

        $spec = is_array($this->recipientSpec)
            ? $this->recipientSpec
            : MailJob::parseRecipientSpec($this->recipientSpec, (int)$this->register);

        $byId = array();
        $memberSql = $this->memberonly ? ' AND `Mitglied` = 1' : '';
        $allRegisters = !empty($spec['allRegisters']);
        $registerIds = isset($spec['registers']) ? $spec['registers'] : array();
        $userIds = isset($spec['users']) ? $spec['users'] : array();

        if($allRegisters) {
            $sql = sprintf(
                "SELECT `Index`, `Vorname`, `Nachname`, `Email`, `Email2`, `activeLink` FROM `%sUser` WHERE `getMail` = 1 AND `Email` != '' AND `Deleted` != 1%s;",
                $GLOBALS['dbprefix'],
                $memberSql
            );
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
            sqlerror();
            if($dbr) {
                while($row = mysqli_fetch_array($dbr)) {
                    $byId[(int)$row['Index']] = $row;
                }
            }
        }
        elseif(count($registerIds) > 0) {
            $ids = array();
            foreach($registerIds as $rid) {
                $rid = (int)$rid;
                if($rid > 0) $ids[] = $rid;
            }
            if(count($ids)) {
                $sql = sprintf(
                    "SELECT `Index`, `Vorname`, `Nachname`, `Email`, `Email2`, `activeLink` FROM `%sUser` INNER JOIN (SELECT `Index` AS `iIndex`, `Register` FROM `%sInstrument`) `%sInstrument` ON `iIndex` = `Instrument` WHERE `getMail` = 1 AND `Email` != '' AND `Deleted` != 1%s AND `Register` IN (%s);",
                    $GLOBALS['dbprefix'],
                    $GLOBALS['dbprefix'],
                    $GLOBALS['dbprefix'],
                    $memberSql,
                    implode(',', $ids)
                );
                $dbr = mysqli_query($GLOBALS['conn'], $sql);
                sqlerror();
                if($dbr) {
                    while($row = mysqli_fetch_array($dbr)) {
                        $byId[(int)$row['Index']] = $row;
                    }
                }
            }
        }

        // Explizit gewählte User (Union); Email Pflicht, getMail nicht zwingend
        if(count($userIds) > 0) {
            $ids = array();
            foreach($userIds as $uid) {
                $uid = (int)$uid;
                if($uid > 0) $ids[] = $uid;
            }
            if(count($ids)) {
                $sql = sprintf(
                    "SELECT `Index`, `Vorname`, `Nachname`, `Email`, `Email2`, `activeLink` FROM `%sUser` WHERE `Index` IN (%s) AND `Deleted` != 1 AND `Email` != '';",
                    $GLOBALS['dbprefix'],
                    implode(',', $ids)
                );
                $dbr = mysqli_query($GLOBALS['conn'], $sql);
                sqlerror();
                if($dbr) {
                    while($row = mysqli_fetch_array($dbr)) {
                        $byId[(int)$row['Index']] = $row;
                    }
                }
            }
        }

        return array_values($byId);
    }
}
?>
