<?php
/**
 * Aggregations for evaluate.php statistics (MELD-89).
 */

/**
 * Clamp evaluation window to a positive day count (default 90).
 *
 * @param int $days
 * @return int
 */
function evaluateNormalizeDays($days) {
    $days = (int)$days;
    if($days < 1) {
        return 90;
    }
    if($days > 3650) {
        return 3650;
    }
    return $days;
}

/**
 * SQL fragment for listed (non-hidden) non-shift termines in the window.
 *
 * @param int $days
 * @param bool $besetzungOnly
 * @param string $alias Table alias including trailing dot, e.g. "`t`." or ""
 * @return string
 */
function evaluateTerminWindowSql($days, $besetzungOnly, $alias = '') {
    $days = (int)$days;
    $a = $alias;
    $extra = $besetzungOnly ? ' AND '.$a.'`Auftritt` = 1' : '';
    return sprintf(
        '%s`Shifts` = 0 AND %s'
        .' AND %s`Datum` BETWEEN (CURRENT_DATE() - INTERVAL %d DAY) AND CURRENT_DATE()%s',
        $a,
        Termin::sqlIsListed($a),
        $a,
        $days,
        $extra
    );
}

/**
 * Attendance counts per termin date in the window.
 *
 * @param int $days
 * @param bool $besetzungOnly
 * @return array{labels:string[],yes:int[],no:int[],maybe:int[],rate:float[]}
 */
function evaluateAttendanceSeries($days, $besetzungOnly = false) {
    $days = evaluateNormalizeDays($days);
    $prefix = $GLOBALS['dbprefix'];
    $window = evaluateTerminWindowSql($days, $besetzungOnly, '`t`.');

    $sql = sprintf(
        'SELECT `t`.`Datum` AS `Datum`,'
        .' COALESCE(SUM(CASE WHEN `m`.`Wert` = 1 THEN 1 ELSE 0 END), 0) AS `Yes`,'
        .' COALESCE(SUM(CASE WHEN `m`.`Wert` = 2 THEN 1 ELSE 0 END), 0) AS `No`,'
        .' COALESCE(SUM(CASE WHEN `m`.`Wert` = 3 THEN 1 ELSE 0 END), 0) AS `Maybe`'
        .' FROM `%sTermine` `t`'
        .' LEFT JOIN `%sMeldungen` `m` ON `m`.`Termin` = `t`.`Index`'
        .' WHERE %s'
        .' GROUP BY `t`.`Index`, `t`.`Datum`'
        .' ORDER BY `t`.`Datum` ASC, `t`.`Index` ASC;',
        $prefix,
        $prefix,
        $window
    );

    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();

    $labels = array();
    $yes = array();
    $no = array();
    $maybe = array();
    $rate = array();

    if($dbr) {
        while($row = mysqli_fetch_assoc($dbr)) {
            $y = (int)$row['Yes'];
            $n = (int)$row['No'];
            $m = (int)$row['Maybe'];
            $total = $y + $n + $m;
            $labels[] = $row['Datum'];
            $yes[] = $y;
            $no[] = $n;
            $maybe[] = $m;
            $rate[] = $total > 0 ? round(($y / $total) * 100, 1) : 0.0;
        }
    }

    return array(
        'labels' => $labels,
        'yes' => $yes,
        'no' => $no,
        'maybe' => $maybe,
        'rate' => $rate,
    );
}

/**
 * System log counts per day in the window.
 *
 * @param int $days
 * @return array{labels:string[],series:array<int,int[]>}
 */
