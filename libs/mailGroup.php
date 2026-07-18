<?php
/**
 * Named audience groups for mail and termin visibility (MELD-61).
 * MemberSpec uses AudienceSpec shape without nested mailGroups.
 */
class MailGroup
{
    private $_data = array(
        'Index' => null,
        'Name' => null,
        'MemberSpec' => null,
        'CreatedBy' => 0,
        'Created' => null,
    );

    public function __get($key) {
        if(array_key_exists($key, $this->_data)) {
            return $this->_data[$key];
        }
        return null;
    }

    public function __set($key, $val) {
        switch($key) {
        case 'Index':
        case 'CreatedBy':
            $this->_data[$key] = (int)$val;
            break;
        case 'Name':
            $this->_data[$key] = trim((string)$val);
            break;
        case 'MemberSpec':
        case 'Created':
            $this->_data[$key] = $val === null ? null : trim((string)$val);
            break;
        default:
            break;
        }
    }

    public function is_valid() {
        return $this->Name !== null && $this->Name !== '';
    }

    public function fill_from_array($row) {
        foreach($row as $key => $val) {
            if(array_key_exists($key, $this->_data)) {
                $this->_data[$key] = $val;
            }
        }
    }

    public static function ensureSchema() {
        static $done = false;
        if($done) return true;
        $table = new SQLtable('MailGroup');
        if(!$table->exists() || !$table->columnExists('MemberSpec')) {
            $manager = new DatabaseManager();
            $manager->create();
            $manager->repair();
        }
        $done = true;
        return (new SQLtable('MailGroup'))->exists();
    }

    /**
     * @return array{groups:string[],registers:int[],users:int[],mailGroups:int[]}
     */
    public function getMemberSpecArray() {
        return AudienceSpec::normalize($this->MemberSpec, array(
            'allowMailGroups' => false,
            'defaultGroups' => null,
        ));
    }

    /**
     * @param array $spec
     */
    public function setMemberSpecArray($spec) {
        $norm = AudienceSpec::normalize($spec, array(
            'allowMailGroups' => false,
            'defaultGroups' => null,
        ));
        unset($norm['mailGroups']);
        $payload = array(
            'groups' => $norm['groups'],
            'registers' => $norm['registers'],
            'users' => $norm['users'],
        );
        $this->MemberSpec = json_encode($payload);
    }

    public function memberCount($requireMail = false) {
        return count(AudienceSpec::resolveUserIds($this->getMemberSpecArray(), $requireMail));
    }

    public function getMemberLabel() {
        return AudienceSpec::formatLabel($this->getMemberSpecArray(), array('allowMailGroups' => false));
    }

    public function getVars() {
        $parts = array();
        $parts[] = sprintf('Gruppen-ID: <b>%d</b>', (int)$this->Index);
        $parts[] = logPart('Name', htmlspecialchars((string)$this->Name, ENT_QUOTES, 'UTF-8'));
        $parts[] = logPart('Mitglieder', htmlspecialchars($this->getMemberLabel(), ENT_QUOTES, 'UTF-8'));
        return implode(', ', $parts);
    }

    public function getChanges() {
        $old = new MailGroup();
        $old->load_by_id((int)$this->Index);
        $str = sprintf('Gruppen-ID: %d, <b>%s</b>', (int)$this->Index, htmlspecialchars((string)$this->Name, ENT_QUOTES, 'UTF-8'));
        if((string)$this->Name !== (string)$old->Name) {
            $str .= ', Name: '.htmlspecialchars((string)$old->Name, ENT_QUOTES, 'UTF-8')
                .' &rArr; <b>'.htmlspecialchars((string)$this->Name, ENT_QUOTES, 'UTF-8').'</b>';
        }
        $oldJson = AudienceSpec::canonicalJson($old->MemberSpec, array('allowMailGroups' => false));
        $newJson = AudienceSpec::canonicalJson($this->MemberSpec, array('allowMailGroups' => false));
        if($oldJson !== $newJson) {
            $str .= ', Mitglieder: '.htmlspecialchars($old->getMemberLabel(), ENT_QUOTES, 'UTF-8')
                .' &rArr; <b>'.htmlspecialchars($this->getMemberLabel(), ENT_QUOTES, 'UTF-8').'</b>';
        }
        return $str;
    }

    public function load_by_id($Index) {
        self::ensureSchema();
        $Index = (int)$Index;
        $sql = sprintf(
            'SELECT * FROM `%sMailGroup` WHERE `Index` = %d;',
            $GLOBALS['dbprefix'],
            $Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = $dbr ? mysqli_fetch_array($dbr) : null;
        if(is_array($row)) {
            $this->fill_from_array($row);
        }
    }

    /**
     * @return MailGroup[]
     */
    public static function listAll() {
        self::ensureSchema();
        $out = array();
        $sql = sprintf(
            'SELECT * FROM `%sMailGroup` ORDER BY `Name`, `Index`;',
            $GLOBALS['dbprefix']
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return $out;
        while($row = mysqli_fetch_array($dbr)) {
            $g = new MailGroup();
            $g->fill_from_array($row);
            $out[] = $g;
        }
        return $out;
    }

    public function save() {
        if(!$this->is_valid()) return false;
        self::ensureSchema();
        if((int)$this->Index > 0) {
            $logentry = new Log;
            $logentry->DBupdate($this->getChanges());
            return $this->update();
        }
        if(!$this->insert()) {
            return false;
        }
        $logentry = new Log;
        $logentry->DBinsert($this->getVars());
        return true;
    }

    protected function insert() {
        if($this->MemberSpec === null || $this->MemberSpec === '') {
            $this->setMemberSpecArray(AudienceSpec::emptySpec());
        }
        $createdBy = (int)$this->CreatedBy;
        if($createdBy <= 0 && isset($_SESSION['userid'])) {
            $createdBy = (int)$_SESSION['userid'];
        }
        $sql = sprintf(
            'INSERT INTO `%sMailGroup` (`Name`, `MemberSpec`, `CreatedBy`) VALUES ("%s", %s, %d);',
            $GLOBALS['dbprefix'],
            mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
            $this->sqlMemberSpec(),
            $createdBy
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }

    protected function update() {
        $sql = sprintf(
            'UPDATE `%sMailGroup` SET `Name` = "%s", `MemberSpec` = %s WHERE `Index` = %d;',
            $GLOBALS['dbprefix'],
            mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
            $this->sqlMemberSpec(),
            (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        return (bool)$dbr;
    }

    protected function sqlMemberSpec() {
        $raw = $this->MemberSpec;
        if($raw === null || $raw === '') {
            return 'NULL';
        }
        return '"'.mysqli_real_escape_string($GLOBALS['conn'], (string)$raw).'"';
    }

    public function delete() {
        if(!(int)$this->Index) return false;
        self::ensureSchema();
        $vars = $this->getVars();
        $sql = sprintf(
            'DELETE FROM `%sMailGroup` WHERE `Index` = %d;',
            $GLOBALS['dbprefix'],
            (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $logentry = new Log;
        $logentry->DBdelete($vars);
        $this->_data['Index'] = null;
        return true;
    }
}
?>
