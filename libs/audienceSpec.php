<?php
/**
 * Shared audience / recipient chip specs (MELD-61).
 *
 * Shape:
 * {
 *   "groups": ["musicians"|"members"|"nonmembers"|"users", ...],
 *   "registers": [id, ...],
 *   "users": [id, ...],
 *   "mailGroups": [id, ...]   // named MailGroup rows; not nested inside MemberSpec
 * }
 */
class AudienceSpec
{
    public static function allowedGroupIds() {
        return array('musicians', 'members', 'nonmembers', 'users');
    }

    /**
     * @return array{groups:string[],registers:int[],users:int[],mailGroups:int[]}
     */
    public static function emptySpec() {
        return array(
            'groups' => array(),
            'registers' => array(),
            'users' => array(),
            'mailGroups' => array(),
        );
    }

    /**
     * @param mixed $input array or JSON string
     * @param array $opts allowMailGroups (bool), defaultGroups (string[]|null), legacyRegister, legacyMemberOnly
     * @return array{groups:string[],registers:int[],users:int[],mailGroups:int[]}
     */
    public static function normalize($input, $opts = array()) {
        $allowMailGroups = !array_key_exists('allowMailGroups', $opts) || !empty($opts['allowMailGroups']);
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
        return empty($spec['groups']) && empty($spec['registers']) && empty($spec['users']) && empty($spec['mailGroups']);
    }

    /**
     * Expand named mailGroups into groups/registers/users (union). Nested mailGroups ignored.
     *
     * @param array $spec
     * @return array{groups:string[],registers:int[],users:int[],mailGroups:int[]}
     */
    public static function expand($spec) {
        $spec = self::normalize($spec, array('allowMailGroups' => true, 'defaultGroups' => null));
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
     * Catalog for chip autocomplete.
     *
     * @param array $opts forMail (bool), includeMailGroups (bool)
     * @return array
     */
    public static function buildCatalog($opts = array()) {
        $forMail = !empty($opts['forMail']);
        $includeMailGroups = !array_key_exists('includeMailGroups', $opts) || !empty($opts['includeMailGroups']);

        $catalog = array(
            'groups' => array(
                array('id' => 'musicians', 'label' => 'Alle Musiker', 'meta' => 'Rolle'),
                array('id' => 'members', 'label' => 'Alle Vereinsmitglieder', 'meta' => 'Rolle'),
                array('id' => 'nonmembers', 'label' => 'alle Nicht-Mitglieder', 'meta' => 'Rolle'),
                array('id' => 'users', 'label' => 'alle User', 'meta' => 'Rolle'),
            ),
            'registers' => array(),
            'users' => array(),
            'mailGroups' => array(),
        );

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

        return $catalog;
    }
}
?>