function evaluateLogSeries($days) {
    $days = evaluateNormalizeDays($days);
    $prefix = $GLOBALS['dbprefix'];

    $sql = sprintf(
        'SELECT DATE(`Timestamp`) AS `LogDate`,'
        .' COUNT(CASE WHEN `Type` = 0 THEN 1 END) AS `NumLogs0`,'
        .' COUNT(CASE WHEN `Type` = 1 THEN 1 END) AS `NumLogs1`,'
        .' COUNT(CASE WHEN `Type` = 2 THEN 1 END) AS `NumLogs2`,'
        .' COUNT(CASE WHEN `Type` = 3 THEN 1 END) AS `NumLogs3`,'
        .' COUNT(CASE WHEN `Type` = 4 THEN 1 END) AS `NumLogs4`,'
        .' COUNT(CASE WHEN `Type` = 5 THEN 1 END) AS `NumLogs5`,'
        .' COUNT(CASE WHEN `Type` = 6 THEN 1 END) AS `NumLogs6`,'
        .' COUNT(CASE WHEN `Type` = 7 THEN 1 END) AS `NumLogs7`'
        .' FROM `%sLog`'
        .' WHERE `Timestamp` >= (NOW() - INTERVAL %d DAY)'
        .' GROUP BY DATE(`Timestamp`)'
        .' ORDER BY `LogDate` ASC;',
        $prefix,
        $days
    );

    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();

    $labels = array();
    $series = array();
    for($i = 0; $i <= 7; $i++) {
        $series[$i] = array();
    }

    if($dbr) {
        while($row = mysqli_fetch_assoc($dbr)) {
            $labels[] = $row['LogDate'];
            for($i = 0; $i <= 7; $i++) {
                $series[$i][] = (int)$row['NumLogs'.$i];
            }
        }
    }

    return array(
        'labels' => $labels,
        'series' => $series,
    );
}

/**
 * Count of matching termines in the window (denominator for ranking quote).
 *
 * @param int $days
 * @param bool $besetzungOnly
 * @return int
 */
