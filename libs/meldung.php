<?php
include 'libs/log.php';
class Meldung
{
    private $_data = array('Index' => null, 'Termin' => null, 'User' => null, 'Wert' => null, 'Timestamp' => null);
    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'Termin':
	    case 'User':
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
            $this->_data[$key] = (int)$val;
            break;
	    case 'Termin':
            $this->_data[$key] = (int)$val;
            break;
	    case 'User':
            $this->_data[$key] = (int)$val;
            break;
	    case 'Wert':
            $this->_data[$key] = (int)$val;
            break;
	    case 'Timestamp':
            $this->_data[$key] = trim($val);
            break;
        default:
            break;
        }	
    }
    public function getVars() {
        return sprintf("Melde-ID: %d, Termin: %d, User: %d, Wert: %d",
        $this->Index,
        $this->Termin,
        $this->User,
        $this->Wert
        );
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
        if(!$this->User) return false;
        if(!$this->Termin) return false;
        if(!$this->Wert) return false;
        return true;
    }
    protected function insert() {
        $sql = sprintf('INSERT INTO `%sMeldungen` (`Termin`, `User`, `Wert`) VALUES ("%s", "%s", "%s");',
        $GLOBALS['dbprefix'],
        $this->Termin,
        $this->User,
        $this->Wert
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }
    protected function update() {
        $sql = sprintf('UPDATE `%sMeldungen` SET `Wert` = "%s", `Timestamp` = DEFAULT WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        $this->Wert,
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        return true;
    }
    public function delete() {
        if(!$this->Index) return false;
        $sql = sprintf('DELETE FROM `%sMeldungen` WHERE `Index` = "%d";',
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
        $sql = sprintf('SELECT * FROM `%sMeldungen` WHERE `User` = "%d" AND `Termin` = "%d";',
        $GLOBALS['dbprefix'],
        $user,
        $event
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