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

    public function getVars() {
        this->getInstrumentName();
        return sprintf("Aushilfen-ID: %d, Name: %s, Instrument: %s",
        $this->Index,
        $this->getName(),
        $this->iName,
        );
    }

    public function save() {
        if(!$this->is_valid()) return false;
        if($this->Index > 0) {
            $logentry = new Log;
            $logentry->DBupdate($this->getVars());
            $this->update();
        }
        else {
            $logentry = new Log;
            $logentry->DBinsert($this->getVars());
            $this->insert();
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
