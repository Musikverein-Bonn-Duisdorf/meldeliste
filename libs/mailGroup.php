<?php
/**
 * Named audience groups for mail and termin visibility (MELD-61).
 * MemberSpec uses AudienceSpec shape without nested mailGroups.
 * PermissionSpec (MELD-137): JSON array of permission keys granted to members.
 */
class MailGroup
{
    private $_data = array(
        'Index' => null,
        'Name' => null,
        'MemberSpec' => null,
        'PermissionSpec' => null,
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
        case 'PermissionSpec':
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
        if(!$table->exists()
            || !$table->columnExists('MemberSpec')
            || !$table->columnExists('PermissionSpec')) {
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

    /**
     * Known permission keys granted by this group (unknown keys dropped).
     * @return string[]
     */
    public function getPermissionSpecArray() {
        return self::normalizePermissionSpec($this->PermissionSpec);
    }

    /**
     * @param array|string|null $spec
     */
    public function setPermissionSpecArray($spec) {
        $keys = self::normalizePermissionSpec($spec);
        $this->PermissionSpec = count($keys) ? json_encode(array_values($keys)) : null;
    }

    /**
     * @param array|string|null $raw
     * @return string[]
     */
    public static function normalizePermissionSpec($raw) {
        if(is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : array();
        }
        if(!is_array($raw)) {
            return array();
        }
        $allowed = array_flip(Permissions::permissionKeys());
        $out = array();
        foreach($raw as $key) {
            if(is_string($key) && isset($allowed[$key])) {
                $out[$key] = true;
            }
        }
        return array_keys($out);
    }

    /**
     * Short labels for granted permissions (empty string if none).
     */
    public function getPermissionLabel() {
        $keys = $this->getPermissionSpecArray();
        if(!count($keys)) {
            return '';
        }
        $labels = Permissions::permissionLabels();
        $parts = array();
        foreach($keys as $key) {
            $parts[] = isset($labels[$key]['short']) ? $labels[$key]['short'] : $key;
        }
        return implode(', ', $parts);
    }

    /**
     * Permission keys granted to a user via group membership (union).
     * @return string[]
     */
    public static function permissionsGrantedToUser($userId) {
        $sources = self::inheritedPermissionSources($userId);
        return array_keys($sources);
    }

    /**
     * Map permission key => group names that grant it (membership via MemberSpec).
     * @return array<string,string[]>
     */
    public static function inheritedPermissionSources($userId) {
        $userId = (int)$userId;
        $out = array();
        if($userId < 1) {
            return $out;
        }
        self::ensureSchema();
        foreach(self::listAll() as $g) {
            $granted = $g->getPermissionSpecArray();
            if(!count($granted)) {
                continue;
            }
            $memberIds = AudienceSpec::resolveUserIds($g->getMemberSpecArray(), false);
            if(!in_array($userId, array_map('intval', $memberIds), true)) {
                continue;
            }
            $name = (string)$g->Name;
            foreach($granted as $key) {
                if(!isset($out[$key])) {
                    $out[$key] = array();
                }
                $out[$key][] = $name;
            }
        }
        return $out;
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
        $permLabel = $this->getPermissionLabel();
        if($permLabel !== '') {
            $parts[] = logPart('Rechte', htmlspecialchars($permLabel, ENT_QUOTES, 'UTF-8'));
        }
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
        $oldPerm = $old->getPermissionSpecArray();
        $newPerm = $this->getPermissionSpecArray();
        if($oldPerm !== $newPerm) {
            $str .= ', Rechte: '.htmlspecialchars($old->getPermissionLabel() !== '' ? $old->getPermissionLabel() : '—', ENT_QUOTES, 'UTF-8')
                .' &rArr; <b>'.htmlspecialchars($this->getPermissionLabel() !== '' ? $this->getPermissionLabel() : '—', ENT_QUOTES, 'UTF-8').'</b>';
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

    /**
     * Whether this group's MemberSpec lists the user explicitly (users[]).
     *
     * @param int $userId
     * @return bool
     */
    public function hasExplicitUser($userId) {
        $userId = (int)$userId;
        if($userId <= 0) {
            return false;
        }
        return in_array($userId, array_map('intval', $this->getMemberSpecArray()['users']), true);
    }

    /**
     * Add/remove user in each named group's explicit users[] list.
     * Membership via roles/registers is unchanged.
     *
     * @param int $userId
     * @param array $wantGroupIds MailGroup Index values that should include the user
     */
    public static function syncUserExplicitMembership($userId, $wantGroupIds) {
        $userId = (int)$userId;
        if($userId <= 0) {
            return;
        }
        $want = array();
        foreach((array)$wantGroupIds as $id) {
            $id = (int)$id;
            if($id > 0) {
                $want[$id] = true;
            }
        }
        foreach(self::listAll() as $g) {
            $spec = $g->getMemberSpecArray();
            $users = array_map('intval', $spec['users']);
            $in = in_array($userId, $users, true);
            $should = isset($want[(int)$g->Index]);
            if($should && !$in) {
                $spec['users'][] = $userId;
                $g->setMemberSpecArray($spec);
                $g->save();
            }
            elseif(!$should && $in) {
                $spec['users'] = array_values(array_filter($users, function($u) use ($userId) {
                    return (int)$u !== $userId;
                }));
                $g->setMemberSpecArray($spec);
                $g->save();
            }
        }
        Permissions::clearEffectiveCache($userId);
    }

    public function save() {
        if(!$this->is_valid()) return false;
        self::ensureSchema();
        if((int)$this->Index > 0) {
            $logentry = new Log;
            $logentry->DBupdate($this->getChanges());
            $ok = $this->update();
            Permissions::clearEffectiveCache();
            return $ok;
        }
        if(!$this->insert()) {
            return false;
        }
        $logentry = new Log;
        $logentry->DBinsert($this->getVars());
        Permissions::clearEffectiveCache();
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
            'INSERT INTO `%sMailGroup` (`Name`, `MemberSpec`, `PermissionSpec`, `CreatedBy`) VALUES ("%s", %s, %s, %d);',
            $GLOBALS['dbprefix'],
            mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
            $this->sqlMemberSpec(),
            $this->sqlPermissionSpec(),
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
            'UPDATE `%sMailGroup` SET `Name` = "%s", `MemberSpec` = %s, `PermissionSpec` = %s WHERE `Index` = %d;',
            $GLOBALS['dbprefix'],
            mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
            $this->sqlMemberSpec(),
            $this->sqlPermissionSpec(),
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

    protected function sqlPermissionSpec() {
        $keys = $this->getPermissionSpecArray();
        if(!count($keys)) {
            return 'NULL';
        }
        return '"'.mysqli_real_escape_string($GLOBALS['conn'], json_encode(array_values($keys))).'"';
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
        Permissions::clearEffectiveCache();
        return true;
    }
}
?>
