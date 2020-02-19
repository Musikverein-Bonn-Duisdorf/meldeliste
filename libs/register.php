<?php
class Register
{
    private $_data = array('Index' => null, 'Name' => null, 'Sortierung' => null, 'Row' => null, 'ArcMin' => null, 'ArcMax' => null, 'Color' => null);
    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'Name':
	    case 'Sortierung':
	    case 'Row':
	    case 'ArcMin':
	    case 'ArcMax':
	    case 'Color':
            return $this->_data[$key];
            break;
        default:
            break;
        }
    }
    public function __set($key, $val) {
        switch($key) {
	    case 'ArcMin':
	    case 'ArcMax':
            $this->_data[$key] = $val;
            break;
	    case 'Index':
	    case 'Sortierung':
	    case 'Row':
            $this->_data[$key] = (int)$val;
            break;
	    case 'Name':
	    case 'Color':
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
        echo "<div class=\"w3-container ".$GLOBALS['optionsDB']['colorTitleBar']." w3-margin-top\"><h3>".$this->Name." (".$this->members().")</h3></div>";
        $sql = sprintf('SELECT * FROM `%sUser` INNER JOIN (SELECT `Index` AS `iIndex`, `Register` FROM `%sInstrument`) `%sInstrument` ON `Instrument` = `iIndex` WHERE `Deleted` != 1 AND `Register` = "%d" ORDER BY `Nachname`, `Vorname`;',
        $GLOBALS['dbprefix'],
        $GLOBALS['dbprefix'],
        $GLOBALS['dbprefix'],
        $this->Index);
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        while($row = mysqli_fetch_array($dbr)) {
            $user = new User;
            $user->load_by_id($row['Index']);
            $user->printTableLine();
        }
    }
    public function members() {
        $sql = sprintf('SELECT `Index`, `Deleted` FROM `%sUser` INNER JOIN (SELECT `Index` AS `iIndex`, `Register` FROM `%sInstrument`) `%sInstrument` ON `Instrument` = `iIndex` WHERE `Deleted` != 1 AND `Register` = "%d";',
        $GLOBALS['dbprefix'],
        $GLOBALS['dbprefix'],
        $GLOBALS['dbprefix'],
        $this->Index);
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $cnt=0;
        while ($row = mysqli_fetch_array($dbr)) {
            $cnt++;
        }
        return $cnt;
    }
    public function load_by_id($Index) {
        $Index = (int) $Index;
        $sql = sprintf('SELECT * FROM `%sRegister` WHERE `Index` = "%d";',
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