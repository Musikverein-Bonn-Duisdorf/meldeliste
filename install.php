<?php
/**
 * First-time database setup for IONOS / empty instances.
 * Open without login while no admin users exist; otherwise requires Admin session.
 */
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();

$configFile = __DIR__.'/common/config.php';
if(!is_readable($configFile)) {
    http_response_code(500);
    echo "<!DOCTYPE html><html lang=\"de\"><head><meta charset=\"utf-8\"><title>Install</title>";
    echo "<link rel=\"stylesheet\" href=\"styles/w3.css\"></head><body class=\"w3-container\">";
    echo "<div class=\"w3-panel w3-red\"><h2>Konfiguration fehlt</h2>";
    echo "<p>Bitte <code>common/config_sample.php</code> nach <code>common/config.php</code> kopieren und die MySQL-Zugangsdaten eintragen.</p></div></body></html>";
    exit;
}

require_once $configFile;

if(!function_exists('sqlerror')) {
    function sqlerror() {
        if(!isset($GLOBALS['conn']) || !mysqli_errno($GLOBALS['conn'])) return;
        echo "<div class=\"w3-panel w3-red\"><b>SQL ERROR</b> "
            .htmlspecialchars(mysqli_errno($GLOBALS['conn']).": ".mysqli_error($GLOBALS['conn']))
            ."</div>\n";
    }
}

require_once __DIR__.'/libs/SQLtable.php';
require_once __DIR__.'/config/ConfigDefaults.php';
require_once __DIR__.'/libs/DatabaseManager.php';
require_once __DIR__.'/dbintegrity.php';

$manager = new DatabaseManager();
$hasAdmins = $manager->hasAdminUsers();
$isAdminSession = !empty($_SESSION['userid']) && !empty($_SESSION['admin']);

if($hasAdmins && !$isAdminSession) {
    header('Location: login.php');
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$reportManager = null;
$allowedActions = array('check', 'create', 'repair');

if(in_array($action, $allowedActions, true)) {
    if($action === 'check') {
        $manager->check();
    }
    elseif($action === 'create') {
        $manager->create();
    }
    else {
        $manager->repair();
    }
    $reportManager = $manager;
    $hasAdmins = $manager->hasAdminUsers();
}

$adminCreated = false;
if($reportManager) {
    foreach($reportManager->getReport() as $entry) {
        if($entry['level'] === 'user' && $entry['target'] === 'Admin' && $entry['status'] === 'created') {
            $adminCreated = true;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Meldeliste – Installation</title>
  <link rel="stylesheet" href="styles/w3.css">
  <link rel="stylesheet" href="styles/fontawesome-free-6.4.2-web/css/fontawesome.css">
  <link rel="stylesheet" href="styles/fontawesome-free-6.4.2-web/css/solid.css">
</head>
<body class="w3-light-grey">
<div class="w3-container w3-teal">
  <h1>Datenbank-Installation</h1>
</div>
<div class="w3-container w3-margin">
<?php if($hasAdmins && $isAdminSession) { ?>
  <div class="w3-panel w3-pale-yellow w3-padding">
    <p>Angemeldet als Admin. Schema prüfen oder reparieren.</p>
  </div>
<?php } elseif(!$hasAdmins) { ?>
  <div class="w3-panel w3-pale-blue w3-padding">
    <p>Neue Instanz erkannt (kein Admin vorhanden). Hier werden Tabellen, Spalten, Config-Defaults und der Default-Admin angelegt.</p>
    <p>Voraussetzung: MySQL-Datenbank angelegt und <code>common/config.php</code> korrekt befüllt.</p>
    <p>Default-Admin nach dem Anlegen: Login <b>MVD</b> / Passwort <b>MVD1949eV</b> — bitte danach ändern.</p>
  </div>
<?php } ?>

  <form method="post" class="w3-margin-bottom">
    <button class="w3-button w3-blue" type="submit" name="action" value="check">Nur prüfen</button>
<?php if(!$hasAdmins) { ?>
    <button class="w3-button w3-green" type="submit" name="action" value="create">Datenbank anlegen</button>
<?php } elseif($isAdminSession) { ?>
    <button class="w3-button w3-orange" type="submit" name="action" value="repair">Datenbank reparieren</button>
<?php } ?>
  </form>

<?php
if($reportManager) {
    echo "<div class=\"w3-card w3-white w3-padding\">";
    echo "<h3>Ergebnis</h3>";
    DBRenderReport($reportManager->getReport());
    if(($action === 'create' || $action === 'repair') && !$reportManager->hasErrors()) {
        echo "<div class=\"w3-panel w3-green\"><p><b>Schema bereit.</b>";
        if($adminCreated) {
            echo " Default-Admin angelegt: Login <b>MVD</b> / Passwort <b>MVD1949eV</b> — bitte ändern.";
        }
        echo " Weiter zum <a href=\"login.php\">Login</a>.</p></div>";
    }
    echo "</div>";
}
?>
</div>
</body>
</html>
