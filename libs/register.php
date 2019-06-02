<?php
class Register
{
    private $_data = array('Index' => null, 'Name' => null);
    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'Name':
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
    public function memberTable() {
        echo "<div class=\"w3-container w3-teal w3-margin-top\"><h3>".$this->Name." (".$this->members().")</h3></div>";
        $sql = sprintf('SELECT * FROM `User` INNER JOIN (SELECT `Index` AS `iIndex`, `Register` FROM `Instrument`) `Instrument` ON `Instrument` = `Instrument`.`iIndex` WHERE `Register` = "%d";',
        $this->Index);
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        while($row = mysqli_fetch_array($dbr)) {
            $user = new User;
            $user->load_by_id($row['Index']);
            $user->printTableLine();
        }
    }
    public function members() {
        $sql = sprintf('SELECT COUNT(`Index`) AS `cnt` FROM `User` INNER JOIN (SELECT `Index` AS `iIndex`, `Register` FROM `Instrument`) `Instrument` ON `Instrument` = `Instrument`.`iIndex` WHERE `Register` = "%d";',
        $this->Index);
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        $row = mysqli_fetch_array($dbr);
        return $row['cnt'];
    }
    public function load_by_id($Index) {
        $Index = (int) $Index;
        $sql = sprintf('SELECT * FROM `MVD`.`Register` WHERE `Index` = "%d";',
        $Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        $row = mysqli_fetch_array($dbr);
        if(is_array($row)) {
            $this->fill_from_array($row);
        }
    }
};
?>