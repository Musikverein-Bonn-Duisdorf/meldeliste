<?php
class AushilfeShift
{
    private $_data = array('Index' => null, 'Name' => null, 'Shift' => null, 'Instrument' => null, 'iName' => null);

    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'Name':
	    case 'Shift':
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
	    case 'Shift':
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
        $old = new AushilfeShift;
        $old->load_by_id($this->Index);

        $str = sprintf('AushilfenShift-ID: %d, Name: <b>%s</b>',
            (int)$this->Index,
            $this->getName()
        );
        if($this->Name != $old->Name) $str .= ', Name: '.$old->Name.' &rArr; <b>'.$this->Name.'</b>';
        if($this->Shift != $old->Shift) $str .= ', Shift: '.$old->Shift.' &rArr; <b>'.$this->Shift.'</b>';
        if($this->Instrument != $old->Instrument) {
            $str .= ', Instrument: '.$old->getInstrumentName().' &rArr; <b>'.$this->getInstrumentName().'</b>';
        }
        return $str;
    }

    public function getVars() {
        $this->getInstrumentName();
        $parts = array();
        $parts[] = sprintf('AushilfenShift-ID: %d', (int)$this->Index);
        logAppendFilled($parts, 'Name', $this->getName(), $this->getName());
        logAppendFilled($parts, 'Instrument', $this->iName, (string)$this->iName);
        return implode(', ', $parts);
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
        if(!$this->Shift) return false;
        return true;
    }

    protected function insert() {
        $sql = sprintf('INSERT INTO `%sAushilfenShift` (`Name`, `Shift`, `Instrument`) VALUES ("%s", "%d", "%d");',
                       $GLOBALS['dbprefix'],
                       mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
                       $this->Shift,
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
        $sql = sprintf('SELECT * FROM `%sAushilfenShift` WHERE `Index` = "%d";',
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
        $this->iName=$i->Name;
        return $i->Name;
    }
};
?>
