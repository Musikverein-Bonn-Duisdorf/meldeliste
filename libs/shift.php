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
            $this->_data[$key] = trim($val);
            break;
	    case 'Name':
            $this->_data[$key] = trim((string)$val);
            break;
        default:
            break;
        }	
    }
    public function getChanges() {
        $old = new Shift;
        $old->load_by_id($this->Index);

        $str = sprintf('Schicht: %d, Termin: <b>%d</b>, Name: <b>%s</b>',
            (int)$this->Index,
            (int)$this->Termin,
            (string)$this->Name
        );
        if($this->Termin != $old->Termin) $str .= ', Termin: '.$old->Termin.' &rArr; <b>'.$this->Termin.'</b>';
        if($this->Name != $old->Name) $str .= ', Name: '.$old->Name.' &rArr; <b>'.$this->Name.'</b>';
        if($this->Start != $old->Start) $str .= ', Start: '.$old->Start.' &rArr; <b>'.$this->Start.'</b>';
        if($this->End != $old->End) $str .= ', Ende: '.$old->End.' &rArr; <b>'.$this->End.'</b>';
        if($this->Bedarf != $old->Bedarf) $str .= ', Bedarf: '.$old->Bedarf.' &rArr; <b>'.$this->Bedarf.'</b>';
        return $str;
    }

    public function getVars() {
        $parts = array();
        $parts[] = sprintf('Schicht: %d', (int)$this->Index);
        $parts[] = logPart('Termin', (string)(int)$this->Termin);
        logAppendFilled($parts, 'Name', $this->Name, (string)$this->Name);
        if(logValueFilled($this->Start) && $this->Start !== '00:00:00') {
            $parts[] = logPart('Start', (string)$this->Start);
        }
        if(logValueFilled($this->End) && $this->End !== '00:00:00') {
            $parts[] = logPart('Ende', (string)$this->End);
        }
        if((int)$this->Bedarf > 0) {
            $parts[] = logPart('Bedarf', (string)(int)$this->Bedarf);
        }
        return implode(', ', $parts);
    }

    public function getTime() {
        if($this->Start == "00:00:00" && $this->End == "00:00:00") return "&nbsp;";
        if($this->End) {
            $str=sql2timeRaw($this->Start)." - ".sql2timeRaw($this->End);
        }
        else {
            $str="ab ".sql2timeRaw($this->Start);
        }
        return $str;
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
    public function getAushilfen() {
        $sql = sprintf('SELECT * FROM `%sAushilfenShift` WHERE `Shift` = "%d";',
        $GLOBALS['dbprefix'],
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $aushilfen = array();
        while($row = mysqli_fetch_array($dbr)) {
            array_push($aushilfen, $row['Index']);
        }
        return $aushilfen;
    }
    public function getAushilfenVal() {
        $sql = sprintf(
            'SELECT COUNT(*) AS `c` FROM `%sAushilfenShift` WHERE `Shift` = %d;',
            $GLOBALS['dbprefix'],
            (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = $dbr ? mysqli_fetch_array($dbr) : null;
        return ($row && isset($row['c'])) ? (int)$row['c'] : 0;
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
    public function getMeldungenAushilfenShift() {
        $user = array();
        $sql = sprintf(
            'SELECT `Name` FROM `%sAushilfenShift` WHERE `Shift` = %d ORDER BY `Name`;',
            $GLOBALS['dbprefix'],
            (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if($dbr) {
            while($row = mysqli_fetch_array($dbr)) {
                $user[] = $row['Name'];
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
            $logentry = new Log;
            $logentry->DBupdate($this->getChanges());
            $this->update();
        }
        else {
            $this->insert();
            $logentry = new Log;
            $logentry->DBinsert($this->getVars());
        }
    }
    public function is_valid() {
        if(!$this->Name) return false;
        if(!$this->Termin) return false;
        return true;
    }
    protected function insert() {
        $sql = sprintf('INSERT INTO `%sSchichten` (`Termin`, `Name`, `Start`, `End`, `Bedarf`) VALUES ("%d", "%s", "%s", "%s", "%d");',
        $GLOBALS['dbprefix'],
        $this->Termin,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
        $this->Start,
        $this->End,
        $this->Bedarf
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }
    protected function update() {
        $sql = sprintf('UPDATE `%sSchichten` SET `Termin` = "%d", `Name` = "%s", `Start` = "%s", `End` = "%s", `Bedarf` = "%d" WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        $this->Termin,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
        $this->Start,
        $this->End,
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
