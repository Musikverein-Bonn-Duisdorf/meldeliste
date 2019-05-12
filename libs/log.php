<?php
class Log
{
    private $_data = array('Index' => null, 'User' => null, 'Timestamp' => null, 'Type' => null, 'Message' => null);
    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'User':
	    case 'Timestamp':
            case 'Type':
	    case 'Message':
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
	    case 'User':
		$this->_data[$key] = (int)$val;
		break;
	    case 'Timestamp':
		$this->_data[$key] = trim($val);
		break;
	    case 'Type':
		$this->_data[$key] = (int)$val;
		break;
	    case 'Message':
		$this->_data[$key] = trim($val);
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
        $sql = sprintf('INSERT INTO `MVD`.`Log` (`User`, `Type`, `Message`) VALUES ("%d", "%d", "%s");',
		       $this->User,
		       $this->Type,
		       mysqli_real_escape_string($GLOBALS['conn'], $this->Message)
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }
    protected function update() {
        $sql = sprintf('UPDATE `MVD`.`Log` SET `User` = "%d", `Type` = "%d", `Message` = "%s" WHERE `Index` = "%d";',
		       $this->User,
		       $this->Type,
		       mysqli_real_escape_string($GLOBALS['conn'], $this->Message),
		       $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if(!$dbr) return false;
        return true;
    }
    public function delete() {
        if(!$this->Index) return false;
        $sql = sprintf('DELETE FROM `MVD`.`Log` WHERE `Index` = "%d";',
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
    public function load_by_id($Index) {
        $Index = (int) $Index;
        $sql = sprintf('SELECT * FROM `MVD`.`Log` WHERE `Index` = "%d";',
		       $Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        $row = mysqli_fetch_array($dbr);
        if(is_array($row)) {
            $this->fill_from_array($row);
        }
    }
    protected function color() {
	switch($this->Type) {
	    case 1:
		break;
	    case 2:
		break;
	    case 3:
		break;
	    case 4:
		break;
	    case 5:
		break;
	    default:
		break;
	}
    }
    public function printTableLine() {
	echo "<div class=\"w3-row w3-hover-gray w3-padding w3-mobile w3-border-bottom w3-border-black\">\n";
	echo "  <div class=\"w3-col l3 w3-container\">".$this->Message."</div>\n";
	echo "</div>\n";
    }
};
?>
