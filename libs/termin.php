<?php
class Termin
{
    private $_data = array('Index' => null, 'Datum' => null, 'Uhrzeit' => null, 'Uhrzeit2' => null, 'Name' => null, 'Auftritt' => null, 'Ort1' => null, 'Ort2' => null, 'Ort3' => null, 'Ort4' => null, 'Beschreibung' => null, 'published' => null);
    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'Datum':
	    case 'Uhrzeit':
	    case 'Uhrzeit2':
	    case 'Name':
	    case 'Auftritt':
	    case 'Ort1':
	    case 'Ort2':
	    case 'Ort3':
	    case 'Ort4':
	    case 'Beschreibung':
	    case 'published':
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
	    case 'Datum':
            $this->_data[$key] = trim($val);
            break;
	    case 'Uhrzeit':
            $this->_data[$key] = trim($val);
            break;
	    case 'Uhrzeit2':
            $this->_data[$key] = trim($val);
            break;
	    case 'Name':
            $this->_data[$key] = trim($val);
            break;
	    case 'Beschreibung':
            $this->_data[$key] = trim($val);
            break;
	    case 'Auftritt':
            $this->_data[$key] = (bool)$val;
            break;
	    case 'Ort1':
            $this->_data[$key] = trim($val);
            break;
	    case 'Ort2':
            $this->_data[$key] = trim($val);
            break;
	    case 'Ort3':
            $this->_data[$key] = trim($val);
            break;
	    case 'Ort4':
            $this->_data[$key] = trim($val);
            break;
	    case 'published':
            $this->_data[$key] = (bool)$val;
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
        if(!$this->Datum) return false;
        if(!$this->Name) return false;
        return true;
    }
    protected function insert() {
        $sql = sprintf('INSERT INTO `MVD`.`Termine` (`Datum`, `Uhrzeit`, `Uhrzeit2`, `Name`, `Beschreibung`, `Auftritt`, `Ort1`, `Ort2`, `Ort3`, `Ort4`, `published`) VALUES ("%s", %s, %s, "%s", "%s", "%d", "%s", "%s", "%s", "%s", "%d");',
        mysqli_real_escape_string($GLOBALS['conn'], $this->Datum),
        $this->Uhrzeit == '' ? 'NULL': "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->Uhrzeit)."\"",
        $this->Uhrzeit2 == '' ? 'NULL': "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->Uhrzeit2)."\"",
        mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Beschreibung),
        $this->Auftritt,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Ort1),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Ort2),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Ort3),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Ort4),
        $this->published
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }
    protected function update() {
        $sql = sprintf('UPDATE `MVD`.`Termine` SET `Datum` = "%s", `Uhrzeit` = "%s", `Uhrzeit2` = "%s", `Name` = "%s", `Beschreibung` = "%s", `Auftritt` = "%d", `Ort1` = "%s", `Ort2` = "%s", `Ort3` = "%s", `Ort4` = "%s", `published` = "%d" WHERE `Index` = "%d";',
        mysqli_real_escape_string($GLOBALS['conn'], $this->Datum),
        $this->Uhrzeit == 'NULL' ? 'NULL': "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->Uhrzeit)."\"",
        $this->Uhrzeit2 == 'NULL' ? 'NULL': "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->Uhrzeit2)."\"",
        mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Beschreibung),
        $this->Auftritt,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Ort1),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Ort2),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Ort3),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Ort4),
        $this->published,
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if(!$dbr) return false;
        return true;
    }
    public function delete() {
        if(!$this->Index) return false;
        $sql = sprintf('DELETE FROM `MVD`.`Termine` WHERE `Index` = "%d";',
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
        $sql = sprintf('SELECT * FROM `MVD`.`Termine` WHERE `Index` = "%d";',
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
        if($this->Auftritt) {
            echo "<tr class=\"w3-lime\">\n";            
        }
        else {
            echo "<tr class=\"w3-khaki\">\n";            
        }
        echo "  <td>".germanDate($this->Datum, 0)."</td>\n";
        echo "  <td>".$this->Uhrzeit."</td>\n";
        echo "  <td>".$this->Uhrzeit2."</td>\n";
        echo "  <td>".$this->Name."</td>\n";
        echo "  <td>".$this->Beschreibung."</td>\n";
        echo "  <td>".$this->Ort1."</td>\n";
        echo "  <td>".$this->Ort2."</td>\n";
        echo "  <td>".$this->Ort3."</td>\n";
        echo "  <td>".$this->Ort4."</td>\n";
        echo "  <td>".bool2string($this->published)."</td>\n";
        echo "</tr>\n";
    }
    public function printBasicTableLine() {
        if($this->Auftritt) {
            echo "<tr class=\"w3-lime\">\n";            
        }
        else {
            echo "<tr class=\"w3-khaki\">\n";            
        }
        echo "  <td>".germanDate($this->Datum, 1)."</td>\n";
        echo "  <td>".$this->Uhrzeit."</td>\n";
        echo "  <td>".$this->Uhrzeit2."</td>\n";
        echo "  <td>".$this->Name."</td>\n";
        echo "  <td>".$this->Ort1."</td>\n";
        echo "</tr>\n";
    }
};
?>