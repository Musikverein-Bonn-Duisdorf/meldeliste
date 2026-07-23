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
        if(!logMessageHasChanges($Message)) {
            return;
        }
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
       $this->User = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
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
        if($this->Type === null || $this->Type === '') return false;
        if($this->Message === null || $this->Message === '') return false;
        return true;
    }
    public function getLast() {
        $sql = sprintf('SELECT * FROM `%sLog` ORDER BY `Index` DESC LIMIT 1;',
        $GLOBALS['dbprefix']
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = mysqli_fetch_array($dbr);
        if(is_array($row)) {
            $this->fill_from_array($row);
        }        
    }
    protected function insert() {
        $sql = sprintf('INSERT INTO `%sLog` (`User`, `Type`, `Message`) VALUES ("%d", "%d", "%s");',
        $GLOBALS['dbprefix'],
        $this->User,
        $this->Type,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Message)
        );
        $last = new Log;
        $last->getLast();
        if($last->Message == mysqli_real_escape_string($GLOBALS['conn'], $this->Message) && $this->User == $last->User) {
            $last->now();
            return true;
        }
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

    protected function now() {
        $sql = sprintf('UPDATE `%sLog` SET `Timestamp` = NOW() WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
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
            $type  = "INFO";
            $chipMod = 'info';
            break;
        case 6:
            $type  = "EMAIL";
            $chipMod = 'email';
            break;
        case 5:
            $type  = "DB UPDATE";
            $chipMod = 'db-update';
            break;
        case 4:
            $type  = "DB INSERT";
            $chipMod = 'db-insert';
            break;
        case 3:
            $type  = "DB DELETE";
            $chipMod = 'db-delete';
            break;
        case 2:
            $type  = "WARNING";
            $chipMod = 'warning';
            break;
        case 1:
            $type  = "ERROR";
            $chipMod = 'error';
            break;
        case 0:
            $type  = "FATAL";
            $chipMod = 'fatal';
            break;
        default:
            $type  = "";
            $chipMod = 'default';
            break;
        }
        $hover = isset($GLOBALS['optionsDB']['HoverEffect']) ? $GLOBALS['optionsDB']['HoverEffect'] : '';
        $userLabel = $User->Index ? $User->getName() : 'SYSTEM';
        $classes = trim('log-row list-row '.$hover);
        $tsRaw = (string)$this->Timestamp;
        $datePart = (string)germanDate($tsRaw, false);
        $timePart = '';
        if(strlen($tsRaw) >= 19) {
            $timePart = substr($tsRaw, 11, 8);
        }
        elseif(strlen($tsRaw) >= 16) {
            $timePart = substr($tsRaw, 11, 5);
        }

        echo '<div id="'.(int)$this->Index.'" class="'.htmlspecialchars($classes, ENT_QUOTES, 'UTF-8').'">';
        echo '<div class="log-id">';
        echo '<div class="log-time">';
        echo '<span class="log-date">'.htmlspecialchars($datePart, ENT_QUOTES, 'UTF-8').'</span>';
        if($timePart !== '') {
            echo '<span class="log-clock">'.htmlspecialchars($timePart, ENT_QUOTES, 'UTF-8').'</span>';
        }
        echo '</div>';
        if($type !== '') {
            echo '<span class="w3-tag log-type-chip log-type-chip--'.htmlspecialchars($chipMod, ENT_QUOTES, 'UTF-8').'">'
                .htmlspecialchars($type, ENT_QUOTES, 'UTF-8').'</span>';
        }
        echo '</div>';
        echo '<div class="log-rail" aria-hidden="true"></div>';
        echo '<div class="log-main">';
        echo '<div class="log-user">'.htmlspecialchars($userLabel, ENT_QUOTES, 'UTF-8').'</div>';
        echo '<div class="log-message">'.$this->Message.'</div>';
        echo '</div>';
        echo '</div>';
    }
};
?>
