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

    public function save() {
        if(!$this->is_valid()) return false;
        if($this->Index > 0) {
            return $this->update();
        }
        return $this->insert();
    }

    protected function insert() {
        $sql = sprintf(
            'INSERT INTO `%sMailOutbox` (`Job`, `User`, `ToEmail`, `Subject`, `BodyText`, `Status`, `Attempts`, `LastError`, `SentAt`, `DeletedByUser`) VALUES (%d, %d, "%s", "%s", "%s", "%s", %d, %s, %s, %d);',
            $GLOBALS['dbprefix'],
            (int)$this->Job,
            (int)$this->User,
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->ToEmail),
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Subject),
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->BodyText),
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Status),
            (int)$this->Attempts,
            $this->LastError === null || $this->LastError === ''
                ? 'NULL'
                : '"'.mysqli_real_escape_string($GLOBALS['conn'], (string)$this->LastError).'"',
            $this->SentAt === null || $this->SentAt === ''
                ? 'NULL'
                : '"'.mysqli_real_escape_string($GLOBALS['conn'], (string)$this->SentAt).'"',
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
            'UPDATE `%sMailOutbox` SET `Job`=%d, `User`=%d, `ToEmail`="%s", `Subject`="%s", `BodyText`="%s", `Status`="%s", `Attempts`=%d, `LastError`=%s, `SentAt`=%s, `DeletedByUser`=%d WHERE `Index`=%d;',
            $GLOBALS['dbprefix'],
            (int)$this->Job,
            (int)$this->User,
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->ToEmail),
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Subject),
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->BodyText),
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Status),
            (int)$this->Attempts,
            $this->LastError === null || $this->LastError === ''
                ? 'NULL'
                : '"'.mysqli_real_escape_string($GLOBALS['conn'], (string)$this->LastError).'"',
            $this->SentAt === null || $this->SentAt === ''
                ? 'NULL'
                : '"'.mysqli_real_escape_string($GLOBALS['conn'], (string)$this->SentAt).'"',
            (int)$this->DeletedByUser,
            (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        return (bool)$dbr;
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
                'UPDATE `%sMailOutbox` SET `Status` = "sending" WHERE `Index` = %d AND `Status` = "pending";',
                $GLOBALS['dbprefix'],
                (int)$item->Index
            );
            $ok = mysqli_query($GLOBALS['conn'], $claim);
            if($ok && mysqli_affected_rows($GLOBALS['conn']) === 1) {
                $item->Status = 'sending';
                $rows[] = $item;
            }
        }
        return $rows;
    }
}
?>
