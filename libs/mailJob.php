<?php
class MailJob
{
    private $_data = array(
        'Index' => null,
        'Created' => null,
        'CreatedBy' => 0,
        'Subject' => null,
        'BodyText' => null,
        'Source' => 'mail',
        'MemberOnly' => 0,
        'Register' => 0,
        'Termin' => 0,
        'Gruss' => 1,
        'PostDiscord' => 0,
        'AttachmentPath' => null,
        'Status' => 'draft',
        'Total' => 0,
        'Sent' => 0,
        'Failed' => 0,
        'QueueCreated' => null,
    );

    public function __get($key) {
        if(array_key_exists($key, $this->_data)) {
            return $this->_data[$key];
        }
        return null;
    }

    public function __set($key, $val) {
        switch($key) {
        case 'Index':
        case 'CreatedBy':
        case 'MemberOnly':
        case 'Register':
        case 'Termin':
        case 'Gruss':
        case 'PostDiscord':
        case 'Total':
        case 'Sent':
        case 'Failed':
            $this->_data[$key] = (int)$val;
            break;
        case 'Subject':
        case 'BodyText':
        case 'Source':
        case 'AttachmentPath':
        case 'Status':
        case 'Created':
        case 'QueueCreated':
            $this->_data[$key] = $val === null ? null : trim((string)$val);
            break;
        default:
            break;
        }
    }

    public function is_valid() {
        if($this->Status === null || $this->Status === '') return false;
        if($this->Status !== 'draft' && ($this->Subject === null || $this->Subject === '')) return false;
        return true;
    }

    public function save() {
        if(!$this->is_valid()) return false;
        if($this->Index > 0) {
            return $this->update();
        }
        return $this->insert();
    }

    /**
     * Ensure MailJob / MailOutbox tables (and columns) exist.
     * Also migrate text columns to utf8mb4 so WYSIWYG/Unicode (Sonderzeichen) can be stored.
     */
    public static function ensureSchema() {
        static $done = false;
        if($done) return true;
        $table = new SQLtable('MailJob');
        $outbox = new SQLtable('MailOutbox');
        $needs = !$table->exists() || !$outbox->exists()
            || !$table->columnExists('Gruss')
            || !$table->columnExists('PostDiscord')
            || !$outbox->columnExists('ReadAt')
            || !$outbox->columnExists('LockedAt');
        if($needs) {
            $manager = new DatabaseManager();
            $manager->create();
            $manager->repair();
        }
        self::ensureUtf8mb4();
        $done = true;
        return (new SQLtable('MailJob'))->exists() && (new SQLtable('MailOutbox'))->exists();
    }

    /**
     * Mail tables were created as latin1; TinyMCE bodies need utf8mb4.
     */
    public static function ensureUtf8mb4() {
        static $utfDone = false;
        if($utfDone || !isset($GLOBALS['conn']) || !isset($GLOBALS['dbprefix'])) {
            return;
        }
        foreach(array('MailJob', 'MailOutbox') as $short) {
            $name = $GLOBALS['dbprefix'].$short;
            $check = mysqli_query(
                $GLOBALS['conn'],
                "SELECT `TABLE_COLLATION` AS `c` FROM INFORMATION_SCHEMA.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '".mysqli_real_escape_string($GLOBALS['conn'], $name)."' LIMIT 1"
            );
            $row = $check ? mysqli_fetch_assoc($check) : null;
            $collation = $row && isset($row['c']) ? (string)$row['c'] : '';
            if($collation !== '' && stripos($collation, 'utf8mb4') === 0) {
                continue;
            }
            mysqli_query(
                $GLOBALS['conn'],
                'ALTER TABLE `'.str_replace('`', '``', $name).'` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
            );
            // ignore errors (missing table / insufficient privileges); next save will surface issues
        }
        $utfDone = true;
    }

    /**
     * Create a new draft with fixed ID and dedicated attachment directory.
     */
    public static function createDraft($createdBy = 0, $termin = 0) {
        self::ensureSchema();
        $job = new MailJob;
        $job->CreatedBy = (int)$createdBy;
        $job->Subject = '';
        $job->BodyText = '';
        $job->Source = 'mail';
        $job->Termin = (int)$termin;
        $job->Status = 'draft';
        $job->Gruss = 1;
        $job->PostDiscord = ((int)$termin === 0) ? 1 : 0;
        if(!$job->save()) {
            return null;
        }
        $job->ensureAttachmentDir();
        return $job;
    }

