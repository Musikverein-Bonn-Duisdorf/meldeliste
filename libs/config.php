<?php
class Config
{
    private $_data = array('Index' => null, 'Parameter' => null, 'Value' => null, 'Type' => null);
    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'Parameter':
	    case 'Value':
	    case 'Type':
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
	    case 'Parameter':
	    case 'Value':
	    case 'Type':
            $this->_data[$key] = trim($val);
            break;
        default:
            break;
        }	
    }

    public function getVars() {
        return sprintf(
            'Config-ID: %d, Parameter: <b>%s</b>, Value: <b>%s</b>, Type: <b>%s</b>',
            (int)$this->Index,
            htmlspecialchars((string)$this->Parameter, ENT_QUOTES, 'UTF-8'),
            formatConfigLogValue($this->Value, (string)$this->Type),
            htmlspecialchars((string)$this->Type, ENT_QUOTES, 'UTF-8')
        );
    }

    public function getChanges() {
        $old = new Config;
        $old->load_by_id($this->Index);
        
        $str = sprintf("Config-ID: %d <b>%s</b>",
        $this->Index,
        htmlspecialchars((string)$this->Parameter, ENT_QUOTES, 'UTF-8')
        );
        if($this->Value != $old->Value) {
            $str.=", Value: ".formatConfigLogValue($old->Value, (string)$this->Type)
                ." &rArr; <b>".formatConfigLogValue($this->Value, (string)$this->Type)."</b>";
        }
        if($this->Type != $old->Type) {
            $str.=", Type: ".htmlspecialchars((string)$old->Type, ENT_QUOTES, 'UTF-8')
                ." &rArr; <b>".htmlspecialchars((string)$this->Type, ENT_QUOTES, 'UTF-8')."</b>";
        }

        return $str;
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
        if(!$this->Parameter) return false;
        if(!$this->Value) return false;
        if(!$this->Type) return false;
        return true;
    }
    
    protected function insert() {
        $sql = sprintf('INSERT INTO `%sconfig` (`Parameter`, `Value`, `Type`) VALUES ("%s", "%s", "%s");',
        $GLOBALS['dbprefix'],
        $this->Parameter,
        $this->Value,
        $this->Type
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }
    
    protected function update() {
        $sql = sprintf('UPDATE `%sconfig` SET `Value` = "%s", `Type` = "%s" WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        mysqli_real_escape_string($GLOBALS['conn'], $this->Value),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Type),
        (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        return true;
    }
    
    public function delete() {
        if(!$this->Index) return false;
        $sql = sprintf('DELETE FROM `%sconfig` WHERE `Index` = "%d";',
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
        $sql = sprintf('SELECT * FROM `%sconfig` WHERE `Index` = "%d";',
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
