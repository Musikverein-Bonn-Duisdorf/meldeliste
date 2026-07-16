<?php
class MailOutbox
{
    private $_data = array(
        'Index' => null,
        'Job' => null,
        'User' => null,
        'ToEmail' => null,
        'Subject' => null,
        'BodyText' => null,
        'Status' => 'pending',
        'Attempts' => 0,
        'LastError' => null,
        'SentAt' => null,
        'LockedAt' => null,
        'ReadAt' => null,
        'DeletedByUser' => 0,
        'Created' => null,
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
        case 'Job':
        case 'User':
        case 'Attempts':
        case 'DeletedByUser':
            $this->_data[$key] = (int)$val;
            break;
        case 'ToEmail':
        case 'Subject':
        case 'BodyText':
        case 'Status':
        case 'LastError':
        case 'SentAt':
        case 'LockedAt':
        case 'ReadAt':
        case 'Created':
            $this->_data[$key] = $val === null ? null : trim((string)$val);
            break;
        default:
            break;
        }
    }

    public function is_valid() {
        if(!$this->Job) return false;
        if(!$this->User) return false;
        if($this->ToEmail === null || $this->ToEmail === '') return false;
        if($this->Subject === null || $this->Subject === '') return false;
        return true;
    }

    public function isUnread() {
        $r = $this->ReadAt;
        return $r === null || $r === '' || $r === '0000-00-00 00:00:00';
    }

    public function save() {
        if(!$this->is_valid()) return false;
        if($this->Index > 0) {
            return $this->update();
        }
        return $this->insert();
    }

    protected function insert() {
        $sql = sprintf(
            'INSERT INTO `%sMailOutbox` (`Job`, `User`, `ToEmail`, `Subject`, `BodyText`, `Status`, `Attempts`, `LastError`, `SentAt`, `LockedAt`, `ReadAt`, `DeletedByUser`) VALUES (%d, %d, "%s", "%s", "%s", "%s", %d, %s, %s, %s, %s, %d);',
            $GLOBALS['dbprefix'],
            (int)$this->Job,
            (int)$this->User,
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->ToEmail),
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Subject),
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->BodyText),
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Status),
            (int)$this->Attempts,
            $this->sqlNullableString($this->LastError),
            $this->sqlNullableString($this->SentAt),
            $this->sqlNullableString($this->LockedAt),
            $this->sqlNullableString($this->ReadAt),
            (int)$this->DeletedByUser
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }

    protected function update() {
        $sql = sprintf(
            'UPDATE `%sMailOutbox` SET `Job`=%d, `User`=%d, `ToEmail`="%s", `Subject`="%s", `BodyText`="%s", `Status`="%s", `Attempts`=%d, `LastError`=%s, `SentAt`=%s, `LockedAt`=%s, `ReadAt`=%s, `DeletedByUser`=%d WHERE `Index`=%d;',
            $GLOBALS['dbprefix'],
            (int)$this->Job,
            (int)$this->User,
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->ToEmail),
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Subject),
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->BodyText),
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Status),
            (int)$this->Attempts,
            $this->sqlNullableString($this->LastError),
            $this->sqlNullableString($this->SentAt),
            $this->sqlNullableString($this->LockedAt),
            $this->sqlNullableString($this->ReadAt),
            (int)$this->DeletedByUser,
            (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        return (bool)$dbr;
    }

    private function sqlNullableString($val) {
        if($val === null || $val === '' || $val === '0000-00-00 00:00:00') {
            return 'NULL';
        }
        return '"'.mysqli_real_escape_string($GLOBALS['conn'], (string)$val).'"';
    }

    public function load_by_id($Index) {
        $Index = (int)$Index;
        $sql = sprintf('SELECT * FROM `%sMailOutbox` WHERE `Index` = %d;', $GLOBALS['dbprefix'], $Index);
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

    public function softDeleteForUser($userId) {
        if((int)$this->User !== (int)$userId) return false;
        $this->DeletedByUser = 1;
        return $this->update();
    }

    public function markRead($userId) {
        if((int)$this->User !== (int)$userId) return false;
        if(!$this->isUnread()) return true;
        $sql = sprintf(
            'UPDATE `%sMailOutbox` SET `ReadAt` = NOW() WHERE `Index` = %d AND `User` = %d AND `ReadAt` IS NULL;',
            $GLOBALS['dbprefix'],
            (int)$this->Index,
            (int)$userId
        );
        try {
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
            sqlerror();
        }
        catch(Throwable $e) {
            $logentry = new Log;
            $logentry->error('markRead fehlgeschlagen: '.$e->getMessage());
            return false;
        }
        if($dbr) {
            $this->load_by_id((int)$this->Index);
        }
        return (bool)$dbr;
    }

    public static function countUnreadForUser($userId) {
        $userId = (int)$userId;
        if($userId <= 0) return 0;
        $sql = sprintf(
            'SELECT COUNT(*) AS `cnt` FROM `%sMailOutbox`
             WHERE `User` = %d AND `DeletedByUser` = 0
               AND `Status` IN ("pending", "sending", "sent")
               AND `ReadAt` IS NULL;',
            $GLOBALS['dbprefix'],
            $userId
        );
        try {
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
        }
        catch(Throwable $e) {
            return 0;
        }
        if(!$dbr) return 0;
        $row = mysqli_fetch_assoc($dbr);
        return $row ? (int)$row['cnt'] : 0;
    }

    /**
     * Reclaim only stale "sending" rows (worker crashed). Never touch fresh locks.
     * @param int $olderThanMinutes
     * @return int
     */
    public static function reclaimStuckSending($olderThanMinutes = 5) {
        $olderThanMinutes = max(2, (int)$olderThanMinutes);
        $sql = sprintf(
            'UPDATE `%sMailOutbox` SET `Status` = "pending", `LockedAt` = NULL
             WHERE `Status` = "sending"
               AND (`LockedAt` IS NULL OR `LockedAt` < (NOW() - INTERVAL %d MINUTE));',
            $GLOBALS['dbprefix'],
            $olderThanMinutes
        );
        try {
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
        }
        catch(Throwable $e) {
            return 0;
        }
        if(!$dbr) return 0;
        $n = mysqli_affected_rows($GLOBALS['conn']);
        if($n > 0) {
            $logentry = new Log;
            $logentry->warning(sprintf(
                'Mail-Queue: <b>%d</b> hängende Einträge (sending älter als %d Min.) zurück auf pending gesetzt',
                $n,
                $olderThanMinutes
            ));
        }
        return max(0, $n);
    }

    /**
     * Claim up to $limit pending rows for sending.
     * @return MailOutbox[]
     */
    public static function claimPending($limit, $maxAttempts) {
        $limit = max(1, (int)$limit);
        $maxAttempts = max(1, (int)$maxAttempts);
        $sql = sprintf(
            'SELECT * FROM `%sMailOutbox` WHERE `Status` = "pending" AND `Attempts` < %d ORDER BY `Index` ASC LIMIT %d;',
            $GLOBALS['dbprefix'],
            $maxAttempts,
            $limit
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $rows = array();
        if(!$dbr) return $rows;
        while($row = mysqli_fetch_array($dbr)) {
            $item = new MailOutbox;
            $item->fill_from_array($row);
            $claim = sprintf(
                'UPDATE `%sMailOutbox` SET `Status` = "sending", `LockedAt` = NOW() WHERE `Index` = %d AND `Status` = "pending";',
                $GLOBALS['dbprefix'],
                (int)$item->Index
            );
            $ok = mysqli_query($GLOBALS['conn'], $claim);
            if($ok && mysqli_affected_rows($GLOBALS['conn']) === 1) {
                $item->Status = 'sending';
                $item->LockedAt = date('Y-m-d H:i:s');
                $rows[] = $item;
            }
        }
        return $rows;
    }
}
?>
