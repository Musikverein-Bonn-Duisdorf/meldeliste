<?php
class Shift
{
    private $_data = array('Index' => null, 'Termin' => null, 'Name' => null, 'Start' => null, 'End' => null, 'Bedarf' => null);
    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'Termin':
	    case 'Name':
	    case 'Start':
        case 'End':
	    case 'Bedarf':
            return $this->_data[$key];
            break;
        default:
            break;
        }
    }
    public function __set($key, $val) {
        switch($key) {
	    case 'Index':
	    case 'Termin':
	    case 'Bedarf':
            $this->_data[$key] = (int)$val;
            break;
	    case 'Start':
	    case 'End':
            $this->_data[$key] = self::normalizedTime($val);
            break;
	    case 'Name':
            $this->_data[$key] = trim((string)$val);
            break;
        default:
            break;
        }	
    }
    /**
     * Real field diffs vs DB row (empty = nothing changed).
     * @return string[]
     */
    protected function collectChanges() {
        $old = new Shift;
        $old->load_by_id($this->Index);
        $parts = array();
        if((int)$this->Termin !== (int)$old->Termin) {
            $parts[] = 'Termin: '.$old->Termin.' &rArr; <b>'.$this->Termin.'</b>';
        }
        if((string)$this->Name !== (string)$old->Name) {
            $parts[] = 'Name: '.htmlspecialchars((string)$old->Name, ENT_QUOTES, 'UTF-8')
                .' &rArr; <b>'.htmlspecialchars((string)$this->Name, ENT_QUOTES, 'UTF-8').'</b>';
        }
        $oldStart = self::normalizedTime($old->Start);
        $newStart = self::normalizedTime($this->Start);
        if($oldStart !== $newStart) {
            $parts[] = 'Start: '.self::formatTimeLog($oldStart).' &rArr; <b>'.self::formatTimeLog($newStart).'</b>';
        }
        $oldEnd = self::normalizedTime($old->End);
        $newEnd = self::normalizedTime($this->End);
        if($oldEnd !== $newEnd) {
            $parts[] = 'Ende: '.self::formatTimeLog($oldEnd).' &rArr; <b>'.self::formatTimeLog($newEnd).'</b>';
        }
        if((int)$this->Bedarf !== (int)$old->Bedarf) {
            $parts[] = 'Bedarf: '.(int)$old->Bedarf.' &rArr; <b>'.(int)$this->Bedarf.'</b>';
        }
        return $parts;
    }

    public function hasChanges() {
        return count($this->collectChanges()) > 0;
    }

    public function getChanges() {
        $parts = $this->collectChanges();
        if(count($parts) < 1) {
            return '';
        }
        return sprintf('Schicht/Aufgabe: %d, ', (int)$this->Index).implode(', ', $parts);
    }

    public function getVars() {
        $parts = array();
        $parts[] = sprintf('Schicht/Aufgabe: %d', (int)$this->Index);
        $parts[] = logPart('Termin', (string)(int)$this->Termin);
        logAppendFilled($parts, 'Name', $this->Name, (string)$this->Name);
        if(self::normalizedTime($this->Start) !== null) {
            $parts[] = logPart('Start', self::formatTimeLog($this->Start));
        }
        if(self::normalizedTime($this->End) !== null) {
            $parts[] = logPart('Ende', self::formatTimeLog($this->End));
        }
        if((int)$this->Bedarf > 0) {
            $parts[] = logPart('Bedarf', (string)(int)$this->Bedarf);
        }
        return implode(', ', $parts);
    }

    public function getTime() {
        $start = self::normalizedTime($this->Start);
        $end = self::normalizedTime($this->End);
        if($start === null && $end === null) {
            return '';
        }
        if($start !== null && $end !== null) {
            return sql2timeRaw($start).' - '.sql2timeRaw($end);
        }
        if($start !== null) {
            return 'ab '.sql2timeRaw($start);
        }
        return 'bis '.sql2timeRaw($end);
    }

    /**
     * Empty / midnight sentinel → null; otherwise canonical HH:MM:SS.
     * HTML time inputs often send HH:MM — treat equal to HH:MM:00.
     */
    public static function normalizedTime($val) {
        if($val === null) {
            return null;
        }
        $val = trim((string)$val);
        if($val === '') {
            return null;
        }
        if(!preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $val, $m)) {
            return null;
        }
        $h = (int)$m[1];
        $i = (int)$m[2];
        $s = isset($m[3]) ? (int)$m[3] : 0;
        if($h === 0 && $i === 0 && $s === 0) {
            return null;
        }
        if($h > 23 || $i > 59 || $s > 59) {
            return null;
        }
        return sprintf('%02d:%02d:%02d', $h, $i, $s);
    }

    /** Log/display form HH:MM (or „—“ if empty). */
    public static function formatTimeLog($val) {
        $t = self::normalizedTime($val);
        if($t === null) {
            return '—';
        }
        return substr($t, 0, 5);
    }

    protected function sqlTimeOrNull($val) {
        $t = self::normalizedTime($val);
        if($t === null) {
            return 'NULL';
        }
        return '"'.mysqli_real_escape_string($GLOBALS['conn'], $t).'"';
    }
    public function getMeldungen() {
        $sql = sprintf('SELECT * FROM `%sSchichtmeldung` WHERE `Shift` = "%d";',
        $GLOBALS['dbprefix'],
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $meldungen = array();
        while($row = mysqli_fetch_array($dbr)) {
            array_push($meldungen, $row['Index']);
        }
        return $meldungen;
    }
    public function getMeldungenUser($val) {
        $user = array();
        $sql = sprintf(
            'SELECT u.`Vorname`, u.`Nachname`
             FROM `%sSchichtmeldung` m
             INNER JOIN `%sUser` u ON m.`User` = u.`Index`
             WHERE m.`Shift` = %d AND m.`Wert` = %d
             ORDER BY u.`Nachname`, u.`Vorname`;',
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            (int)$this->Index,
            (int)$val
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if($dbr) {
            while($row = mysqli_fetch_array($dbr)) {
                $user[] = trim($row['Vorname'].' '.$row['Nachname']);
            }
        }
        return $user;
    }
    public function getMeldungenVal($val) {
        $sql = sprintf(
            'SELECT COUNT(*) AS `c` FROM `%sSchichtmeldung` WHERE `Shift` = %d AND `Wert` = %d;',
            $GLOBALS['dbprefix'],
            (int)$this->Index,
            (int)$val
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = $dbr ? mysqli_fetch_array($dbr) : null;
        return ($row && isset($row['c'])) ? (int)$row['c'] : 0;
    }
    
    public function getResponseString() {
        $str=$this->getMeldungenVal(1);
        $str=$str." / ".$this->Bedarf;
        return $str;
    }
    public function save() {
        if(!$this->is_valid()) return false;
        if($this->Index > 0) {
            $changes = $this->getChanges();
            if($changes === '') {
                return true;
            }
            $logentry = new Log;
            $logentry->DBupdate($changes);
            return $this->update();
        }
        $this->insert();
        $logentry = new Log;
        $logentry->DBinsert($this->getVars());
        return (int)$this->Index > 0;
    }
    public function is_valid() {
        if(!$this->Name) return false;
        if(!$this->Termin) return false;
        return true;
    }
    protected function insert() {
        $sql = sprintf('INSERT INTO `%sSchichten` (`Termin`, `Name`, `Start`, `End`, `Bedarf`) VALUES ("%d", "%s", %s, %s, "%d");',
        $GLOBALS['dbprefix'],
        $this->Termin,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
        $this->sqlTimeOrNull($this->Start),
        $this->sqlTimeOrNull($this->End),
        $this->Bedarf
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }
    protected function update() {
        $sql = sprintf('UPDATE `%sSchichten` SET `Termin` = "%d", `Name` = "%s", `Start` = %s, `End` = %s, `Bedarf` = "%d" WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        $this->Termin,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
        $this->sqlTimeOrNull($this->Start),
        $this->sqlTimeOrNull($this->End),
        $this->Bedarf,
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        return true;
    }
    public function delete() {
        if(!$this->Index) return false;
        $logentry = new Log;
        $logentry->DBdelete($this->getVars());

        $sql = sprintf('SELECT * FROM `%sSchichtmeldung` WHERE `Shift` = "%d";',
        $GLOBALS['dbprefix'],
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        while($row = mysqli_fetch_array($dbr)) {
            $n = new Shiftmeldung;
            $n->load_by_id($row['Index']);
            $n->delete();
        }

        $sql = sprintf('DELETE FROM `%sSchichten` WHERE `Index` = "%d" LIMIT 1;',
        $GLOBALS['dbprefix'],
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = null;

        return true;
    }
    public function fill_from_array($row) {
        foreach($row as $key => $val) {
            if($key === 'Start' || $key === 'End') {
                $this->$key = $val;
                continue;
            }
            $this->_data[$key] = $val;
        }
    }
    public function load_by_id($Index) {
        $Index = (int) $Index;
        $sql = sprintf('SELECT * FROM `%sSchichten` WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        $Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = mysqli_fetch_array($dbr);
        if(is_array($row)) {
            $this->fill_from_array($row);
        }
    }
};
?>
