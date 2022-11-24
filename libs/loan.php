<?php
class Loan
{
    private $_data = array('Index' => null, 'User' => null, 'Instrument' => null, 'StartDate' => null, 'EndDate' => null, 'ContractFile' => null);
    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'User':
	    case 'Instrument':
	    case 'StartDate':
	    case 'EndDate':
	    case 'ContractFile':
            return $this->_data[$key];
            break;
        default:
            break;
        }
    }

    public function __set($key, $val) {
        switch($key) {
	    case 'StartDate':
	    case 'EndDate':
	    case 'ContractFile':
            $this->_data[$key] = $val;
            break;
	    case 'Index':
	    case 'Instrument':
	    case 'User':
            $this->_data[$key] = (int)$val;
            break;
        default:
            break;
        }	
    }

    public function is_valid() {
        if(!$this->Instrument) return false;
        if(!$this->User) return false;
        return true;
    }

    public function fill_from_array($row) {
        foreach($row as $key => $val) {
                $this->_data[$key] = $val;
        }
    }

    public function load_by_id($Index) {
        $Index = (int) $Index;
        $sql = sprintf('SELECT * FROM `%sLoans` WHERE `Index` = "%d";',
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
        $u = new User;
        $u->load_by_id($this->User);
        return $u->getName();
    }
    
};
?>