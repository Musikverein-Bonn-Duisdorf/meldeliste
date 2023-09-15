<?php
/* include 'libs/log.php'; */
class Meldung
{
    private $_data = array('Index' => null, 'Termin' => null, 'User' => null, 'Wert' => null, 'Instrument' => 0, 'Timestamp' => null, 'Children' => null, 'Guests' => null);
    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'Termin':
	    case 'User':
	    case 'Wert':
	    case 'Instrument':
	    case 'Timestamp':
	    case 'Children':
        case 'Guests':
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
	    case 'Wert':
	    case 'Instrument':
	    case 'Children':
	    case 'Guests':
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
        $old = new Meldung;
        $old->load_by_id($this->Index);

        $u = new User;
        $u->load_by_id($this->User);
        $t = new Termin;
        $t->load_by_id($this->Termin);

        $str = sprintf("Melde-ID: %d, Termin: (%d) <b>%s</b> %s %s, User: <b>%s</b>",
        $this->Index,
        $this->Termin,
        $t->Name,
        $t->Datum,
        $t->Uhrzeit,
        $u->getName()
        );
        if($this->Wert != $old->Wert) $str.=", Wert: ".meldeWert($old->Wert)." &rArr; <b>".meldeSymbol($this->Wert)."</b>";
        if($this->Children != $old->Children) $str.=", Kinder: ".$old->Children." &rArr; <b>".$this->Children."</b>";
        if($this->Guests != $old->Guests) $str.=", G&auml;ste: ".$old->Guests." &rArr; <b>".$this->Guests."</b>";
        if($this->Instrument != $old->Instrument) {
            $newinstrument = $this->Instrument;
            if($newinstrument == 0) $newinstrument = $u->Instrument;
            $newinstr = new Instrument;
            $newinstr->load_by_id($newinstrument);

            $oldinstrument = $old->Instrument;
            if($oldinstrument == 0) $oldinstrument = $u->Instrument;
            $oldinstr = new Instrument;
            $oldinstr->load_by_id($oldinstrument);
            
            $str.=", Instrument: ".$oldinstr->Name." &rArr; <b>".$newinstr->Name."</b>";
        }
        return $str;
    }

    public function getVars() {
        $u = new User;
        $u->load_by_id($this->User);
        $t = new Termin;
        $t->load_by_id($this->Termin);
        $instrument = $this->Instrument;
        if($instrument == 0) $instrument = $u->Instrument;
        $instr = new Instrument;
        $instr->load_by_id($instrument);
        $str = sprintf("Melde-ID: %d, Termin: (%d) <b>%s</b> %s %s, User: %s, Wert: <b>%s</b>, Instrument: %s",
        $this->Index,
        $this->Termin,
        $t->Name,
        $t->Datum,
        $t->Uhrzeit,
        $u->getName(),
        meldeSymbol($this->Wert),
        $instr->Name
        );
        if($GLOBALS['optionsDB']['showChildOption']) {
            $str=$str.", Kinder: ".$this->Children;
        }
        if($GLOBALS['optionsDB']['showGuestOption']) {
            $str=$str.", G&auml;ste: ".$this->Guests;
        }
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
        if(!$this->User) return false;
        if(!$this->Termin) return false;
        if(!$this->Wert) return false;
        return true;
    }
    protected function insert() {
        $sql = sprintf('INSERT INTO `%sMeldungen` (`Termin`, `User`, `Wert`, `Instrument`, `Children`, `Guests`) VALUES ("%d", "%d", "%d", "%d", "%d", "%d");',
        $GLOBALS['dbprefix'],
        $this->Termin,
        $this->User,
        $this->Wert,
        $this->Instrument,
        $this->Children,
        $this->Guests
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }
    protected function update() {
        $sql = sprintf('UPDATE `%sMeldungen` SET `Wert` = "%d", `Instrument` = "%d", `Children` = "%d", `Guests` = "%d", `Timestamp` = DEFAULT WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        $this->Wert,
        $this->Instrument,
        $this->Children,
        $this->Guests,
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
        $sql = sprintf('DELETE FROM `%sMeldungen` WHERE `Index` = "%d";',
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