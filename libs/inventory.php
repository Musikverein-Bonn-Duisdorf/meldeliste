<?php
class Inventory
{
    private $_data = array('Index' => null, 'Typ' => null, 'Prefix' => null, 'Protected' => 0, 'Sortierung' => null);
    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'Typ':
	    case 'Prefix':
	    case 'Protected':
	    case 'Sortierung':
            return $this->_data[$key];
        default:
            break;
        }
    }
    public function __set($key, $val) {
        switch($key) {
	    case 'Index':
	    case 'Sortierung':
	    case 'Protected':
            $this->_data[$key] = (int)$val;
            break;
	    case 'Typ':
            $this->_data[$key] = trim($val);
            break;
	    case 'Prefix':
            $this->_data[$key] = RegNumber::normalizePrefix($val);
            break;
        default:
            break;
        }
    }
    public function is_valid() {
        if(!$this->Typ) return false;
        if(!$this->Prefix) return false;
        if(!preg_match('/^[A-Z0-9]+$/', $this->Prefix)) return false;
        return true;
    }
    public function fill_from_array($row) {
        foreach($row as $key => $val) {
            if($key === 'Prefix') {
                $this->Prefix = $val;
            }
            else {
                $this->_data[$key] = $val;
            }
        }
    }
    public function load_by_id($Index) {
        $Index = (int) $Index;
        $sql = sprintf('SELECT * FROM `%sInventory` WHERE `Index` = "%d";',
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

    public function prefixInUse($prefix, $excludeId = 0) {
        $prefix = RegNumber::normalizePrefix($prefix);
        $sql = sprintf(
            'SELECT `Index` FROM `%sInventory` WHERE `Prefix` = "%s" AND `Index` != %d LIMIT 1;',
            $GLOBALS['dbprefix'],
            mysqli_real_escape_string($GLOBALS['conn'], $prefix),
            (int)$excludeId
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        return (bool)($dbr && mysqli_fetch_array($dbr));
    }

    public function usageCount() {
        $inv = 0;
        $sql = sprintf(
            'SELECT COUNT(`Index`) AS `CNT` FROM `%sInventories` WHERE `Inventory` = %d;',
            $GLOBALS['dbprefix'],
            (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if($dbr && ($row = mysqli_fetch_array($dbr))) $inv = (int)$row['CNT'];

        $inst = 0;
        if($this->Prefix === RegNumber::DEFAULT_INSTR_PREFIX || (int)$this->Protected === 1) {
            $sql = sprintf('SELECT COUNT(`Index`) AS `CNT` FROM `%sInstruments`;', $GLOBALS['dbprefix']);
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
            if($dbr && ($row = mysqli_fetch_array($dbr))) $inst = (int)$row['CNT'];
        }
        return array('inventories' => $inv, 'instruments' => $inst);
    }

    public function canDelete() {
        if(!$this->Index) return false;
        if((int)$this->Protected === 1) return false;
        $u = $this->usageCount();
        return ($u['inventories'] === 0 && $u['instruments'] === 0);
    }

    public function save() {
        if(!$this->is_valid()) return false;
        if($this->prefixInUse($this->Prefix, (int)$this->Index)) return false;
        if($this->Index > 0) {
            return $this->update();
        }
        return $this->insert();
    }

    protected function insert() {
        $sql = sprintf(
            'INSERT INTO `%sInventory` (`Typ`, `Prefix`, `Protected`, `Sortierung`) VALUES ("%s", "%s", "%d", "%d");',
            $GLOBALS['dbprefix'],
            mysqli_real_escape_string($GLOBALS['conn'], $this->Typ),
            mysqli_real_escape_string($GLOBALS['conn'], $this->Prefix),
            (int)$this->Protected,
            (int)$this->Sortierung ? (int)$this->Sortierung : 1
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }

    protected function update() {
        $sql = sprintf(
            'UPDATE `%sInventory` SET `Typ` = "%s", `Prefix` = "%s", `Protected` = "%d", `Sortierung` = "%d" WHERE `Index` = "%d";',
            $GLOBALS['dbprefix'],
            mysqli_real_escape_string($GLOBALS['conn'], $this->Typ),
            mysqli_real_escape_string($GLOBALS['conn'], $this->Prefix),
            (int)$this->Protected,
            (int)$this->Sortierung,
            (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        return (bool)$dbr;
    }

    public function delete() {
        if(!$this->canDelete()) return false;
        $sql = sprintf(
            'DELETE FROM `%sInventory` WHERE `Index` = "%d" LIMIT 1;',
            $GLOBALS['dbprefix'],
            (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = null;
        return true;
    }
};
?>
