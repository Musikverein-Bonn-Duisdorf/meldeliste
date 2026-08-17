<?php
function bin2date($v) {
    $c=array(false, false, false, false, false, false, false);
    for($i=7; $i>=1; $i--) {
        if($v/2**($i-1)>=1) {
            $c[$i-1]=true;
            $v=$v-2**($i-1);
        }
    }
    return $c;
}

function bool2string($val) {
    if($val) return "ja";
    return "nein";
}

/** Compare two values as booleans (avoids null/"0" false-positive diffs). */
function boolsDiffer($a, $b) {
    return (bool)$a !== (bool)$b;
}

function bool2color($val) {
    if($val) return "w3-light-green";
    return "";
}

function checkCronDate($v) {
    $c = bin2date($v);
    $dow = intval(date("N"));
    if($c[$dow-1] == false) { 
        return false;
    }
    return true;
}

function genitiv($string) {
    $last = substr($string, -1);
    if($last == "s" || $last == "x") {
        return $string.'\'';
    }
    else {
        return $string."s";
    }
}

function medDate($string) {
    if($string == '') return;
    if($string == null) return;
    $y = substr($string, 0, 4);
    $m = substr($string, 5, 2);
    $d = substr($string, 8, 2);
    
    $date = mktime(0,0,0, $m, $d, $y);
    return date("d-M-Y", $date);
}

function germanDate($string, $monthLetters) {
    if($string == '') return;
    if($string == null) return;
    return germanDates($string, $monthLetters, false);
}

function germanDates($string, $monthLetters, $short) {
    if($string == '') {
	return;
    }
    $months = array(
        "01" => "Januar",
        "02" => "Februar",
        "03" => "März",
        "04" => "April",
        "05" => "Mai",
        "06" => "Juni",
        "07" => "Juli",
        "08" => "August",
        "09" => "September",
        "10" => "Oktober",
        "11" => "November",
        "12" => "Dezember"
    );
    $dows = array(
        1 => 'Montag',
        2 => 'Dienstag',
        3 => 'Mittwoch',
        4 => 'Donnerstag',
        5 => 'Freitag',
        6 => 'Samstag',
        7 => 'Sonntag'
    );

    if($short) {
        $months = array(
            "01" => "Jan",
            "02" => "Feb",
            "03" => "Mär",
            "04" => "Apr",
            "05" => "Mai",
            "06" => "Jun",
            "07" => "Jul",
            "08" => "Aug",
            "09" => "Sep",
            "10" => "Okt",
            "11" => "Nov",
            "12" => "Dez"
        );
        $dows = array(
            1 => 'Mo',
            2 => 'Di',
            3 => 'Mi',
            4 => 'Do',
            5 => 'Fr',
            6 => 'Sa',
            7 => 'So'
        );
    }
    $y = substr($string, 0, 4);
    $m = substr($string, 5, 2);
    $d = substr($string, 8, 2);

    $date = mktime(0,0,0, $m, $d, $y);
    $dow = date("N", $date);

    if($monthLetters) {
        $s = $dows[$dow].", ".$d.". ".$months[$m]." ".$y;
    } else {
        $s = $d.".".$m.".".$y;
    }
    return $s;
}

function germanDateSpan($string1, $string2) {
    return germanDates($string1, true, true)." - ".germanDates($string2, true, true);
}

/**
 * Two-letter German weekday (Mo…So) for a YYYY-MM-DD date.
 */
function germanWeekdayShort($string) {
    if($string == '' || $string == null) {
        return '';
    }
    $y = substr($string, 0, 4);
    $m = substr($string, 5, 2);
    $d = substr($string, 8, 2);
    $date = mktime(0, 0, 0, (int)$m, (int)$d, (int)$y);
    $dows = array(1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do', 5 => 'Fr', 6 => 'Sa', 7 => 'So');
    return $dows[(int)date('N', $date)];
}

/**
 * Compact numeric date dd.mm.yyyy (no weekday).
 */
function germanDateCompact($string) {
    return germanDates($string, false, true);
}

/**
 * Qualify a User.Index column for EXISTS subqueries.
 *
 * @param string|null $userColumnSql
 * @return string
 */
function sqlQualifyUserIndexColumn($userColumnSql = null) {
    if($userColumnSql === null || $userColumnSql === '' || $userColumnSql === '`Index`') {
        return sprintf('`%sUser`.`Index`', $GLOBALS['dbprefix']);
    }
    return $userColumnSql;
}

/**
 * SQL fragment: open MIT type period (aktiv or foerdernd).
 *
 * @param string|null $userColumnSql
 * @param string $type aktiv|foerdernd
 * @return string
 */
function sqlUserHasOpenMitType($userColumnSql, $type) {
    $userColumnSql = sqlQualifyUserIndexColumn($userColumnSql);
    $type = ($type === 'foerdernd') ? 'foerdernd' : 'aktiv';
    if(mitMembershipPeriodsReady()) {
        $today = date('Y-m-d');
        $esc = mysqli_real_escape_string($GLOBALS['conn'], $today);
        return sprintf(
            'EXISTS (
                SELECT 1 FROM `mit_Membership` m
                INNER JOIN `mit_MembershipTypePeriod` t ON t.`Membership` = m.`Index`
                WHERE m.`User` = %s AND t.`Type` = "%s"
                  AND t.`DateFrom` <= "%s"
                  AND (t.`DateTo` IS NULL OR t.`DateTo` >= "%s")
            )',
            $userColumnSql,
            $type,
            $esc,
            $esc
        );
    }
    if(mitMembershipTableReady() && mitMembershipHasTypeStatusColumns()) {
        return sprintf(
            'EXISTS (SELECT 1 FROM `mit_Membership` m WHERE m.`User` = %s AND m.`Type` = "%s" AND m.`Status` = "active")',
            $userColumnSql,
            $type
        );
    }
    if($type === 'aktiv' && function_exists('meldeUserHasMitgliedColumn') && meldeUserHasMitgliedColumn()) {
        return '`Mitglied` = 1';
    }
    return '0=1';
}

/**
 * SQL: open aktiv or foerdernd MIT type (Alle Mitglieder).
 *
 * @param string|null $userColumnSql
 * @return string
 */
function sqlUserHasOpenMitMitgliedschaft($userColumnSql = null) {
    $userColumnSql = sqlQualifyUserIndexColumn($userColumnSql);
    return '('.sqlUserHasOpenMitType($userColumnSql, 'aktiv').' OR '.sqlUserHasOpenMitType($userColumnSql, 'foerdernd').')';
}

/**
 * Open MIT type ids for a user (aktiv, foerdernd).
 *
 * @param int $userId
 * @return list<string>
 */
function userOpenMitTypes($userId) {
    $userId = (int)$userId;
    if($userId < 1) {
        return array();
    }
    $types = array();
    if(function_exists('mitMembershipPeriodsReady') && mitMembershipPeriodsReady()) {
        $today = date('Y-m-d');
        $esc = mysqli_real_escape_string($GLOBALS['conn'], $today);
        $sql = sprintf(
            'SELECT DISTINCT t.`Type` FROM `mit_Membership` m
             INNER JOIN `mit_MembershipTypePeriod` t ON t.`Membership` = m.`Index`
             WHERE m.`User` = %d AND t.`DateFrom` <= "%s"
               AND (t.`DateTo` IS NULL OR t.`DateTo` >= "%s");',
            $userId,
            $esc,
            $esc
        );
        try {
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
        }
        catch(Throwable $e) {
            return array();
        }
        if($dbr) {
            while($row = mysqli_fetch_assoc($dbr)) {
                $t = (string)$row['Type'];
                if($t === 'aktiv' || $t === 'foerdernd') {
                    $types[] = $t;
                }
            }
        }
        return array_values(array_unique($types));
    }
    if(function_exists('mitMembershipTableReady') && mitMembershipTableReady() && mitMembershipHasTypeStatusColumns()) {
        $sql = sprintf(
            'SELECT `Type` FROM `mit_Membership` WHERE `User` = %d AND `Status` = "active" LIMIT 1;',
            $userId
        );
        try {
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
        }
        catch(Throwable $e) {
            return array();
        }
        $row = $dbr ? mysqli_fetch_assoc($dbr) : null;
        if($row) {
            $t = (string)$row['Type'];
            if($t === 'aktiv' || $t === 'foerdernd') {
                return array($t);
            }
        }
    }
    return array();
}

/**
 * SQL fragment: hide Fördernde (open MIT Type=foerdernd).
 * No-op if mit_Membership is missing.
 * $userColumnSql must be unambiguous (qualify with table/alias), e.g. 'u.`Index`'
 * or '`meldeliste_User`.`Index`'. Bare '`Index`' is wrong inside NOT EXISTS.
 */
function sqlExcludeFoerderndeUsers($userColumnSql = null) {
    $userColumnSql = sqlQualifyUserIndexColumn($userColumnSql);
    if(!mitMembershipPeriodsReady()) {
        // Legacy flag-based Membership until periods exist
        if(mitMembershipTableReady() && mitMembershipHasTypeStatusColumns()) {
            return sprintf(
                'NOT EXISTS (SELECT 1 FROM `mit_Membership` m WHERE m.`User` = %s AND m.`Type` = "foerdernd" AND m.`Status` = "active")',
                $userColumnSql
            );
        }
        return '1=1';
    }
    $today = date('Y-m-d');
    $esc = mysqli_real_escape_string($GLOBALS['conn'], $today);
    return sprintf(
        'NOT EXISTS (
            SELECT 1 FROM `mit_Membership` m
            INNER JOIN `mit_MembershipTypePeriod` t ON t.`Membership` = m.`Index`
            WHERE m.`User` = %s AND t.`Type` = "foerdernd"
              AND t.`DateFrom` <= "%s"
              AND (t.`DateTo` IS NULL OR t.`DateTo` >= "%s")
        )',
        $userColumnSql,
        $esc,
        $esc
    );
}

/** Whether mit_Membership exists (cached; re-probes until found). */
function mitMembershipTableReady() {
    static $ready = null;
    if($ready === true) {
        return true;
    }
    $ready = false;
    if(isset($GLOBALS['conn']) && $GLOBALS['conn']) {
        try {
            $dbr = mysqli_query($GLOBALS['conn'], "SHOW TABLES LIKE 'mit_Membership'");
            if($dbr && mysqli_fetch_row($dbr)) {
                $ready = true;
            }
        }
        catch(Throwable $e) {
            $ready = false;
        }
    }
    return $ready === true;
}

/**
 * Whether mit_MemberProfile exists (cached; re-probes until found).
 * @return bool
 */
function mitMemberProfileTableReady() {
    static $ready = null;
    static $probedMissing = false;
    if($ready === true) {
        return true;
    }
    if($ready === false && $probedMissing) {
        $probedMissing = false;
    }
    $ready = false;
    if(!isset($GLOBALS['conn']) || !($GLOBALS['conn'] instanceof mysqli)) {
        $probedMissing = true;
        return false;
    }
    try {
        $dbr = mysqli_query($GLOBALS['conn'], "SHOW TABLES LIKE 'mit_MemberProfile'");
        if($dbr && mysqli_fetch_row($dbr)) {
            $ready = true;
            return true;
        }
    }
    catch(Throwable $e) {
        $ready = false;
    }
    $probedMissing = true;
    return false;
}

/**
 * MIT-Anschrift for a Melde user (Street, Zip City, Country), or '' if unavailable.
 * Does not include PHP from Mitgliederverwaltung — shared DB read only (PLATFORM).
 * @param int $userId
 * @return string
 */
function mitBorrowerAddressForUser($userId) {
    $userId = (int)$userId;
    if($userId < 1 || !mitMemberProfileTableReady()) {
        return '';
    }
    $sql = sprintf(
        'SELECT `Street`, `Zip`, `City`, `Country` FROM `mit_MemberProfile` WHERE `User` = %d LIMIT 1;',
        $userId
    );
    try {
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
    }
    catch(Throwable $e) {
        return '';
    }
    if(!$dbr) {
        return '';
    }
    $row = mysqli_fetch_assoc($dbr);
    if(!$row) {
        return '';
    }
    $street = trim((string)(isset($row['Street']) ? $row['Street'] : ''));
    $zip = trim((string)(isset($row['Zip']) ? $row['Zip'] : ''));
    $city = trim((string)(isset($row['City']) ? $row['City'] : ''));
    $country = trim((string)(isset($row['Country']) ? $row['Country'] : ''));
    $zipCity = trim($zip.' '.$city);
    $parts = array();
    if($street !== '') {
        $parts[] = $street;
    }
    if($zipCity !== '') {
        $parts[] = $zipCity;
    }
    if($country !== '') {
        $parts[] = $country;
    }
    return trim(implode(', ', $parts));
}

/**
 * Prefill loan BorrowerAddress from MIT when empty.
 * @param InventoriesLoan $loan
 * @return bool true if address was set on the loan object
 */
function mitPrefillLoanBorrowerAddress($loan) {
    if(!($loan instanceof InventoriesLoan)) {
        return false;
    }
    if(trim((string)$loan->BorrowerAddress) !== '') {
        return false;
    }
    $addr = mitBorrowerAddressForUser((int)$loan->User);
    if($addr === '') {
        return false;
    }
    $loan->BorrowerAddress = $addr;
    return true;
}

