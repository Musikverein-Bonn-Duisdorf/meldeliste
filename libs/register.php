<?php
class Register
{
    private $_data = array(
        'Index' => null,
        'Name' => null,
        'Sortierung' => null,
        'Row' => null,
        'ArcMin' => null,
        'ArcMax' => null,
        'Color' => null,
    );

    public function __get($key) {
        switch($key) {
        case 'Index':
        case 'Name':
        case 'Sortierung':
        case 'Row':
        case 'ArcMin':
        case 'ArcMax':
        case 'Color':
            return $this->_data[$key];
        default:
            break;
        }
    }

    public function __set($key, $val) {
        switch($key) {
        case 'ArcMin':
        case 'ArcMax':
            $this->_data[$key] = $val;
            break;
        case 'Index':
        case 'Sortierung':
        case 'Row':
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
        if($this->Color !== null && $this->Color !== '' && !isHexColor($this->Color)) return false;
        return true;
    }

    public function fill_from_array($row) {
        foreach($row as $key => $val) {
            if($key === 'Color') {
                $this->Color = $val;
            }
            else {
                $this->_data[$key] = $val;
            }
        }
    }

    public function isProtectedName() {
        $n = html_entity_decode((string)$this->Name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $n = mb_strtolower(trim($n), 'UTF-8');
        return ($n === 'keins');
    }

    public function memberTable() {
        $style = $this->headerStyle();
        $cls = $style === '' ? $GLOBALS['optionsDB']['colorTitleBar'] : '';
        $styleAttr = $style !== '' ? ' style="'.$style.'"' : '';
        echo '<div class="w3-container '.$cls.' w3-margin-top register-section-header"'.$styleAttr.'><h3>'
            .htmlspecialchars(html_entity_decode((string)$this->Name, ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES, 'UTF-8')
            .' ('.$this->members().')</h3></div>';
        $sql = sprintf(
            'SELECT * FROM `%sUser` INNER JOIN (SELECT `Index` AS `iIndex`, `Register` FROM `%sInstrument`) `%sInstrument` ON `Instrument` = `iIndex` WHERE `Deleted` != 1 AND `Active` = 1 AND `Register` = "%d" ORDER BY `Nachname`, `Vorname`;',
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        while($row = mysqli_fetch_array($dbr)) {
            $user = new User;
            $user->load_by_id($row['Index']);
            $user->printTableLine();
        }
    }

    public function headerStyle() {
        $hex = normalizeHexColor($this->Color);
        if($hex === '') {
            return '';
        }
        return 'background-color:'.$hex.';color:'.hexContrastText($hex).';';
    }

    public function getMembers() {
        $sql = sprintf(
            'SELECT `Index`, `Deleted` FROM `%sUser` INNER JOIN (SELECT `Index` AS `iIndex`, `Register` FROM `%sInstrument`) `%sInstrument` ON `Instrument` = `iIndex` WHERE `Deleted` != 1 AND `Active` = 1 AND `Register` = "%d";',
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $members = array();
        while($row = mysqli_fetch_array($dbr)) {
            array_push($members, $row['Index']);
        }
        return $members;
    }

    public function members() {
        return sizeof($this->getMembers());
    }

    public function load_by_id($Index) {
        $Index = (int)$Index;
        $sql = sprintf(
            'SELECT * FROM `%sRegister` WHERE `Index` = "%d";',
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
        $instruments = 0;
        $sql = sprintf(
            'SELECT COUNT(`Index`) AS `CNT` FROM `%sInstrument` WHERE `Register` = %d;',
            $GLOBALS['dbprefix'],
            (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if($dbr && ($row = mysqli_fetch_array($dbr))) {
            $instruments = (int)$row['CNT'];
        }
        return array(
            'instruments' => $instruments,
            'members' => $this->members(),
        );
    }

    public function canDelete() {
        if(!$this->Index) return false;
        if($this->isProtectedName()) return false;
        $u = $this->usageCount();
        return ($u['instruments'] === 0);
    }

    public function save() {
        if(!$this->is_valid()) return false;
        if($this->Index > 0) {
            return $this->update();
        }
        return $this->insert();
    }

    protected function insert() {
        $color = $this->Color !== '' ? $this->Color : '#cccccc';
        $sql = sprintf(
            'INSERT INTO `%sRegister` (`Name`, `Sortierung`, `Row`, `ArcMin`, `ArcMax`, `Color`) VALUES ("%s", %d, %d, %s, %s, "%s");',
            $GLOBALS['dbprefix'],
            mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
            (int)$this->Sortierung,
            (int)$this->Row,
            $this->sqlDoubleOrNull($this->ArcMin),
            $this->sqlDoubleOrNull($this->ArcMax),
            mysqli_real_escape_string($GLOBALS['conn'], $color)
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }

    protected function update() {
        $color = $this->Color !== '' ? $this->Color : '#cccccc';
        $sql = sprintf(
            'UPDATE `%sRegister` SET `Name` = "%s", `Sortierung` = %d, `Row` = %d, `ArcMin` = %s, `ArcMax` = %s, `Color` = "%s" WHERE `Index` = %d;',
            $GLOBALS['dbprefix'],
            mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
            (int)$this->Sortierung,
            (int)$this->Row,
            $this->sqlDoubleOrNull($this->ArcMin),
            $this->sqlDoubleOrNull($this->ArcMax),
            mysqli_real_escape_string($GLOBALS['conn'], $color),
            (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        return (bool)$dbr;
    }

    private function sqlDoubleOrNull($val) {
        if($val === null || $val === '') {
            return 'NULL';
        }
        return (string)(float)$val;
    }

    public function delete() {
        if(!$this->canDelete()) return false;
        $sql = sprintf(
            'DELETE FROM `%sRegister` WHERE `Index` = %d LIMIT 1;',
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
