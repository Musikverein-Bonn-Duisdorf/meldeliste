<?php
/* include 'libs/log.php'; */
class ExternMeldung
{
    private $_data = array('Index' => null, 'Termin' => null, 'User' => null, 'Name' => null, 'Instrument' => null, 'Wert' => null, 'Timestamp' => null);
    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'Termin':
	    case 'User':
	    case 'Name':
	    case 'Instrument':
	    case 'Wert':
	    case 'Timestamp':
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
	    case 'User':
	    case 'Instrument':
	    case 'Wert':
            $this->_data[$key] = (int)$val;
            break;
	    case 'Name':
	    case 'Timestamp':
            $this->_data[$key] = trim($val);
            break;
        default:
            break;
        }	
    }
    public function getVars() {
        $t = new Termin;
        $t->load_by_id($this->Termin);
        $i = new Instrument;
        $i->load_by_id($this->Instrument);
        $u = new User;
        $u->load_by_id($this->User);
        $str = sprintf("extMelde-ID: %d, eingetragen von: %s, Termin: %d (%s), Name: %s, Instrument: %s, Wert: %s",
        $this->Index,
        $u->getName(),
        $this->Termin,
        $t->Name,
        $this->Name,
        $i->Name,
        meldeWert($this->Wert)
        );
        return $str;
    }
    public function save() {
        if(!$this->is_valid()) return false;
        if($this->Index > 0) {
            $this->update();
            $logentry = new Log;
            $logentry->DBupdate($this->getVars());
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
        if(!$this->Wert) return false;
        return true;
    }
    protected function insert() {
        $sql = sprintf('INSERT INTO `%sexternMeldungen` (`Termin`, `User`, `Name`, `Instrument`, `Wert`, `Timestamp`) VALUES ("%d", "%s", "%d", "%d", "%s");',
        $GLOBALS['dbprefix'],
        $this->Termin,
        $this->User,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
        $this->Instrument,
        $this->Wert,
        $this->Timestamp
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }
    protected function update() {
        $sql = sprintf('UPDATE `%sexternMeldungen` SET `Termin` = "%d", `User` = "%d", `Name` = "%s", `Instrument` = "%d", `Wert` = "%d", `Timestamp` = "%s" WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        $this->Termin,
        $this->User,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
        $this->Instrument,
        $this->Wert,
        $this->Timestamp,
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        return true;
    }
    public function delete() {
        if(!$this->Index) return false;
        $sql = sprintf('DELETE FROM `%sexternMeldungen` WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $logentry = new Log;
        $logentry->DBdelete($this->getVars());
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
        $sql = sprintf('SELECT * FROM `%sMeldungen` WHERE `Index` = "%d";',
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
    public function load_by_user_event($user, $event) {
        $r = array();
        $sql = sprintf('SELECT * FROM `%sexternMeldungen` WHERE `User` = "%d" AND `Termin` = "%d";',
        $GLOBALS['dbprefix'],
        $user,
        $event
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        while($row = mysqli_fetch_array($dbr)) {
            array_push($r, $row['Index']);
        }
    }
};
?>