/** Tenure + type period tables available (schema v6+). */
function mitMembershipPeriodsReady() {
    static $ready = null;
    if($ready === true) {
        return true;
    }
    $ready = false;
    if(!isset($GLOBALS['conn']) || !$GLOBALS['conn'] || !mitMembershipTableReady()) {
        return false;
    }
    try {
        $dbr1 = mysqli_query($GLOBALS['conn'], "SHOW TABLES LIKE 'mit_MembershipPeriod'");
        $dbr2 = mysqli_query($GLOBALS['conn'], "SHOW TABLES LIKE 'mit_MembershipTypePeriod'");
        if($dbr1 && mysqli_fetch_row($dbr1) && $dbr2 && mysqli_fetch_row($dbr2)) {
            $ready = true;
        }
    }
    catch(Throwable $e) {
        $ready = false;
    }
    return $ready === true;
}

/** Legacy Membership.Type / Status columns still present. */
function mitMembershipHasTypeStatusColumns() {
    static $has = null;
    if($has !== null) {
        return $has;
    }
    $has = false;
    if(!mitMembershipTableReady()) {
        return false;
    }
    try {
        $dbr = mysqli_query($GLOBALS['conn'], 'SHOW COLUMNS FROM `mit_Membership` LIKE "Type"');
        $has = ($dbr && mysqli_fetch_row($dbr)) ? true : false;
    }
    catch(Throwable $e) {
        $has = false;
    }
    return $has;
}

/**
 * True if User still has legacy Mitglied column (pre-drop).
 */
function meldeUserHasMitgliedColumn() {
    static $has = null;
    if($has !== null) {
        return $has;
    }
    $has = false;
    if(!isset($GLOBALS['conn']) || !$GLOBALS['conn']) {
        return false;
    }
    try {
        $dbr = mysqli_query($GLOBALS['conn'], sprintf(
            'SHOW COLUMNS FROM `%sUser` LIKE "Mitglied";',
            $GLOBALS['dbprefix']
        ));
        $has = ($dbr && mysqli_fetch_row($dbr)) ? true : false;
    }
    catch(Throwable $e) {
        $has = false;
    }
    return $has;
}

/**
 * SQL: user is Vereinsmitglied via open mit_MembershipPeriod (any type).
 * Legacy: Type=aktiv Status=active, then User.Mitglied=1.
 * Note: Melde User.Active (Orchesterbetrieb) is unrelated.
 */
function sqlUserIsVereinMitglied($userColumnSql = null) {
    $userColumnSql = sqlQualifyUserIndexColumn($userColumnSql);
    if(mitMembershipPeriodsReady()) {
        $today = date('Y-m-d');
        $esc = mysqli_real_escape_string($GLOBALS['conn'], $today);
        return sprintf(
            'EXISTS (
                SELECT 1 FROM `mit_Membership` m
                INNER JOIN `mit_MembershipPeriod` p ON p.`Membership` = m.`Index`
                WHERE m.`User` = %s AND p.`DateFrom` <= "%s"
                  AND (p.`DateTo` IS NULL OR p.`DateTo` >= "%s")
            )',
            $userColumnSql,
            $esc,
            $esc
        );
    }
    if(mitMembershipTableReady() && mitMembershipHasTypeStatusColumns()) {
        return sprintf(
            'EXISTS (SELECT 1 FROM `mit_Membership` m WHERE m.`User` = %s AND m.`Type` = "aktiv" AND m.`Status` = "active")',
            $userColumnSql
        );
    }
    if(meldeUserHasMitgliedColumn()) {
        // Legacy transitional: unqualified Mitglied on User table in same FROM.
        return '`Mitglied` = 1';
    }
    return '0=1';
}

/**
 * @param int $userId
 * @return bool
 */
function userIsVereinMitglied($userId) {
    $userId = (int)$userId;
    if($userId < 1) {
        return false;
    }
    if(mitMembershipPeriodsReady()) {
        $today = date('Y-m-d');
        $sql = sprintf(
            'SELECT 1 FROM `mit_Membership` m
             INNER JOIN `mit_MembershipPeriod` p ON p.`Membership` = m.`Index`
             WHERE m.`User` = %d AND p.`DateFrom` <= "%s"
               AND (p.`DateTo` IS NULL OR p.`DateTo` >= "%s")
             LIMIT 1;',
            $userId,
            mysqli_real_escape_string($GLOBALS['conn'], $today),
            mysqli_real_escape_string($GLOBALS['conn'], $today)
        );
        try {
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
        }
        catch(Throwable $e) {
            return false;
        }
        return ($dbr && mysqli_fetch_row($dbr)) ? true : false;
    }
    if(mitMembershipTableReady() && mitMembershipHasTypeStatusColumns()) {
        $sql = sprintf(
            'SELECT 1 FROM `mit_Membership` WHERE `User` = %d AND `Type` = "aktiv" AND `Status` = "active" LIMIT 1;',
            $userId
        );
        try {
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
        }
        catch(Throwable $e) {
            return false;
        }
        return ($dbr && mysqli_fetch_row($dbr)) ? true : false;
    }
    if(meldeUserHasMitgliedColumn()) {
        $sql = sprintf(
            'SELECT `Mitglied` FROM `%sUser` WHERE `Index` = %d LIMIT 1;',
            $GLOBALS['dbprefix'],
            $userId
        );
        try {
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
        }
        catch(Throwable $e) {
            return false;
        }
        $row = $dbr ? mysqli_fetch_assoc($dbr) : null;
        return $row && !empty($row['Mitglied']);
    }
    return false;
}

function getActiveUsers($date) {
    $users = array();
    $excludeFoerdernd = sqlExcludeFoerderndeUsers('`Index`');
    if($date) {
        $sql = sprintf('SELECT * FROM `%sUser` INNER JOIN (SELECT `Index` AS `iIndex`, `Name` AS `iName` FROM `%sInstrument`) `%sInstrument` ON `iIndex` = `Instrument` WHERE `Joined` >= "%s" AND (`DeletedOn` <= "%s" OR `DeletedOn` = NULL) AND `Active` = 1 AND `iName` != "Admin" AND %s ORDER BY `Nachname`, `Vorname`;',
        $GLOBALS['dbprefix'],
        $GLOBALS['dbprefix'],
        $GLOBALS['dbprefix'],
        $date,
        $date,
        $excludeFoerdernd
        );
    }
    else {
        $sql = sprintf('SELECT * FROM `%sUser` INNER JOIN (SELECT `Index` AS `iIndex`, `Name` AS `iName` FROM `%sInstrument`) `%sInstrument` ON `iIndex` = `Instrument` WHERE `Deleted` = 0 AND `Active` = 1 AND `iName` != "Admin" AND %s ORDER BY `Nachname`, `Vorname`;',
        $GLOBALS['dbprefix'],
        $GLOBALS['dbprefix'],
        $GLOBALS['dbprefix'],
        $excludeFoerdernd
        );
    }
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    while($row = mysqli_fetch_array($dbr)) {
        array_push($users, $row['Index']);
    }
    return $users;
}

function getAdminPage($string) {
    if($string == $_SESSION['page'] && $_SESSION['adminpage']) {
        echo $GLOBALS['optionsDB']['colorTitleBar'];
    }
    else {
        echo $GLOBALS['optionsDB']['colorNavAdmin'];
    }
}

/**
 * Admin-Menü-Eintrag: aktive Seite = TitleBar, sonst Berechtigungs-Farbgruppe.
 * @param string $page
 * @param string $permKey Permissions::* key
 */
function getAdminPagePerm($page, $permKey) {
    if($page == $_SESSION['page'] && !empty($_SESSION['adminpage'])) {
        echo $GLOBALS['optionsDB']['colorTitleBar'];
        return;
    }
    echo adminNavPermClass($permKey);
}

/**
 * Desktop sidebar: keep the admin accordion section open when one of its pages is active.
 * @param string|string[] $pages $_SESSION['page'] values belonging to this group
 * @return string CSS classes (leading space) or ''
 */
function adminNavGroupActiveClass($pages) {
    if(empty($_SESSION['adminpage'])) {
        return '';
    }
    $current = isset($_SESSION['page']) ? (string)$_SESSION['page'] : '';
    if($current === '') {
        return '';
    }
    foreach((array)$pages as $p) {
        if((string)$p === $current) {
            return ' admin-nav-open admin-nav-current-group';
        }
    }
    return '';
}

/**
 * CSS-Klassen für Nav anhand der Rechte-Farbgruppe (wie Admin-Nav / Chips).
 * @param string $groupId nutzer|termine|register|inventar|kommunikation|system
 * @return string
 */
function navGroupClass($groupId) {
    $gid = preg_replace('/[^a-z0-9_-]/i', '', (string)$groupId);
    if($gid === '') {
        $gid = 'system';
    }
    return 'admin-nav-perm admin-nav-perm--'.$gid;
}

/**
 * CSS-Klassen für Admin-Nav anhand der Rechte-Farbgruppe (wie Profil-Chips).
 * @param string $permKey
 * @return string
 */
function adminNavPermClass($permKey) {
    $gid = Permissions::groupIdForPermission($permKey);
    return navGroupClass($gid);
}

/**
 * Map Admin-Listen-Kicker (Personen, Inventar, …) auf Rechte-Farbgruppe.
 * @param string $kicker
 * @return string nutzer|termine|register|inventar|kommunikation|system
 */
function adminListSectionGroupId($kicker) {
    $map = array(
        'Personen' => 'nutzer',
        'Termine' => 'termine',
        'Meldungen' => 'termine',
        'Kommunikation' => 'kommunikation',
        'Inventar' => 'inventar',
        'Register' => 'register',
        'System' => 'system',
        // Nutzer-Seiten (gleicher Hero wie Admin-Listen)
        'Hilfe' => 'system',
        'Medien' => 'system',
        'Anwesenheit' => 'termine',
        'Stimme' => 'nutzer',
        // Admin-Formulare (nicht Listen)
        'Neuer Termin' => 'termine',
        'Termin bearbeiten' => 'termine',
        'Termin kopieren' => 'termine',
        'Schichten & Aufgaben bearbeiten' => 'termine',
        'Neuer Nutzer' => 'nutzer',
        'Nutzer bearbeiten' => 'nutzer',
        'Mein Profil' => 'nutzer',
        'globale Einstellungen' => 'system',
        'Datenauswertung' => 'system',
    );
    $k = trim((string)$kicker);
    return isset($map[$k]) ? $map[$k] : 'system';
}

/**
 * CSS classes for Admin-Hero (Rechte-Farben wie Nav/Chips).
 * @param array $options kicker|groupId|permKey, withProfileHero (bool, default true)
 * @return string
 */
function adminHeroClass($options = array()) {
    if(isset($options['groupId']) && (string)$options['groupId'] !== '') {
        $gid = (string)$options['groupId'];
    }
    elseif(isset($options['permKey']) && (string)$options['permKey'] !== '') {
        $gid = Permissions::groupIdForPermission((string)$options['permKey']);
    }
    elseif(isset($options['kicker'])) {
        $gid = adminListSectionGroupId((string)$options['kicker']);
    }
    else {
        $gid = 'system';
    }
    $gid = preg_replace('/[^a-z0-9_-]/i', '', (string)$gid);
    if($gid === '') {
        $gid = 'system';
    }
    $withProfile = !array_key_exists('withProfileHero', $options) || !empty($options['withProfileHero']);
    $base = $withProfile ? 'profile-hero admin-list-hero' : 'admin-list-hero';
    return $base.' admin-list-hero--'.$gid;
}

/**
 * Open admin list page chrome (profile-shell, same style as new-musiker / permissions).
 * Hero color follows the Admin-Rechte group (same palette as Nav/Chips).
 * @param string $kicker Section label (Personen, Inventar, …)
 * @param string $title Page title (plain text)
 * @param array $options actionsHtml (string), shellClass (string), permKey (string), groupId (string)
 */
/**
 * Queue modal HTML to render outside .app-main (MELD-147).
 * Footer echoes $GLOBALS['mlDeferredPageModals'] after .app-shell closes.
 */
function deferPageModalHtml($html) {
    $html = (string)$html;
    if($html === '') {
        return;
    }
    if(!isset($GLOBALS['mlDeferredPageModals'])) {
        $GLOBALS['mlDeferredPageModals'] = '';
    }
    $GLOBALS['mlDeferredPageModals'] .= $html;
}

/**
 * Start list page shell (hero in sticky chrome).
 * Content after this (or after an optional toolbar/search) goes into padded .admin-list-body.
 *
 * @param array $options actionsHtml, shellClass, groupId, permKey
 */
