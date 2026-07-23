<?php
/**
 * Shared audience / recipient chip specs (MELD-61 / MELD-135).
 *
 * Shape:
 * {
 *   "groups": ["musicians"|"members"|"nonmembers"|"users", ...],
 *   "registers": [id, ...],
 *   "users": [id, ...],
 *   "namedGroups": [id, ...],  // named Group rows; not nested inside MemberSpec
 *   "mailGroups": [id, ...],   // legacy alias for namedGroups (still read)
 *   "termine": [id, ...]      // Termin-Teilnehmer (ja+vielleicht); Mail-Verteiler only
 * }
 */
class AudienceSpec
{

    /**
     * Whether named Group chips are allowed (opts: allowNamedGroups; legacy allowMailGroups).
     * @param array $opts
     * @return bool
     */
    public static function allowNamedGroupsOpt($opts) {
        if(array_key_exists('allowNamedGroups', $opts)) {
            return !empty($opts['allowNamedGroups']);
        }
        if(array_key_exists('allowMailGroups', $opts)) {
            return !empty($opts['allowMailGroups']);
        }
        return true;
    }

    /**
     * Whether named groups are included in catalogs (opts: includeNamedGroups; legacy includeMailGroups).
     * @param array $opts
     * @return bool
     */
    public static function includeNamedGroupsOpt($opts) {
        if(array_key_exists('includeNamedGroups', $opts)) {
            return !empty($opts['includeNamedGroups']);
        }
        if(array_key_exists('includeMailGroups', $opts)) {
            return !empty($opts['includeMailGroups']);
        }
        return true;
    }
    public static function allowedGroupIds() {
        return array('musicians', 'members', 'nonmembers', 'users');
    }

    /**
     * Canonical UI labels for role chips (consistent capitalization).
     *
     * @return array<string,string>
     */
    public static function groupLabels() {
        return array(
            'musicians' => 'Alle Musiker',
            'members' => 'Alle Vereinsmitglieder',
            'nonmembers' => 'Alle Nicht-Mitglieder',
            'users' => 'Alle User',
        );
    }

    /**
     * @param string $groupId
     * @return string
     */
    public static function groupLabel($groupId) {
        $labels = self::groupLabels();
        $groupId = (string)$groupId;
        return isset($labels[$groupId]) ? $labels[$groupId] : $groupId;
    }

    /**
     * True when spec is exactly the „Alle User“ role chip (no registers/users/groups extras).
     *
     * @param mixed $spec
     * @return bool
     */
    public static function isAlleUserSpec($spec) {
        $norm = self::normalize($spec, array('allowNamedGroups' => true, 'defaultGroups' => null));
        return count($norm['groups']) === 1
            && $norm['groups'][0] === 'users'
            && empty($norm['registers'])
            && empty($norm['users'])
            && empty($norm['namedGroups'])
            && empty($norm['termine']);
    }

    /**
     * @return array{groups:string[],registers:int[],users:int[],namedGroups:int[],termine:int[]}
     */
    public static function emptySpec() {
        return array(
            'groups' => array(),
            'registers' => array(),
            'users' => array(),
            'namedGroups' => array(),
            'termine' => array(),
        );
    }

    /**
     * Default termin visibility: chip „Alle User“.
     *
     * @return array{groups:string[],registers:int[],users:int[],namedGroups:int[],termine:int[]}
     */
    public static function defaultVisibilitySpec() {
        return array(
            'groups' => array('users'),
            'registers' => array(),
            'users' => array(),
            'namedGroups' => array(),
            'termine' => array(),
        );
    }

