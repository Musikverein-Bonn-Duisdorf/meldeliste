<?php
/**
 * Lokal: 50 Test-User mit ausgewogener Registerverteilung anlegen.
 *
 * Usage:
 *   php scripts/seedOrchestraTestUsers.php
 *   php scripts/seedOrchestraTestUsers.php --delete   # nur diese Test-User soft-löschen
 */
if(php_sapi_name() !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__);
require_once $root.'/common/config.php';
mysqli_set_charset($GLOBALS['conn'], 'utf8mb4');
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));

$prefix = 'orchestertest';
$loginPrefix = $prefix.'_';
$nachnameTag = 'OrchesterTest';

$delete = in_array('--delete', $argv, true);

if($delete) {
    $sqlDel = sprintf(
        'UPDATE `%sUser` SET `Deleted` = 1, `DeletedOn` = CURRENT_TIMESTAMP,
         `Vorname` = "gelöschter", `Nachname` = "Benutzer", `Email` = "", `Email2` = "",
         `login` = "", `Passhash` = "", `getMail` = 0
         WHERE `login` LIKE "%s%%" OR `Nachname` = "%s";',
        $GLOBALS['dbprefix'],
        mysqli_real_escape_string($GLOBALS['conn'], $loginPrefix),
        mysqli_real_escape_string($GLOBALS['conn'], $nachnameTag)
    );
    if(!mysqli_query($GLOBALS['conn'], $sqlDel)) {
        fwrite(STDERR, mysqli_error($GLOBALS['conn'])."\n");
        exit(1);
    }
    echo 'Soft-gelöscht: '.mysqli_affected_rows($GLOBALS['conn'])." Test-User\n";
    exit(0);
}

// Zielanzahl je Register (Summe = 50), grob realistisches Blasorchester
$targetsByRegister = array(
    10 => 1,  // Dirigent
    1  => 6,  // Flöten
    2  => 7,  // Klarinetten
    3  => 3,  // Oboen / Fagott
    4  => 3,  // Alt-Sax
    13 => 2,  // Tenor-Sax
    14 => 1,  // Bariton-Sax
    5  => 7,  // Trompeten
    12 => 4,  // Bügelhörner
    6  => 3,  // Waldhörner
    7  => 5,  // Posaunen
    8  => 4,  // Bass
    9  => 4,  // Schlagwerk
);

$sum = array_sum($targetsByRegister);
if($sum !== 50) {
    fwrite(STDERR, "Interne Zielsumme ist $sum, erwartet 50.\n");
    exit(1);
}

// Spielbare Instrumente je Register laden
$instrumentsByRegister = array();
$q = sprintf(
    'SELECT i.`Index`, i.`Name`, i.`Register`, i.`Sortierung`
     FROM `%sInstrument` i
     INNER JOIN `%sRegister` r ON r.`Index` = i.`Register`
     WHERE i.`Spielbar` = 1 AND r.`Name` != "keins"
     ORDER BY r.`Sortierung`, i.`Sortierung`;',
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix']
);
$dbr = mysqli_query($GLOBALS['conn'], $q);
if(!$dbr) {
    fwrite(STDERR, mysqli_error($GLOBALS['conn'])."\n");
    exit(1);
}
while($row = mysqli_fetch_assoc($dbr)) {
    $rid = (int)$row['Register'];
    if(!isset($instrumentsByRegister[$rid])) {
        $instrumentsByRegister[$rid] = array();
    }
    $instrumentsByRegister[$rid][] = (int)$row['Index'];
}

foreach($targetsByRegister as $rid => $n) {
    if(empty($instrumentsByRegister[$rid])) {
        fwrite(STDERR, "Kein spielbares Instrument für Register $rid\n");
        exit(1);
    }
}

// Bestehende Test-Logins überspringen / idempotent neu nur fehlende
$existing = array();
$eq = sprintf(
    'SELECT `login` FROM `%sUser` WHERE `login` LIKE "%s%%" AND `Deleted` = 0;',
    $GLOBALS['dbprefix'],
    mysqli_real_escape_string($GLOBALS['conn'], $loginPrefix)
);
$er = mysqli_query($GLOBALS['conn'], $eq);
while($row = mysqli_fetch_assoc($er)) {
    $existing[$row['login']] = true;
}

$vornamen = array(
    'Anna', 'Ben', 'Clara', 'David', 'Emma', 'Felix', 'Greta', 'Hans', 'Ida', 'Jonas',
    'Klara', 'Leo', 'Mia', 'Noah', 'Olivia', 'Paul', 'Quinn', 'Rosa', 'Sam', 'Tina',
    'Uwe', 'Vera', 'Willi', 'Xenia', 'Yuki', 'Zoe', 'Anton', 'Berta', 'Carl', 'Dora',
    'Emil', 'Frieda', 'Georg', 'Hilda', 'Ingo', 'Jana', 'Kurt', 'Lina', 'Max', 'Nina',
    'Otto', 'Petra', 'Quentin', 'Rita', 'Stefan', 'Thea', 'Ulrich', 'Vera', 'Walter', 'Yvonne',
);

$passhash = password_hash('test1234', PASSWORD_DEFAULT);
$created = 0;
$skipped = 0;
$n = 0;
$distribution = array();

foreach($targetsByRegister as $rid => $count) {
    $instList = $instrumentsByRegister[$rid];
    for($i = 0; $i < $count; $i++) {
        $n++;
        $login = sprintf('%s%02d', $loginPrefix, $n);
        if(isset($existing[$login])) {
            $skipped++;
            continue;
        }
        $instrument = $instList[$i % count($instList)];
        $vorname = $vornamen[($n - 1) % count($vornamen)];
        $activeLink = uniqid('ot', true);

        $sqlIns = sprintf(
            'INSERT INTO `%sUser`
             (`Nachname`, `Vorname`, `RefID`, `login`, `Passhash`, `activeLink`, `Mitglied`, `Instrument`,
              `Email`, `Email2`, `Birthday`, `getMail`, `Admin`, `RegisterLead`, `Deleted`)
             VALUES ("%s", "%s", NULL, "%s", "%s", "%s", 1, %d, "%s", "", NULL, 0, 0, 0, 0);',
            $GLOBALS['dbprefix'],
            mysqli_real_escape_string($GLOBALS['conn'], $nachnameTag),
            mysqli_real_escape_string($GLOBALS['conn'], $vorname),
            mysqli_real_escape_string($GLOBALS['conn'], $login),
            mysqli_real_escape_string($GLOBALS['conn'], $passhash),
            mysqli_real_escape_string($GLOBALS['conn'], $activeLink),
            (int)$instrument,
            mysqli_real_escape_string($GLOBALS['conn'], $login.'@example.invalid')
        );
        if(!mysqli_query($GLOBALS['conn'], $sqlIns)) {
            fwrite(STDERR, "Fehler bei $login: ".mysqli_error($GLOBALS['conn'])."\n");
            exit(1);
        }
        $created++;
        if(!isset($distribution[$rid])) {
            $distribution[$rid] = 0;
        }
        $distribution[$rid]++;
    }
}

echo "Angelegt: $created, übersprungen (schon vorhanden): $skipped\n";
echo "Verteilung (Register-ID => neu):\n";
foreach($distribution as $rid => $c) {
    echo "  Register $rid: $c\n";
}
echo "Login-Muster: {$loginPrefix}01 … {$loginPrefix}50, Passwort: test1234\n";
echo "Löschen: php scripts/seedOrchestraTestUsers.php --delete\n";
