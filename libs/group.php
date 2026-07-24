<?php
/**
 * Named audience groups for mail, termin visibility, and permission inheritance (MELD-61 / MELD-137).
 * MemberSpec uses AudienceSpec shape without nested namedGroups.
 * PermissionSpec: JSON array of permission keys granted to members.
 */
class Group
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
        self::migrateTableFromMailGroup();
        $table = new SQLtable('Group');
        if(!$table->exists()
            || !$table->columnExists('MemberSpec')
            || !$table->columnExists('PermissionSpec')) {
            $manager = new DatabaseManager();
            $manager->create();
            $manager->repair();
            self::migrateTableFromMailGroup();
        }
        $done = true;
        return (new SQLtable('Group'))->exists();
    }

    /**
     * MELD-137: MailGroup → Group ohne Datenverlust.
     * - Nur MailGroup: RENAME (inkl. Index/AUTO_INCREMENT/PermissionSpec).
     * - Leere Group + MailGroup mit Daten: leere Group droppen, dann RENAME
     *   (passiert, wenn repair() Group aus DBconfig anlegt, bevor umbenannt wurde).
     * - Beide mit Daten: fehlende Zeilen aus MailGroup nach Group kopieren, dann MailGroup droppen.
     *
     * @return bool true wenn kein MailGroup mit Daten mehr übrig ist
     */
    public static function migrateTableFromMailGroup() {
        $prefix = isset($GLOBALS['dbprefix']) ? (string)$GLOBALS['dbprefix'] : '';
        $conn = $GLOBALS['conn'];
        $old = new SQLtable('MailGroup');
        $new = new SQLtable('Group');
        if(!$old->exists()) {
            return true;
        }

        $oldName = $prefix.'MailGroup';
        $newName = $prefix.'Group';

        if(!$new->exists()) {
            $sql = sprintf('RENAME TABLE `%s` TO `%s`;', $oldName, $newName);
            $ok = mysqli_query($conn, $sql);
            sqlerror();
            return (bool)$ok && !(new SQLtable('MailGroup'))->exists() && (new SQLtable('Group'))->exists();
        }

        $oldCount = self::tableRowCount($oldName);
        $newCount = self::tableRowCount($newName);
        if($oldCount < 0 || $newCount < 0) {
            return false;
        }

        // Empty Group shell from processSchema while MailGroup still holds data.
        if($newCount === 0) {
            $drop = sprintf('DROP TABLE `%s`;', $newName);
            if(!mysqli_query($conn, $drop)) {
                sqlerror();
                return false;
            }
            $sql = sprintf('RENAME TABLE `%s` TO `%s`;', $oldName, $newName);
            $ok = mysqli_query($conn, $sql);
            sqlerror();
            return (bool)$ok && !(new SQLtable('MailGroup'))->exists();
        }

        // Both have rows: copy any MailGroup rows missing in Group, then drop legacy table.
        if(!self::copyMissingMailGroupRows($oldName, $newName)) {
            return false;
        }
        $remaining = self::tableRowCount($oldName);
        if($remaining !== 0) {
            // Still rows that could not be copied (should not happen after INSERT IGNORE by Index).
            return false;
        }
        $dropOld = sprintf('DROP TABLE `%s`;', $oldName);
        $ok = mysqli_query($conn, $dropOld);
        sqlerror();
        return (bool)$ok;
    }

    /**
     * @param string $tableName fully prefixed table name
     * @return int row count, or -1 on error
     */
    private static function tableRowCount($tableName) {
        $sql = sprintf('SELECT COUNT(*) AS `c` FROM `%s`;', $tableName);
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if(!$dbr) {
            sqlerror();
            return -1;
        }
        $row = mysqli_fetch_assoc($dbr);
        return $row ? (int)$row['c'] : 0;
    }

    /**
     * Insert MailGroup rows whose Index is not yet in Group (never overwrite Group rows).
     * @param string $oldName
     * @param string $newName
     * @return bool
     */
    private static function copyMissingMailGroupRows($oldName, $newName) {
        $conn = $GLOBALS['conn'];
        $old = new SQLtable('MailGroup');
        $new = new SQLtable('Group');
        $hasPerm = $old->columnExists('PermissionSpec');
        if(!$new->columnExists('PermissionSpec')) {
            $add = sprintf(
                'ALTER TABLE `%s` ADD `PermissionSpec` text NULL COLLATE utf8mb4_unicode_ci;',
                $newName
            );
            if(!mysqli_query($conn, $add)) {
                sqlerror();
                return false;
            }
        }
        $hasNewPerm = (new SQLtable('Group'))->columnExists('PermissionSpec');

        if($hasPerm && $hasNewPerm) {
            $sql = sprintf(
                'INSERT INTO `%s` (`Index`, `Name`, `MemberSpec`, `PermissionSpec`, `CreatedBy`, `Created`)'
                .' SELECT `o`.`Index`, `o`.`Name`, `o`.`MemberSpec`, `o`.`PermissionSpec`, `o`.`CreatedBy`, `o`.`Created`'
                .' FROM `%s` `o`'
                .' LEFT JOIN `%s` `g` ON `g`.`Index` = `o`.`Index`'
                .' WHERE `g`.`Index` IS NULL;',
                $newName,
                $oldName,
                $newName
            );
        }
        else {
            $sql = sprintf(
                'INSERT INTO `%s` (`Index`, `Name`, `MemberSpec`, `CreatedBy`, `Created`)'
                .' SELECT `o`.`Index`, `o`.`Name`, `o`.`MemberSpec`, `o`.`CreatedBy`, `o`.`Created`'
                .' FROM `%s` `o`'
                .' LEFT JOIN `%s` `g` ON `g`.`Index` = `o`.`Index`'
                .' WHERE `g`.`Index` IS NULL;',
                $newName,
                $oldName,
                $newName
            );
        }
        if(!mysqli_query($conn, $sql)) {
            sqlerror();
            return false;
        }

        // Remove successfully migrated legacy rows (matched by Index).
        $del = sprintf(
            'DELETE `o` FROM `%s` `o` INNER JOIN `%s` `g` ON `g`.`Index` = `o`.`Index`;',
            $oldName,
            $newName
        );
        if(!mysqli_query($conn, $del)) {
            sqlerror();
            return false;
        }

        // Keep AUTO_INCREMENT above max Index.
        $maxSql = sprintf('SELECT COALESCE(MAX(`Index`), 0) AS `m` FROM `%s`;', $newName);
        $dbr = mysqli_query($conn, $maxSql);
        $row = $dbr ? mysqli_fetch_assoc($dbr) : null;
        $next = $row ? ((int)$row['m'] + 1) : 1;
        mysqli_query($conn, sprintf('ALTER TABLE `%s` AUTO_INCREMENT = %d;', $newName, $next));
        return true;
    }

    /**
     * @return array{groups:string[],registers:int[],users:int[],namedGroups:int[]}
     */
    public function getMemberSpecArray() {
        return AudienceSpec::normalize($this->MemberSpec, array(
            'allowNamedGroups' => false,
            'defaultGroups' => null,
        ));
    }

    /**
     * @param array $spec
     */
    public function setMemberSpecArray($spec) {
        $norm = AudienceSpec::normalize($spec, array(
            'allowNamedGroups' => false,
            'defaultGroups' => null,
        ));
        unset($norm['namedGroups']);
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

    /** @var array<int, array<string, string[]>>|null */
    private static $inheritedPermissionSourcesCache = null;

    /**
     * Drop request-level caches (after user/group mutations in long-lived PHPUnit processes).
     */
    public static function clearRequestCaches() {
        self::$inheritedPermissionSourcesCache = null;
    }

    /**
     * All users: permission key => group names (request-cached).
     *
     * @return array<int, array<string, string[]>>
     */
    public static function inheritedPermissionSourcesByUser() {
        if(self::$inheritedPermissionSourcesCache !== null) {
            return self::$inheritedPermissionSourcesCache;
        }
        $map = array();
        self::ensureSchema();
        foreach(self::listAll() as $g) {
            $granted = $g->getPermissionSpecArray();
            if(!count($granted)) {
                continue;
            }
            $name = (string)$g->Name;
            foreach(AudienceSpec::resolveUserIds($g->getMemberSpecArray(), false) as $uid) {
                $uid = (int)$uid;
                if($uid < 1) {
                    continue;
                }
                if(!isset($map[$uid])) {
                    $map[$uid] = array();
                }
                foreach($granted as $key) {
                    if(!isset($map[$uid][$key])) {
                        $map[$uid][$key] = array();
                    }
                    if(!in_array($name, $map[$uid][$key], true)) {
                        $map[$uid][$key][] = $name;
                    }
                }
            }
        }
        self::$inheritedPermissionSourcesCache = $map;
        return $map;
    }

    /**
     * Map permission key => group names that grant it (membership via MemberSpec).
     * @return array<string,string[]>
     */
    public static function inheritedPermissionSources($userId) {
        $userId = (int)$userId;
        if($userId < 1) {
            return array();
        }
        $all = self::inheritedPermissionSourcesByUser();
        return isset($all[$userId]) ? $all[$userId] : array();
    }

    public function memberCount($requireMail = false) {
        return count(AudienceSpec::resolveUserIds($this->getMemberSpecArray(), $requireMail));
    }

    public function getMemberLabel() {
        return AudienceSpec::formatLabel($this->getMemberSpecArray(), array('allowNamedGroups' => false));
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
        $old = new Group();
        $old->load_by_id((int)$this->Index);
        $str = sprintf('Gruppen-ID: %d, <b>%s</b>', (int)$this->Index, htmlspecialchars((string)$this->Name, ENT_QUOTES, 'UTF-8'));
        if((string)$this->Name !== (string)$old->Name) {
            $str .= ', Name: '.htmlspecialchars((string)$old->Name, ENT_QUOTES, 'UTF-8')
                .' &rArr; <b>'.htmlspecialchars((string)$this->Name, ENT_QUOTES, 'UTF-8').'</b>';
        }
        $oldJson = AudienceSpec::canonicalJson($old->MemberSpec, array('allowNamedGroups' => false));
        $newJson = AudienceSpec::canonicalJson($this->MemberSpec, array('allowNamedGroups' => false));
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
            'SELECT * FROM `%sGroup` WHERE `Index` = %d;',
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
     * @return Group[]
     */
    public static function listAll() {
        self::ensureSchema();
        $out = array();
        $sql = sprintf(
            'SELECT * FROM `%sGroup` ORDER BY `Name`, `Index`;',
            $GLOBALS['dbprefix']
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return $out;
        while($row = mysqli_fetch_array($dbr)) {
            $g = new Group();
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
     * @param array $wantGroupIds Group Index values that should include the user
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
            'INSERT INTO `%sGroup` (`Name`, `MemberSpec`, `PermissionSpec`, `CreatedBy`) VALUES ("%s", %s, %s, %d);',
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
            'UPDATE `%sGroup` SET `Name` = "%s", `MemberSpec` = %s, `PermissionSpec` = %s WHERE `Index` = %d;',
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
            'DELETE FROM `%sGroup` WHERE `Index` = %d;',
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