    /**
     * Chip label for termin participants (ja + vielleicht).
     *
     * @param int $terminId
     * @return string
     */
    public static function terminParticipantLabel($terminId) {
        $terminId = (int)$terminId;
        $t = new Termin();
        $t->load_by_id($terminId);
        if(!(int)$t->Index) {
            return 'Teilnehmer: Termin #'.$terminId;
        }
        $name = trim((string)$t->Name);
        if($name === '') {
            $name = 'Termin #'.$terminId;
        }
        $date = germanDate($t->Datum, 0);
        if($date === null || $date === '') {
            return 'Teilnehmer: '.$name;
        }
        return 'Teilnehmer: '.$name.', '.$date;
    }

    /**
     * User ids with melde ja (1) or vielleicht (3) for a termin.
     *
     * @param int $terminId
     * @param bool $requireMail
     * @return int[]
     */
    public static function userIdsForTerminParticipants($terminId, $requireMail = false) {
        $terminId = (int)$terminId;
        if($terminId <= 0) {
            return array();
        }
        $where = array('u.`Deleted` != 1');
        if($requireMail) {
            $where[] = 'u.`getMail` = 1';
            $where[] = '(u.`Email` != \'\' OR u.`Email2` != \'\')';
        }
        $sql = sprintf(
            'SELECT u.`Index` FROM `%sMeldungen` m INNER JOIN `%sUser` u ON u.`Index` = m.`User` WHERE m.`Termin` = %d AND m.`Wert` != 2 AND %s;',
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $terminId,
            implode(' AND ', $where)
        );
        $ids = array();
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if($dbr) {
            while($row = mysqli_fetch_array($dbr)) {
                $ids[] = (int)$row['Index'];
            }
        }
        return $ids;
    }

    /**
     * @param mixed $input array or JSON string
     * @param array $opts allowNamedGroups (bool; legacy allowMailGroups), allowTermine (bool), defaultGroups (string[]|null), legacyRegister, legacyMemberOnly
     * @return array{groups:string[],registers:int[],users:int[],namedGroups:int[],termine:int[]}
     */
    public static function normalize($input, $opts = array()) {
        $allowNamedGroups = self::allowNamedGroupsOpt($opts);
        $allowTermine = !empty($opts['allowTermine']);
        $defaultGroups = array_key_exists('defaultGroups', $opts) ? $opts['defaultGroups'] : null;
        $legacyRegister = isset($opts['legacyRegister']) ? (int)$opts['legacyRegister'] : 0;
        $legacyMemberOnly = !empty($opts['legacyMemberOnly']) ? 1 : 0;

        $allowed = self::allowedGroupIds();
        $out = self::emptySpec();

        $decoded = null;
        if(is_array($input)) {
            $decoded = $input;
        }
        else {
            $raw = trim((string)$input);
            if($raw !== '') {
                $tmp = json_decode($raw, true);
                if(is_array($tmp)) {
                    $decoded = $tmp;
                }
            }
        }

        if(is_array($decoded)) {
            if(isset($decoded['groups']) && is_array($decoded['groups'])) {
                foreach($decoded['groups'] as $g) {
                    $g = (string)$g;
                    if(in_array($g, $allowed, true)) {
                        $out['groups'][] = $g;
                    }
                }
            }
            elseif(isset($decoded['audience'])) {
                $aud = (string)$decoded['audience'];
                if(in_array($aud, $allowed, true)) {
                    $out['groups'][] = $aud;
                }
            }
            elseif(array_key_exists('allRegisters', $decoded)) {
                $out['groups'][] = $legacyMemberOnly ? 'members' : 'musicians';
            }
            if(isset($decoded['registers']) && is_array($decoded['registers'])) {
                foreach($decoded['registers'] as $id) {
                    $id = (int)$id;
                    if($id > 0) $out['registers'][] = $id;
                }
            }
            if(!empty($decoded['allRegisters'])) {
                $out['registers'] = array();
            }
            if(isset($decoded['users']) && is_array($decoded['users'])) {
                foreach($decoded['users'] as $id) {
                    $id = (int)$id;
                    if($id > 0) $out['users'][] = $id;
                }
            }
            if($allowNamedGroups) {
                foreach(array('namedGroups', 'mailGroups') as $ngKey) {
                    if(!isset($decoded[$ngKey]) || !is_array($decoded[$ngKey])) {
                        continue;
                    }
                    foreach($decoded[$ngKey] as $id) {
                        $id = (int)$id;
                        if($id > 0) {
                            $out['namedGroups'][] = $id;
                        }
                    }
                }
            }
            if($allowTermine && isset($decoded['termine']) && is_array($decoded['termine'])) {
                foreach($decoded['termine'] as $id) {
                    $id = (int)$id;
                    if($id > 0) $out['termine'][] = $id;
                }
            }
        }
        elseif($decoded === null && ($legacyRegister > 0 || $legacyMemberOnly)) {
            $out['groups'] = array($legacyMemberOnly ? 'members' : 'musicians');
            if($legacyRegister > 0) {
                $out['registers'] = array($legacyRegister);
            }
        }

        $out['groups'] = array_values(array_unique($out['groups']));
        $out['registers'] = array_values(array_unique($out['registers']));
        $out['users'] = array_values(array_unique($out['users']));
        $out['namedGroups'] = array_values(array_unique($out['namedGroups']));
        $out['termine'] = array_values(array_unique($out['termine']));

        if(self::isEmpty($out) && is_array($defaultGroups) && count($defaultGroups) > 0) {
            foreach($defaultGroups as $g) {
                $g = (string)$g;
                if(in_array($g, $allowed, true)) {
                    $out['groups'][] = $g;
                }
            }
            $out['groups'] = array_values(array_unique($out['groups']));
        }

        return $out;
    }

