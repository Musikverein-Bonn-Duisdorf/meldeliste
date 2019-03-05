<?php
class User
{
    private $_data = array('Index' => null, 'Nachname' => null, 'Vorname' => null, 'Passhash' => null, 'Mitglied' => null, 'Instrument' => null, 'Stimme' => null, 'getMail' => null);
    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'Nachname':
	    case 'Vorname':
	    case 'Passhash':
	    case 'Mitglied':
	    case 'Instrument':
	    case 'Stimme':
	    case 'getMail':
            return $this->_data[$key];
        }
    }
    public function __set($key, $val) {
        switch($key) {
	    case 'Index':
            $this->_data['key'] = $val;
            break;
	    case 'Nachname':
            $this->_data['key'] = trim($val);
            break;
	    case 'Vorname':
            $this->_data['key'] = trim($val);
            break;
	    case 'Passhash':
            $this->_data['key'] = $val;
            break;
	    case 'Mitglied':
            $this->_data['key'] = (bool) $val;
            break;
	    case 'Instrument':
            $this->_data['key'] = (bool) $val;
            break;
	    case 'getMail':
            $this->_data['key'] = (bool) $val;
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
        if(!$this->the_date) return false;
        return true;
    }
    protected function insert() {
        $sql = sprintf('INSERT INTO `MVD`.`User` (`Nachname`, `Vorname`, `Passhash`, `Mitglied`, `Instrument`, `Stimme`, `getMail`) VALUES ("%s", "%s", "%s", "%d", "%d", "%d", "%d");',
        mysql_real_escape_string($this->Nachname),
        mysql_real_escape_string($this->Vorname),
        mysql_real_escape_string($this->Passhash),
        $this->Mitglied,
        $this->Instrument,
        $this->Stimme,
        $this->getMail
        );
        $dbr = mysqli_query($conn, $sql);
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($conn);
        return true;
    }
    protected function update() {
        $sql = sprintf('UPDATE `MVD`.`User` SET `Nachname` = "%s", `Vorname` = "%s", `Passhash` = "%s", `Mitglied` = "%d", `Instrument` = "%d", `Stimme` = "%d", `getMail` = "%d" WHERE `Index` = "%d";',
        mysql_real_escape_string($this->Nachname),
        mysql_real_escape_string($this->Vorname),
        mysql_real_escape_string($this->Passhash),
        $this->Mitglied,
        $this->Instrument,
        $this->Stimme,
        $this->getMail,
        $this->Index
        );
        $dbr = mysqli_query($conn, $sql);
        if(!$dbr) return false;
        return true;
    }
    public function delete() {
        if(!$this->Index) return false;
        $sql = sprintf('DELETE FROM `MVD`.`User` WHERE `Index` = "%d";',
        $this->Index
        );
        $dbr = mysqli_query($conn, $sql);
        if(!$dbr) return false;
        $this->_data['Index'] = null;
        return true;
    }
    public function fill_from_array($row) {
        foreach($row as $key => $val) {
            if(array_key_exists($key, $this->_data)) {
                $this->_data[$key] = $val;
            }
        }
    }
    public static function &load_by_id($Index) {
        $Index = (int) $Index;
        $sql = sprintf('SELECT * FROM `MVD`.`User` WHERE `Index` = "%d";',
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
        echo "\t<td>".$this->Index."<td>\n";
        echo "\t<td>".$this->Vorname."<td>\n";
        echo "\t<td>".$this->Nachname."<td>\n";
        echo "\t<td>".$this->Mitglied."<td>\n";
        echo "\t<td>".$this->Instrument."<td>\n";
        echo "\t<td>".$this->Stimme."<td>\n";
        echo "\t<td>".$this->getMail."<td>\n";
        echo "</tr>\n";
    }
};
?>