    public function ensureAttachmentDir() {
        if(!$this->Index) return false;
        $path = 'uploads/mailjob_'.(int)$this->Index;
        if(!is_dir($path)) {
            if(!@mkdir($path, 0755, true)) {
                return false;
            }
        }
        if($this->AttachmentPath !== $path) {
            $this->AttachmentPath = $path;
            $this->update();
        }
        return true;
    }

    protected function insert() {
        $sql = sprintf(
            'INSERT INTO `%sMailJob` (`CreatedBy`, `Subject`, `BodyText`, `Source`, `MemberOnly`, `Register`, `Termin`, `Gruss`, `PostDiscord`, `AttachmentPath`, `Status`, `Total`, `Sent`, `Failed`) VALUES (%d, "%s", "%s", "%s", %d, %d, %d, %d, %d, %s, "%s", %d, %d, %d);',
            $GLOBALS['dbprefix'],
            (int)$this->CreatedBy,
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Subject),
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->BodyText),
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Source),
            (int)$this->MemberOnly,
            (int)$this->Register,
            (int)$this->Termin,
            (int)$this->Gruss,
            (int)$this->PostDiscord,
            $this->AttachmentPath === null || $this->AttachmentPath === ''
                ? 'NULL'
                : '"'.mysqli_real_escape_string($GLOBALS['conn'], (string)$this->AttachmentPath).'"',
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Status),
            (int)$this->Total,
            (int)$this->Sent,
            (int)$this->Failed
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }

    protected function update() {
        $sql = sprintf(
            'UPDATE `%sMailJob` SET `CreatedBy`=%d, `Subject`="%s", `BodyText`="%s", `Source`="%s", `MemberOnly`=%d, `Register`=%d, `Termin`=%d, `Gruss`=%d, `PostDiscord`=%d, `AttachmentPath`=%s, `Status`="%s", `Total`=%d, `Sent`=%d, `Failed`=%d WHERE `Index`=%d;',
            $GLOBALS['dbprefix'],
            (int)$this->CreatedBy,
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Subject),
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->BodyText),
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Source),
            (int)$this->MemberOnly,
            (int)$this->Register,
            (int)$this->Termin,
            (int)$this->Gruss,
            (int)$this->PostDiscord,
            $this->AttachmentPath === null || $this->AttachmentPath === ''
                ? 'NULL'
                : '"'.mysqli_real_escape_string($GLOBALS['conn'], (string)$this->AttachmentPath).'"',
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Status),
            (int)$this->Total,
            (int)$this->Sent,
            (int)$this->Failed,
            (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        return (bool)$dbr;
    }

    public function load_by_id($Index) {
        self::ensureSchema();
        $Index = (int)$Index;
        $sql = sprintf(
            'SELECT j.*, (SELECT MIN(o.`Created`) FROM `%sMailOutbox` o WHERE o.`Job` = j.`Index`) AS `QueueCreated`
             FROM `%sMailJob` j WHERE j.`Index` = %d;',
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = $dbr ? mysqli_fetch_array($dbr) : null;
        if(is_array($row)) {
            $this->fill_from_array($row);
        }
    }

    public function fill_from_array($row) {
        foreach($row as $key => $val) {
            if(array_key_exists($key, $this->_data)) {
                $this->_data[$key] = $val;
            }
        }
    }

    public function deleteDraft() {
        if($this->Status !== 'draft' || !$this->Index) return false;
        $this->cleanupAttachments();
        $sql = sprintf('DELETE FROM `%sMailJob` WHERE `Index` = %d AND `Status` = "draft";', $GLOBALS['dbprefix'], (int)$this->Index);
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if($dbr) {
            $logentry = new Log;
            $logentry->DBdelete(sprintf('Email-Entwurf gelöscht | Email-ID: <b>%d</b>', (int)$this->Index));
            $this->_data['Index'] = null;
            return true;
        }
        return false;
    }

    public function canCancel() {
        return $this->Index && in_array((string)$this->Status, array('queued', 'processing'), true);
    }

    public function canDelete() {
        if(!$this->Index) return false;
        if((string)$this->Status === 'draft') return true;
        return (int)$this->Sent === 0;
    }

    /**
     * Stop further PHPMailer sends; already sent outbox rows stay.
     */
    public function cancel() {
        if(!$this->canCancel()) return false;
        $sql = sprintf(
            'UPDATE `%sMailOutbox` SET `Status` = "cancelled" WHERE `Job` = %d AND `Status` IN ("pending", "sending");',
            $GLOBALS['dbprefix'],
            (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;

        $this->Status = 'cancelled';
        $this->update();
        $this->refreshCounts();

        $logentry = new Log;
        $logentry->email(sprintf(
            'Email-Versand abgebrochen | Email-ID: <b>%d</b>, Betreff: <b>%s</b>, bereits gesendet: <b>%d</b>/%d',
            (int)$this->Index,
            htmlspecialchars((string)$this->Subject),
            (int)$this->Sent,
            (int)$this->Total
        ));
        return true;
    }

    /**
     * Delete draft, or any job that has not yet reached any recipient (Sent = 0).
     */
    public function deleteCompletely() {
        if(!$this->canDelete()) return false;
        if((string)$this->Status === 'draft') {
            return $this->deleteDraft();
        }

        $id = (int)$this->Index;
        $subject = (string)$this->Subject;
        $sqlOut = sprintf('DELETE FROM `%sMailOutbox` WHERE `Job` = %d;', $GLOBALS['dbprefix'], $id);
        $dbrOut = mysqli_query($GLOBALS['conn'], $sqlOut);
        sqlerror();
        if(!$dbrOut) return false;

        $this->cleanupAttachments();
        $sql = sprintf('DELETE FROM `%sMailJob` WHERE `Index` = %d;', $GLOBALS['dbprefix'], $id);
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;

        $logentry = new Log;
        $logentry->DBdelete(sprintf(
            'Email gelöscht (noch nicht versendet) | Email-ID: <b>%d</b>, Betreff: <b>%s</b>',
            $id,
            htmlspecialchars($subject)
        ));
        $this->_data['Index'] = null;
        return true;
    }

    public function applyGreeting($vornameSession = '') {
        $body = (string)$this->BodyText;
        $gruss = (int)$this->Gruss;
        $asHtml = function_exists('mailBodyLooksLikeHtml') && mailBodyLooksLikeHtml($body);
        $suffix = '';
        if($asHtml) {
            $safeName = htmlspecialchars((string)$vornameSession, ENT_QUOTES, 'UTF-8');
            $safeGreet = htmlspecialchars((string)$GLOBALS['optionsDB']['MailGreetings'], ENT_QUOTES, 'UTF-8');
            if($gruss === 1) {
                $suffix = '<p>Viele Grüße<br>'.$safeName.'</p>';
            }
            elseif($gruss === 2) {
                $suffix = '<p>Viele Grüße<br>der Vorstand</p>';
            }
            elseif($gruss === 3) {
                $suffix = '<p>Viele Grüße<br>'.$safeGreet.'</p>';
            }
            elseif($gruss === 4) {
                $suffix = '<p>'.$safeName.'</p>';
            }
            return $body.$suffix;
        }
        if($gruss === 1) {
            $suffix = "\n\nViele Grüße\n".$vornameSession;
        }
        elseif($gruss === 2) {
            $suffix = "\n\nViele Grüße\nder Vorstand";
        }
        elseif($gruss === 3) {
            $suffix = "\n\nViele Grüße\n".$GLOBALS['optionsDB']['MailGreetings'];
        }
        elseif($gruss === 4) {
            $suffix = "\n".$vornameSession;
        }
        return $body.$suffix;
    }

    /**
     * Plain-text greeting suffix (for Discord / non-HTML).
     */
    public function plainGreetingSuffix($vornameSession = '') {
        $gruss = (int)$this->Gruss;
        if($gruss === 1) {
            return "\n\nViele Grüße\n".$vornameSession;
        }
        if($gruss === 2) {
            return "\n\nViele Grüße\nder Vorstand";
        }
        if($gruss === 3) {
            return "\n\nViele Grüße\n".$GLOBALS['optionsDB']['MailGreetings'];
        }
        if($gruss === 4) {
            return "\n".$vornameSession;
        }
        return '';
    }

    /**
     * Build Discord message for this job (non-personalized greeting).
     */
    public function buildDiscordMessage($vornameSession = '') {
        $subject = trim((string)$this->Subject);
        $prefix = isset($GLOBALS['mailconfig']['subjectprefix']) ? (string)$GLOBALS['mailconfig']['subjectprefix'] : '';
        if($prefix !== '' && strpos($subject, $prefix) === 0) {
            $subject = trim(substr($subject, strlen($prefix)));
        }
        $body = function_exists('mailBodyToPlainText')
            ? mailBodyToPlainText((string)$this->BodyText)
            : trim(html_entity_decode(strip_tags((string)$this->BodyText), ENT_QUOTES, 'UTF-8'));
        $msg = '';
        if($subject !== '') {
            $msg .= '**'.$subject."**\n\n";
        }
        $msg .= "Liebe Musikfreunde,\n\n".$body.$this->plainGreetingSuffix($vornameSession);
        $path = (string)$this->AttachmentPath;
        if($path !== '' && is_dir($path)) {
            $files = scandir($path);
            $hasFile = false;
            if(is_array($files)) {
                foreach($files as $file) {
                    if($file === '.' || $file === '..') continue;
                    if(is_file($path.'/'.$file)) {
                        $hasFile = true;
                        break;
                    }
                }
            }
            if($hasFile) {
                $msg .= "\n\n_(Anhänge nur in der E-Mail)_";
            }
        }
        return trim($msg);
    }

    /**
     * Post once to Discord if flagged. Never throws; logs errors.
     * @return bool
     */
    public function publishToDiscord($vornameSession = '') {
        if(!(int)$this->PostDiscord) {
            return false;
        }
        $webhookUrl = isset($GLOBALS['optionsDB']['DiscordWebHookURL'])
            ? trim((string)$GLOBALS['optionsDB']['DiscordWebHookURL'])
            : '';
        if($webhookUrl === '') {
            $logentry = new Log;
            $logentry->warning(sprintf(
                'Discord-Post übersprungen (kein Webhook) | Email-ID: <b>%d</b>',
                (int)$this->Index
            ));
            return false;
        }
        $botname = isset($GLOBALS['optionsDB']['DiscordBotName'])
            ? (string)$GLOBALS['optionsDB']['DiscordBotName']
            : 'Bot';
        $message = $this->buildDiscordMessage($vornameSession);
        if(function_exists('mb_strlen') && mb_strlen($message, 'UTF-8') > 1900) {
            $message = mb_substr($message, 0, 1900, 'UTF-8').'…';
        }
        elseif(strlen($message) > 1900) {
            $message = substr($message, 0, 1900).'…';
        }
        try {
            $discord = new Discord($webhookUrl);
            $discord->sendMessage($message, $botname);
            $logentry = new Log;
            $logentry->info(sprintf(
                'Email auf Discord gepostet | Email-ID: <b>%d</b>, Betreff: <b>%s</b>',
                (int)$this->Index,
                htmlspecialchars((string)$this->Subject)
            ));
            return true;
        }
        catch(Throwable $e) {
            $logentry = new Log;
            $logentry->error(sprintf(
                'Discord-Post fehlgeschlagen | Email-ID: <b>%d</b>, Exception: <b>%s</b>',
                (int)$this->Index,
                htmlspecialchars($e->getMessage())
            ));
            return false;
        }
    }

    public function refreshCounts() {
        if(!$this->Index) return;
        if($this->Status === 'draft') return;
        $sql = sprintf(
            'SELECT
                SUM(CASE WHEN `Status` = "sent" THEN 1 ELSE 0 END) AS `sent`,
                SUM(CASE WHEN `Status` = "failed" THEN 1 ELSE 0 END) AS `failed`,
                SUM(CASE WHEN `Status` IN ("pending","sending") THEN 1 ELSE 0 END) AS `open`
             FROM `%sMailOutbox` WHERE `Job` = %d;',
            $GLOBALS['dbprefix'],
            (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = $dbr ? mysqli_fetch_assoc($dbr) : null;
        if(!$row) return;
        $this->Sent = (int)$row['sent'];
        $this->Failed = (int)$row['failed'];
        $open = (int)$row['open'];

        if($this->Status === 'cancelled') {
            $this->update();
            return;
        }

        if($open > 0) {
            $this->Status = 'processing';
        }
        elseif($this->Failed > 0 && $this->Sent === 0) {
            $this->Status = 'failed';
        }
        else {
            $this->Status = 'done';
        }
        $this->update();
        if($this->Status === 'done' || $this->Status === 'failed') {
            $this->cleanupAttachments();
        }
    }

    public function cleanupAttachments() {
        $path = (string)$this->AttachmentPath;
        if($path === '' || !is_dir($path)) return;
        $files = scandir($path);
        if(is_array($files)) {
            foreach($files as $file) {
                if($file === '.' || $file === '..') continue;
                $full = $path.'/'.$file;
                if(is_file($full)) {
                    @unlink($full);
                }
            }
        }
        @rmdir($path);
        $this->AttachmentPath = null;
        if($this->Index) {
            $this->update();
        }
    }

    /**
     * @return MailJob[]
     */
    /**
     * @return array[] associative rows from MailOutbox for this job
     */
    public function listOutboxRows() {
        if(!$this->Index) return array();
        $sql = sprintf(
            'SELECT * FROM `%sMailOutbox` WHERE `Job` = %d ORDER BY `Index` ASC;',
            $GLOBALS['dbprefix'],
            (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $rows = array();
        if(!$dbr) return $rows;
        while($row = mysqli_fetch_assoc($dbr)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public static function listDrafts() {
        return self::listJobs('draft');
    }

    /**
     * Timestamp for lists: queue/inbox time when available, else job Created.
     */
    public function listTimestamp() {
        if($this->QueueCreated !== null && $this->QueueCreated !== '') {
            return (string)$this->QueueCreated;
        }
        return (string)$this->Created;
    }

    /**
     * @param string|null $statusFilter null = all
     * @return MailJob[]
     */
    public static function listJobs($statusFilter = null, $limit = 200) {
        self::ensureSchema();
        $limit = max(1, (int)$limit);
        $prefix = $GLOBALS['dbprefix'];
        $queueCreated = sprintf(
            '(SELECT MIN(o.`Created`) FROM `%sMailOutbox` o WHERE o.`Job` = j.`Index`) AS `QueueCreated`',
            $prefix
        );
        if($statusFilter !== null && $statusFilter !== '') {
            $sql = sprintf(
                'SELECT j.*, %s FROM `%sMailJob` j WHERE j.`Status` = "%s" ORDER BY COALESCE((SELECT MIN(o2.`Created`) FROM `%sMailOutbox` o2 WHERE o2.`Job` = j.`Index`), j.`Created`) DESC, j.`Index` DESC LIMIT %d;',
                $queueCreated,
                $prefix,
                mysqli_real_escape_string($GLOBALS['conn'], (string)$statusFilter),
                $prefix,
                $limit
            );
        }
        else {
            $sql = sprintf(
                'SELECT j.*, %s FROM `%sMailJob` j ORDER BY COALESCE((SELECT MIN(o2.`Created`) FROM `%sMailOutbox` o2 WHERE o2.`Job` = j.`Index`), j.`Created`) DESC, j.`Index` DESC LIMIT %d;',
                $queueCreated,
                $prefix,
                $prefix,
                $limit
            );
        }
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $list = array();
        if(!$dbr) return $list;
        while($row = mysqli_fetch_array($dbr)) {
            $j = new MailJob;
            $j->fill_from_array($row);
            $list[] = $j;
        }
        return $list;
    }

    public function statusLabel() {
        switch((string)$this->Status) {
        case 'draft':
            return 'Entwurf';
        case 'queued':
        case 'processing':
            return 'wird versendet…';
        case 'cancelled':
            return 'abgebrochen';
        case 'done':
            return 'Versendet';
        case 'failed':
            return 'Fehler';
        default:
            return (string)$this->Status;
        }
    }

    public function statusClass() {
        switch((string)$this->Status) {
        case 'draft':
            return self::tagClass('colorBtnEdit', 'w3-light-grey');
        case 'queued':
        case 'processing':
            return self::tagClass('colorWarning', 'w3-amber');
        case 'cancelled':
            return self::tagClass('colorBtnNo', 'w3-grey');
        case 'done':
            return self::tagClass('colorLogEmail', 'w3-green');
        case 'failed':
            return self::tagClass('colorLogError', 'w3-red');
        default:
            return 'w3-light-grey';
        }
    }

    /**
     * Prefer w3-* classes for tags; Hex-Farben sind als class ungeeignet.
     */
    private static function tagClass($optionKey, $fallback) {
        $v = isset($GLOBALS['optionsDB'][$optionKey]) ? (string)$GLOBALS['optionsDB'][$optionKey] : '';
        if($v !== '' && strpos($v, 'w3-') === 0) {
            return $v;
        }
        return $fallback;
    }

    /**
     * Live progress from outbox without finalizing job status.
     * @return array{sent:int,failed:int,open:int,total:int,sending:bool,counts:string,statusLabel:string,statusClass:string}
     */
    public function liveProgress() {
        $sent = (int)$this->Sent;
        $failed = (int)$this->Failed;
        $open = 0;
        $total = (int)$this->Total;
        if($this->Index) {
            $sql = sprintf(
                'SELECT
                    SUM(CASE WHEN `Status` = "sent" THEN 1 ELSE 0 END) AS `sent`,
                    SUM(CASE WHEN `Status` = "failed" THEN 1 ELSE 0 END) AS `failed`,
                    SUM(CASE WHEN `Status` IN ("pending","sending") THEN 1 ELSE 0 END) AS `open`,
                    COUNT(*) AS `total`
                 FROM `%sMailOutbox` WHERE `Job` = %d;',
                $GLOBALS['dbprefix'],
                (int)$this->Index
            );
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
            $row = $dbr ? mysqli_fetch_assoc($dbr) : null;
            if($row) {
                $sent = (int)$row['sent'];
                $failed = (int)$row['failed'];
                $open = (int)$row['open'];
                $total = (int)$row['total'];
            }
        }
        $sending = $open > 0;
        $counts = $sent.'/'.$total;
        if($failed > 0) {
            $counts .= ' ('.$failed.' Fehler)';
        }
        if($sending) {
            $label = 'wird versendet…';
            $cls = self::tagClass('colorWarning', 'w3-amber');
        }
        else {
            $label = $this->statusLabel();
            $cls = $this->statusClass();
            if($total > 0 && $sent > 0 && $failed === 0) {
                $label = 'Versendet';
                $cls = self::tagClass('colorLogEmail', 'w3-green');
            }
            elseif($total > 0 && $failed > 0 && $sent === 0) {
                $label = 'Fehler';
                $cls = self::tagClass('colorLogError', 'w3-red');
            }
            elseif($total > 0 && $failed > 0 && $sent > 0) {
                $label = 'Versendet';
                $cls = self::tagClass('colorLogEmail', 'w3-green');
            }
        }
        return array(
            'sent' => $sent,
            'failed' => $failed,
            'open' => $open,
            'total' => $total,
            'sending' => $sending,
            'counts' => $counts,
            'statusLabel' => $label,
            'statusClass' => $cls,
        );
    }

    /**
     * Copy this job into a new draft (content + recipient settings; attachments if still present).
     * @return MailJob|null
     */
    public function copyAsDraft($createdBy = 0) {
        if(!$this->Index) return null;
        $copy = self::createDraft((int)$createdBy, (int)$this->Termin);
        if(!$copy || !$copy->Index) return null;

        $subject = (string)$this->Subject;
        $prefix = isset($GLOBALS['mailconfig']['subjectprefix']) ? (string)$GLOBALS['mailconfig']['subjectprefix'] : '';
        if($prefix !== '' && strpos($subject, $prefix) === 0) {
            $subject = substr($subject, strlen($prefix));
        }

        $copy->Subject = $subject;
        $copy->BodyText = (string)$this->BodyText;
        $copy->Source = 'mail';
        $copy->MemberOnly = (int)$this->MemberOnly;
        $copy->Register = (int)$this->Register;
        $copy->Termin = (int)$this->Termin;
        $copy->Gruss = (int)$this->Gruss;
        $copy->PostDiscord = (int)$this->PostDiscord;
        $copy->save();
        $copy->ensureAttachmentDir();

        $src = (string)$this->AttachmentPath;
        $dst = (string)$copy->AttachmentPath;
        if($src !== '' && $dst !== '' && is_dir($src) && is_dir($dst)) {
            $files = scandir($src);
            if(is_array($files)) {
                foreach($files as $file) {
                    if($file === '.' || $file === '..') continue;
                    $from = $src.'/'.$file;
                    if(is_file($from)) {
                        @copy($from, $dst.'/'.$file);
                    }
                }
            }
        }
        return $copy;
    }
}
?>
