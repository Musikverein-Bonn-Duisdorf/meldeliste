<?php
ob_start();
session_start();
$_SESSION['page'] = 'updater';
$_SESSION['adminpage'] = true;

include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
requireLoggedInOrRedirect();

if(!requirePermission('perm_editConfig')) {
    die();
}
if(empty($_SESSION['admin'])) {
    die('<div class="w3-panel w3-red w3-padding"><b>Admin-Zugang erforderlich.</b></div>');
}

$pullOutput = null;
$dbReportHtml = '';
$dbError = '';
$dbModeLabel = '';
$schemaInfo = '';

$schemaMgr = new DatabaseManager();
$schemaInfo = sprintf(
    'Schema-Version: installiert <b>%d</b>, Soll <b>%d</b>%s',
    $schemaMgr->getInstalledSchemaVersion(),
    $schemaMgr->getExpectedSchemaVersion(),
    $schemaMgr->isSchemaOutdated() ? ' — <span class="w3-text-orange"><b>Update nötig</b></span>' : ' — aktuell'
);

if(isset($_POST['pull'])) {
    $vCurrent = trim((string)shell_exec('git rev-parse --short HEAD 2>&1'));
    $pullLines = explode("\n", (string)shell_exec('git pull origin '.getBranchName().' 2>&1'));
    $vNew = trim((string)shell_exec('git rev-parse --short HEAD 2>&1'));
    $pullOutput = array(
        'lines' => $pullLines,
        'vCurrent' => $vCurrent,
        'vNew' => $vNew,
        'updated' => ($vCurrent !== $vNew),
        'dbRepaired' => false,
    );
    if($pullOutput['updated']) {
        $logentry = new Log;
        $logentry->info('<b>Software Update</b> from version <b>'.$vCurrent.'</b> to <b>'.$vNew.'</b>');
    }

    // After pull: resolve DB schema if outdated (re-read version file from disk)
    $postPullMgr = new DatabaseManager();
    if($postPullMgr->isSchemaOutdated(true)) {
        require_once __DIR__.'/dbintegrity.php';
        try {
            ob_start();
            DBCheckIntegrity('repair');
            $reportHtml = ob_get_clean();
            $_SESSION['db_integrity_report_html'] = $reportHtml;
            $_SESSION['db_integrity_mode'] = 'repair';
            $_SESSION['db_integrity_after_pull'] = 1;
            $pullOutput['dbRepaired'] = true;
            $_SESSION['updater_pull_output'] = $pullOutput;
        }
        catch(Throwable $e) {
            $_SESSION['db_integrity_error'] = $e->getMessage();
            $_SESSION['db_integrity_after_pull'] = 1;
            $_SESSION['updater_pull_output'] = $pullOutput;
        }
        redirectAfterPost('updater.php');
    }
}

$dbAction = isset($_POST['db_action']) ? (string)$_POST['db_action'] : '';
if($dbAction === 'check' || $dbAction === 'repair') {
    require_once __DIR__.'/dbintegrity.php';
    try {
        $mode = ($dbAction === 'repair') ? 'repair' : 'check';
        ob_start();
        DBCheckIntegrity($mode);
        $reportHtml = ob_get_clean();
        $_SESSION['db_integrity_report_html'] = $reportHtml;
        $_SESSION['db_integrity_mode'] = $mode;
        redirectAfterPost('updater.php');
    }
    catch(Throwable $e) {
        $_SESSION['db_integrity_error'] = $e->getMessage();
        redirectAfterPost('updater.php');
    }
}

$afterPull = !empty($_SESSION['db_integrity_after_pull']);
if(isset($_SESSION['db_integrity_after_pull'])) {
    unset($_SESSION['db_integrity_after_pull']);
}
if(isset($_SESSION['updater_pull_output']) && is_array($_SESSION['updater_pull_output'])) {
    $pullOutput = $_SESSION['updater_pull_output'];
    unset($_SESSION['updater_pull_output']);
}

if(isset($_SESSION['db_integrity_report_html'])) {
    $dbReportHtml = (string)$_SESSION['db_integrity_report_html'];
    $dbMode = isset($_SESSION['db_integrity_mode']) ? (string)$_SESSION['db_integrity_mode'] : '';
    $dbModeLabel = ($dbMode === 'repair')
        ? ($afterPull ? 'Reparatur nach Pull' : 'Reparatur')
        : 'Prüfung';
    unset($_SESSION['db_integrity_report_html'], $_SESSION['db_integrity_mode']);
}
if(isset($_SESSION['db_integrity_error'])) {
    $dbError = (string)$_SESSION['db_integrity_error'];
    unset($_SESSION['db_integrity_error']);
}

