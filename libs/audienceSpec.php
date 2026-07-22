<?php
/**
 * Shared audience / recipient chip specs (MELD-61 / MELD-135).
 *
 * Shape:
 * {
 *   "groups": ["musicians"|"members"|"nonmembers"|"users", ...],
 *   "registers": [id, ...],
 *   "users": [id, ...],
 *   "mailGroups": [id, ...],  // named MailGroup rows; not nested inside MemberSpec
 *   "termine": [id, ...]      // Termin-Teilnehmer (ja+vielleicht); Mail-Verteiler only
 * }
 */
class AudienceSpec
{
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
        $norm = self::normalize($spec, array('allowMailGroups' => true, 'defaultGroups' => null));
        return count($norm['groups']) === 1
            && $norm['groups'][0] === 'users'
            && empty($norm['registers'])
            && empty($norm['users'])
            && empty($norm['mailGroups'])
            && empty($norm['termine']);
    }

    /**
     * @return array{groups:string[],registers:int[],users:int[],mailGroups:int[],termine:int[]}
     */
    public static function emptySpec() {
        return array(
            'groups' => array(),
            'registers' => array(),
            'users' => array(),
            'mailGroups' => array(),
            'termine' => array(),
        );
    }

    /**
     * Default termin visibility: chip „Alle User“.
     *
     * @return array{groups:string[],registers:int[],users:int[],mailGroups:int[],termine:int[]}
     */
    public static function defaultVisibilitySpec() {
        return array(
            'groups' => array('users'),
            'registers' => array(),
            'users' => array(),
            'mailGroups' => array(),
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
     * @param array $opts allowMailGroups (bool), allowTermine (bool), defaultGroups (string[]|null), legacyRegister, legacyMemberOnly
     * @return array{groups:string[],registers:int[],users:int[],mailGroups:int[],termine:int[]}
     */
    public static function normalize($input, $opts = array()) {
        $allowMailGroups = !array_key_exists('allowMailGroups', $opts) || !empty($opts['allowMailGroups']);
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
            if($allowMailGroups && isset($decoded['mailGroups']) && is_array($decoded['mailGroups'])) {
                foreach($decoded['mailGroups'] as $id) {
                    $id = (int)$id;
                    if($id > 0) $out['mailGroups'][] = $id;
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
        $out['mailGroups'] = array_values(array_unique($out['mailGroups']));
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
        return empty($spec['groups']) && empty($spec['registers']) && empty($spec['users']) && empty($spec['mailGroups']) && empty($spec['termine']);
    }

    /**
     * Expand named mailGroups into groups/registers/users (union). Nested mailGroups ignored.
     * Termin-IDs remain on the spec for resolveUserIds.
     *
     * @param array $spec
     * @return array{groups:string[],registers:int[],users:int[],mailGroups:int[],termine:int[]}
     */
    public static function expand($spec) {
        $spec = self::normalize($spec, array(
            'allowMailGroups' => true,
            'allowTermine' => true,
            'defaultGroups' => null,
        ));
        if(empty($spec['mailGroups'])) {
            return $spec;
        }
        foreach($spec['mailGroups'] as $gid) {
            $g = new MailGroup();
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
        $spec['mailGroups'] = array();
        $spec['groups'] = array_values(array_unique($spec['groups']));
        $spec['registers'] = array_values(array_unique($spec['registers']));
        $spec['users'] = array_values(array_unique($spec['users']));
        return $spec;
    }

    /**
     * Base WHERE fragments for User rows.
     *
     * @param bool $requireMail getMail + has email
     * @return string[]
     */
    public static function userBaseWhere($requireMail = false) {
        $where = array('`Deleted` != 1');
        if($requireMail) {
            $where[] = '`getMail` = 1';
            $where[] = '(`Email` != \'\' OR `Email2` != \'\')';
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
            if(count($registerIds) > 0) {
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
        $norm = self::normalize($spec, array('allowMailGroups' => true, 'defaultGroups' => null));
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
        if((int)$u->Index === $userId) {
            $regName = html_entity_decode((string)$u->getRegisterName(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if($regName !== '' && strtolower(trim($regName)) !== 'keins') {
                $items[] = array(
                    'type' => 'register',
                    'label' => 'Register: '.$regName,
                );
            }
        }

        foreach(MailGroup::listAll() as $g) {
            $memberIds = self::resolveUserIds($g->getMemberSpecArray(), false);
            if(in_array($userId, $memberIds, true)) {
                $items[] = array(
                    'type' => 'mailGroup',
                    'label' => 'Gruppe: '.(string)$g->Name,
                );
            }
        }

        return $items;
    }

    /**
     * Human-readable audience for logs/UI (roles, registers, users, named groups).
     *
     * @param mixed $spec
     * @param array $opts allowMailGroups (bool)
     * @return string
     */
    public static function formatLabel($spec, $opts = array()) {
        $allowMailGroups = !array_key_exists('allowMailGroups', $opts) || !empty($opts['allowMailGroups']);
        $allowTermine = !empty($opts['allowTermine']);
        $norm = self::normalize($spec, array(
            'allowMailGroups' => $allowMailGroups,
            'allowTermine' => $allowTermine,
            'defaultGroups' => null,
        ));
        if(self::isEmpty($norm)) {
            return '—';
        }
        $bits = array();
        if($allowMailGroups) {
            foreach($norm['mailGroups'] as $gid) {
                $g = new MailGroup();
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
                $bits[] = $u->getName();
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
     * @param array $opts allowMailGroups (bool), allowTermine (bool)
     * @return array<int,array{type:string,label:string}>
     */
    public static function chipsForSpec($spec, $opts = array()) {
        $allowMailGroups = !array_key_exists('allowMailGroups', $opts) || !empty($opts['allowMailGroups']);
        $allowTermine = !empty($opts['allowTermine']);
        $norm = self::normalize($spec, array(
            'allowMailGroups' => $allowMailGroups,
            'allowTermine' => $allowTermine,
            'defaultGroups' => null,
        ));
        $chips = array();
        if($allowMailGroups) {
            foreach($norm['mailGroups'] as $gid) {
                $g = new MailGroup();
                $g->load_by_id((int)$gid);
                if((int)$g->Index) {
                    $chips[] = array('type' => 'mailGroup', 'label' => 'Gruppe: '.(string)$g->Name);
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
                $chips[] = array('type' => 'user', 'label' => $u->getName());
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
     * @param array $opts allowMailGroups, ariaLabel, emptyHtml
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
     * @param array $opts allowMailGroups (bool)
     * @return string
     */
    public static function canonicalJson($spec, $opts = array()) {
        $allowMailGroups = !array_key_exists('allowMailGroups', $opts) || !empty($opts['allowMailGroups']);
        $allowTermine = !empty($opts['allowTermine']);
        $norm = self::normalize($spec, array(
            'allowMailGroups' => $allowMailGroups,
            'allowTermine' => $allowTermine,
            'defaultGroups' => null,
        ));
        if(!$allowMailGroups) {
            $norm['mailGroups'] = array();
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
            'mailGroups' => $norm['mailGroups'],
            'termine' => $norm['termine'],
        ));
    }

    /**
     * Catalog for chip autocomplete.
     *
     * @param array $opts forMail (bool), includeMailGroups (bool), includeTermine (bool)
     * @return array
     */
    public static function buildCatalog($opts = array()) {
        $forMail = !empty($opts['forMail']);
        $includeMailGroups = !array_key_exists('includeMailGroups', $opts) || !empty($opts['includeMailGroups']);
        $includeTermine = !empty($opts['includeTermine']);

        $catalog = array(
            'groups' => array(),
            'registers' => array(),
            'users' => array(),
            'mailGroups' => array(),
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
            $userWhere .= ' AND u.`getMail` = 1 AND (u.`Email` != "" OR u.`Email2` != "")';
        }
        $sqlUser = sprintf(
            'SELECT u.`Index`, u.`Vorname`, u.`Nachname`, COALESCE(r.`Name`, "") AS `RegisterName`
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
                $catalog['users'][] = array(
                    'id' => (int)$u['Index'],
                    'label' => $name,
                    'meta' => $regName,
                );
            }
        }

        if($includeMailGroups) {
            foreach(MailGroup::listAll() as $g) {
                $catalog['mailGroups'][] = array(
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
