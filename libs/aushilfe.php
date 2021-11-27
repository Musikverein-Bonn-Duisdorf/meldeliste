<?php
class Aushilfe
{
    private $_data = array('Index' => null, 'Vorname' => null, 'Nachname' => null, 'Instrument' => null, 'iName' => null);

    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'Vorname':
	    case 'Nachname':
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
            $this->_data[$key] = (int)$val;
            break;
	    case 'Vorname':
	    case 'Nachname':
	    case 'iName':
            $this->_data[$key] = trim($val);
            break;
        default:
            break;
        }	
    }

    public function getVars() {
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
            $this->makeAlwaysYes();
        }
    }

    public function is_valid() {
        if(!$this->Vorname) return false;
        if(!$this->Nachname) return false;
        if(!$this->Instrument) return false;
        return true;
    }

    protected function insert() {
        $sql = sprintf('INSERT INTO `%sAushilfen` (`Vorname`, `Nachname`, `Instrument`) VALUES ("%s", "%s", "%d");',
        $GLOBALS['dbprefix'],
        mysqli_real_escape_string($GLOBALS['conn'], $this->Vorname),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Nachname),
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
        $sql = sprintf('SELECT * FROM `%sAushilfen` INNER JOIN (SELECT `Index` AS `iIndex`, `Name` AS `iName` FROM `%sInstrument`) `%sInstrument` ON `iIndex` = `Instrument` WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        $GLOBALS['dbprefix'],
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

    public function getName() {
        return $this->Vorname." ".$this->Nachname;
    }
};
?>