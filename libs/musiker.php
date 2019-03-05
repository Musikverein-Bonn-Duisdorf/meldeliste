<?php
class User
{
    private $_data = array('Index' => null, 'Nachname' => null, 'Vorname' => null, 'Passhash' => null, 'Mitglied' => null, 'Instrument' => null, 'iName' => null, 'Stimme' => null, 'Email' => null, 'getMail' => null);
    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'Nachname':
	    case 'Vorname':
	    case 'Passhash':
	    case 'Mitglied':
	    case 'Instrument':
	    case 'iName':
	    case 'Stimme':
	    case 'Email':
	    case 'getMail':
            return $this->_data[$key];
            break;
        default:
            break;
        }
    }
    public function __set($key, $val) {
        switch($key) {
	    case 'Index':
            $this->_data[$key] = $val;
            break;
	    case 'Nachname':
            $this->_data[$key] = trim($val);
            break;
	    case 'Vorname':
            $this->_data[$key] = trim($val);
            break;
	    case 'Passhash':
            $this->_data[$key] = $val;
            break;
	    case 'Mitglied':
            $this->_data[$key] = (bool) $val;
            break;
	    case 'Instrument':
            $this->_data[$key] = (int) $val;
            break;
	    case 'iName':
            $this->_data[$key] = trim($val);
            break;
	    case 'Email':
            $this->_data[$key] = trim($val);
            break;
	    case 'getMail':
            $this->_data[$key] = (bool) $val;
            break;
        default:
            break;
        }	
    }
    public function save() {
        if(!$this->is_valid()) return false;
        if($this->Index > 0) {
            $this->update();	    
        }
        else {
            $this->insert();
        }
    }
    public function is_valid() {
        if(!$this->Nachname) return false;
        if(!$this->Vorname) return false;
        return true;
    }
    protected function insert() {
        $sql = sprintf('INSERT INTO `MVD`.`User` (`Nachname`, `Vorname`, `Passhash`, `Mitglied`, `Instrument`, `Stimme`, `Email`, `getMail`) VALUES ("%s", "%s", "%s", "%d", "%d", "%d", "%s", "%d");',
        mysqli_real_escape_string($GLOBALS['conn'], $this->Nachname),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Vorname),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Passhash),
        $this->Mitglied,
        $this->Instrument,
        $this->Stimme,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Email),
        $this->getMail
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }
    protected function update() {
        $sql = sprintf('UPDATE `MVD`.`User` SET `Nachname` = "%s", `Vorname` = "%s", `Passhash` = "%s", `Mitglied` = "%d", `Instrument` = "%d", `Stimme` = "%d", `Email` = "%s", `getMail` = "%d" WHERE `Index` = "%d";',
        mysqli_real_escape_string($GLOBALS['conn'], $this->Nachname),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Vorname),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Passhash),
        $this->Mitglied,
        $this->Instrument,
        $this->Stimme,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Email),
        $this->getMail,
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if(!$dbr) return false;
        return true;
    }
    public function delete() {
        if(!$this->Index) return false;
        $sql = sprintf('DELETE FROM `MVD`.`User` WHERE `Index` = "%d";',
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if(!$dbr) return false;
        $this->_data['Index'] = null;
        return true;
    }
    public function fill_from_array($row) {
        foreach($row as $key => $val) {
                $this->_data[$key] = $val;
        }
    }
    public static function &load_by_id($Index) {
        $Index = (int) $Index;
        $sql = sprintf('SELECT * FROM `MVD`.`User` INNER JOIN (SELECT `Index` AS `iIndex`, `Name` AS `iName` FROM `Instrument`) `Instrument` ON `iIndex` = `Instrument` WHERE `Index` = "%d";',
        $Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        $row = mysqli_fetch_array($dbr);
        if(is_array($row)) {
            $obj = new self();
            $obj->fill_from_array($row);
            return $obj;
        }
    }
    public function printTableLine() {
        echo "<tr>\n";
        echo "  <td>".$this->Vorname."</td>\n";
        echo "  <td>".$this->Nachname."</td>\n";
        echo "  <td class=\"right\">".$this->Stimme.".</td>\n";
        echo "  <td class=\"left\">".$this->iName."</td>\n";
        echo "  <td><a href=\"mailto:\"".$this->Email."\">".$this->Email."</a></td>\n";
        echo "  <td>".bool2string($this->Mitglied)."</td>\n";
        echo "  <td>".bool2string($this->getMail)."</td>\n";
        echo "</tr>\n";
    }
};
?>