<?php
class SentEmails
{
    private $_data = array('Index' => null, 'Email' => null, 'Date' => null, 'Receiver' => null);
    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'Email':
	    case 'Date':
	    case 'Receiver':
            return $this->_data[$key];
            break;
        default:
            break;
        }
    }
    public function __set($key, $val) {
        switch($key) {
	    case 'Index':
	    case 'Email':
	    case 'Receiver':
            $this->_data[$key] = (int)$val;
            break;
	    case 'Date':
            $this->_data[$key] = trim($val);
            break;
        default:
            break;
        }	
    }
    public function getVars() {
        $sql = sprintf('SELECT * FROM `%sSentEmails` WHERE `Index` = %d;',
        $GLOBALS['dbprefix'],
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = mysqli_fetch_array($dbr);

        return sprintf("Gesendete Email: %d, Datum: %s, Receiver: %s",
        $this->Index,
        $this->Email,
        $this->Date,
        $this->Receiver
        );
    }
    public function save() {
        if(!$this->is_valid()) return false;
        if($this->Index > 0) {
            $this->update();
            $logentry = new Log;
            $logentry->DBupdate($this->getVars());
        }
        else {
            $this->insert();
            $logentry = new Log;
            $logentry->DBinsert($this->getVars());
        }
    }
    public function is_valid() {
        if(!$this->Email) return false;
        if(!$this->Date) return false;
        if(!$this->Receiver) return false;
        return true;
    }
    protected function insert() {
        $sql = sprintf('INSERT INTO `%sSentEmails` (`Email`, `Date`, `Receiver`) VALUES ("%d", "%s", "%d");',
                       $GLOBALS['dbprefix'],
                       $this->Email,
                       $this->Date,
                       $this->Receiver
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }
    protected function update() {
        $sql = sprintf('UPDATE `%sEmail` SET `Email` = "%s", `Date` = "%d", `Receiver` = "%s", `Body` = "%s", `Draft` = "%d" WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
                       $this->Date,
                       $this->Sender,
                       mysqli_real_escape_string($GLOBALS['conn'], $this->Subject),
                       mysqli_real_escape_string($GLOBALS['conn'], $this->Body),
                       $this->Draft,
                       $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        return true;
    }
    public function delete() {
        if(!$this->Index) return false;
        $logentry = new Log;
        $logentry->DBdelete($this->getVars());

        $sql = sprintf('DELETE FROM `%sEmail` WHERE `Index` = "%d" LIMIT 1;',
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
        $sql = sprintf('SELECT * FROM `%sEmail` WHERE `Index` = "%d";',
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
