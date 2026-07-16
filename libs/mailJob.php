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
        'AttachmentPath' => null,
        'Status' => 'draft',
        'Total' => 0,
        'Sent' => 0,
        'Failed' => 0,
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
     */
    public static function ensureSchema() {
        static $done = false;
        if($done) return true;
        $table = new SQLtable('MailJob');
        $outbox = new SQLtable('MailOutbox');
        $needs = !$table->exists() || !$outbox->exists()
            || !$table->columnExists('Gruss')
            || !$outbox->columnExists('ReadAt');
        if($needs) {
            $manager = new DatabaseManager();
            $manager->create();
            $manager->repair();
        }
        $done = true;
        return (new SQLtable('MailJob'))->exists() && (new SQLtable('MailOutbox'))->exists();
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
            'INSERT INTO `%sMailJob` (`CreatedBy`, `Subject`, `BodyText`, `Source`, `MemberOnly`, `Register`, `Termin`, `Gruss`, `AttachmentPath`, `Status`, `Total`, `Sent`, `Failed`) VALUES (%d, "%s", "%s", "%s", %d, %d, %d, %d, %s, "%s", %d, %d, %d);',
            $GLOBALS['dbprefix'],
            (int)$this->CreatedBy,
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Subject),
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->BodyText),
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Source),
            (int)$this->MemberOnly,
            (int)$this->Register,
            (int)$this->Termin,
            (int)$this->Gruss,
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
            'UPDATE `%sMailJob` SET `CreatedBy`=%d, `Subject`="%s", `BodyText`="%s", `Source`="%s", `MemberOnly`=%d, `Register`=%d, `Termin`=%d, `Gruss`=%d, `AttachmentPath`=%s, `Status`="%s", `Total`=%d, `Sent`=%d, `Failed`=%d WHERE `Index`=%d;',
            $GLOBALS['dbprefix'],
            (int)$this->CreatedBy,
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Subject),
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->BodyText),
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Source),
            (int)$this->MemberOnly,
            (int)$this->Register,
            (int)$this->Termin,
            (int)$this->Gruss,
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
        $sql = sprintf('SELECT * FROM `%sMailJob` WHERE `Index` = %d;', $GLOBALS['dbprefix'], $Index);
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
        $gruss = (int)$this->Gruss;
        $suffix = '';
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
        return (string)$this->BodyText.$suffix;
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
    public static function listDrafts() {
        return self::listJobs('draft');
    }

    /**
     * @param string|null $statusFilter null = all
     * @return MailJob[]
     */
    public static function listJobs($statusFilter = null, $limit = 200) {
        self::ensureSchema();
        $limit = max(1, (int)$limit);
        if($statusFilter !== null && $statusFilter !== '') {
            $sql = sprintf(
                'SELECT * FROM `%sMailJob` WHERE `Status` = "%s" ORDER BY `Created` DESC, `Index` DESC LIMIT %d;',
                $GLOBALS['dbprefix'],
                mysqli_real_escape_string($GLOBALS['conn'], (string)$statusFilter),
                $limit
            );
        }
        else {
            $sql = sprintf(
                'SELECT * FROM `%sMailJob` ORDER BY `Created` DESC, `Index` DESC LIMIT %d;',
                $GLOBALS['dbprefix'],
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
            return isset($GLOBALS['optionsDB']['colorBtnEdit']) ? $GLOBALS['optionsDB']['colorBtnEdit'] : 'w3-light-grey';
        case 'queued':
        case 'processing':
            return isset($GLOBALS['optionsDB']['colorWarning']) ? $GLOBALS['optionsDB']['colorWarning'] : 'w3-yellow';
        case 'cancelled':
            return isset($GLOBALS['optionsDB']['colorBtnNo']) ? $GLOBALS['optionsDB']['colorBtnNo'] : 'w3-grey';
        case 'done':
            return isset($GLOBALS['optionsDB']['colorLogEmail']) ? $GLOBALS['optionsDB']['colorLogEmail'] : 'w3-green';
        case 'failed':
            return isset($GLOBALS['optionsDB']['colorLogError']) ? $GLOBALS['optionsDB']['colorLogError'] : 'w3-red';
        default:
            return 'w3-light-grey';
        }
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