function adminListPageBegin($kicker, $title, $options = array()) {
    $actionsHtml = isset($options['actionsHtml']) ? (string)$options['actionsHtml'] : '';
    $shellClass = isset($options['shellClass']) ? trim((string)$options['shellClass']) : '';
    $shellCls = 'profile-shell admin-list-shell'.($shellClass !== '' ? ' '.$shellClass : '');
    $heroOpts = array('kicker' => $kicker);
    if(isset($options['groupId'])) {
        $heroOpts['groupId'] = $options['groupId'];
    }
    if(isset($options['permKey'])) {
        $heroOpts['permKey'] = $options['permKey'];
    }
    $heroCls = adminHeroClass($heroOpts);
    echo '<div class="profile-page">'."\n";
    echo '  <div class="'.htmlspecialchars($shellCls, ENT_QUOTES, 'UTF-8').'">'."\n";
    echo '    <div class="app-page-chrome">'."\n";
    echo '    <header class="'.htmlspecialchars($heroCls, ENT_QUOTES, 'UTF-8').'">'."\n";
    echo '      <div class="profile-hero-text">'."\n";
    echo '        <p class="profile-kicker">'.htmlspecialchars((string)$kicker, ENT_QUOTES, 'UTF-8').'</p>'."\n";
    echo '        <h2 class="profile-title">'.htmlspecialchars((string)$title, ENT_QUOTES, 'UTF-8').'</h2>'."\n";
    echo '      </div>'."\n";
    if($actionsHtml !== '') {
        echo '      <div class="profile-hero-actions">'.$actionsHtml.'</div>'."\n";
    }
    echo '    </header>'."\n";
    $GLOBALS['mlAdminListChrome'] = 'open';
    $GLOBALS['mlAdminListBody'] = null;
    /* Buffer until Search/ChromeClose/End so content lands in padded body */
    ob_start();
    $GLOBALS['mlAdminListCapturing'] = true;
}

/**
 * Flush chrome capture buffer into open chrome (toolbar etc.), then stop capturing.
 */
function adminListFlushChromeCapture() {
    if(empty($GLOBALS['mlAdminListCapturing'])) {
        return '';
    }
    $chunk = ob_get_clean();
    $GLOBALS['mlAdminListCapturing'] = false;
    return ($chunk === false) ? '' : $chunk;
}

/**
 * Close open app-page-chrome and open scrollable body (after search or before other content).
 *
 * @param bool $captureToBody true = buffered content belongs in body (End without toolbar)
 */
function adminListChromeClose($captureToBody = false) {
    if(empty($GLOBALS['mlAdminListChrome']) || $GLOBALS['mlAdminListChrome'] !== 'open') {
        return;
    }
    $chunk = adminListFlushChromeCapture();
    if(!$captureToBody && $chunk !== '') {
        echo $chunk;
        $chunk = '';
    }
    echo '    </div><!-- .app-page-chrome -->'."\n";
    echo '    <div class="admin-list-body">'."\n";
    $GLOBALS['mlAdminListChrome'] = 'closed';
    $GLOBALS['mlAdminListBody'] = 'open';
    if($captureToBody && $chunk !== '') {
        echo $chunk;
    }
}

/** Close adminListPageBegin(). */
function adminListPageEnd() {
    if(!empty($GLOBALS['mlAdminListChrome']) && $GLOBALS['mlAdminListChrome'] === 'open') {
        /* Kein Search/ChromeClose: Capture → Body mit Seiten-Gutter */
        adminListChromeClose(true);
    }
    if(!empty($GLOBALS['mlAdminListBody']) && $GLOBALS['mlAdminListBody'] === 'open') {
        echo '    </div><!-- .admin-list-body -->'."\n";
        $GLOBALS['mlAdminListBody'] = 'closed';
    }
    echo '  </div>'."\n";
    echo '</div>'."\n";
}

/**
 * Search field styled like profile controls (keeps #filterString for existing JS).
 * Kein sichtbares Label — nur Placeholder (+ aria-label). Siehe .cursor/rules/short-ui-labels.mdc
 * @param string $placeholder
 * @param array $options id, onkeyup, extraHtml (label wird ignoriert / nur für aria)
 */
function adminListSearchField($placeholder, $options = array()) {
    $id = isset($options['id']) ? (string)$options['id'] : 'filterString';
    $onkeyup = isset($options['onkeyup']) ? (string)$options['onkeyup'] : '';
    $extra = isset($options['extraHtml']) ? (string)$options['extraHtml'] : '';
    $inputBg = isset($GLOBALS['optionsDB']['colorInputBackground'])
        ? (string)$GLOBALS['optionsDB']['colorInputBackground'] : '';
    $aria = isset($options['ariaLabel']) && (string)$options['ariaLabel'] !== ''
        ? (string)$options['ariaLabel']
        : (string)$placeholder;
    $pre = adminListFlushChromeCapture();
    if($pre !== '') {
        echo $pre;
    }
    echo '    <div class="admin-list-toolbar">'."\n";
    echo '      <div class="profile-field admin-list-search">'."\n";
    echo '        <input type="search" id="'.htmlspecialchars($id, ENT_QUOTES, 'UTF-8').'"'
        .' class="w3-input w3-border profile-control '.htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8').'"'
        .' placeholder="'.htmlspecialchars((string)$placeholder, ENT_QUOTES, 'UTF-8').'"'
        .' aria-label="'.htmlspecialchars($aria, ENT_QUOTES, 'UTF-8').'"'
        .' autocomplete="off"';
    if($onkeyup !== '') {
        echo ' onkeyup="'.htmlspecialchars($onkeyup, ENT_QUOTES, 'UTF-8').'"';
    }
    echo '>'."\n";
    echo '      </div>'."\n";
    if($extra !== '') {
        echo '      <div class="admin-list-toolbar-extra">'.$extra.'</div>'."\n";
    }
    echo '    </div>'."\n";
    adminListChromeClose(false);
}

function getNextRegInventoryNumber($inventoryTypeId = 0) {
    $inventoryTypeId = (int)$inventoryTypeId;
    if($inventoryTypeId < 1) {
        $map = RegNumber::nextMapForInventoryTypes();
        if(empty($map)) return 1;
        return (int)reset($map);
    }
    return RegNumber::nextForType($inventoryTypeId);
}

/** Display name for Inventories.Owner (UI: Eigentümer). MELD-124: rename column/helper when DB is renamed. */
function getOwner($index) {
    if($index == 0) {
        return $GLOBALS['optionsDB']['orgNameShort'];
    }
    
    $user = new User;
    $user->load_by_id($index);
    return $user->getName();
}

/**
 * Clickable entity chip (MELD-167). Markup only — modal loads async via openModal.
 *
 * @param string $type user|termin|inventar
 * @param int $id
 * @param string $label
 * @param string $chipMod Optional visual modifier (default from type map)
 * @return string HTML (escaped label); plain escaped text if type/id invalid
 */
function entityOpenHtml($type, $id, $label, $chipMod = '') {
    $type = strtolower(trim((string)$type));
    if($type === 'inventory') {
        $type = 'inventar';
    }
    $id = (int)$id;
    $label = trim((string)$label);
    $h = function ($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    };
    if($label === '') {
        return '';
    }
    $chipMods = array(
        'user' => 'user',
        'termin' => 'termin',
        'inventar' => 'instrument',
        'mail' => 'mailGroup',
        'shift' => 'termin',
        'sammlung' => 'instrument',
        'programm' => 'programm',
    );
    if($id < 1 || !isset($chipMods[$type])) {
        return $h($label);
    }
    $mod = trim((string)$chipMod);
    if($mod === '' || !preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/', $mod)) {
        $mod = $chipMods[$type];
    }
    return '<span class="mail-recipient-chip mail-recipient-chip--'.$mod.' entity-open"'
        .' role="button" tabindex="0"'
        .' data-entity-type="'.$h($type).'"'
        .' data-entity-id="'.$id.'">'
        .$h($label)
        .'</span>';
}

/**
 * Resolve user id for legacy log fragments "User: <b>Name</b>" (no id).
 * Prefers Melde-ID in the same message (Meldung vs Schichtmeldung).
 *
 * @return int 0 if unknown
 */
