<?php
class Aushilfe
{
    private $_data = array('Index' => null, 'Name' => null, 'Termin' => null, 'Instrument' => null, 'iName' => null);

    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'Name':
	    case 'Termin':
	    case 'Instrument':
	    case 'iName':
            return $this->_data[$key];
            break;
        default:
            break;
        }
    }

    public function __set($key, $val) {
        switch($key) {
	    case 'Index':
	    case 'Instrument':
	    case 'Termin':
            $this->_data[$key] = (int)$val;
            break;
	    case 'Name':
	    case 'iName':
            $this->_data[$key] = trim($val);
            break;
        default:
            break;
        }	
    }

    public function getChanges() {
        $old = new Aushilfe;
        $old->load_by_id($this->Index);

        $t = new Termin;
        $t->load_by_id($this->Termin);

        $str = sprintf("Aushilfen-ID: %d <b>%s</b>, Termin: (%d) <b>%s</b>",
        $this->Index,
        $this->Name,
        $this->Termin,
        $t->Name
        );
        if($this->Name != $old->Name) $str.=", Name: ".$old->Name." &rArr; <b>".$this->Name."</b>";
        if($this->Nachname != $old->Nachname) $str.=", Nachname: ".$old->Nachname." &rArr; <b>".$this->Nachname."</b>";
        if($this->Instrument != $old->Instrument) {
            $newinstr = new Instrument;
            $newinstr->load_by_id($this->Instrument);

            $oldinstr = new Instrument;
            $oldinstr->load_by_id($old->Instrument);
            
            $str.=", Instrument: ".$oldinstr->Name." &rArr; <b>".$newinstr->Name."</b>";
        }

        return $str;
    }

    public function getVars() {
        $t = new Termin;
        $t->load_by_id($this->Termin);
        $this->getInstrumentName();
        return sprintf("Aushilfen-ID: %d, Termin: (%d) <b>%s</b>, Name: <b>%s</b>, Instrument: <b>%s</b>",
        $this->Index,
        $this->Termin,
        $t->Name,
        $this->getName(),
        $this->iName,
        );
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
        $sql = sprintf('INSERT INTO `%sAushilfen` (`Name`, `Termin`, `Instrument`) VALUES ("%s", "%d", "%d");',
                       $GLOBALS['dbprefix'],
                       mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
                       $this->Termin,
                       $this->Instrument
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }
    
    public function load_by_id($Index) {
        $Index = (int) $Index;
        $sql = sprintf('SELECT * FROM `%sAushilfen` WHERE `Index` = "%d";',
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

    public function fill_from_array($row) {
        foreach($row as $key => $val) {
            $this->_data[$key] = $val;
        }
    }

    public function getName() {
        return $this->Name;
    }
    public function getInstrumentName() {
        if(!$this->Instrument || $this->Instrument == 0) return "";
        $i = new Instrument;
        $i->load_by_id($this->Instrument);
        $this->iName = $i->Name;
        return $this->iName;
    }
};
?>
