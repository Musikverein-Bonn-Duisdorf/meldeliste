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
    public function fatal($Message) {
        $this->generate(0, $Message);
    }
    public function error($Message) {
        $this->generate(1, $Message);
    }
    public function warning($Message) {
        $this->generate(2, $Message);
    }
    public function DBdelete($Message) {
        $this->generate(3, $Message);
    }
    public function DBinsert($Message) {
        $this->generate(4, $Message);
    }
    public function DBupdate($Message) {
        $this->generate(5, $Message);
    }
    public function email($Message) {
        $this->generate(6, $Message);
    }
    public function info($Message) {
        $this->generate(7, $Message);
    }

    public function generate($Type, $Message) {
       $this->Type = $Type;
       $this->Message = $Message;
       $this->User = $_SESSION['userid'];
       $this->save();
       $this->Index = NULL;
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
        if(!$this->Type) return false;
        if(!$this->Message) return false;
        return true;
    }
    protected function insert() {
        $sql = sprintf('INSERT INTO `%sLog` (`User`, `Type`, `Message`) VALUES ("%d", "%d", "%s");',
        $GLOBALS['dbprefix'],
        $this->User,
        $this->Type,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Message)
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }
    protected function update() {
        $sql = sprintf('UPDATE `%sLog` SET `User` = "%d", `Type` = "%d", `Message` = "%s" WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
		       $this->User,
		       $this->Type,
		       mysqli_real_escape_string($GLOBALS['conn'], $this->Message),
		       $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        return true;
    }
    public function delete() {
        if(!$this->Index) return false;
        $sql = sprintf('DELETE FROM `%sLog` WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
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
        $sql = sprintf('SELECT * FROM `%sLog` WHERE `Index` = "%d";',
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
    public function printTableLine() {
        $User = new User;
        $User->load_by_id($this->User);
        switch($this->Type) {
        case 7:
            $color = $GLOBALS['optionsDB']['colorLogInfo'];
            $type  = "INFO";
            break;
        case 6:
            $color = $GLOBALS['optionsDB']['colorLogEmail'];
            $type  = "EMAIL";
            break;
        case 5:
            $color = $GLOBALS['optionsDB']['colorLogDBUpdate'];
            $type  = "DB UPDATE";
            break;
        case 4:
            $color = $GLOBALS['optionsDB']['colorLogDBInsert'];
            $type  = "DB INSERT";
            break;
        case 3:
            $color = $GLOBALS['optionsDB']['colorLogDBDelete'];
            $type  = "DB DELETE";
            break;
        case 2:
            $color = $GLOBALS['optionsDB']['colorLogWarning'];
            $type  = "WARNING";
            break;
        case 1:
            $color = $GLOBALS['optionsDB']['colorLogError'];
            $type  = "ERROR";
            break;
        case 0:
            $color = $GLOBALS['optionsDB']['colorLogFatal'];
            $type  = "FATAL";
            break;
        default:
            $color = $GLOBALS['optionsDB']['colorLogDefault'];
            $type  = "";
            break;
        }
	echo "<div id=\"".$this->Index."\" class=\"w3-row ".$color." ".$GLOBALS['optionsDB']['HoverEffect']." w3-padding w3-mobile w3-border-bottom w3-border-black\">\n";
	echo "  <div class=\"w3-col l1 w3-container\">".$this->Timestamp."</div>\n";
	echo "  <div class=\"w3-col l1 w3-container\"><b>".$type."</b></div>\n";
    echo "  <div class=\"w3-col l1 w3-container\">";
    if($User->Index) {
        echo $User->getName();
    }
    else {
        echo "<b>SYSTEM</b>";
    }
    echo "</div>\n";
	echo "  <div class=\"w3-col l9 w3-container\"><i>".$this->Message."</i></div>\n";
	echo "</div>\n";
    }
};
?>