function logResolveUserIdFromMessage($html, $nameHint = '') {
    $html = (string)$html;
    $nameHint = trim((string)$nameHint);
    if(preg_match('/\bMelde-ID:\s*(\d+)/i', $html, $m)) {
        $mid = (int)$m[1];
        if($mid > 0) {
            if(stripos($html, 'Schicht/Aufgabe:') !== false && class_exists('Shiftmeldung')) {
                $sm = new Shiftmeldung;
                $sm->load_by_id($mid);
                if((int)$sm->Index === $mid && (int)$sm->User > 0) {
                    return (int)$sm->User;
                }
            }
            if(class_exists('Meldung')) {
                $melde = new Meldung;
                $melde->load_by_id($mid);
                if((int)$melde->Index === $mid && (int)$melde->User > 0) {
                    return (int)$melde->User;
                }
            }
            if(class_exists('Shiftmeldung')) {
                $sm = new Shiftmeldung;
                $sm->load_by_id($mid);
                if((int)$sm->Index === $mid && (int)$sm->User > 0) {
                    return (int)$sm->User;
                }
            }
        }
    }
    if($nameHint === '' || !isset($GLOBALS['conn']) || !isset($GLOBALS['dbprefix'])) {
        return 0;
    }
    // Unique Vorname+Nachname match (legacy fallback)
    $parts = preg_split('/\s+/u', $nameHint, 2);
    if(!is_array($parts) || count($parts) < 2) {
        return 0;
    }
    $sql = sprintf(
        'SELECT `Index` FROM `%sUser` WHERE `Vorname` = "%s" AND `Nachname` = "%s" AND `Deleted` != 1 LIMIT 2;',
        $GLOBALS['dbprefix'],
        mysqli_real_escape_string($GLOBALS['conn'], $parts[0]),
        mysqli_real_escape_string($GLOBALS['conn'], $parts[1])
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    if(!$dbr) {
        return 0;
    }
    $ids = array();
    while($row = mysqli_fetch_array($dbr)) {
        $ids[] = (int)$row['Index'];
    }
    return count($ids) === 1 ? $ids[0] : 0;
}

/**
 * Enrich stored log HTML with entity chips (MELD-167).
 * Matches common "Label: (id) <b>name</b>" fragments; safe to run on each display.
 *
 * @param string $html Log.Message (may already contain <b> etc.)
 * @return string
 */
function logMessageLinkEntities($html) {
    $html = (string)$html;
    if($html === '' || !function_exists('entityOpenHtml')) {
        return $html;
    }
    // Skip if already enriched (avoid nested chips on re-process)
    if(strpos($html, 'entity-open') !== false && strpos($html, 'data-entity-type') !== false) {
        return $html;
    }

    $source = $html;
    $plainLabel = function ($raw) {
        $t = trim(html_entity_decode(strip_tags((string)$raw), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        return preg_replace('/\s+/u', ' ', $t);
    };
    $chip = function ($type, $id, $labelRaw) use ($plainLabel) {
        $label = $plainLabel($labelRaw);
        if($label === '') {
            $label = '#'.(int)$id;
        }
        return entityOpenHtml($type, (int)$id, $label);
    };

    // Termin: (12) <b>Probe</b>
    $html = preg_replace_callback(
        '/\bTermin:\s*\((\d+)\)\s*<b>(.*?)<\/b>/si',
        function ($m) use ($chip) {
            return 'Termin: '.$chip('termin', $m[1], $m[2]);
        },
        $html
    );

    // Legacy termin edit: Termin-ID: 6, <b>Probe</b>
    $html = preg_replace_callback(
        '/\bTermin-ID:\s*(\d+)\s*,\s*<b>(.*?)<\/b>/si',
        function ($m) use ($chip) {
            return 'Termin-ID: '.$chip('termin', $m[1], $m[2]);
        },
        $html
    );

    // Legacy insert-ish: Termin-ID: <b>6</b> (id only in bold)
    $html = preg_replace_callback(
        '/\bTermin-ID:\s*<b>(\d+)<\/b>/si',
        function ($m) use ($chip) {
            return 'Termin-ID: '.$chip('termin', $m[1], 'Termin #'.$m[1]);
        },
        $html
    );

    // Inventory: (9) <b>Marsch</b> optional RegNumber… (loan / edit with both headers)
    $html = preg_replace_callback(
        '/\bInventory:\s*\((\d+)\)\s*<b>(.*?)<\/b>(?:\s*([^,<]*?))?(?=(?:\s*&rArr;|\s*,|\s*$))/si',
        function ($m) use ($chip, $plainLabel) {
            $extra = isset($m[3]) ? $plainLabel($m[3]) : '';
            $label = $plainLabel($m[2]);
            if($label === '' || $label === '?') {
                if(class_exists('User') && method_exists('User', 'inventoryChipLabel')) {
                    $label = $plainLabel(User::inventoryChipLabel((int)$m[1]));
                }
                if($label === '' || $label === '?') {
                    $label = '#'.$m[1];
                }
                $extra = '';
            }
            if($extra !== '') {
                $label = trim($label.' '.$extra);
            }
            return 'Inventory: '.$chip('inventar', $m[1], $label);
        },
        $html
    );

    // Drop redundant Inventory-ID: N, when Inventory: chip already follows
    $html = preg_replace('/\bInventory-ID:\s*\d+\s*,\s*(?=Inventory:)/i', '', $html);

    // Legacy inventory edit header: Inventory-ID: 4, <b>Flöte</b> INSTR-001 → Inventory: chip
    $html = preg_replace_callback(
        '/\bInventory-ID:\s*(\d+)\s*,\s*<b>(.*?)<\/b>(?:\s*([^,<]*?))?(?=,|$)/si',
        function ($m) use ($chip, $plainLabel) {
            $extra = isset($m[3]) ? $plainLabel($m[3]) : '';
            $label = $plainLabel($m[2]);
            if($extra !== '') {
                $label = trim($label.' '.$extra);
            }
            if($label === '') {
                $label = '#'.$m[1];
            }
            return 'Inventory: '.$chip('inventar', $m[1], $label);
        },
        $html
    );

    // Legacy insert: Inventory-ID: N … Inventory: <b>name</b> (no id on Inventory:)
    if(preg_match('/\bInventory-ID:\s*(\d+)/i', $source, $im)) {
        $invId = (int)$im[1];
        if($invId > 0) {
            $html = preg_replace_callback(
                '/\bInventory:\s*<b>(.*?)<\/b>/si',
                function ($m) use ($chip, $invId) {
                    return 'Inventory: '.$chip('inventar', $invId, $m[1]);
                },
                $html
            );
            $html = preg_replace('/\bInventory-ID:\s*'.$invId.'\s*,\s*(?=Inventory:)/i', '', $html);
        }
    }

    // User: (5) <b>Name</b>
    $html = preg_replace_callback(
        '/\bUser:\s*\((\d+)\)\s*<b>(.*?)<\/b>/si',
        function ($m) use ($chip) {
            return 'User: '.$chip('user', $m[1], $m[2]);
        },
        $html
    );

    // Drop redundant User-ID: N, when User: chip already follows
    $html = preg_replace('/\bUser-ID:\s*\d+\s*,\s*(?=User:)/i', '', $html);

    // User-ID: 9 <b>Name</b> → User: chip
    $html = preg_replace_callback(
        '/\bUser-ID:\s*(\d+)\s*,?\s*<b>(.*?)<\/b>/si',
        function ($m) use ($chip) {
            return 'User: '.$chip('user', $m[1], $m[2]);
        },
        $html
    );

    // User-ID: <b>2</b> → User: #2
    $html = preg_replace_callback(
        '/\bUser-ID:\s*<b>(\d+)<\/b>/si',
        function ($m) use ($chip) {
            return 'User: '.$chip('user', $m[1], '#'.$m[1]);
        },
        $html
    );

    // Create-style: User-ID: 34, Vorname/Nachname → User: chip; drop duplicate name fields
    $userCreateLinked = false;
    $html = preg_replace_callback(
        '/\bUser-ID:\s*(\d+)(?=\s*,)/si',
        function ($m) use ($chip, $plainLabel, $source, &$userCreateLinked) {
            $label = '';
            if(preg_match('/\bVorname:\s*<b>(.*?)<\/b>/si', $source, $v)
                && preg_match('/\bNachname:\s*<b>(.*?)<\/b>/si', $source, $n)) {
                $label = trim($plainLabel($v[1]).' '.$plainLabel($n[1]));
            }
            if($label === '') {
                $label = '#'.$m[1];
            }
            else {
                $userCreateLinked = true;
            }
            return 'User: '.$chip('user', $m[1], $label);
        },
        $html
    );
    if($userCreateLinked) {
        $html = preg_replace('/,?\s*Vorname:\s*<b>.*?<\/b>/si', '', $html, 1);
        $html = preg_replace('/,?\s*Nachname:\s*<b>.*?<\/b>/si', '', $html, 1);
    }

    // Melde / canonical: Schicht/Aufgabe: (3) <b>Bierstand …</b> optional time
    $html = preg_replace_callback(
        '/\bSchicht\/Aufgabe:\s*\((\d+)\)\s*<b>(.*?)<\/b>(?:\s*([^,<]*?))?(?=,|$)/si',
        function ($m) use ($chip, $plainLabel) {
            $extra = isset($m[3]) ? $plainLabel($m[3]) : '';
            $label = $plainLabel($m[2]);
            if($extra !== '') {
                $label = trim($label.' '.$extra);
            }
            if($label === '') {
                $label = '#'.$m[1];
            }
            return 'Schicht/Aufgabe: '.$chip('shift', $m[1], $label);
        },
        $html
    );

    // Edit/create: Schicht/Aufgabe: 7, … Name: <b>…</b>
    $shiftNameLinked = false;
    $html = preg_replace_callback(
        '/\bSchicht\/Aufgabe:\s*(\d+)(?=\s*,|\s*$)/si',
        function ($m) use ($chip, $plainLabel, $source, &$shiftNameLinked) {
            $label = '';
            if(preg_match('/\bName:\s*<b>(.*?)<\/b>/si', $source, $n)) {
                $label = $plainLabel($n[1]);
            }
            if($label === '') {
                $label = '#'.$m[1];
            }
            else {
                $shiftNameLinked = true;
            }
            return 'Schicht/Aufgabe: '.$chip('shift', $m[1], $label);
        },
        $html
    );
    if($shiftNameLinked) {
        $html = preg_replace('/,?\s*Name:\s*<b>.*?<\/b>/si', '', $html, 1);
    }

    // Email: (23) <b>Betreff</b>
    $html = preg_replace_callback(
        '/\bEmail:\s*\((\d+)\)\s*<b>(.*?)<\/b>/si',
        function ($m) use ($chip) {
            return 'Email: '.$chip('mail', $m[1], $m[2]);
        },
        $html
    );

    // Email-ID: <b>23</b> → Email: chip (Betreff as label when present; drop duplicate Betreff field)
    $emailBetreff = '';
    if(preg_match('/\bBetreff:\s*<b>(.*?)<\/b>/si', $source, $eb)) {
        $emailBetreff = $plainLabel($eb[1]);
    }
    $emailIdLinked = false;
    $html = preg_replace_callback(
        '/\bEmail-ID:\s*<b>(\d+)<\/b>/si',
        function ($m) use ($chip, $emailBetreff, &$emailIdLinked) {
            $emailIdLinked = true;
            $label = $emailBetreff !== '' ? $emailBetreff : ('#'.$m[1]);
            return 'Email: '.$chip('mail', $m[1], $label);
        },
        $html
    );
    if($emailIdLinked && $emailBetreff !== '') {
        $html = preg_replace('/,?\s*Betreff:\s*<b>.*?<\/b>/si', '', $html, 1);
    }

    // Eigentümer: (5) <b>Name</b>
    $html = preg_replace_callback(
        '/\bEigentümer:\s*\((\d+)\)\s*<b>(.*?)<\/b>/si',
        function ($m) use ($chip) {
            return 'Eigentümer: '.$chip('user', $m[1], $m[2]);
        },
        $html
    );

    // Eigentümer: (5) PlainName &rArr;
    $html = preg_replace_callback(
        '/\bEigentümer:\s*\((\d+)\)\s+(?!<)([^,<]+?)(?=\s*&rArr;|\s*,|\s*$)/si',
        function ($m) use ($chip) {
            return 'Eigentümer: '.$chip('user', $m[1], $m[2]);
        },
        $html
    );

    // User: (5) PlainName  (change-log without <b> on old value)
    $html = preg_replace_callback(
        '/\bUser:\s*\((\d+)\)\s+(?!<)([^,<]+?)(?=\s*&rArr;|\s*,|\s*$)/si',
        function ($m) use ($chip) {
            return 'User: '.$chip('user', $m[1], $m[2]);
        },
        $html
    );

    // Legacy Melde-Logs: User: <b>Name</b> (no id) — resolve via Melde-ID / unique name
    $html = preg_replace_callback(
        '/\bUser:\s*<b>(.*?)<\/b>/si',
        function ($m) use ($chip, $plainLabel, $source) {
            $label = $plainLabel($m[1]);
            // Bold may contain "(id) Name" from change arrows
            if(preg_match('/^\((\d+)\)\s*(.*)$/s', $label, $im)) {
                $rest = trim($im[2]);
                return 'User: '.$chip('user', $im[1], $rest !== '' ? $rest : $label);
            }
            $uid = logResolveUserIdFromMessage($source, $label);
            if($uid > 0) {
                return 'User: '.$chip('user', $uid, $label);
            }
            return $m[0];
        },
        $html
    );

    // Eigentümer: <b>(id) Name</b> (arrow target)
    $html = preg_replace_callback(
        '/\bEigentümer:\s*<b>\((\d+)\)\s*(.*?)<\/b>/si',
        function ($m) use ($chip) {
            return 'Eigentümer: '.$chip('user', $m[1], $m[2]);
        },
        $html
    );

    return $html;
}

function getPage($string, $groupId = '') {
    if($string == $_SESSION['page']) {
        echo $GLOBALS['optionsDB']['colorTitleBar'];
        return;
    }
    if($groupId !== '') {
        echo navGroupClass($groupId);
        return;
    }
    echo $GLOBALS['optionsDB']['colorNav'];
}

function getShort($Vorname, $Nachname) {
    $flags = ENT_QUOTES;
    if(defined('ENT_HTML5')) {
        $flags = ENT_QUOTES | ENT_HTML5;
    }
    $Vorname = html_entity_decode((string)$Vorname, $flags, 'UTF-8');
    $Nachname = html_entity_decode((string)$Nachname, $flags, 'UTF-8');
    if(function_exists('mb_substr')) {
        $short1 = mb_substr($Vorname, 0, 2, 'UTF-8');
        $parts = preg_split('/\s+/u', trim($Nachname), -1, PREG_SPLIT_NO_EMPTY);
        $last = $parts ? $parts[count($parts) - 1] : $Nachname;
        $short2 = mb_substr($last, 0, 2, 'UTF-8');
        return $short1.$short2;
    }
    $short1 = substr($Vorname, 0, 2);
    $narray = explode(' ', $Nachname);
    $short2 = substr($narray[sizeof($narray) - 1], 0, 2);
    return $short1.$short2;
}

function instrumentOption($val) {
    $str='';
    $str=$str."<option value=\"0\">keins</option>\n";
    // LEFT JOIN: Instrument types must appear even if Register rows are missing
    $sql = sprintf('SELECT `%sInstrument`.* FROM `%sInstrument` LEFT JOIN (SELECT `Index` AS `rIndex`, `Sortierung` AS `rSort` FROM `%sRegister`) `%sRegister` ON `rIndex` = `Register` WHERE `Spielbar` = 1 ORDER BY COALESCE(`rSort`, 9999), `Sortierung`;',
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix']
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    while($row = mysqli_fetch_array($dbr)) {
        if($val == $row['Index']) {
            $str=$str."<option value=\"".$row['Index']."\" selected>".$row['Name']."</option>\n";
        }
        else {
            $str=$str."<option value=\"".$row['Index']."\">".$row['Name']."</option>\n";
        }
    }
    return $str;
}

function instrumentOptionAll($val) {
    $str='';
    $str=$str."<option value=\"0\">keins</option>\n";
    // LEFT JOIN: Instrument types must appear even if Register rows are missing
    $sql = sprintf('SELECT `%sInstrument`.* FROM `%sInstrument` LEFT JOIN (SELECT `Index` AS `rIndex`, `Sortierung` AS `rSort` FROM `%sRegister`) `%sRegister` ON `rIndex` = `Register` ORDER BY COALESCE(`rSort`, 9999), `Sortierung`;',
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix']
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    if(!$dbr) return $str;
    while($row = mysqli_fetch_array($dbr)) {
        if($val == $row['Index']) {
            $str=$str."<option value=\"".$row['Index']."\" selected>".$row['Name']."</option>\n";
        }
        else {
            $str=$str."<option value=\"".$row['Index']."\">".$row['Name']."</option>\n";
        }
    }
    return $str;
}

function inventoryOptionAll($val) {
    $str='';
    $str=$str."<option value=\"0\">keins</option>\n";
    $sql = sprintf(
        'SELECT * FROM `%sInventory` ORDER BY `Sortierung`;',
        $GLOBALS['dbprefix']
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    while($row = mysqli_fetch_array($dbr)) {
        $label = $row['Typ'];
        if(!empty($row['Prefix'])) $label = $row['Prefix'].' — '.$row['Typ'];
        if($val == $row['Index']) {
            $str=$str."<option value=\"".$row['Index']."\" selected>".htmlspecialchars($label)."</option>\n";
        }
        else {
            $str=$str."<option value=\"".$row['Index']."\">".htmlspecialchars($label)."</option>\n";
        }
    }
    return $str;
}

function isHexColor($value) {
    if(!is_string($value)) return false;
    return (bool)preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', trim($value));
}

function normalizeHexColor($value) {
    $value = strtoupper(trim((string)$value));
    if(!isHexColor($value)) return '';
    if(strlen($value) === 4) {
        return '#'.$value[1].$value[1].$value[2].$value[2].$value[3].$value[3];
    }
    return $value;
}

/**
 * Mix two hex colors. $t=0 → $hexA, $t=1 → $hexB.
 * @param string $hexA
 * @param string $hexB
 * @param float $t
 * @return string
 */
function hexMix($hexA, $hexB, $t) {
    $hexA = normalizeHexColor($hexA);
    $hexB = normalizeHexColor($hexB);
    if($hexA === '') return $hexB !== '' ? $hexB : '#808080';
    if($hexB === '') return $hexA;
    $t = max(0.0, min(1.0, (float)$t));
    $ar = hexdec(substr($hexA, 1, 2));
    $ag = hexdec(substr($hexA, 3, 2));
    $ab = hexdec(substr($hexA, 5, 2));
    $br = hexdec(substr($hexB, 1, 2));
    $bg = hexdec(substr($hexB, 3, 2));
    $bb = hexdec(substr($hexB, 5, 2));
    $r = (int)round($ar + ($br - $ar) * $t);
    $g = (int)round($ag + ($bg - $ag) * $t);
    $b = (int)round($ab + ($bb - $ab) * $t);
    return sprintf('#%02X%02X%02X', $r, $g, $b);
}

/**
 * Soft / accent / strong / softOff from a group accent color.
 * @param string $accentHex
 * @return array{accent:string,soft:string,strong:string,softOff:string,fg:string}
 */
function permissionGroupTonePalette($accentHex) {
    $accent = normalizeHexColor($accentHex);
    if($accent === '') {
        $accent = '#78909C';
    }
    return array(
        'accent' => $accent,
        'soft' => hexMix($accent, '#FFFFFF', 0.82),
        'strong' => hexMix($accent, '#FFFFFF', 0.38),
        'softOff' => hexMix($accent, '#FFFFFF', 0.92),
        'fg' => '#222222',
    );
}

/**
 * Palette for all permission groups (id → tones).
 * @return array<string,array{accent:string,soft:string,strong:string,softOff:string,fg:string}>
 */
function permissionGroupPalettes() {
    static $cache = null;
    if($cache !== null) {
        return $cache;
    }
    $cache = array();
    if(!class_exists('Permissions')) {
        return $cache;
    }
    foreach(Permissions::permissionGroups() as $group) {
        $id = isset($group['id']) ? preg_replace('/[^a-z0-9_-]/i', '', (string)$group['id']) : '';
        if($id === '') {
            continue;
        }
        $accent = isset($group['color']) ? (string)$group['color'] : Permissions::groupColor($id);
        $cache[$id] = permissionGroupTonePalette($accent);
    }
    return $cache;
}

/**
 * CSS for Nav / Chips / Heroes / Rechte-Matrix from permission group colors.
 * @param bool $wrapStyleTag
 * @return string
 */
function renderPermissionGroupColorCss($wrapStyleTag = true) {
    $palettes = permissionGroupPalettes();
    if(!$palettes) {
        return '';
    }
    $css = '';
    foreach($palettes as $id => $tone) {
        $soft = $tone['soft'];
        $accent = $tone['accent'];
        $strong = $tone['strong'];
        $softOff = $tone['softOff'];
        $fg = $tone['fg'];

        $css .= '.app-nav .admin-nav-perm--'.$id
            .',.profile-perm-tile--'.$id
            .'{background:'.$soft.' !important;border-color:'.$accent.';color:'.$fg.' !important;}';

        $css .= '.admin-list-shell:has(.admin-list-hero--'.$id.')'
            .'{--page-title-accent:'.$accent.';}';

        $css .= '.profile-shell .profile-hero.admin-list-hero--'.$id
            .',.w3-container.admin-list-hero--'.$id
            .'{background:'.$strong.';border-left-color:'.$accent.';--page-title-accent:'.$accent.';}';

        $css .= '.perm-matrix thead th.perm-group--'.$id
            .'{background:'.$soft.';box-shadow:inset 0 -3px 0 '.$accent.';}';

        $css .= '.perm-matrix td.perm-group--'.$id.'.perm-off{background:'.$softOff.';}';
        $css .= '.perm-matrix td.perm-group--'.$id.'.perm-on{background:'.$strong.';}';
    }

    $css .= '@media (max-width:992px){';
    foreach($palettes as $id => $tone) {
        $accent = $tone['accent'];
        $css .= '.app-nav>.app-nav-primary>.app-nav-item.admin-nav-perm--'.$id
            .',.app-nav>.app-nav-primary>.app-nav-form>.app-nav-item.admin-nav-perm--'.$id
            .',.app-nav>.app-nav-more-wrap>.app-nav-more-toggle.admin-nav-perm--'.$id
            .'{border-top-color:'.$accent.';}';
    }
    $css .= '}';

    if($css === '') {
        return '';
    }
    return $wrapStyleTag ? '<style type="text/css" id="perm-group-colors">'.$css.'</style>' : $css;
}

function hexContrastText($hex) {
    $hex = normalizeHexColor($hex);
    if($hex === '') return '#000000';
    $r = hexdec(substr($hex, 1, 2));
    $g = hexdec(substr($hex, 3, 2));
    $b = hexdec(substr($hex, 5, 2));
    // Relative luminance (sRGB approx.)
    $luma = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;
    return ($luma > 0.55) ? '#000000' : '#FFFFFF';
}

/**
 * Contrast text for a fill that may be translucent over a light page background.
 * Blends fill with $bgHex by $opacity before choosing black/white.
 */
function hexContrastTextOnFill($hex, $opacity = 1.0, $bgHex = '#FFFFFF') {
    $hex = normalizeHexColor($hex);
    $bgHex = normalizeHexColor($bgHex);
    if($hex === '') return '#000000';
    if($bgHex === '') $bgHex = '#FFFFFF';
    $opacity = max(0.0, min(1.0, (float)$opacity));
    if($opacity >= 0.999) {
        return hexContrastText($hex);
    }
    $fr = hexdec(substr($hex, 1, 2));
    $fg = hexdec(substr($hex, 3, 2));
    $fb = hexdec(substr($hex, 5, 2));
    $br = hexdec(substr($bgHex, 1, 2));
    $bg = hexdec(substr($bgHex, 3, 2));
    $bb = hexdec(substr($bgHex, 5, 2));
    $r = (int)round($fr * $opacity + $br * (1.0 - $opacity));
    $g = (int)round($fg * $opacity + $bg * (1.0 - $opacity));
    $b = (int)round($fb * $opacity + $bb * (1.0 - $opacity));
    $luma = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;
    return ($luma > 0.55) ? '#000000' : '#FFFFFF';
}

function w3ColorToHex($class) {
    static $map = array(
        'w3-mvd-blue' => '#345A95',
        'w3-mvd-gray' => '#969696',
        'w3-mvd-dark-gray' => '#454545',
        'w3-mvd-egg' => '#FDF9E7',
        'w3-mvd-yellow' => '#FFC300',
        'w3-mvd-white' => '#FDFFFC',
        'w3-mvd-black' => '#040006',
        'w3-mvd-light-blue' => '#7F9DC1',
        'w3-amber' => '#FFC107',
        'w3-aqua' => '#00FFFF',
        'w3-blue' => '#2196F3',
        'w3-light-blue' => '#87CEEB',
        'w3-brown' => '#795548',
        'w3-cyan' => '#00BCD4',
        'w3-blue-grey' => '#607D8B',
        'w3-blue-gray' => '#607D8B',
        'w3-green' => '#4CAF50',
        'w3-light-green' => '#8BC34A',
        'w3-indigo' => '#3F51B5',
        'w3-khaki' => '#F0E68C',
        'w3-lime' => '#CDDC39',
        'w3-orange' => '#FF9800',
        'w3-deep-orange' => '#FF5722',
        'w3-pink' => '#E91E63',
        'w3-purple' => '#9C27B0',
        'w3-deep-purple' => '#673AB7',
        'w3-red' => '#F44336',
        'w3-sand' => '#FDF5E6',
        'w3-teal' => '#009688',
        'w3-yellow' => '#FFEB3B',
        'w3-white' => '#FFFFFF',
        'w3-black' => '#000000',
        'w3-grey' => '#9E9E9E',
        'w3-gray' => '#9E9E9E',
        'w3-light-grey' => '#F1F1F1',
        'w3-light-gray' => '#F1F1F1',
        'w3-dark-grey' => '#616161',
        'w3-dark-gray' => '#616161',
        'w3-pale-red' => '#FFDDDD',
        'w3-pale-green' => '#DDFFDD',
        'w3-pale-yellow' => '#FFFFCC',
        'w3-pale-blue' => '#DDFFFF',
        'w3-highway-brown' => '#633517',
        'w3-highway-red' => '#A6001A',
        'w3-highway-orange' => '#E06000',
        'w3-highway-schoolbus' => '#EE9600',
        'w3-highway-yellow' => '#FFAB00',
        'w3-highway-green' => '#004D33',
        'w3-highway-blue' => '#00477E',
    );
    $class = trim((string)$class);
    return isset($map[$class]) ? $map[$class] : '#808080';
}

function colorPickerValue($raw) {
    $raw = trim((string)$raw);
    if($raw === '') return '#808080';
    if(isHexColor($raw)) return normalizeHexColor($raw);
    return w3ColorToHex($raw);
}

function colorToCssClass($value) {
    $value = trim((string)$value);
    if($value === '') return '';
    if(isHexColor($value)) {
        $hex = normalizeHexColor($value);
        $class = 'cfg-hex-'.strtolower(substr($hex, 1));
        if(!isset($GLOBALS['cfgColorCssRules'])) {
            $GLOBALS['cfgColorCssRules'] = array();
        }
        $GLOBALS['cfgColorCssRules'][$class] = array(
            'bg' => $hex,
            'fg' => hexContrastText($hex),
        );
        return $class;
    }
    return $value;
}

function renderConfigColorCss($wrapStyleTag = true) {
    $css = '';
    $pageBg = '#FDFFFC';
    $bgClass = isset($GLOBALS['optionsDB']['colorBackground']) ? (string)$GLOBALS['optionsDB']['colorBackground'] : '';
    if($bgClass !== ''
        && !empty($GLOBALS['cfgColorCssRules'][$bgClass]['bg'])
        && isHexColor($GLOBALS['cfgColorCssRules'][$bgClass]['bg'])) {
        $pageBg = normalizeHexColor($GLOBALS['cfgColorCssRules'][$bgClass]['bg']);
    }
    $css .= ':root{--app-page-bg:'.$pageBg.';}';
    if(!empty($GLOBALS['cfgColorCssRules']) && is_array($GLOBALS['cfgColorCssRules'])) {
        foreach($GLOBALS['cfgColorCssRules'] as $class => $colors) {
            $css .= '.'.preg_replace('/[^a-z0-9\-]/i', '', $class)
                .'{color:'.$colors['fg'].' !important;background-color:'.$colors['bg'].' !important;}';
        }
    }
    if($css === '') return '';
    return $wrapStyleTag ? '<style type="text/css">'.$css.'</style>' : $css;
}

function getColorConfigParameters() {
    static $params = null;
    if($params !== null && count($params) > 0) return $params;
    $params = array();
    if(function_exists('getConfigDefaults')) {
        foreach(getConfigDefaults() as $item) {
            if(isset($item['Type']) && $item['Type'] === 'color' && isset($item['Parameter'])) {
                $params[$item['Parameter']] = true;
            }
        }
    }
    return $params;
}

function loadconfig() {
    $optionsDB = array();
    $sql = sprintf('SELECT * FROM `%sconfig`;',
		   $GLOBALS['dbprefix']
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    if($dbr) {
        while($row = mysqli_fetch_array($dbr)) {
            $optionsDB[$row['Parameter']] = $row['Value'];
        }
    }
    if(function_exists('getConfigDefaults')) {
        foreach(getConfigDefaults() as $item) {
            if(!array_key_exists($item['Parameter'], $optionsDB)) {
                $optionsDB[$item['Parameter']] = $item['Value'];
            }
        }
    }
    $colorParams = getColorConfigParameters();
    foreach($optionsDB as $param => $value) {
        if(isset($colorParams[$param]) || isHexColor($value)) {
            $optionsDB[$param] = colorToCssClass($value);
        }
    }
    return $optionsDB;
}

function loadPermissions($user) {
    return Permissions::loadEffectiveByUser($user);
}

/**
 * Cache-busting URL for static assets (release version + hash + file mtime).
 * Needed so JS/CSS reload after deploys (esp. Android WebView).
 */
function assetUrl($rel) {
    $rel = ltrim(str_replace('\\', '/', (string)$rel), '/');
    $ver = isset($GLOBALS['version']['String']) ? (string)$GLOBALS['version']['String'] : '0';
    $hash = isset($GLOBALS['version']['Hash']) ? (string)$GLOBALS['version']['Hash'] : '0';
    $mtime = @filemtime(dirname(__DIR__).'/'.$rel);
    if($mtime === false) {
        $mtime = 0;
    }
    return htmlspecialchars($rel.'?v='.rawurlencode($ver).'&h='.$hash.'-'.$mtime, ENT_QUOTES, 'UTF-8');
}

function meldeRequest($key, $default = null) {
    if(isset($_POST[$key])) {
        return $_POST[$key];
    }
    if(isset($_GET[$key])) {
        return $_GET[$key];
    }
    return $default;
}

function requireEditResponseAuth($targetUser) {
    if(!loggedIn()) {
        http_response_code(403);
        die('forbidden');
    }
    $targetUser = (int)$targetUser;
    $sessionUser = (int)$_SESSION['userid'];
    $proxyUser = (isset($_SESSION['proxy']) && (int)$_SESSION['proxy'] > 0) ? (int)$_SESSION['proxy'] : 0;
    if($targetUser !== $sessionUser && $targetUser !== $proxyUser && !requirePermission('perm_editResponse')) {
        http_response_code(403);
        die('forbidden');
    }
}

function csrf_token() {
    if(empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify($token) {
    if(!isset($_SESSION['csrf_token']) || !is_string($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="'
        .htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8').'">';
}

function loggedIn() {
    if(!isset($_SESSION['userid'])) {
	session_destroy();
	return false;
    }
    if($_SESSION['userid'] > 0) return true;
    session_destroy();
    return false;
}

function meldeWert($val) {
    switch($val) {
	case 1:
            return "ja";
	case 2:
            return "nein";
	case 3:
            return "vielleicht";
	default:
            break;
    }
}

function meldeSymbol($val) {
    $label = meldeWert($val);
    if($label === null || $label === '') {
        return '';
    }
    $colors = array(
        1 => isset($GLOBALS['optionsDB']['colorBtnYes']) ? $GLOBALS['optionsDB']['colorBtnYes'] : 'w3-green',
        2 => isset($GLOBALS['optionsDB']['colorBtnNo']) ? $GLOBALS['optionsDB']['colorBtnNo'] : 'w3-red',
        3 => isset($GLOBALS['optionsDB']['colorBtnMaybe']) ? $GLOBALS['optionsDB']['colorBtnMaybe'] : 'w3-orange',
    );
    $color = isset($colors[(int)$val]) ? $colors[(int)$val] : '';
    $div = new div;
    $div->tag = 'span';
    $div->class = 'w3-tag log-melde-chip '.$color;
    $div->body = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
    return $div->print();
}

function mkAdmin() {
    $_SESSION['userid'] = 0;
    $_SESSION['admin'] = true;
    $_SESSION['username'] = 'SYSTEM';
}

function mkEmpty($str) {
    if($str) return $str;
    return "";
}

function mkNULL($str) {
    if($str) return $str;
    return "NULL";
}

function mkNULLstr($str) {
    if($str) return "\"".$str."\"";
    return "NULL";
}

function mkPrize($val) {
    if((float)$val != 0) {
        return sprintf("%.2f &euro;", $val);
    }
}

/**
 * True if a value should appear in an INSERT/DELETE log (non-empty).
 * Empty string, null, and whitespace-only are skipped. Numeric 0 is empty
 * unless $allowZero is true. Booleans: use logPartTrue for true-only.
 */
function logValueFilled($value, $allowZero = false) {
    if($value === null) {
        return false;
    }
    if(is_bool($value)) {
        return $value;
    }
    if(is_int($value) || is_float($value)) {
        if(!$allowZero && (float)$value == 0) {
            return false;
        }
        return true;
    }
    $s = trim((string)$value);
    if($s === '' || $s === '-') {
        return false;
    }
    return true;
}

/**
 * Build one log fragment: "Label: <b>value</b>".
 * $valueHtml is inserted as-is (already escaped/formatted by caller if needed).
 */
function logPart($label, $valueHtml) {
    return $label.': <b>'.$valueHtml.'</b>';
}

/**
 * Append logPart to $parts if value is filled.
 */
function logAppendFilled(array &$parts, $label, $value, $valueHtml = null, $allowZero = false) {
    if(!logValueFilled($value, $allowZero)) {
        return;
    }
    $parts[] = logPart($label, $valueHtml !== null ? $valueHtml : htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'));
}

/**
 * Append logPart for a boolean only when true.
 */
function logAppendTrue(array &$parts, $label, $value) {
    if(!$value) {
        return;
    }
    $parts[] = logPart($label, bool2string($value));
}

/**
 * True when a DB-UPDATE log message describes at least one field change.
 * Header-only messages (ID + name) must not create log noise.
 */
function logMessageHasChanges($message) {
    $message = (string)$message;
    if($message === '') {
        return false;
    }
    if(strpos($message, '&rArr;') !== false) {
        return true;
    }
    // Meldung::getChanges uses "(vorher:…)" for status without &rArr;
    if(strpos($message, '(vorher:') !== false) {
        return true;
    }
    if(preg_match('/\b(?:Passhash|activeLink)\s+geändert\b/u', $message)) {
        return true;
    }
    if(strpos($message, 'zurückgesetzt') !== false) {
        return true;
    }
    if(strpos($message, 'umbenannt:') !== false) {
        return true;
    }
    return false;
}

/**
 * Format a config value for log display (HTML-escaped).
 */
function formatConfigLogValue($value, $type = '') {
    if($value === null || $value === '') {
        return '(leer)';
    }
    if($type === 'bool') {
        return bool2string($value);
    }
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Write a DBupdate log entry for a config parameter change.
 */
function logConfigChange($parameter, $oldValue, $newValue, $type = '') {
    if((string)$oldValue === (string)$newValue) {
        return;
    }
    $label = (string)$parameter;
    if($type === '' && function_exists('getConfigDefaults')) {
        foreach(getConfigDefaults() as $item) {
            if($item['Parameter'] === $parameter) {
                $type = isset($item['Type']) ? (string)$item['Type'] : '';
                if(!empty($item['Description'])) {
                    $label = $parameter.' ('.$item['Description'].')';
                }
                break;
            }
        }
    }
    $logentry = new Log;
    $logentry->DBupdate(sprintf(
        'Config <b>%s</b>: %s &rArr; <b>%s</b>',
        htmlspecialchars($label, ENT_QUOTES, 'UTF-8'),
        formatConfigLogValue($oldValue, $type),
        formatConfigLogValue($newValue, $type)
    ));
}

function recordLogin() {
    $sql = sprintf("UPDATE `%sUser` SET `LastLogin` = CURRENT_TIMESTAMP() WHERE `Index` = %d;",
		   $GLOBALS['dbprefix'],
		   $_SESSION['userid']
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
}

function RegisterOption($val) {
    $sql = sprintf('SELECT * FROM `%sRegister` ORDER BY `Sortierung`;',
		   $GLOBALS['dbprefix']
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    while($row = mysqli_fetch_array($dbr)) {
        if($val == $row['Index']) {
            echo "<option value=\"".$row['Index']."\" selected>".$row['Name']."</option>\n";
        }
        else {
            echo "<option value=\"".$row['Index']."\">".$row['Name']."</option>\n";
        }
    }
}

function requirePermission($perm) {
    $uid = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
    if($uid < 1) {
        return false;
    }
    return Permissions::loadEffectiveByUser($uid)->getPermission($perm);
}

/**
 * Deny page access with header/nav (if not yet rendered), warning panel, footer, then exit.
 * @param string $message Plain-text warning shown to the user
 */
function denyAccess($message = 'Keine Berechtigung für diesen Bereich.') {
    if(!headers_sent()) {
        http_response_code(403);
    }
    // header/nav/footer expect classic page-scope vars; including from a function
    // would otherwise leave $sql/$optionsDB undefined and yield a blank page.
    foreach(array('sql', 'optionsDB', 'conn') as $k) {
        if(array_key_exists($k, $GLOBALS)) {
            $$k = $GLOBALS[$k];
        }
    }
    if(empty($GLOBALS['mlHeaderRendered'])) {
        include __DIR__.'/../common/header.php';
    }
    $color = isset($optionsDB['colorLogWarning'])
        ? $optionsDB['colorLogWarning']
        : (isset($GLOBALS['optionsDB']['colorLogWarning']) ? $GLOBALS['optionsDB']['colorLogWarning'] : 'w3-orange');
    echo '<div class="w3-panel '.$color.' w3-padding w3-margin">'
        .'<h3>Zugriff verweigert</h3>'
        .'<p>'.htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8').'</p>'
        .'</div>';
    include __DIR__.'/../common/footer.php';
    exit;
}

function isAdmin() {
    $uid = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
    if($uid < 1) {
        return false;
    }
    return (bool)Permissions::loadEffectiveByUser($uid)->getAdmin();
}

function sql2time($time) {
    if($time != '') {
        return sql2timeRaw($time)." Uhr";
    }
}

function sql2timeRaw($time) {
    if($time === null || $time === '') {
        return '';
    }
    // Log payloads and UI: HH:MM without seconds (MELD-168)
    return substr((string)$time, 0, 5);
}

function sqlerror() {
    if(!isset($GLOBALS['conn']) || !mysqli_errno($GLOBALS['conn'])) {
        return;
    }
    $msg = mysqli_errno($GLOBALS['conn']).": ".mysqli_error($GLOBALS['conn']);
    $color = isset($GLOBALS['optionsDB']['colorLogFatal']) ? $GLOBALS['optionsDB']['colorLogFatal'] : 'w3-red';
    echo "<div class=\"w3-container ".$color." w3-mobile w3-border w3-padding w3-border-black\"><b>SQL ERROR </b>".htmlspecialchars($msg)."</div>";
    if(class_exists('Log')) {
        $logentry = new Log;
        $logentry->error($msg);
    }
}

function string2gDate($string) {
    $y = substr($string, 0, 4);
    $m = substr($string, 5, 2);
    $d = substr($string, 8, 2);
    return "new Date(".intval($y).", ".(intval($m)-1).", ".intval($d).")";
}

function UserOptionAll($val) {
    $str='';
    $str=$str."<option value=\"0\">".$GLOBALS['optionsDB']['orgNameShort']."</option>\n";
    $sql = sprintf('SELECT * FROM `%sUser` WHERE `Deleted` = 0 AND %s ORDER BY `Nachname`, `Vorname`;',
    $GLOBALS['dbprefix'],
    sqlExcludeFoerderndeUsers('`Index`')
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    if(!$dbr) return $str;
    while($row = mysqli_fetch_array($dbr)) {
        if($val == $row['Index']) {
            $str=$str."<option value=\"".$row['Index']."\" selected>".$row['Vorname']." ".$row['Nachname']."</option>\n";
        }
        else {
            $str=$str."<option value=\"".$row['Index']."\">".$row['Vorname']." ".$row['Nachname']."</option>\n";
        }
    }
    return $str;
}

/**
 * User catalog for loan person chip picker (same set as UserOptionAll).
 * @return list<array{id:int,label:string,meta:string}>
 */
function loanUserChipCatalog() {
    $org = isset($GLOBALS['optionsDB']['orgNameShort'])
        ? trim((string)$GLOBALS['optionsDB']['orgNameShort'])
        : '';
    if($org === '') {
        $org = 'Verein';
    }
    $catalog = array(
        array('id' => 0, 'label' => $org, 'meta' => 'Verein'),
    );
    $sql = sprintf(
        'SELECT u.`Index`, u.`Vorname`, u.`Nachname`, COALESCE(r.`Name`, "") AS `RegisterName`
         FROM `%sUser` u
         LEFT JOIN `%sInstrument` i ON i.`Index` = u.`Instrument`
         LEFT JOIN `%sRegister` r ON r.`Index` = i.`Register`
         WHERE u.`Deleted` = 0 AND %s
         ORDER BY u.`Nachname`, u.`Vorname`;',
        $GLOBALS['dbprefix'],
        $GLOBALS['dbprefix'],
        $GLOBALS['dbprefix'],
        sqlExcludeFoerderndeUsers('u.`Index`')
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    if(!$dbr) {
        return $catalog;
    }
    while($row = mysqli_fetch_array($dbr)) {
        $catalog[] = array(
            'id' => (int)$row['Index'],
            'label' => trim($row['Vorname'].' '.$row['Nachname']),
            'meta' => html_entity_decode((string)$row['RegisterName'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        );
    }
    return $catalog;
}

function validateLink($hash) {
    $_SESSION['userid'] = 0;
    $hash = function_exists('normalizeAlinkInput')
        ? normalizeAlinkInput($hash)
        : trim((string)$hash);
    if($hash === '' || !preg_match('/^[a-zA-Z0-9]+$/', $hash)) {
        $logentry = new Log;
        $logentry->error("Login not successful. Invalid hash for login via link.");
        return false;
    }
    if(function_exists('findUserByActiveLink')) {
        $row = findUserByActiveLink($hash);
        if($row) {
            return establishSessionFromUserRow($row, 'Link');
        }
    } else {
        $sql = sprintf(
            "SELECT * FROM `%sUser` WHERE `activeLink` = '%s' AND `Deleted` != 1 LIMIT 1;",
            $GLOBALS['dbprefix'],
            mysqli_real_escape_string($GLOBALS['conn'], $hash)
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = mysqli_fetch_assoc($dbr);
        if($row) {
            return establishSessionFromUserRow($row, 'Link');
        }
    }
    $logentry = new Log;
    $logentry->error("Login not successful. Invalid hash for login via link <b>".htmlspecialchars($hash)."</b>.");
    return false;
}
function validateUser($login, $password) {
    $_SESSION['userid'] = 0;
    $login = trim((string)$login);
    if($login === '' || $password === null || $password === '') {
        $logentry = new Log;
        $logentry->error("Login not successful. Leerer Benutzername oder Passwort.");
        return false;
    }
    $sql = sprintf("SELECT * FROM `%sUser` WHERE `login` = '%s' AND `Deleted` != 1;",
		   $GLOBALS['dbprefix'],
		   mysqli_real_escape_string($GLOBALS['conn'], $login)
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    while($row = mysqli_fetch_assoc($dbr)) {
        $hash = (string)$row['Passhash'];
        if($hash !== '' && password_verify($password, $hash)) {
            return establishSessionFromUserRow($row, 'Password');
        }
    }
    $logentry = new Log;
    $logentry->error("Login not successful. Invalid password for username <b>".htmlspecialchars($login)."</b>.");
    return false;
}

function VehicleOption($val) {
    $sql = sprintf('SELECT * FROM `%svehicle`;',
		   $GLOBALS['dbprefix']
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    while($row = mysqli_fetch_array($dbr)) {
        if($val == $row['Index']) {
            echo "<option value=\"".$row['Index']."\" selected>".$row['Name']."</option>\n";
        }
        else {
            echo "<option value=\"".$row['Index']."\">".$row['Name']."</option>\n";
        }
    }
}

/**
 * True if mail body looks like HTML (WYSIWYG) rather than plain text.
 */
function mailBodyLooksLikeHtml($text) {
    return (bool)preg_match('/<[a-z][\s\S]*>/i', (string)$text);
}

/**
 * Convert mail body (HTML or plain) to plain text.
 */
function mailBodyToPlainText($text) {
    $text = (string)$text;
    if(mailBodyLooksLikeHtml($text) || strpos($text, '<') !== false) {
        $text = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $text);
        $text = preg_replace('/<\s*\/\s*p\s*>/i', "\n\n", $text);
        $text = preg_replace('/<\s*\/\s*div\s*>/i', "\n", $text);
        $text = preg_replace('/<\s*\/\s*li\s*>/i', "\n", $text);
        $text = preg_replace('/<\s*\/\s*h[1-6]\s*>/i', "\n\n", $text);
        $text = preg_replace('/<\s*\/\s*tr\s*>/i', "\n", $text);
        $text = strip_tags($text);
    }
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace("\xc2\xa0", ' ', $text);
    $text = preg_replace("/[ \t]+/", ' ', $text);
    $text = preg_replace("/ *\n */", "\n", $text);
    $text = preg_replace("/\n{3,}/", "\n\n", $text);
    return trim($text);
}

/**
 * Convert mail body to Discord-friendly text (Markdown where useful, no raw HTML).
 */
function mailBodyToDiscordMarkdown($text) {
    $text = (string)$text;
    if(!mailBodyLooksLikeHtml($text) && strpos($text, '<') === false) {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xc2\xa0", ' ', $text);
        return trim($text);
    }

    $text = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $text);
    $text = preg_replace('/<\s*hr\s*\/?\s*>/i', "\n---\n", $text);
    $text = preg_replace('/<\s*\/\s*p\s*>/i', "\n\n", $text);
    $text = preg_replace('/<\s*p[^>]*>/i', '', $text);
    $text = preg_replace('/<\s*\/\s*div\s*>/i', "\n", $text);
    $text = preg_replace('/<\s*div[^>]*>/i', '', $text);

    $text = preg_replace('/<\s*h[1-6][^>]*>(.*?)<\s*\/\s*h[1-6]\s*>/is', "**$1**\n\n", $text);

    // Nested-ish inline tags: run a few passes
    for($i = 0; $i < 3; $i++) {
        $text = preg_replace('/<\s*(strong|b)(?:\s[^>]*)?>(.*?)<\s*\/\s*\1\s*>/is', '**$2**', $text);
        $text = preg_replace('/<\s*(em|i)(?:\s[^>]*)?>(.*?)<\s*\/\s*\1\s*>/is', '*$2*', $text);
        $text = preg_replace('/<\s*u(?:\s[^>]*)?>(.*?)<\s*\/\s*u\s*>/is', '__$1__', $text);
        $text = preg_replace('/<\s*(s|strike|del)(?:\s[^>]*)?>(.*?)<\s*\/\s*\1\s*>/is', '~~$2~~', $text);
    }

    $text = preg_replace_callback(
        '/<\s*a\s+[^>]*href\s*=\s*(["\'])(.*?)\1[^>]*>(.*?)<\s*\/\s*a\s*>/is',
        function($m) {
            $url = trim(html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $label = trim(html_entity_decode(strip_tags($m[3]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if($url === '') {
                return $label;
            }
            if($label === '' || strcasecmp($label, $url) === 0) {
                return $url;
            }
            return '['.$label.']('.$url.')';
        },
        $text
    );

    $text = preg_replace('/<\s*li[^>]*>/i', "• ", $text);
    $text = preg_replace('/<\s*\/\s*li\s*>/i', "\n", $text);
    $text = preg_replace('/<\s*\/?\s*(ul|ol)[^>]*>/i', "\n", $text);

    $text = preg_replace('/<\s*\/\s*tr\s*>/i', "\n", $text);
    $text = preg_replace('/<\s*\/\s*t[dh]\s*>/i', " · ", $text);
    $text = preg_replace('/<\s*(t[dh]|thead|tbody|table)[^>]*>/i', '', $text);
    $text = preg_replace('/<\s*\/\s*(thead|tbody|table)\s*>/i', "\n", $text);

    $text = preg_replace('/<\s*span[^>]*>/i', '', $text);
    $text = preg_replace('/<\s*\/\s*span\s*>/i', '', $text);

    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace("\xc2\xa0", ' ', $text);
    // Collapse spaces/tabs but keep newlines
    $text = preg_replace('/[^\S\n]+/', ' ', $text);
    $text = preg_replace('/ *\n */', "\n", $text);
    $text = preg_replace("/\n{3,}/", "\n\n", $text);
    // Drop empty bold markers only
    $text = preg_replace('/\*\*\s*\*\*/', '', $text);
    return trim($text);
}

/**
 * Allow only safe CSS declarations for mail HTML (color, size, align).
 */
function sanitizeMailHtmlStyleAttr($style) {
    $allowed = array(
        'color' => true,
        'background-color' => true,
        'font-size' => true,
        'font-family' => true,
        'font-weight' => true,
        'font-style' => true,
        'text-decoration' => true,
        'text-align' => true,
        'border-collapse' => true,
        'border' => true,
        'width' => true,
        'padding' => true,
        'margin-left' => true,
    );
    $out = array();
    foreach(explode(';', (string)$style) as $part) {
        $part = trim($part);
        if($part === '' || strpos($part, ':') === false) {
            continue;
        }
        list($prop, $val) = array_map('trim', explode(':', $part, 2));
        $prop = strtolower($prop);
        if(!isset($allowed[$prop])) {
            continue;
        }
        if(preg_match('/expression|javascript|import|@import|url\s*\(/i', $val)) {
            continue;
        }
        if($prop === 'color' || $prop === 'background-color') {
            if(!preg_match('/^(#[0-9a-f]{3,8}|rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}(?:\s*,\s*[\d.]+)?\s*\)|[a-z]+)$/i', $val)) {
                continue;
            }
        }
        elseif($prop === 'font-size') {
            if(!preg_match('/^[\d.]+(px|pt|em|rem|%)$/i', $val)) {
                continue;
            }
        }
        elseif($prop === 'font-family') {
            if(!preg_match('/^[a-z0-9\s,"\'\-]+$/i', $val)) {
                continue;
            }
        }
        elseif($prop === 'text-align') {
            if(!in_array(strtolower($val), array('left', 'right', 'center', 'justify'), true)) {
                continue;
            }
        }
        elseif($prop === 'border-collapse') {
            if(!in_array(strtolower($val), array('collapse', 'separate'), true)) {
                continue;
            }
        }
        elseif($prop === 'border' || $prop === 'padding' || $prop === 'margin-left' || $prop === 'width') {
            if(!preg_match('/^[\d.]+\s*(px|pt|em|rem|%)?(\s+solid\s+(#[0-9a-f]{3,8}|[a-z]+))?$/i', $val)
                && !preg_match('/^[\d.]+(px|pt|em|rem|%)$/i', $val)
                && !preg_match('/^\d+(\s+\d+){0,3}$/', $val)) {
                continue;
            }
        }
        elseif($prop === 'font-weight') {
            if(!preg_match('/^(normal|bold|bolder|lighter|[1-9]00)$/i', $val)) {
                continue;
            }
        }
        elseif($prop === 'font-style') {
            if(!in_array(strtolower($val), array('normal', 'italic', 'oblique'), true)) {
                continue;
            }
        }
        elseif($prop === 'text-decoration') {
            if(!preg_match('/^(none|underline|line-through)(\s+(none|underline|line-through))*$/i', $val)) {
                continue;
            }
        }
        $out[] = $prop.': '.$val;
    }
    return implode('; ', $out);
}

/**
 * Allowlist-sanitize HTML for email bodies (MELD-46).
 */
function sanitizeMailHtml($html) {
    $html = (string)$html;
    if($html === '') {
        return '';
    }
    $html = preg_replace('#<(script|iframe|object|embed|form|input|button|link|meta|style|svg|math)(\s[^>]*)?>[\s\S]*?</\1>#i', '', $html);
    $html = preg_replace('#<(script|iframe|object|embed|form|input|button|link|meta|style|svg|math)(\s[^>]*)?/?>#i', '', $html);
    $html = strip_tags($html, '<p><br><b><strong><i><em><u><s><strike><ul><ol><li><a><h1><h2><h3><h4><blockquote><span><div><hr><table><thead><tbody><tr><th><td>');
    $html = preg_replace('/\son[a-z]+\s*=\s*("|\')[\s\S]*?\1/i', '', $html);
    $html = preg_replace('/\son[a-z]+\s*=\s*[^\s>]+/i', '', $html);
    $html = preg_replace('/\s(href|src)\s*=\s*("|\')\s*javascript:[^"\']*\2/i', ' href="#"', $html);
    $html = preg_replace('/\s(href|src)\s*=\s*javascript:[^\s>]+/i', ' href="#"', $html);
    $html = preg_replace('/\s(class|id|data-[\w-]+)\s*=\s*("|\')[\s\S]*?\2/i', '', $html);
    // Keep simple table attributes commonly set by TinyMCE
    $html = preg_replace_callback('/<(table|td|th|tr)(\s[^>]*)?>/i', function($m) {
        $tag = strtolower($m[1]);
        $attrs = isset($m[2]) ? $m[2] : '';
        $keep = '';
        if(preg_match('/\sborder\s*=\s*("|\')?(\d+)\1?/i', $attrs, $bm)) {
            $keep .= ' border="'.(int)$bm[2].'"';
        }
        if(preg_match('/\scolspan\s*=\s*("|\')?(\d+)\1?/i', $attrs, $cm)) {
            $keep .= ' colspan="'.(int)$cm[2].'"';
        }
        if(preg_match('/\srowspan\s*=\s*("|\')?(\d+)\1?/i', $attrs, $rm)) {
            $keep .= ' rowspan="'.(int)$rm[2].'"';
        }
        if(preg_match('/\sstyle\s*=\s*("|\')(.*?)\1/is', $attrs, $sm)) {
            $clean = sanitizeMailHtmlStyleAttr($sm[2]);
            if($clean !== '') {
                $keep .= ' style="'.htmlspecialchars($clean, ENT_QUOTES, 'UTF-8').'"';
            }
        }
        return '<'.$tag.$keep.'>';
    }, $html);
    $html = preg_replace_callback('/\sstyle\s*=\s*("|\')(.*?)\1/is', function($m) {
        $clean = sanitizeMailHtmlStyleAttr($m[2]);
        return $clean !== '' ? ' style="'.htmlspecialchars($clean, ENT_QUOTES, 'UTF-8').'"' : '';
    }, $html);
    return $html;
}

/**
 * Format stored mail body for safe HTML display (preview / inbox).
 */
function formatMailBodyForDisplay($text) {
    $text = (string)$text;
    if($text === '') {
        return '';
    }
    if(mailBodyLooksLikeHtml($text)) {
        return sanitizeMailHtml($text);
    }
    return nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
}

/**
 * Format stored mail body for embedding into the PHPMailer HTML wrapper.
 */
function formatMailBodyForEmail($text) {
    return formatMailBodyForDisplay($text);
}

/**
 * Strip leading personal greeting from outbox body (plain or HTML).
 */
function stripMailBodyGreeting($body, $vorname) {
    $body = (string)$body;
    $vorname = (string)$vorname;
    $anrede = $vorname !== '' ? 'Hallo '.$vorname.',' : 'Hallo,';
    $prefix = $anrede."\n\n";
    if(strpos($body, $prefix) === 0) {
        return substr($body, strlen($prefix));
    }
    $htmlPrefix = '<p>'.htmlspecialchars($anrede, ENT_QUOTES, 'UTF-8').'</p>';
    if(strpos($body, $htmlPrefix) === 0) {
        return substr($body, strlen($htmlPrefix));
    }
    // TinyMCE may wrap without escaping differently
    $htmlPrefixLoose = '<p>'.$anrede.'</p>';
    if(strpos($body, $htmlPrefixLoose) === 0) {
        return substr($body, strlen($htmlPrefixLoose));
    }
    return $body;
}

/** Allowed return targets for form POST redirects (MELD-15 / MELD-57). */
function allowedReturnUrls() {
    return array(
        'musiker.php',
        'new-musiker.php',
        'mein-register.php',
        'index.php',
        'termine-archiv.php',
        'user-voice.php',
        'groups.php',
        'group-edit.php',
        'inventories.php',
        'myinventories.php',
        'insurance.php',
        'new-inventory.php',
        'mail.php',
        'meine-mails.php',
        'edit-shifts.php',
        'new-termin.php',
    );
}

/**
 * Map retired person-list URLs onto musiker.php (MELD-155).
 *
 * @param string $path path or basename, optional query
 * @return string
 */
function canonicalizeReturnUrl($path) {
    $path = trim((string)$path);
    if($path === '') {
        return $path;
    }
    $parts = parse_url($path);
    $file = isset($parts['path']) ? basename((string)$parts['path']) : basename($path);
    $legacy = array(
        'users.php',
        'gastmusiker.php',
        'mitglied.php',
        'no-mitglied.php',
        'register.php',
    );
    if(in_array($file, $legacy, true)) {
        return 'musiker.php';
    }
    return $path;
}

/** Map $_SESSION['page'] to list/edit PHP file. */
function pageToReturnUrl($page) {
    $map = array(
        'users' => 'musiker.php',
        'musiker' => 'musiker.php',
        'gastmusiker' => 'musiker.php',
        'mitglied' => 'musiker.php',
        'nomitglied' => 'musiker.php',
        'newmusiker' => 'new-musiker.php',
        'register' => 'musiker.php',
        'meinregister' => 'mein-register.php',
        'user-voice' => 'user-voice.php',
        'groups' => 'groups.php',
        'inventories' => 'inventories.php',
        'myinventories' => 'myinventories.php',
        'insurance' => 'inventories.php',
        'newinventory' => 'new-inventory.php',
        'mail' => 'mail.php',
        'meinemails' => 'meine-mails.php',
        'home' => 'index.php',
        'me' => 'index.php',
        'termine-archiv' => 'termine-archiv.php',
        'shifts' => 'edit-shifts.php',
        'newtermin' => 'new-termin.php',
    );
    $page = (string)$page;
    return isset($map[$page]) ? $map[$page] : 'index.php';
}

/**
 * Whitelist local PHP page; prevents open redirects.
 * Keeps a safe query string (only simple key=value pairs, no fragments/hosts).
 */
function safeReturnUrl($url, $default = 'index.php') {
    $allowed = allowedReturnUrls();
    $url = trim((string)$url);
    if($url === '') {
        return $default;
    }
    if(strpos($url, '://') !== false || strpos($url, '//') === 0 || strpos($url, '..') !== false) {
        return $default;
    }
    // Only a local script name (+ optional query), never a path.
    if(!preg_match('/^([a-zA-Z0-9_-]+\.php)(?:\?([a-zA-Z0-9_&=%-]*))?$/', $url, $m)) {
        return $default;
    }
    $base = canonicalizeReturnUrl($m[1]);
    if(!in_array($base, $allowed, true)) {
        return $default;
    }
    // Legacy person-list pages drop their query (filters live on musiker.php now).
    if($base !== $m[1]) {
        return $base;
    }
    if(empty($m[2])) {
        return $base;
    }
    return $base.'?'.$m[2];
}

/**
 * Store a return URL in the session and return an opaque token (MELD-57).
 * Forms POST the token instead of a raw return URL when possible.
 */
function issueReturnToken($url) {
    $url = safeReturnUrl($url, 'index.php');
    if(!isset($_SESSION['return_tokens']) || !is_array($_SESSION['return_tokens'])) {
        $_SESSION['return_tokens'] = array();
    }
    if(count($_SESSION['return_tokens']) > 40) {
        $_SESSION['return_tokens'] = array_slice($_SESSION['return_tokens'], -25, null, true);
    }
    $token = bin2hex(random_bytes(16));
    $_SESSION['return_tokens'][$token] = array(
        'url' => $url,
        'exp' => time() + 86400,
    );
    return $token;
}

/**
 * Resolve and consume a one-time return token.
 */
function consumeReturnToken($token, $default = 'index.php') {
    $token = (string)$token;
    if($token === '' || empty($_SESSION['return_tokens'][$token]) || !is_array($_SESSION['return_tokens'][$token])) {
        return $default;
    }
    $entry = $_SESSION['return_tokens'][$token];
    unset($_SESSION['return_tokens'][$token]);
    if(empty($entry['url']) || (isset($entry['exp']) && time() > (int)$entry['exp'])) {
        return $default;
    }
    return safeReturnUrl($entry['url'], $default);
}

/**
 * Prefer return_token (session), fall back to whitelisted return_to.
 */
function resolvePostReturnUrl($default = 'index.php') {
    if(!empty($_POST['return_token'])) {
        return consumeReturnToken($_POST['return_token'], $default);
    }
    return safeReturnUrl(isset($_POST['return_to']) ? $_POST['return_to'] : '', $default);
}

function setFlash($type, $message) {
    $_SESSION['flash'] = array(
        'type' => (string)$type,
        'message' => (string)$message,
    );
}

function getFlash() {
    if(!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function renderFlashHtml($flash = null) {
    if($flash === null) {
        $flash = getFlash();
    }
    if(!$flash || $flash['message'] === '') {
        return '';
    }
    $isError = ($flash['type'] === 'error');
    $mod = $isError ? 'app-toast--error' : 'app-toast--success';
    $role = $isError ? 'alert' : 'status';
    $attrs = $isError ? '' : ' data-autodismiss="3500"';
    $close = $isError
        ? '<button type="button" class="app-toast-close" aria-label="Hinweis schließen">&times;</button>'
        : '';
    $html = '<div class="app-toast '.$mod.'" role="'.$role.'"'.$attrs.'>'
        .'<div class="app-toast-body">'
        .htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8')
        .'</div>'
        .$close
        .'</div>';
    /* Outside .app-main so fixed toasts are not clipped by overflow/stacking. */
    if(!isset($GLOBALS['mlDeferredToasts'])) {
        $GLOBALS['mlDeferredToasts'] = '';
    }
    $GLOBALS['mlDeferredToasts'] .= $html;
    return '';
}

function redirectAfterPost($url) {
    while(ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Location: '.$url);
    exit;
}

function requireLoggedInOrRedirect() {
    if(!loggedIn()) {
        header('Location: login.php');
        exit;
    }
    if(!empty($_SESSION['singleUsePW'])) {
        header('Location: changePW.php');
        exit;
    }
}

/**
 * Run a git command in the repo root; return stdout or null on failure.
 */
function gitRepoOutput($args) {
    $root = dirname(__DIR__);
    if(!is_dir($root.'/.git')) {
        return null;
    }
    $cmd = 'git -C '.escapeshellarg($root).' '.$args.' 2>/dev/null';
    $out = array();
    $code = 1;
    exec($cmd, $out, $code);
    if($code !== 0) {
        return null;
    }
    return implode("\n", $out);
}

/**
 * Short git HEAD hash, or null if unavailable.
 */
function getGitHeadShort() {
    $head = gitRepoOutput('rev-parse --short=7 HEAD');
    if($head === null || $head === '') {
        return null;
    }
    return trim($head);
}

/**
 * Full hash of the git commit that created the current VERSION string, or null.
 */
function getGitReleaseCommitHash() {
    $version = isset($GLOBALS['version']['String']) ? (string)$GLOBALS['version']['String'] : '';
    if($version === '') {
        return null;
    }
    $hash = gitRepoOutput('log -1 --fixed-strings --grep='.escapeshellarg('release '.$version).' --pretty=%H');
    if($hash === null || $hash === '') {
        return null;
    }
    return trim(explode("\n", $hash)[0]);
}

/**
 * True when working tree HEAD is not exactly the release commit for VERSION.
 */
function isUnreleasedGitCheckout() {
    $head = gitRepoOutput('rev-parse HEAD');
    $release = getGitReleaseCommitHash();
    if($head === null || $release === null) {
        return false;
    }
    return trim($head) !== trim($release);
}

/**
 * Commit subjects since the VERSION release commit (for unreleased changelog row).
 * @return string[]
 */
function collectUnreleasedGitNotes() {
    $release = getGitReleaseCommitHash();
    if($release === null) {
        return array();
    }
    $log = gitRepoOutput('log --pretty=%s '.escapeshellarg($release.'..HEAD'));
    if($log === null || $log === '') {
        return array();
    }
    $notes = array();
    $seen = array();
    foreach(explode("\n", $log) as $subj) {
        $subj = trim($subj);
        if($subj === '' || isset($seen[$subj])) {
            continue;
        }
        if(preg_match('/^Merge /i', $subj)) {
            continue;
        }
        if(preg_match('/^release\s+/i', $subj)) {
            continue;
        }
        if(preg_match('/^Sync release/i', $subj)) {
            continue;
        }
        $seen[$subj] = true;
        $notes[] = $subj;
        if(count($notes) >= 20) {
            break;
        }
    }
    return $notes;
}

/**
 * Parse CHANGELOG.md into structured release entries.
 * @return array<int,array{version:string,date:string,notes:string[],unreleased?:bool}>
 */
function parseChangelogEntries() {
    $path = dirname(__DIR__).'/CHANGELOG.md';
    if(!is_file($path)) {
        return array();
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if($lines === false) {
        return array();
    }
    $entries = array();
    $current = null;
    foreach($lines as $line) {
        $line = rtrim($line);
        if(preg_match('/^##\s+(\S+)\s+\((\d{4}-\d{2}-\d{2})\)\s*$/', $line, $m)) {
            if($current !== null) {
                $entries[] = $current;
            }
            $current = array(
                'version' => $m[1],
                'date' => $m[2],
                'notes' => array(),
                'unreleased' => false
            );
            continue;
        }
        if($current === null) {
            continue;
        }
        if(preg_match('/^-\s+(.+)$/', $line, $m)) {
            $current['notes'][] = $m[1];
        }
    }
    if($current !== null) {
        $entries[] = $current;
    }
    return $entries;
}

/**
 * Render CHANGELOG.md as an HTML table for the Info page.
 * Prepends an unreleased row when HEAD is ahead of the VERSION release commit.
 */
function renderChangelogHtml() {
    $entries = parseChangelogEntries();
    $currentVersion = isset($GLOBALS['version']['String']) ? (string)$GLOBALS['version']['String'] : '';
    $unreleased = isUnreleasedGitCheckout();
    $headShort = $unreleased ? getGitHeadShort() : null;

    if($unreleased) {
        $notes = collectUnreleasedGitNotes();
        if(!$notes) {
            $notes = array('Noch nicht released (Commit '.($headShort ? $headShort : 'dev').')');
        }
        array_unshift($entries, array(
            'version' => $headShort ? ('unreleased-'.$headShort) : 'unreleased',
            'date' => date('Y-m-d'),
            'notes' => $notes,
            'unreleased' => true
        ));
    }

    if(!$entries) {
        return '<p class="w3-text-gray">Kein Changelog vorhanden.</p>';
    }

    $html = '<div class="help-changelog-wrap">'."\n";
    $html .= '<table class="w3-table w3-striped w3-bordered help-changelog-table">'."\n";
    $html .= '<thead><tr>'
        .'<th>Version</th>'
        .'<th>Datum</th>'
        .'<th>&Auml;nderungen</th>'
        .'</tr></thead>'."\n<tbody>\n";
    foreach($entries as $entry) {
        $notes = $entry['notes'];
        if(!$notes) {
            $notes = array('(keine weiteren Notizen)');
        }
        $isUnreleasedRow = !empty($entry['unreleased']);
        $isCurrent = $isUnreleasedRow
            ? true
            : (!$unreleased && $currentVersion !== '' && $entry['version'] === $currentVersion);
        $rowClass = '';
        if($isCurrent && $isUnreleasedRow) {
            $rowClass = ' class="help-changelog-current help-changelog-unreleased"';
        }
        elseif($isCurrent) {
            $rowClass = ' class="help-changelog-current"';
        }
        elseif($isUnreleasedRow) {
            $rowClass = ' class="help-changelog-unreleased"';
        }
        $html .= '<tr'.$rowClass.'>';
        $html .= '<td class="help-changelog-version"><code>'
            .htmlspecialchars($entry['version'], ENT_QUOTES, 'UTF-8')
            .'</code>';
        if($isCurrent && $isUnreleasedRow) {
            $html .= ' <span class="help-changelog-badge help-changelog-badge-unreleased">nicht released</span>';
        }
        elseif($isCurrent) {
            $html .= ' <span class="help-changelog-badge">aktuell</span>';
        }
        $html .= '</td>';
        $html .= '<td class="help-changelog-date">'
            .htmlspecialchars($entry['date'], ENT_QUOTES, 'UTF-8')
            .'</td>';
        $html .= '<td class="help-changelog-notes"><ul class="help-changelog-list">';
        foreach($notes as $note) {
            $html .= '<li>'.htmlspecialchars($note, ENT_QUOTES, 'UTF-8').'</li>';
        }
        $html .= '</ul></td></tr>'."\n";
    }
    $html .= "</tbody></table>\n</div>\n";
    return $html;
}

/**
 * Render a PHP view from views/ and return the HTML.
 * Convention: SQL/data in libs/, markup in views/, behaviour in js/.
 *
 * @param string $view Path relative to views/ without .php (e.g. 'user/modal')
 * @param array $vars Variables extracted into the view scope
 * @return string
 */
function render($view, $vars = array()) {
    $path = dirname(__DIR__).'/views/'.$view.'.php';
    if(!is_file($path)) {
        trigger_error('View not found: '.$view, E_USER_WARNING);
        return '';
    }
    extract($vars, EXTR_SKIP);
    ob_start();
    include $path;
    return ob_get_clean();
}
?>
