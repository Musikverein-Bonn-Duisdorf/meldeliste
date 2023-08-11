<?php
class Shiftmeldung
{
    private $_data = array('Index' => null, 'Shift' => null, 'User' => null, 'Wert' => null, 'Timestamp' => null);
    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'Shift':
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
	    case 'Shift':
	    case 'User':
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

    public function getChanges() {
        $old = new Shiftmeldung;
        $old->load_by_id($this->Index);

        $u = new User;
        $u->load_by_id($this->User);
        $s = new Shift;
        $s->load_by_id($this->Shift);
        $t = new Termin;
        $t->load_by_id($s->Termin);
        $str = sprintf("Melde-ID: %d, Termin: (%d) %s, Schicht: (%d) %s %s %s, User: %s",
        $this->Index,
        $t->Index,
        $t->Name,
        $s->Index,
        $s->Name,
        $t->Datum,
        $s->Start,
        $u->getName()
        );

        if($this->Wert != $old->Wert) $str.=", Wert: ".meldeWert($old->Wert)." &rArr; <b>".meldeWert($this->Wert)."</b>";
        return $str;
    }

    public function getVars() {
        $u = new User;
        $u->load_by_id($this->User);
        $s = new Shift;
        $s->load_by_id($this->Shift);
        $t = new Termin;
        $t->load_by_id($s->Termin);
        $str = sprintf("Melde-ID: %d, Termin: (%d) %s, Schicht: (%d) %s %s %s, User: %s, Wert: %s",
        $this->Index,
        $t->Index,
        $t->Name,
        $s->Index,
        $s->Name,
        $t->Datum,
        $s->Start,
        $u->getName(),
        meldeWert($this->Wert)
        );
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
        if(!$this->Shift) return false;
        if(!$this->User) return false;
        if(!$this->Wert) return false;
        return true;
    }
    protected function insert() {
        $sql = sprintf('INSERT INTO `%sSchichtmeldung` (`Shift`, `User`, `Wert`) VALUES ("%d", "%d", "%d");',
        $GLOBALS['dbprefix'],
        $this->Shift,
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
        $sql = sprintf('UPDATE `%sSchichtmeldung` SET `Wert` = "%s", `Timestamp` = DEFAULT WHERE `Index` = "%d";',
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
        $sql = sprintf('DELETE FROM `%sSchichtmeldung` WHERE `Index` = "%d";',
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
        $sql = sprintf('SELECT * FROM `%sSchichtmeldung` WHERE `Index` = "%d";',
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
    public function load_by_user_event($user, $shift) {
        $sql = sprintf('SELECT * FROM `%sSchichtmeldung` WHERE `User` = "%d" AND `Shift` = "%d";',
        $GLOBALS['dbprefix'],
        $user,
        $shift
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = mysqli_fetch_array($dbr);
        if(is_array($row)) {
            $this->fill_from_array($row);
        }
    }
    public function melde($user, $wert) {
        $this->User = $user;
        $this->Wert = $wert;
        $this->save();
    }
};
?>