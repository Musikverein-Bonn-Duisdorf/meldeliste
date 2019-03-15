<?php
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
    public function save() {
        if(!$this->is_valid()) return false;
        if($this->Index > 0) {
            $this->update();
        }
        else {
            $this->insert();
        }
    }
    public function is_valid() {
        if(!$this->User) return false;
        if(!$this->Termin) return false;
        if(!$this->Wert) return false;
        return true;
    }
    protected function insert() {
        $sql = sprintf('INSERT INTO `MVD`.`Meldungen` (`Termin`, `User`, `Wert`) VALUES ("%s", "%s", "%s");',
        $this->Termin,
        $this->User,
        $this->Wert
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }
    protected function update() {
        $sql = sprintf('UPDATE `MVD`.`Meldungen` SET `Wert` = "%s", `Timestamp` = DEFAULT WHERE `Index` = "%d";',
        $this->Wert,
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if(!$dbr) return false;
        return true;
    }
    public function delete() {
        if(!$this->Index) return false;
        $sql = sprintf('DELETE FROM `MVD`.`Meldungen` WHERE `Index` = "%d";',
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
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
        $sql = sprintf('SELECT * FROM `MVD`.`Meldungen` WHERE `Index` = "%d";',
        $Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        $row = mysqli_fetch_array($dbr);
        if(is_array($row)) {
            $this->fill_from_array($row);
        }
    }
    public function load_by_user_event($user, $event) {
        $sql = sprintf('SELECT * FROM `MVD`.`Meldungen` WHERE `User` = "%d" AND `Termin` = "%d";',
        $user,
        $event
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        $row = mysqli_fetch_array($dbr);
        if(is_array($row)) {
            $this->fill_from_array($row);
        }
    }
};
?>