// Refresh schema info after possible redirect
$schemaMgr = new DatabaseManager();
$schemaInfo = sprintf(
    'Schema-Version: installiert <b>%d</b>, Soll <b>%d</b>%s',
    $schemaMgr->getInstalledSchemaVersion(),
    $schemaMgr->getExpectedSchemaVersion(),
    $schemaMgr->isSchemaOutdated() ? ' — <span class="w3-text-orange"><b>Update nötig</b></span>' : ' — aktuell'
);

include 'common/header.php';
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2>Updater</h2>
</div>
<div class="w3-container w3-card w3-margin w3-padding <?php echo $GLOBALS['optionsDB']['colorWarning']; ?>">
  <div class="w3-col l3 m3 s2 w3-center">
    <i class="fas fa-exclamation-triangle"></i>
  </div>
  <div class="w3-col l6 m6 s8 w3-center">
    <b>Nur nutzen, wenn man weiß, was man tut!</b>
  </div>
  <div class="w3-col l3 m3 s2 w3-center">
    <i class="fas fa-exclamation-triangle"></i>
  </div>
</div>
<div class="w3-yellow w3-padding"><i class="fas fa-code-branch"></i>
  <?php echo 'Aktueller Branch: <b>'.htmlspecialchars(getBranchName(), ENT_QUOTES, 'UTF-8').'</b>'; ?>
</div>
<div class="w3-padding w3-light-grey"><i class="fas fa-database"></i>
  <?php echo $schemaInfo; ?>
</div>
<?php if($pullOutput !== null) { ?>
<div class="w3-card-4 w3-margin">
  <div class="w3-container w3-teal"><h3>git pull</h3></div>
  <div class="w3-padding w3-code">
  <?php
    foreach($pullOutput['lines'] as $line) {
        echo '<div>'.htmlspecialchars($line, ENT_QUOTES, 'UTF-8').'</div>';
    }
  ?>
  </div>
<?php if($pullOutput['updated']) { ?>
  <div class="w3-container w3-yellow w3-padding">updated <b><?php echo htmlspecialchars($pullOutput['vCurrent'], ENT_QUOTES, 'UTF-8'); ?></b> -&gt; <b><?php echo htmlspecialchars($pullOutput['vNew'], ENT_QUOTES, 'UTF-8'); ?></b></div>
<?php } ?>
</div>
<?php } ?>

<div class="w3-card-4 w3-margin">
  <div class="w3-container w3-teal"><h3>git status</h3></div>
  <div class="w3-padding w3-code">
    <?php
      $status = explode("\n", (string)shell_exec('git remote -v update origin 2>&1'));
      foreach($status as $line) {
          $found = strpos($line, 'origin/'.getBranchName());
          if($found !== false) {
              echo '<div class="w3-yellow"><b>'.htmlspecialchars($line, ENT_QUOTES, 'UTF-8').'</b></div>';
          }
          else {
              echo '<div>'.htmlspecialchars($line, ENT_QUOTES, 'UTF-8').'</div>';
          }
      }
    ?>
  </div>
</div>

<div class="w3-card-4 w3-margin">
  <div class="w3-container w3-teal"><h3>git pull</h3></div>
  <div class="w3-padding">
    <p>Nach dem Pull wird die Datenbank automatisch repariert, falls die Schema-Version veraltet ist.</p>
  </div>
  <form action="updater.php" method="post" class="w3-padding">
    <button class="w3-button w3-blue" type="submit" name="pull" value="1">pull</button>
  </form>
</div>

<div class="w3-card-4 w3-margin">
  <div class="w3-container w3-teal"><h3>Datenbank Integrität</h3></div>
  <div class="w3-padding">
    <p>Prüfen meldet Abweichungen ohne Änderungen. Reparieren legt fehlende Tabellen/Spalten an und gleicht abweichende Spalten-Definitionen an. Bei Erfolg wird die Schema-Version aktualisiert.</p>
  </div>
  <div class="w3-padding">
    <form action="updater.php" method="post" style="display:inline;">
      <input type="hidden" name="db_action" value="check">
      <button class="w3-button w3-blue" type="submit">Datenbank prüfen</button>
    </form>
    <form action="updater.php" method="post" style="display:inline;">
      <input type="hidden" name="db_action" value="repair">
      <button class="w3-button w3-orange" type="submit">Datenbank reparieren</button>
    </form>
  </div>
  <div class="w3-container w3-padding">
<?php if($dbError !== '') { ?>
    <div class="w3-panel w3-red"><b>Fehler:</b> <?php echo htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8'); ?></div>
<?php } ?>
<?php if($dbReportHtml !== '') { ?>
    <div class="w3-panel w3-pale-yellow"><b>Ergebnis (<?php echo htmlspecialchars($dbModeLabel, ENT_QUOTES, 'UTF-8'); ?>)</b></div>
    <?php echo $dbReportHtml; ?>
<?php } ?>
  </div>
</div>

<?php
include 'common/footer.php';
?>
