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
        $this->recipientSpec = $job->getRecipientSpecArray();
        $groups = isset($this->recipientSpec['groups']) ? $this->recipientSpec['groups'] : array();
        $this->memberonly = in_array('members', $groups, true);
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
            $job->Termin = (int)$this->termin;
            $job->ensureAttachmentDir();
        }
        else {
            $job = new MailJob;
            $job->CreatedBy = $createdBy;
            $job->Subject = $subjectBase;
            $job->BodyText = $text;
            $job->Source = $source;
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
        $queuedEmails = array();
        $queuedUsers = array();
        foreach($recipients as $row) {
            $uid = (int)$row['Index'];
            if($uid > 0 && isset($queuedUsers[$uid])) {
                continue;
            }
            $wantMail = !empty($row['getMail']);
            $wantInbox = !empty($row['notifyInbox']);
            // Single-user system mail: always treat as mail+outbox, ignore prefs
            if($this->User > 0) {
                $wantMail = true;
                $wantInbox = true;
            }
            if(!$wantMail && !$wantInbox) {
                continue;
            }

            $emails = array();
            if(!empty($row['Email'])) {
                $emails[] = trim($row['Email']);
            }
            if(!empty($row['Email2'])) {
                $emails[] = trim($row['Email2']);
            }
            $emails = array_values(array_unique(array_filter($emails)));
            // Skip addresses already queued for this job (same mailbox via another user)
            $fresh = array();
            foreach($emails as $em) {
                $key = strtolower($em);
                if(isset($queuedEmails[$key])) {
                    continue;
                }
                $queuedEmails[$key] = true;
                $fresh[] = $em;
            }
            if($wantMail && !$fresh && !$wantInbox) {
                continue;
            }
            if($uid > 0) {
                $queuedUsers[$uid] = true;
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
            $out->User = $uid;
            $out->ToEmail = ($wantMail && $fresh) ? implode(', ', $fresh) : '';
            $out->Subject = $subjectFull;
            $out->BodyText = $bodyStored;
            $out->Attempts = 0;
            // Inbox-only (or mail without usable address): deliver to Meine Nachrichten without SMTP
            if(!$wantMail || !$fresh) {
                $out->Status = 'sent';
                $out->SentAt = date('Y-m-d H:i:s');
            }
            else {
                $out->Status = 'pending';
            }
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
        if($count > 0) {
            $job->refreshCounts();
        }

        $logentry = new Log;
        if($count > 0) {
            $specLabel = AudienceSpec::formatLabel(
                $job->getRecipientSpecArray(),
                array('allowNamedGroups' => true, 'allowTermine' => true)
            );
            $logentry->email(sprintf(
                "In Warteschlange gestellt | Email-ID: <b>%d</b>, Betreff: <b>%s</b>, Empfänger: <b>%d</b>, Quelle: <b>%s</b>, Auswahl: <b>%s</b>",
                (int)$job->Index,
                htmlspecialchars($subjectFull),
                $count,
                htmlspecialchars($source),
                htmlspecialchars($specLabel)
            ));
        }
        else {
            $logentry->warning(sprintf(
                "Warteschlange leer | Email-ID: <b>%d</b>, Betreff: <b>%s</b>, Quelle: <b>%s</b> (keine Empfänger für E-Mail/Nachrichten)",
                (int)$job->Index,
                htmlspecialchars($subjectFull),
                htmlspecialchars($source)
            ));
        }

        // Success banner removed (MELD-121); overview table shows queue status.
        if(!$this->quiet && $count === 0) {
            echo "<div class=\"w3-container ".$GLOBALS['optionsDB']['colorLogError']." w3-mobile\"><h3>Keine Empfänger für E-Mail oder Nachrichten gefunden.</h3></div>";
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
                'Email-Versand fehlgeschlagen | Outbox-ID: <b>%d</b>, Job fehlt, User-ID: <b>%d</b>',
                (int)$outbox->Index,
                (int)$outbox->User
            ));
            return false;
        }
        if($job->Status === 'cancelled') {
            $outbox->Status = 'cancelled';
            $outbox->LastError = null;
            $outbox->save();
            $logentry = new Log;
            $logentry->warning(sprintf(
                'Email-Versand übersprungen (Job abgebrochen) | Outbox-ID: <b>%d</b>, Email-ID: <b>%d</b>, User-ID: <b>%d</b>',
                (int)$outbox->Index,
                (int)$job->Index,
                (int)$outbox->User
            ));
            return false;
        }

        $user = new User;
        $user->load_by_id($outbox->User);

        $toEmail = trim((string)$outbox->ToEmail);
        $wantSmtp = $toEmail !== '' && (!$user->Index || (int)$user->getMail === 1);
        // Inbox-only / no SMTP: mark delivered without PHPMailer
        if(!$wantSmtp) {
            $outbox->Status = 'sent';
            $outbox->SentAt = date('Y-m-d H:i:s');
            $outbox->LastError = null;
            $outbox->Attempts = (int)$outbox->Attempts + 1;
            $outbox->save();
            $job->refreshCounts();
            return true;
        }

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
                'Email-Versand fehlgeschlagen | Outbox-ID: <b>%d</b>, Email-ID: <b>%d</b>, User-ID: <b>%d</b> — keine gültige Empfängeradresse',
                (int)$outbox->Index,
                (int)$job->Index,
                (int)$outbox->User
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
                    'Email möglicherweise doppelt vermieden | Outbox-ID: <b>%d</b>, Email-ID: <b>%d</b>, User-ID: <b>%d</b> — Status war nicht mehr sending nach Send()',
                    (int)$outbox->Index,
                    (int)$job->Index,
                    (int)$outbox->User
                ));
                $job->refreshCounts();
                return false;
            }
            $outbox->Status = 'sent';
            $outbox->SentAt = date('Y-m-d H:i:s');
            $outbox->LastError = null;

            // Successful sends are not logged individually (MELD-118).
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
                'Email-Versand %s | Outbox-ID: <b>%d</b>, Email-ID: <b>%d</b>, User-ID: <b>%d</b>, Versuch: <b>%d</b>/%d, PHPMailer: <b>%s</b>, Exception: <b>%s</b>',
                $final ? 'endgültig fehlgeschlagen' : 'fehlgeschlagen (wird erneut versucht)',
                (int)$outbox->Index,
                (int)$job->Index,
                (int)$outbox->User,
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
                $parts = array(
                    sprintf(
                        'Mail-Queue Lauf | Batch: <b>%d</b>, beansprucht: <b>%d</b>, gesendet: <b>%d</b>',
                        $batchSize,
                        count($items),
                        $sent
                    ),
                );
                if($failed > 0) {
                    $parts[] = sprintf('fehlgeschlagen/übersprungen: <b>%d</b>', $failed);
                }
                if($reclaimed > 0) {
                    $parts[] = sprintf('hängende sending zurückgeholt: <b>%d</b>', $reclaimed);
                }
                $summary = implode(', ', $parts);
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
     * Close the HTTP response (e.g. after a redirect), then start one queue batch
     * without blocking the browser. Prefer a background CLI worker; fall back to
     * in-request processing after fastcgi_finish_request / flush (MELD-66).
     */
    public static function finishResponseThenProcessQueue() {
        ignore_user_abort(true);
        if(function_exists('session_write_close')) {
            session_write_close();
        }

        $spawned = self::spawnMailQueueWorker();

        if(function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
            if(!$spawned) {
                self::runProcessQueueSafe();
            }
            return;
        }

        if(!headers_sent()) {
            header('Connection: close');
        }
        while(ob_get_level() > 0) {
            @ob_end_flush();
        }
        @flush();

        // Without FPM, flush often does not disconnect the client — only process
        // in-request if we could not spawn a background worker.
        if(!$spawned) {
            self::runProcessQueueSafe();
        }
    }

    /**
     * Start cron.php processMailQueue in the background (non-blocking).
     * @return bool true if a spawn command was issued
     */
    protected static function spawnMailQueueWorker() {
        if(empty($GLOBALS['cronID'])) {
            return false;
        }
        if(!function_exists('exec')) {
            return false;
        }
        $disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));
        if(in_array('exec', $disabled, true)) {
            return false;
        }

        $cron = dirname(__DIR__).DIRECTORY_SEPARATOR.'cron.php';
        if(!is_readable($cron)) {
            return false;
        }

        $php = (defined('PHP_BINARY') && PHP_BINARY !== '' && strpos(PHP_BINARY, 'php') !== false)
            ? PHP_BINARY
            : 'php';
        $cmd = escapeshellarg($php)
            .' '.escapeshellarg($cron)
            .' '.escapeshellarg((string)$GLOBALS['cronID'])
            .' '.escapeshellarg('processMailQueue');

        if(strncasecmp(PHP_OS, 'WIN', 3) === 0) {
            @pclose(@popen('start /B '.$cmd, 'r'));
            return true;
        }

        @exec($cmd.' > /dev/null 2>&1 &');
        return true;
    }

    protected static function runProcessQueueSafe() {
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
     * Count unique recipients for a chip spec or termin (same rules as enqueue).
     * @param array|string|null $spec
     * @param int $termin
     * @return int
     */
    public static function countFromRecipientSpec($spec = null, $termin = 0) {
        $mail = new self;
        $mail->quiet = true;
        $mail->User = 0;
        $mail->termin = (int)$termin;
        if((int)$termin > 0) {
            $mail->recipientSpec = null;
        }
        elseif(is_array($spec)) {
            $mail->recipientSpec = MailJob::parseRecipientSpec(json_encode($spec));
        }
        elseif(is_string($spec) && trim($spec) !== '') {
            $mail->recipientSpec = MailJob::parseRecipientSpec($spec);
        }
        else {
            $mail->recipientSpec = MailJob::defaultRecipientSpecArray();
        }
        return count($mail->resolveRecipients());
    }

    /**
     * Bulk/mail-job recipients: E-Mail und/oder Nachrichten-Kanal aktiv.
     * @return string SQL AND-clauses without leading AND
     */
    protected function mailRecipientBaseWhere($alias = '') {
        $p = $alias !== '' ? $alias.'.' : '';
        return array(
            $p.'`Deleted` != 1',
            '('.$p.'`getMail` = 1 OR '.$p.'`notifyInbox` = 1)',
        );
    }

    /**
     * @return array list of user rows with Index, Vorname, Nachname, Email, Email2, activeLink, getMail, notifyInbox
     */
    protected function resolveRecipients() {
        $rows = array();
        if($this->User > 0) {
            // Einzelversand (System/Benachrichtigung): keine Verteiler-Pflicht
            $sql = sprintf(
                "SELECT `Index`, `Vorname`, `Nachname`, `Email`, `Email2`, `activeLink`, `getMail`, `notifyInbox` FROM `%sUser` WHERE `Index` = %d AND `Deleted` != 1;",
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
            $base = implode(' AND ', $this->mailRecipientBaseWhere());
            $sql = sprintf(
                "SELECT `uIndex` AS `Index`, `Vorname`, `Nachname`, `Email`, `Email2`, `activeLink`, `getMail`, `notifyInbox` FROM `%sMeldungen` INNER JOIN (SELECT `Index` AS `uIndex`, `Vorname`, `activeLink`, `Email`, `Email2`, `Nachname`, `getMail`, `notifyInbox`, `Deleted` FROM `%sUser`) `%sUser` ON `uIndex` = `User` WHERE `Termin` = '%d' AND `Wert` != 2 AND %s;",
                $GLOBALS['dbprefix'],
                $GLOBALS['dbprefix'],
                $GLOBALS['dbprefix'],
                (int)$this->termin,
                $base
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
            return $this->dedupeRecipients($rows);
        }

        $spec = is_array($this->recipientSpec)
            ? AudienceSpec::normalize($this->recipientSpec, array(
                'allowNamedGroups' => true,
                'allowTermine' => true,
                'defaultGroups' => array('musicians'),
                'legacyRegister' => (int)$this->register,
                'legacyMemberOnly' => $this->memberonly ? 1 : 0,
            ))
            : MailJob::parseRecipientSpec($this->recipientSpec, (int)$this->register, $this->memberonly ? 1 : 0);

        if(AudienceSpec::isEmpty($spec)) {
            $spec = MailJob::defaultRecipientSpecArray();
        }

        $userIds = AudienceSpec::resolveUserIds($spec, true);
        if(!count($userIds)) {
            return array();
        }
        $sql = sprintf(
            'SELECT `Index`, `Vorname`, `Nachname`, `Email`, `Email2`, `activeLink`, `getMail`, `notifyInbox` FROM `%sUser` WHERE `Index` IN (%s);',
            $GLOBALS['dbprefix'],
            implode(',', array_map('intval', $userIds))
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $byId = array();
        if($dbr) {
            while($row = mysqli_fetch_array($dbr)) {
                $byId[(int)$row['Index']] = $row;
            }
        }
        // Preserve resolveUserIds order for stable queues
        $rows = array();
        foreach($userIds as $uid) {
            if(isset($byId[$uid])) {
                $rows[] = $byId[$uid];
            }
        }
        return $this->dedupeRecipients($rows);
    }

    /**
     * One recipient row per user id and per primary email (case-insensitive).
     * @param array $rows
     * @return array
     */
    protected function dedupeRecipients($rows) {
        $out = array();
        $seenUsers = array();
        $seenEmails = array();
        foreach($rows as $row) {
            $uid = isset($row['Index']) ? (int)$row['Index'] : 0;
            if($uid > 0) {
                if(isset($seenUsers[$uid])) {
                    continue;
                }
            }
            $emailKey = strtolower(trim((string)(isset($row['Email']) ? $row['Email'] : '')));
            if($emailKey !== '' && isset($seenEmails[$emailKey])) {
                continue;
            }
            if($uid > 0) {
                $seenUsers[$uid] = true;
            }
            if($emailKey !== '') {
                $seenEmails[$emailKey] = true;
            }
            $out[] = $row;
        }
        return $out;
    }
}
?>