function evaluateTerminCount($days, $besetzungOnly = false) {
    $days = evaluateNormalizeDays($days);
    $prefix = $GLOBALS['dbprefix'];
    $window = evaluateTerminWindowSql($days, $besetzungOnly);

    $sql = sprintf(
        'SELECT COUNT(*) AS `CNT` FROM `%sTermine` WHERE %s;',
        $prefix,
        $window
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    $row = $dbr ? mysqli_fetch_assoc($dbr) : null;
    return $row ? (int)$row['CNT'] : 0;
}

/**
 * Per-user attendance ranking in the window (all non-deleted users; filter client-side).
 *
 * @param int $days
 * @param bool $besetzungOnly
 * @return array<int,array<string,mixed>>
 */
function evaluateAttendanceRanking($days, $besetzungOnly = false) {
    $days = evaluateNormalizeDays($days);
    $prefix = $GLOBALS['dbprefix'];
    $termineCount = evaluateTerminCount($days, $besetzungOnly);
    $window = evaluateTerminWindowSql($days, $besetzungOnly);
    $groupMap = evaluateUserGroupIdsByUser();

    $sql = sprintf(
        'SELECT `u`.`Index` AS `UserId`, `u`.`Vorname`, `u`.`Nachname`, `u`.`Active`, `u`.`Mitglied`,'
        .' COALESCE(`i`.`Register`, 0) AS `RegisterId`,'
        .' COALESCE(SUM(CASE WHEN `m`.`Wert` = 1 THEN 1 ELSE 0 END), 0) AS `Yes`,'
        .' COALESCE(SUM(CASE WHEN `m`.`Wert` = 2 THEN 1 ELSE 0 END), 0) AS `No`,'
        .' COALESCE(SUM(CASE WHEN `m`.`Wert` = 3 THEN 1 ELSE 0 END), 0) AS `Maybe`'
        .' FROM `%sUser` `u`'
        .' LEFT JOIN `%sInstrument` `i` ON `i`.`Index` = `u`.`Instrument`'
        .' LEFT JOIN `%sMeldungen` `m` ON `m`.`User` = `u`.`Index`'
        .' AND `m`.`Termin` IN (SELECT `Index` FROM `%sTermine` WHERE %s)'
        .' WHERE `u`.`Deleted` != 1'
        .' GROUP BY `u`.`Index`, `u`.`Vorname`, `u`.`Nachname`, `u`.`Active`, `u`.`Mitglied`, `i`.`Register`'
        .' ORDER BY `u`.`Nachname` ASC, `u`.`Vorname` ASC;',
        $prefix,
        $prefix,
        $prefix,
        $prefix,
        $window
    );

    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();

    $rows = array();
    if($dbr) {
        while($row = mysqli_fetch_assoc($dbr)) {
            $yes = (int)$row['Yes'];
            $uid = (int)$row['UserId'];
            $quote = $termineCount > 0 ? round($yes / $termineCount, 4) : 0.0;
            $rows[] = array_merge(
                array(
                    'id' => $uid,
                    'name' => trim($row['Nachname'].', '.$row['Vorname']),
                    'yes' => $yes,
                    'no' => (int)$row['No'],
                    'maybe' => (int)$row['Maybe'],
                    'termine' => $termineCount,
                    'quote' => $quote,
                ),
                evaluatePersonFilterMeta(
                    (int)$row['Active'],
                    (int)$row['Mitglied'],
                    (int)$row['RegisterId'],
                    isset($groupMap[$uid]) ? $groupMap[$uid] : array()
                )
            );
        }
    }

    usort($rows, function($a, $b) {
        if($a['quote'] == $b['quote']) {
            if($a['yes'] == $b['yes']) {
                return strcmp($a['name'], $b['name']);
            }
            return $b['yes'] - $a['yes'];
        }
        return ($a['quote'] < $b['quote']) ? 1 : -1;
    });

    return $rows;
}

/**
 * Register chips for evaluate person filter (MELD-161).
 *
 * @return array<int,array{Index:int,Name:string,Color:string}>
 */
function evaluateRegisterFilterOptions() {
    $opts = array();
    $sql = sprintf(
        'SELECT `Index`, `Name`, `Color` FROM `%sRegister` WHERE LOWER(TRIM(`Name`)) != "keins" ORDER BY `Sortierung`, `Name`;',
        $GLOBALS['dbprefix']
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    if($dbr) {
        while($row = mysqli_fetch_assoc($dbr)) {
            $opts[] = array(
                'Index' => (int)$row['Index'],
                'Name' => (string)$row['Name'],
                'Color' => isset($row['Color']) ? (string)$row['Color'] : '',
            );
        }
    }
    return $opts;
}

/**
 * Named group chips for evaluate person filter.
 *
 * @return array<int,array{Index:int,Name:string}>
 */
function evaluateGroupFilterOptions() {
    $opts = array();
    if(!class_exists('Group')) {
        return $opts;
    }
    Group::ensureSchema();
    foreach(Group::listAll() as $g) {
        if(!(int)$g->Index) {
            continue;
        }
        $opts[] = array(
            'Index' => (int)$g->Index,
            'Name' => (string)$g->Name,
        );
    }
    return $opts;
}

/**
 * @return array<int,int[]>
 */
function evaluateUserGroupIdsByUser() {
    $map = array();
    if(!class_exists('Group') || !class_exists('AudienceSpec')) {
        return $map;
    }
    Group::ensureSchema();
    foreach(Group::listAll() as $g) {
        $gid = (int)$g->Index;
        if($gid < 1) {
            continue;
        }
        foreach(AudienceSpec::resolveUserIds($g->getMemberSpecArray(), false) as $uid) {
            $uid = (int)$uid;
            if($uid < 1) {
                continue;
            }
            if(!isset($map[$uid])) {
                $map[$uid] = array();
            }
            if(!in_array($gid, $map[$uid], true)) {
                $map[$uid][] = $gid;
            }
        }
    }
    return $map;
}

/**
 * @param int $active
 * @param int $mitglied
 * @param int $registerId
 * @param int[] $groupIds
 * @return array{active:int,mitglied:int,registerId:int,groupIds:int[]}
 */
function evaluatePersonFilterMeta($active, $mitglied, $registerId, array $groupIds) {
    $gids = array();
    foreach($groupIds as $gid) {
        $gid = (int)$gid;
        if($gid > 0) {
            $gids[] = $gid;
        }
    }
    return array(
        'active' => ((int)$active) ? 1 : 0,
        'mitglied' => ((int)$mitglied) ? 1 : 0,
        'registerId' => max(0, (int)$registerId),
        'groupIds' => $gids,
    );
}

/**
 * Same rules as Personenliste chips (+ optional group chips).
 *
 * @param array<string,mixed> $row
 * @param array{showAktive:bool,showGaeste:bool,showMitglied:bool,showNoMitglied:bool,registers:string[],groups:string[]} $filter
 * @return bool
 */
function evaluateRowMatchesPersonFilter(array $row, array $filter) {
    $active = !empty($row['active']);
    $mitglied = !empty($row['mitglied']);
    $regId = (string)(isset($row['registerId']) ? (int)$row['registerId'] : 0);
    $groupIds = array();
    if(isset($row['groupIds']) && is_array($row['groupIds'])) {
        foreach($row['groupIds'] as $gid) {
            $groupIds[] = (string)(int)$gid;
        }
    }

    if($active && empty($filter['showAktive'])) {
        return false;
    }
    if(!$active && empty($filter['showGaeste'])) {
        return false;
    }
    if($mitglied && empty($filter['showMitglied'])) {
        return false;
    }
    if(!$mitglied && empty($filter['showNoMitglied'])) {
        return false;
    }

    $regs = isset($filter['registers']) && is_array($filter['registers']) ? $filter['registers'] : array();
    if(count($regs) > 0 && !in_array($regId, $regs, true)) {
        return false;
    }

    $groups = isset($filter['groups']) && is_array($filter['groups']) ? $filter['groups'] : array();
    if(count($groups) > 0) {
        $hit = false;
        foreach($groupIds as $gid) {
            if(in_array($gid, $groups, true)) {
                $hit = true;
                break;
            }
        }
        if(!$hit) {
            return false;
        }
    }

    return true;
}

/**
 * Users considered inactive by last login / last attendance (Wert=1).
 * All non-deleted users; person chips filter client-side (MELD-161).
 *
 * @param int $thresholdDays
 * @return array<int,array<string,mixed>>
 */
function evaluateInactiveUsers($thresholdDays) {
    $thresholdDays = max(1, (int)$thresholdDays);
    $prefix = $GLOBALS['dbprefix'];
    $groupMap = evaluateUserGroupIdsByUser();

    $sql = sprintf(
        'SELECT `u`.`Index` AS `UserId`, `u`.`Vorname`, `u`.`Nachname`, `u`.`LastLogin`, `u`.`Joined`,'
        .' `u`.`Active`, `u`.`Mitglied`, COALESCE(`i`.`Register`, 0) AS `RegisterId`,'
        .' ('
        .'   SELECT MAX(`t`.`Datum`) FROM `%sMeldungen` `m`'
        .'   INNER JOIN `%sTermine` `t` ON `t`.`Index` = `m`.`Termin`'
        .'   WHERE `m`.`User` = `u`.`Index` AND `m`.`Wert` = 1 AND `t`.`Datum` <= CURRENT_DATE()'
        .' ) AS `LastAttend`'
        .' FROM `%sUser` `u`'
        .' LEFT JOIN `%sInstrument` `i` ON `i`.`Index` = `u`.`Instrument`'
        .' WHERE `u`.`Deleted` != 1;',
        $prefix,
        $prefix,
        $prefix,
        $prefix
    );

    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();

    $cutoff = new DateTimeImmutable('today');
    $cutoff = $cutoff->modify('-'.$thresholdDays.' days');
    $cutoffDate = $cutoff->format('Y-m-d');

    $rows = array();
    if($dbr) {
        while($row = mysqli_fetch_assoc($dbr)) {
            $lastLogin = $row['LastLogin'] ? substr($row['LastLogin'], 0, 10) : null;
            $lastAttend = $row['LastAttend'] ? substr($row['LastAttend'], 0, 10) : null;

            $lastActivity = null;
            if($lastLogin && $lastAttend) {
                $lastActivity = ($lastLogin > $lastAttend) ? $lastLogin : $lastAttend;
            }
            elseif($lastLogin) {
                $lastActivity = $lastLogin;
            }
            elseif($lastAttend) {
                $lastActivity = $lastAttend;
            }

            if($lastActivity !== null && $lastActivity >= $cutoffDate) {
                continue;
            }

            $uid = (int)$row['UserId'];
            $user = new User();
            $user->load_by_id($uid);
            $quote = 0.0;
            if($user->Index) {
                $quote = (float)$user->getMeldeQuote();
            }

            $rows[] = array_merge(
                array(
                    'id' => $uid,
                    'name' => trim($row['Nachname'].', '.$row['Vorname']),
                    'lastLogin' => $lastLogin,
                    'lastAttend' => $lastAttend,
                    'quote' => $quote,
                ),
                evaluatePersonFilterMeta(
                    (int)$row['Active'],
                    (int)$row['Mitglied'],
                    (int)$row['RegisterId'],
                    isset($groupMap[$uid]) ? $groupMap[$uid] : array()
                )
            );
        }
    }

    usort($rows, function($a, $b) {
        $aKey = $a['lastLogin'] ?: '0000-00-00';
        $bKey = $b['lastLogin'] ?: '0000-00-00';
        if($aKey === $bKey) {
            $aA = $a['lastAttend'] ?: '0000-00-00';
            $bA = $b['lastAttend'] ?: '0000-00-00';
            return strcmp($aA, $bA);
        }
        return strcmp($aKey, $bKey);
    });

    return $rows;
}
?>