    /**
     * @param array $spec
     * @return bool
     */
    public static function isEmpty($spec) {
        if(!is_array($spec)) return true;
        return empty($spec['groups']) && empty($spec['registers']) && empty($spec['users']) && empty($spec['namedGroups']) && empty($spec['termine']);
    }

    /**
     * Expand namedGroups into groups/registers/users (union). Nested namedGroups ignored.
     * Termin-IDs remain on the spec for resolveUserIds.
     *
     * @param array $spec
     * @return array{groups:string[],registers:int[],users:int[],namedGroups:int[],termine:int[]}
     */
    public static function expand($spec) {
        $spec = self::normalize($spec, array(
            'allowNamedGroups' => true,
            'allowTermine' => true,
            'defaultGroups' => null,
        ));
        if(empty($spec['namedGroups'])) {
            return $spec;
        }
        foreach($spec['namedGroups'] as $gid) {
            $g = new Group();
            $g->load_by_id((int)$gid);
            if(!(int)$g->Index) continue;
            $member = $g->getMemberSpecArray();
            foreach($member['groups'] as $aud) {
                $spec['groups'][] = $aud;
            }
            foreach($member['registers'] as $rid) {
                $spec['registers'][] = (int)$rid;
            }
            foreach($member['users'] as $uid) {
                $spec['users'][] = (int)$uid;
            }
        }
        $spec['namedGroups'] = array();
        $spec['groups'] = array_values(array_unique($spec['groups']));
        $spec['registers'] = array_values(array_unique($spec['registers']));
        $spec['users'] = array_values(array_unique($spec['users']));
        return $spec;
    }

    /**
     * Base WHERE fragments for User rows.
     *
     * @param bool $requireMail getMail/notifyInbox (message recipient channels)
     * @return string[]
     */
    public static function userBaseWhere($requireMail = false) {
        $where = array('`Deleted` != 1');
        if($requireMail) {
            $where[] = '(`getMail` = 1 OR `notifyInbox` = 1)';
        }
        return $where;
    }

