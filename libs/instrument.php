<?php
class Instrument
{
    private $_data = array('Index' => null, 'Name' => null, 'Register' => null, 'Sortierung' => null);
    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'Name':
	    case 'Register':
	    case 'Sortierung':
            return $this->_data[$key];
            break;
        default:
            break;
        }
    }
    public function __set($key, $val) {
        switch($key) {
	    case 'Index':
	    case 'Register':
	    case 'Sortierung':
            $this->_data[$key] = (int)$val;
            break;
	    case 'Name':
            $this->_data[$key] = trim($val);
            break;
        default:
            break;
        }	
    }
    public function is_valid() {
        if(!$this->Name) return false;
        return true;
    }
    public function fill_from_array($row) {
        foreach($row as $key => $val) {
                $this->_data[$key] = $val;
        }
    }
    public function load_by_id($Index) {
        $Index = (int) $Index;
        $sql = sprintf('SELECT * FROM `%sInstrument` WHERE `Index` = "%d";',
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