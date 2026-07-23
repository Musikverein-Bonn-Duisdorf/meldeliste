<?php
/**
 * 50 Test-User mit ausgewogener Registerverteilung anlegen/löschen.
 *
 * CLI:
 *   php seedOrchestraTestUsers.php
 *   php seedOrchestraTestUsers.php --delete
 *   php scripts/seedOrchestraTestUsers.php   (Wrapper)
 *
 * HTTP: nur mit Admin-Session (analog install.php / Backup).
 */
$isCli = (PHP_SAPI === 'cli');

if($isCli) {
    require_once __DIR__.'/common/config.php';
    mysqli_set_charset($GLOBALS['conn'], 'utf8mb4');
    mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
    $cliArgs = (isset($GLOBALS['argv']) && is_array($GLOBALS['argv'])) ? $GLOBALS['argv'] : array();
    $delete = in_array('--delete', $cliArgs, true);
    $result = $delete ? seedOrchestraTestUsersDelete() : seedOrchestraTestUsersCreate();
    seedOrchestraTestUsersPrintCli($result);
    exit(!empty($result['error']) ? 1 : 0);
}

require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page'] = 'seedOrchestraTestUsers';
$_SESSION['adminpage'] = true;

include_once __DIR__.'/common/header.php';
requireLoggedInOrRedirect();

if(!isAdmin()) {
    denyAccess('Nur Admins dürfen Test-User anlegen oder löschen.');
}

$flash = null;
$detail = null;

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!csrf_verify(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
        $flash = array('type' => 'error', 'message' => 'Ungültiges Sicherheits-Token.');
    }
    else {
        $action = isset($_POST['action']) ? (string)$_POST['action'] : '';
        if($action === 'create') {
            $detail = seedOrchestraTestUsersCreate();
        }
        elseif($action === 'delete') {
            $detail = seedOrchestraTestUsersDelete();
        }
        else {
            $flash = array('type' => 'error', 'message' => 'Unbekannte Aktion.');
        }
        if($detail !== null) {
            if(!empty($detail['error'])) {
                $flash = array('type' => 'error', 'message' => $detail['error']);
            }
            else {
                $flash = array('type' => 'ok', 'message' => $detail['summary']);
                $logentry = new Log;
                $logentry->info('<b>Orchester-Test-User</b>: '.htmlspecialchars($detail['summary'], ENT_QUOTES, 'UTF-8'));
            }
        }
    }
}
?>
<div id="header" class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2>Orchester-Test-User</h2>
</div>
<div class="w3-container w3-padding">
  <p>Legt lokal/staging 50 Test-User mit ausgewogener Registerverteilung an (Login <code>orchestertest_01</code> … <code>orchestertest_50</code>, Passwort <code>test1234</code>).</p>
<?php if($flash) {
    $toastType = ($flash['type'] === 'ok') ? 'success' : 'error';
    echo renderFlashHtml(array('type' => $toastType, 'message' => $flash['message']));
} ?>
  <form method="post" class="w3-margin-bottom" style="display:inline-block;margin-right:0.5rem;">
    <?php echo csrf_field(); ?>
    <button class="w3-button w3-green" type="submit" name="action" value="create">50 Test-User anlegen</button>
  </form>
  <form method="post" class="w3-margin-bottom" style="display:inline-block;" onsubmit="return confirm('Alle OrchesterTest-User soft-löschen?');">
    <?php echo csrf_field(); ?>
    <button class="w3-button w3-orange" type="submit" name="action" value="delete">Test-User löschen</button>
  </form>
<?php if($detail && empty($detail['error']) && !empty($detail['lines'])) { ?>
  <pre class="w3-code w3-padding w3-light-grey"><?php echo htmlspecialchars(implode("\n", $detail['lines']), ENT_QUOTES, 'UTF-8'); ?></pre>
<?php } ?>
</div>
<?php
include_once __DIR__.'/common/footer.php';

/**
 * Soft-delete seeded test users.
 * @return array{summary:string,lines:string[],error?:string,affected?:int}
 */
function seedOrchestraTestUsersDelete() {
    $loginPrefix = 'orchestertest_';
    $nachnameTag = 'OrchesterTest';
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
        return array(
            'summary' => '',
            'lines' => array(),
            'error' => mysqli_error($GLOBALS['conn']),
        );
    }
    $n = mysqli_affected_rows($GLOBALS['conn']);
    return array(
        'summary' => 'Soft-gelöscht: '.$n.' Test-User',
        'lines' => array('Soft-gelöscht: '.$n.' Test-User'),
        'affected' => $n,
    );
}

/**
 * Create up to 50 orchestra test users (idempotent by login).
 * @return array{summary:string,lines:string[],error?:string,created?:int,skipped?:int}
 */
function seedOrchestraTestUsersCreate() {
    $loginPrefix = 'orchestertest_';
    $nachnameTag = 'OrchesterTest';

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
        return array(
            'summary' => '',
            'lines' => array(),
            'error' => "Interne Zielsumme ist $sum, erwartet 50.",
        );
    }

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
        return array(
            'summary' => '',
            'lines' => array(),
            'error' => mysqli_error($GLOBALS['conn']),
        );
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
            return array(
                'summary' => '',
                'lines' => array(),
                'error' => "Kein spielbares Instrument für Register $rid",
            );
        }
    }

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
                return array(
                    'summary' => '',
                    'lines' => array(),
                    'error' => "Fehler bei $login: ".mysqli_error($GLOBALS['conn']),
                );
            }
            $created++;
            if(!isset($distribution[$rid])) {
                $distribution[$rid] = 0;
            }
            $distribution[$rid]++;
        }
    }

    $lines = array(
        "Angelegt: $created, übersprungen (schon vorhanden): $skipped",
        'Verteilung (Register-ID => neu):',
    );
    foreach($distribution as $rid => $c) {
        $lines[] = "  Register $rid: $c";
    }
    $lines[] = "Login-Muster: {$loginPrefix}01 … {$loginPrefix}50, Passwort: test1234";

    return array(
        'summary' => "Angelegt: $created, übersprungen: $skipped",
        'lines' => $lines,
        'created' => $created,
        'skipped' => $skipped,
    );
}

/**
 * @param array $result
 */
function seedOrchestraTestUsersPrintCli(array $result) {
    if(!empty($result['error'])) {
        fwrite(STDERR, $result['error']."\n");
        return;
    }
    foreach($result['lines'] as $line) {
        echo $line."\n";
    }
    if(isset($result['created'])) {
        echo "Löschen: php seedOrchestraTestUsers.php --delete\n";
    }
}