    /**
     * Resolve matching user ids (union semantics).
     *
     * @param array $spec
     * @param bool $requireMail
     * @return int[]
     */
    public static function resolveUserIds($spec, $requireMail = false) {
        $flat = self::expand($spec);
        $byId = array();
        $groups = $flat['groups'];
        $registerIds = $flat['registers'];
        $userIds = $flat['users'];

        if(!count($groups) && count($registerIds) > 0) {
            $groups = array('musicians');
        }

        foreach($groups as $audience) {
            $where = self::userBaseWhere($requireMail);
            if($audience === 'members') {
                $where[] = '`Mitglied` = 1';
            }
            elseif($audience === 'nonmembers') {
                $where[] = '`Mitglied` != 1';
            }
            elseif($audience === 'musicians') {
                $where[] = '`Active` = 1';
            }
            if(count($registerIds) > 0) {
                $where[] = '`Active` = 1';
                $sql = sprintf(
                    'SELECT `Index` FROM `%sUser` INNER JOIN (SELECT `Index` AS `iIndex`, `Register` FROM `%sInstrument`) `%sInstrument` ON `iIndex` = `Instrument` WHERE %s AND `Register` IN (%s);',
                    $GLOBALS['dbprefix'],
                    $GLOBALS['dbprefix'],
                    $GLOBALS['dbprefix'],
                    implode(' AND ', $where),
                    implode(',', array_map('intval', $registerIds))
                );
            }
            else {
                $sql = sprintf(
                    'SELECT `Index` FROM `%sUser` WHERE %s;',
                    $GLOBALS['dbprefix'],
                    implode(' AND ', $where)
                );
            }
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
            if($dbr) {
                while($row = mysqli_fetch_array($dbr)) {
                    $byId[(int)$row['Index']] = true;
                }
            }
        }

        if(count($userIds) > 0) {
            $uids = array();
            foreach($userIds as $uid) {
                $uid = (int)$uid;
                if($uid > 0) $uids[] = $uid;
            }
            if(count($uids)) {
                $sql = sprintf(
                    'SELECT `Index` FROM `%sUser` WHERE `Index` IN (%s) AND %s;',
                    $GLOBALS['dbprefix'],
                    implode(',', $uids),
                    implode(' AND ', self::userBaseWhere($requireMail))
                );
                $dbr = mysqli_query($GLOBALS['conn'], $sql);
                if($dbr) {
                    while($row = mysqli_fetch_array($dbr)) {
                        $byId[(int)$row['Index']] = true;
                    }
                }
            }
        }

        if(!empty($flat['termine'])) {
            foreach($flat['termine'] as $tid) {
                foreach(self::userIdsForTerminParticipants((int)$tid, $requireMail) as $uid) {
                    $byId[(int)$uid] = true;
                }
            }
        }

        return array_map('intval', array_keys($byId));
    }

    /**
     * Visibility: empty spec matches everyone; otherwise membership in resolved set.
     *
     * @param int $userId
     * @param mixed $spec
     * @return bool
     */
    public static function userMatches($userId, $spec) {
        $userId = (int)$userId;
        if($userId <= 0) return false;
        $norm = self::normalize($spec, array('allowNamedGroups' => true, 'defaultGroups' => null));
        if(self::isEmpty($norm)) {
            return true;
        }
        $ids = self::resolveUserIds($norm, false);
        return in_array($userId, $ids, true);
    }

