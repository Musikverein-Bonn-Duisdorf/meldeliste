<?php
class Shift
{
    private $_data = array('Index' => null, 'Termin' => null, 'Name' => null, 'Start' => null, 'End' => null, 'Bedarf' => null);
    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'Termin':
	    case 'Name':
	    case 'Start':
        case 'End':
	    case 'Bedarf':
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
	    case 'Bedarf':
            $this->_data[$key] = (int)$val;
            break;
	    case 'Start':
	    case 'End':
            $this->_data[$key] = trim($val);
            break;
	    case 'Name':
            $this->_data[$key] = htmlentities(trim($val));
            break;
        default:
            break;
        }	
    }
    public function getVars() {
        $sql = sprintf('SELECT * FROM `%sSchichten` WHERE `Index` = %d;',
        $GLOBALS['dbprefix'],
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = mysqli_fetch_array($dbr);

        return sprintf("Schicht: %d, Termin: %d, Name: %s, Start: %s, Ende: %s, Bedarf: %d",
        $this->Index,
        $this->Termin,
        $this->Name,
        $this->Start,
        $this->End,
        $this->Bedarf
        );
    }
    public function getTime() {
        if($this->End) {
            $str=sql2timeRaw($this->Start)." - ".sql2time($this->End);
        }
        else {
            $str="ab ".sql2time($this->Start);
        }
        return $str;
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
        if(!$this->Name) return false;
        if(!$this->Termin) return false;
        return true;
    }
    protected function insert() {
        $sql = sprintf('INSERT INTO `%sSchichten` (`Termin`, `Name`, `Start`, `End`, `Bedarf`) VALUES ("%d", "%s", "%s", "%s", "%d");',
        $GLOBALS['dbprefix'],
        $this->Termin,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
        $this->Start,
        $this->End,
        $this->Bedarf
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }
    protected function update() {
        $sql = sprintf('UPDATE `%sSchichen` SET `Termin` = "%d", `Name` = "%s", `Start` = "%s", `End` = "%s", `Bedarf` = "%d" WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        $this->Termin,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
        $this->Start,
        $this->End,
        $this->Bedarf,
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        return true;
    }
    public function delete() {
        if(!$this->Index) return false;

        $sql = sprintf('SELECT * FROM `%sSchichtmeldung` WHERE `Shift` = "%d";',
        $GLOBALS['dbprefix'],
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        while($row = mysqli_fetch_array($dbr)) {
            $n = new Shiftmeldung;
            $n->load_by_id($row['Index']);
            $n->delete();
        }

        $sql = sprintf('DELETE FROM `%sSchichten` WHERE `Index` = "%d" LIMIT 1;',
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
    public function fill_from_array($row) {
        foreach($row as $key => $val) {
                $this->_data[$key] = $val;
        }
    }
    public function load_by_id($Index) {
        $Index = (int) $Index;
        $sql = sprintf('SELECT * FROM `%sSchichten` WHERE `Index` = "%d";',
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
