<?php
class Email
{
    private $_data = array('Index' => null, 'Sender' => null, 'Date' => null, 'Subject' => null, 'Body' => null, 'Draft' => null);
    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'Sender':
	    case 'Date':
	    case 'Subject':
        case 'Body':
	    case 'Draft':
            return $this->_data[$key];
            break;
        default:
            break;
        }
    }
    public function __set($key, $val) {
        switch($key) {
	    case 'Index':
	    case 'Sender':
	    case 'Draft':
            $this->_data[$key] = (int)$val;
            break;
	    case 'Date':
	    case 'Subject':
            $this->_data[$key] = trim($val);
            break;
	    case 'Body':
            $this->_data[$key] = htmlentities(trim($val));
            break;
        default:
            break;
        }	
    }
    public function getVars() {
        $sql = sprintf('SELECT * FROM `%sEmail` WHERE `Index` = %d;',
        $GLOBALS['dbprefix'],
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = mysqli_fetch_array($dbr);

        return sprintf("Email: %d, Betreff: %d, Sender: %s, Entwurf: %s, Text: %s",
        $this->Index,
        $this->Subject,
        $this->Sender,
        $this->Draft,
        $this->Body
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
        if(!$this->Subject) return false;
        if(!$this->Body) return false;
        if(!$this->Sender) return false;
        if(!$this->Date) return false;
        return true;
    }
    protected function insert() {
        $sql = sprintf('INSERT INTO `%sEmail` (`Date`, `Sender`, `Subject`, `Body`, `Draft`) VALUES ("%s", "%d", "%s", "%s", "%d");',
                       $GLOBALS['dbprefix'],
                       $this->Date,
                       $this->Sender,
                       mysqli_real_escape_string($GLOBALS['conn'], $this->Subject),
                       mysqli_real_escape_string($GLOBALS['conn'], $this->Body),
                       $this->Draft
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }
    protected function update() {
        $sql = sprintf('UPDATE `%sEmail` SET `Date` = "%s", `Sender` = "%d", `Subject` = "%s", `Body` = "%s", `Draft` = "%d" WHERE `Index` = "%d";',
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