    /**
     * Labels for roles / register / named groups the user belongs to (profile display).
     * Uses resolveUserIds (not userMatches), so empty MemberSpec ≠ everyone.
     *
     * @param int $userId
     * @return array<int,array{type:string,label:string}>
     */
    public static function membershipForUser($userId) {
        $userId = (int)$userId;
        $items = array();
        if($userId <= 0) {
            return $items;
        }

        foreach(self::allowedGroupIds() as $gid) {
            $spec = self::emptySpec();
            $spec['groups'] = array($gid);
            if(in_array($userId, self::resolveUserIds($spec, false), true)) {
                $items[] = array(
                    'type' => 'group',
                    'label' => self::groupLabel($gid),
                );
            }
        }

        $u = new User;
        $u->load_by_id($userId);
        if((int)$u->Index === $userId && (int)$u->Active !== 0) {
            $regName = html_entity_decode((string)$u->getRegisterName(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if($regName !== '' && strtolower(trim($regName)) !== 'keins') {
                $items[] = array(
                    'type' => 'register',
                    'label' => 'Register: '.$regName,
                );
            }
        }

        foreach(Group::listAll() as $g) {
            $memberIds = self::resolveUserIds($g->getMemberSpecArray(), false);
            if(in_array($userId, $memberIds, true)) {
                $items[] = array(
                    'type' => 'namedGroup',
                    'label' => 'Gruppe: '.(string)$g->Name,
                );
            }
        }

        return $items;
    }

    /**
     * Whether provisional user attributes match a MemberSpec (no DB user row required).
     * Mirrors resolveUserIds union semantics for groups/registers; optional users[] via userId.
     *
     * @param array $attrs mitglied (bool), active (bool, default true), registerId (int), userId (int, optional)
     * @param mixed $spec
     * @param array $opts includeUsers (bool, default true)
     * @return bool
     */
    public static function attributesMatchMemberSpec($attrs, $spec, $opts = array()) {
        $includeUsers = !array_key_exists('includeUsers', $opts) || !empty($opts['includeUsers']);
        $mitglied = !empty($attrs['mitglied']);
        $active = !array_key_exists('active', $attrs) || !empty($attrs['active']);
        $registerId = isset($attrs['registerId']) ? (int)$attrs['registerId'] : 0;
        $userId = isset($attrs['userId']) ? (int)$attrs['userId'] : 0;

        $norm = self::normalize($spec, array(
            'allowNamedGroups' => false,
            'defaultGroups' => null,
        ));
        unset($norm['namedGroups']);
        $norm['namedGroups'] = array();

        if($includeUsers && $userId > 0 && in_array($userId, array_map('intval', $norm['users']), true)) {
            return true;
        }

        $groups = $norm['groups'];
        $regs = array_map('intval', $norm['registers']);
        if(!count($groups) && !count($regs)) {
            return false;
        }
        if(!count($groups) && count($regs) > 0) {
            $groups = array('musicians');
        }

        foreach($groups as $audience) {
            $roleOk = false;
            if($audience === 'users') {
                $roleOk = true;
            }
            elseif($audience === 'musicians') {
                $roleOk = $active;
            }
            elseif($audience === 'members') {
                $roleOk = $mitglied;
            }
            elseif($audience === 'nonmembers') {
                $roleOk = !$mitglied;
            }
            if(!$roleOk) {
                continue;
            }
            if(count($regs) > 0) {
                if(!$active) {
                    continue;
                }
                if(!in_array($registerId, $regs, true)) {
                    continue;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Derived membership chips from form attributes (roles, register, rule-based mail groups).
     * Explicit users[]-only mail groups are omitted (profile checkboxes).
     *
     * @param array $attrs mitglied (bool), active (bool), registerId (int), registerName (string), userId (int)
     * @return array<int,array{type:string,label:string}>
     */
    public static function previewDerivedMembership($attrs) {
        $items = array();
        $registerName = isset($attrs['registerName'])
            ? html_entity_decode((string)$attrs['registerName'], ENT_QUOTES | ENT_HTML5, 'UTF-8')
            : '';

        foreach(self::allowedGroupIds() as $gid) {
            $spec = self::emptySpec();
            $spec['groups'] = array($gid);
            if(self::attributesMatchMemberSpec($attrs, $spec, array('includeUsers' => false))) {
                $items[] = array(
                    'type' => 'group',
                    'label' => self::groupLabel($gid),
                );
            }
        }

        if($registerName !== '' && strtolower(trim($registerName)) !== 'keins'
            && (!array_key_exists('active', $attrs) || !empty($attrs['active']))) {
            $items[] = array(
                'type' => 'register',
                'label' => 'Register: '.$registerName,
            );
        }

        foreach(Group::listAll() as $g) {
            $member = $g->getMemberSpecArray();
            if(self::attributesMatchMemberSpec($attrs, $member, array('includeUsers' => false))) {
                $items[] = array(
                    'type' => 'namedGroup',
                    'label' => 'Gruppe: '.(string)$g->Name,
                );
            }
        }

        return $items;
    }

    /**
     * Catalog for live profile membership preview (Instrument → Register, mail group rules).
     *
     * @return array
     */
    public static function buildMembershipPreviewCatalog() {
        $instruments = array();
        $sql = sprintf(
            'SELECT i.`Index` AS `InstrumentId`, i.`Register` AS `RegisterId`,
                    COALESCE(r.`Name`, "") AS `RegisterName`
             FROM `%sInstrument` i
             LEFT JOIN `%sRegister` r ON r.`Index` = i.`Register`
             ORDER BY i.`Index`;',
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix']
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if($dbr) {
            while($row = mysqli_fetch_array($dbr)) {
                $instruments[(string)(int)$row['InstrumentId']] = array(
                    'registerId' => (int)$row['RegisterId'],
                    'registerName' => html_entity_decode((string)$row['RegisterName'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                );
            }
        }

        $namedGroups = array();
        foreach(Group::listAll() as $g) {
            $spec = $g->getMemberSpecArray();
            $namedGroups[] = array(
                'id' => (int)$g->Index,
                'name' => (string)$g->Name,
                'groups' => $spec['groups'],
                'registers' => array_map('intval', $spec['registers']),
            );
        }

        return array(
            'groupLabels' => self::groupLabels(),
            'groupIds' => self::allowedGroupIds(),
            'instruments' => $instruments,
            'namedGroups' => $namedGroups,
        );
    }

    /**
     * Human-readable audience for logs/UI (roles, registers, users, named groups).
     *
     * @param mixed $spec
     * @param array $opts allowNamedGroups (bool; legacy allowMailGroups)
     * @return string
     */
    public static function formatLabel($spec, $opts = array()) {
        $allowNamedGroups = self::allowNamedGroupsOpt($opts);
        $allowTermine = !empty($opts['allowTermine']);
        $norm = self::normalize($spec, array(
            'allowNamedGroups' => $allowNamedGroups,
            'allowTermine' => $allowTermine,
            'defaultGroups' => null,
        ));
        if(self::isEmpty($norm)) {
            return '—';
        }
        $bits = array();
        if($allowNamedGroups) {
            foreach($norm['namedGroups'] as $gid) {
                $g = new Group();
                $g->load_by_id((int)$gid);
                if((int)$g->Index) {
                    $bits[] = 'Gruppe: '.(string)$g->Name;
                }
            }
        }
        foreach($norm['groups'] as $gid) {
            $bits[] = self::groupLabel($gid);
        }
        foreach($norm['registers'] as $rid) {
            $r = new Register();
            $r->load_by_id((int)$rid);
            if((int)$r->Index) {
                $name = html_entity_decode((string)$r->Name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $bits[] = 'Register: '.$name;
            }
        }
        foreach($norm['users'] as $uid) {
            $u = new User();
            $u->load_by_id((int)$uid);
            if((int)$u->Index) {
                $bits[] = ((int)$u->Active === 0 ? 'Gast: ' : '').$u->getName();
            }
        }
        if($allowTermine) {
            foreach($norm['termine'] as $tid) {
                $bits[] = self::terminParticipantLabel((int)$tid);
            }
        }
        return count($bits) ? implode(', ', $bits) : '—';
    }

    /**
     * Chip items for read-only display (same types/labels as mailRecipients.js).
     *
     * @param mixed $spec
     * @param array $opts allowNamedGroups (bool; legacy allowMailGroups), allowTermine (bool)
     * @return array<int,array{type:string,label:string}>
     */
    public static function chipsForSpec($spec, $opts = array()) {
        $allowNamedGroups = self::allowNamedGroupsOpt($opts);
        $allowTermine = !empty($opts['allowTermine']);
        $norm = self::normalize($spec, array(
            'allowNamedGroups' => $allowNamedGroups,
            'allowTermine' => $allowTermine,
            'defaultGroups' => null,
        ));
        $chips = array();
        if($allowNamedGroups) {
            foreach($norm['namedGroups'] as $gid) {
                $g = new Group();
                $g->load_by_id((int)$gid);
                if((int)$g->Index) {
                    $chips[] = array('type' => 'namedGroup', 'label' => 'Gruppe: '.(string)$g->Name);
                }
            }
        }
        foreach($norm['groups'] as $gid) {
            $chips[] = array(
                'type' => 'group',
                'label' => self::groupLabel($gid),
            );
        }
        foreach($norm['registers'] as $rid) {
            $r = new Register();
            $r->load_by_id((int)$rid);
            if((int)$r->Index) {
                $name = html_entity_decode((string)$r->Name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $chips[] = array('type' => 'register', 'label' => 'Register: '.$name);
            }
        }
        foreach($norm['users'] as $uid) {
            $u = new User();
            $u->load_by_id((int)$uid);
            if((int)$u->Index) {
                $label = $u->getName();
                if((int)$u->Active === 0) {
                    $label = 'Gast: '.$label;
                    $chips[] = array('type' => 'guestMusician', 'label' => $label);
                }
                else {
                    $chips[] = array('type' => 'user', 'label' => $label);
                }
            }
        }
        if($allowTermine) {
            foreach($norm['termine'] as $tid) {
                $chips[] = array(
                    'type' => 'termin',
                    'label' => self::terminParticipantLabel((int)$tid),
                );
            }
        }
        return $chips;
    }

    /**
     * Read-only chip HTML (no remove buttons).
     *
     * @param mixed $spec
     * @param array $opts allowNamedGroups (legacy allowMailGroups), ariaLabel, emptyHtml
     * @return string
     */
    public static function renderChipsHtml($spec, $opts = array()) {
        $chips = self::chipsForSpec($spec, $opts);
        if(!count($chips)) {
            return array_key_exists('emptyHtml', $opts)
                ? (string)$opts['emptyHtml']
                : '<span class="w3-text-gray">—</span>';
        }
        $aria = isset($opts['ariaLabel']) ? (string)$opts['ariaLabel'] : 'Auswahl';
        $html = '<div class="mail-recipient-chips" aria-label="'.htmlspecialchars($aria, ENT_QUOTES, 'UTF-8').'">';
        foreach($chips as $chip) {
            $type = htmlspecialchars((string)$chip['type'], ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars((string)$chip['label'], ENT_QUOTES, 'UTF-8');
            $html .= '<span class="mail-recipient-chip mail-recipient-chip--'.$type.'">'.$label.'</span>';
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * Canonical JSON for equality checks in change logs.
     *
     * @param mixed $spec
     * @param array $opts allowNamedGroups (bool; legacy allowMailGroups)
     * @return string
     */
    public static function canonicalJson($spec, $opts = array()) {
        $allowNamedGroups = self::allowNamedGroupsOpt($opts);
        $allowTermine = !empty($opts['allowTermine']);
        $norm = self::normalize($spec, array(
            'allowNamedGroups' => $allowNamedGroups,
            'allowTermine' => $allowTermine,
            'defaultGroups' => null,
        ));
        if(!$allowNamedGroups) {
            $norm['namedGroups'] = array();
        }
        if(!$allowTermine) {
            $norm['termine'] = array();
        }
        if(self::isEmpty($norm)) {
            return '';
        }
        return json_encode(array(
            'groups' => $norm['groups'],
            'registers' => $norm['registers'],
            'users' => $norm['users'],
            'namedGroups' => $norm['namedGroups'],
            'termine' => $norm['termine'],
        ));
    }

    /**
     * Catalog for chip autocomplete.
     *
     * @param array $opts forMail (bool), includeNamedGroups (bool; legacy includeMailGroups), includeTermine (bool)
     * @return array
     */
    public static function buildCatalog($opts = array()) {
        $forMail = !empty($opts['forMail']);
        $includeNamedGroups = self::includeNamedGroupsOpt($opts);
        $includeTermine = !empty($opts['includeTermine']);

        $catalog = array(
            'groups' => array(),
            'registers' => array(),
            'users' => array(),
            'namedGroups' => array(),
            'termine' => array(),
        );
        foreach(self::groupLabels() as $id => $label) {
            $catalog['groups'][] = array('id' => $id, 'label' => $label, 'meta' => 'Rolle');
        }

        $sqlReg = sprintf(
            'SELECT `Index`, `Name` FROM `%sRegister` WHERE LOWER(TRIM(`Name`)) != "keins" ORDER BY `Sortierung`, `Name`;',
            $GLOBALS['dbprefix']
        );
        $dbrReg = mysqli_query($GLOBALS['conn'], $sqlReg);
        if($dbrReg) {
            while($r = mysqli_fetch_array($dbrReg)) {
                $catalog['registers'][] = array(
                    'id' => (int)$r['Index'],
                    'label' => html_entity_decode((string)$r['Name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                );
            }
        }

        $userWhere = 'u.`Deleted` != 1';
        if($forMail) {
            $userWhere .= ' AND (u.`getMail` = 1 OR u.`notifyInbox` = 1)';
        }
        $sqlUser = sprintf(
            'SELECT u.`Index`, u.`Vorname`, u.`Nachname`, u.`Active`, COALESCE(r.`Name`, "") AS `RegisterName`
             FROM `%sUser` u
             LEFT JOIN `%sInstrument` i ON i.`Index` = u.`Instrument`
             LEFT JOIN `%sRegister` r ON r.`Index` = i.`Register`
             WHERE %s
             ORDER BY u.`Nachname`, u.`Vorname`;',
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $userWhere
        );
        $dbrUser = mysqli_query($GLOBALS['conn'], $sqlUser);
        if($dbrUser) {
            while($u = mysqli_fetch_array($dbrUser)) {
                $name = trim($u['Vorname'].' '.$u['Nachname']);
                $regName = html_entity_decode((string)$u['RegisterName'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $isGuest = array_key_exists('Active', $u) && (int)$u['Active'] === 0;
                $catalog['users'][] = array(
                    'id' => (int)$u['Index'],
                    'label' => $name,
                    'meta' => $regName,
                    'guest' => $isGuest,
                );
            }
        }

        if($includeNamedGroups) {
            foreach(Group::listAll() as $g) {
                $catalog['namedGroups'][] = array(
                    'id' => (int)$g->Index,
                    'label' => (string)$g->Name,
                    'meta' => 'Gruppe',
                );
            }
        }

        if($includeTermine) {
            $today = date('Y-m-d');
            $sqlTerm = sprintf(
                'SELECT `Index`, `Name`, `Datum` FROM `%sTermine` WHERE `Datum` >= "%s" ORDER BY `Datum`, `Name` LIMIT 200;',
                $GLOBALS['dbprefix'],
                mysqli_real_escape_string($GLOBALS['conn'], $today)
            );
            $dbrTerm = mysqli_query($GLOBALS['conn'], $sqlTerm);
            if($dbrTerm) {
                while($row = mysqli_fetch_array($dbrTerm)) {
                    $tid = (int)$row['Index'];
                    $catalog['termine'][] = array(
                        'id' => $tid,
                        'label' => self::terminParticipantLabel($tid),
                        'meta' => 'Termin',
                    );
                }
            }
        }

        return $catalog;
    }
}
?>
