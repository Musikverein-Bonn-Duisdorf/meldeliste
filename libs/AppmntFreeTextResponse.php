<?php
class AppmntFreeTextResponse
{
    private $_data = array('Index' => null, 'Termin' => null, 'User' => null, 'Text' => null);

    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'Termin':
	    case 'User':
	    case 'Text':
            return $this->_data[$key];
            break;
        default:
            break;
        }
    }

    public function __set($key, $val) {
        switch($key) {
	    case 'Index':
	    case 'Termin':
	    case 'User':
            $this->_data[$key] = (int)$val;
            break;
	    case 'Text':
            $this->_data[$key] = trim($val);
            break;
        default:
            $this->_data[$key] = $val;
            break;
        }	
    }

    public function getChanges() {
        $old = new AppmntFreeTextResponse;
        $old->load_by_id($this->Index);

        $t = new Termin;
        $t->load_by_id($this->Termin);

        $str = sprintf("FreeText-ID: %d <b>%s</b>, Termin: (%d) <b>%s</b>",
                       $this->Index,
                       $t->Name,
                       $this->Termin,
                       $this->Text
        );
        if($this->Text != $old->Text) $str.=", Text: ".$old->Text." &rArr; <b>".$this->Text."</b>";
        return $str;
    }

    public function getVars() {
        $t = new Termin;
        $t->load_by_id($this->Termin);
        return sprintf("FreeText-ID: %d, Termin: (%d) <b>%s</b>, Text: <b>%s</b>",
        $this->Index,
        $this->Termin,
        $t->Name,
        $this->Text,
        );
    }

    public function save() {
        if(!$this->is_valid()) return false;
        if($this->Index > 0) {
            $logentry = new Log;
            $logentry->DBupdate($this->getChanges());
            $this->update();
        }
        else {
            $this->insert();
            $logentry = new Log;
            $logentry->DBinsert($this->getVars());
        }
    }

    public function is_valid() {
        if(!$this->User) return false;
        if(!$this->Termin) return false;
        return true;
    }

    protected function insert() {
        $sql = sprintf('INSERT INTO `%sAppmntFreeTextResponse` (`Termin`, `User`, `Text`) VALUES ("%d", "%d", "%s");',
                       $GLOBALS['dbprefix'],
                       $this->Termin,
                       $this->User,
                       $this->Text
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }
    
    public function delete() {
        if(!$this->Index) return false;
        $sql = sprintf('DELETE FROM `%sAppmntFreeTextResponse` WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $logentry = new Log;
        $logentry->DBdelete($this->getVars());
        $this->_data['Index'] = null;
        return true;
    }

    protected function update() {
        $sql = sprintf('UPDATE `%sAppmntFreeTextResponse` SET `Termin` = "%d", `User` = "%d", `Text` = "%s" WHERE `Index` = "%d";',
                       $GLOBALS['dbprefix'],
                       $this->Termin,
                       $this->User,
                       $this->Text,
                       $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        return true;
    }

    
    public function load_by_id($Index) {
        $Index = (int) $Index;
        $sql = sprintf('SELECT * FROM `%sAppmntFreeTextResponse` WHERE `Index` = "%d";',
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

    public function load_by_user_event($user, $event) {
        $sql = sprintf('SELECT * FROM `%sAppmntFreeTextResponse` WHERE `User` = "%d" AND `Termin` = "%d";',
        $GLOBALS['dbprefix'],
        $user,
        $event
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = mysqli_fetch_array($dbr);
        if(is_array($row)) {
            $this->fill_from_array($row);
        }
    }

    public function fill_from_array($row) {
        foreach($row as $key => $val) {
            $this->_data[$key] = $val;
        }
    }
};
?>
