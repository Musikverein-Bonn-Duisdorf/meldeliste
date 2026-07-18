<?php
class Instrument
{
    private $_data = array(
        'Index' => null,
        'Name' => null,
        'Register' => null,
        'Sortierung' => null,
        'Spielbar' => 1,
        'Color' => null,
    );

    public function __get($key) {
        switch($key) {
        case 'Index':
        case 'Name':
        case 'Register':
        case 'Sortierung':
        case 'Spielbar':
        case 'Color':
            return $this->_data[$key];
        default:
            break;
        }
    }

    public function __set($key, $val) {
        switch($key) {
        case 'Index':
        case 'Register':
        case 'Sortierung':
        case 'Spielbar':
            $this->_data[$key] = (int)$val;
            break;
        case 'Name':
            $this->_data[$key] = trim((string)$val);
            break;
        case 'Color':
            $hex = normalizeHexColor($val);
            $this->_data[$key] = $hex !== '' ? $hex : '';
            break;
        default:
            break;
        }
    }

    public function is_valid() {
        if(!$this->Name) return false;
        if((int)$this->Register < 1) return false;
        if($this->Color !== null && $this->Color !== '' && !isHexColor($this->Color)) return false;
        return true;
    }

    public function fill_from_array($row) {
        foreach($row as $key => $val) {
            if($key === 'Color') {
                $this->Color = $val;
            }
            elseif($key === 'Spielbar') {
                $this->_data['Spielbar'] = (int)$val;
            }
            else {
                $this->_data[$key] = $val;
            }
        }
    }

    public function load_by_id($Index) {
        $Index = (int)$Index;
        $sql = sprintf(
            'SELECT * FROM `%sInstrument` WHERE `Index` = "%d";',
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

    public function usageCount() {
        $users = 0;
        $sql = sprintf(
            'SELECT COUNT(`Index`) AS `CNT` FROM `%sUser` WHERE `Instrument` = %d AND `Deleted` = 0;',
            $GLOBALS['dbprefix'],
            (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if($dbr && ($row = mysqli_fetch_array($dbr))) {
            $users = (int)$row['CNT'];
        }

        $inv = 0;
        $sql = sprintf(
            'SELECT COUNT(`Index`) AS `CNT` FROM `%sInventories` WHERE `Instrument` = %d;',
            $GLOBALS['dbprefix'],
            (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if($dbr && ($row = mysqli_fetch_array($dbr))) {
            $inv = (int)$row['CNT'];
        }

        $meld = 0;
        $sql = sprintf(
            'SELECT COUNT(`Index`) AS `CNT` FROM `%sMeldungen` WHERE `Instrument` = %d;',
            $GLOBALS['dbprefix'],
            (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if($dbr && ($row = mysqli_fetch_array($dbr))) {
            $meld = (int)$row['CNT'];
        }

        $aus = 0;
        $sql = sprintf(
            'SELECT COUNT(`Index`) AS `CNT` FROM `%sAushilfen` WHERE `Instrument` = %d;',
            $GLOBALS['dbprefix'],
            (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if($dbr && ($row = mysqli_fetch_array($dbr))) {
            $aus = (int)$row['CNT'];
        }

        return array(
            'users' => $users,
            'inventories' => $inv,
            'meldungen' => $meld,
            'aushilfen' => $aus,
        );
    }

    public function canDelete() {
        if(!$this->Index) return false;
        $u = $this->usageCount();
        return ($u['users'] + $u['inventories'] + $u['meldungen'] + $u['aushilfen']) === 0;
    }

    public function save() {
        if(!$this->is_valid()) return false;
        if($this->Index > 0) {
            return $this->update();
        }
        return $this->insert();
    }

    protected function insert() {
        $color = $this->Color !== '' ? $this->Color : null;
        $sql = sprintf(
            'INSERT INTO `%sInstrument` (`Name`, `Register`, `Sortierung`, `Spielbar`, `Color`) VALUES ("%s", %d, %d, %d, %s);',
            $GLOBALS['dbprefix'],
            mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
            (int)$this->Register,
            (int)$this->Sortierung ? (int)$this->Sortierung : 1,
            (int)$this->Spielbar ? 1 : 0,
            $color === null
                ? 'NULL'
                : '"'.mysqli_real_escape_string($GLOBALS['conn'], $color).'"'
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }

    protected function update() {
        $color = $this->Color !== '' ? $this->Color : null;
        $sql = sprintf(
            'UPDATE `%sInstrument` SET `Name` = "%s", `Register` = %d, `Sortierung` = %d, `Spielbar` = %d, `Color` = %s WHERE `Index` = %d;',
            $GLOBALS['dbprefix'],
            mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
            (int)$this->Register,
            (int)$this->Sortierung,
            (int)$this->Spielbar ? 1 : 0,
            $color === null
                ? 'NULL'
                : '"'.mysqli_real_escape_string($GLOBALS['conn'], $color).'"',
            (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        return (bool)$dbr;
    }

    public function delete() {
        if(!$this->canDelete()) return false;
        $sql = sprintf(
            'DELETE FROM `%sInstrument` WHERE `Index` = %d LIMIT 1;',
            $GLOBALS['dbprefix'],
            (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = null;
        return true;
    }

    /** CSS-safe background for overview headers. */
    public function headerStyle() {
        $hex = normalizeHexColor($this->Color);
        if($hex === '') {
            return '';
        }
        return 'background-color:'.$hex.';';
    }
};
?>
