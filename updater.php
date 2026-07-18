<?php
ob_start();
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page'] = 'updater';
$_SESSION['adminpage'] = true;

include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
requireLoggedInOrRedirect();

if(!requirePermission('perm_editConfig')) {
    denyAccess('Keine Berechtigung für den Updater.');
}

$pullOutput = null;
$checkOutput = null;
$dbReportHtml = '';
$dbError = '';
$dbModeLabel = '';
$schemaInfo = '';
$branchName = getBranchName();

$schemaMgr = new DatabaseManager();
$schemaInfo = sprintf(
    'Schema-Version: installiert <b>%d</b>, Soll <b>%d</b>%s',
    $schemaMgr->getInstalledSchemaVersion(),
    $schemaMgr->getExpectedSchemaVersion(),
    $schemaMgr->isSchemaOutdated() ? ' — <span class="w3-text-orange"><b>Update nötig</b></span>' : ' — aktuell'
);

$gitAction = isset($_POST['git_action']) ? (string)$_POST['git_action'] : '';
if($gitAction === 'check') {
    $check = gitCheckForUpdates($branchName);
    $_SESSION['updater_check_output'] = $check;
    redirectAfterPost('updater.php');
}

if($gitAction === 'update' || isset($_POST['pull'])) {
    $pull = gitPullOrigin($branchName);
    $pullOutput = array(
        'lines' => $pull['lines'],
        'vCurrent' => $pull['vCurrent'],
        'vNew' => $pull['vNew'],
        'updated' => $pull['updated'],
        'dbRepaired' => false,
    );
    if($pullOutput['updated']) {
        $logentry = new Log;
        $logentry->info('<b>Software Update</b> from version <b>'.$pullOutput['vCurrent'].'</b> to <b>'.$pullOutput['vNew'].'</b>');
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
    $_SESSION['updater_pull_output'] = $pullOutput;
    redirectAfterPost('updater.php');
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
if(isset($_SESSION['updater_check_output']) && is_array($_SESSION['updater_check_output'])) {
    $checkOutput = $_SESSION['updater_check_output'];
    unset($_SESSION['updater_check_output']);
}

if(isset($_SESSION['db_integrity_report_html'])) {
    $dbReportHtml = (string)$_SESSION['db_integrity_report_html'];
    $dbMode = isset($_SESSION['db_integrity_mode']) ? (string)$_SESSION['db_integrity_mode'] : '';
    $dbModeLabel = ($dbMode === 'repair')
        ? ($afterPull ? 'Reparatur nach Update' : 'Reparatur')
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

<div class="w3-card-4 w3-margin">
  <div class="w3-container w3-teal"><h3>Software &amp; Datenbank</h3></div>
  <div class="w3-padding w3-yellow"><i class="fas fa-code-branch"></i>
    <?php echo 'Aktueller Branch: <b>'.htmlspecialchars($branchName, ENT_QUOTES, 'UTF-8').'</b>'; ?>
  </div>
  <div class="w3-padding w3-light-grey"><i class="fas fa-database"></i>
    <?php echo $schemaInfo; ?>
  </div>
  <div class="w3-padding">
    <p>„Auf Updates prüfen“ holt den Remote-Stand und zeigt, ob Commits verfügbar sind.
      „Update durchführen“ zieht die Änderungen und repariert die Datenbank bei veraltetem Schema automatisch.
      Datenbank prüfen/reparieren kann unabhängig davon ausgeführt werden.</p>
  </div>
  <div class="w3-padding">
    <form action="updater.php" method="post" style="display:inline;">
      <input type="hidden" name="git_action" value="check">
      <button class="w3-button w3-blue" type="submit">Auf Updates prüfen</button>
    </form>
    <form action="updater.php" method="post" style="display:inline;">
      <input type="hidden" name="git_action" value="update">
      <button class="w3-button w3-green" type="submit">Update durchführen</button>
    </form>
    <form action="updater.php" method="post" style="display:inline;">
      <input type="hidden" name="db_action" value="check">
      <button class="w3-button w3-blue" type="submit">Datenbank prüfen</button>
    </form>
    <form action="updater.php" method="post" style="display:inline;">
      <input type="hidden" name="db_action" value="repair">
      <button class="w3-button w3-orange" type="submit">Datenbank reparieren</button>
    </form>
  </div>

<?php if($checkOutput !== null) {
    $st = isset($checkOutput['status']) && is_array($checkOutput['status']) ? $checkOutput['status'] : array();
    $behind = isset($st['behind']) ? (int)$st['behind'] : 0;
    $ahead = isset($st['ahead']) ? (int)$st['ahead'] : 0;
    $localSha = isset($st['localSha']) ? (string)$st['localSha'] : '';
    $remoteSha = isset($st['remoteSha']) ? (string)$st['remoteSha'] : '';
    $checkError = isset($st['error']) ? $st['error'] : null;
    $logLines = isset($st['logLines']) && is_array($st['logLines']) ? $st['logLines'] : array();
?>
  <div class="w3-container w3-padding">
    <div class="w3-panel w3-pale-blue"><b>Ergebnis (Updates prüfen)</b></div>
<?php if($checkError) { ?>
    <div class="w3-panel w3-red"><b>Fehler:</b> <?php echo htmlspecialchars((string)$checkError, ENT_QUOTES, 'UTF-8'); ?></div>
<?php } else { ?>
    <div class="w3-padding">
      Lokal: <b><?php echo htmlspecialchars($localSha, ENT_QUOTES, 'UTF-8'); ?></b>
      &nbsp;|&nbsp;
      Remote: <b><?php echo htmlspecialchars($remoteSha, ENT_QUOTES, 'UTF-8'); ?></b>
    </div>
<?php if($behind > 0) { ?>
    <div class="w3-panel w3-yellow"><b><?php echo (int)$behind; ?> Commit<?php echo $behind === 1 ? '' : 's'; ?> verfügbar</b>
      <?php if($ahead > 0) { echo ' (lokal '.$ahead.' Commit'.($ahead === 1 ? '' : 's').' voraus)'; } ?>
    </div>
<?php if(count($logLines) > 0) { ?>
    <div class="w3-padding w3-code">
<?php foreach($logLines as $line) { ?>
      <div><?php echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8'); ?></div>
<?php } ?>
    </div>
<?php } ?>
<?php } elseif($ahead > 0) { ?>
    <div class="w3-panel w3-pale-yellow"><b>Aktuell</b> — lokal <?php echo (int)$ahead; ?> Commit<?php echo $ahead === 1 ? '' : 's'; ?> voraus</div>
<?php } else { ?>
    <div class="w3-panel w3-pale-green"><b>Aktuell</b> — keine Remote-Updates</div>
<?php } ?>
<?php } ?>
  </div>
<?php } ?>

<?php if($pullOutput !== null) {
    $pullLines = isset($pullOutput['lines']) && is_array($pullOutput['lines']) ? $pullOutput['lines'] : array();
    $pullUpdated = !empty($pullOutput['updated']);
    $alreadyUpToDate = false;
    foreach($pullLines as $line) {
        if(stripos($line, 'Already up to date') !== false || stripos($line, 'Bereits aktuell') !== false) {
            $alreadyUpToDate = true;
            break;
        }
    }
?>
  <div class="w3-container w3-padding">
    <div class="w3-panel w3-pale-blue"><b>Ergebnis (Update)</b></div>
<?php if($pullUpdated) { ?>
    <div class="w3-panel w3-yellow">aktualisiert <b><?php echo htmlspecialchars($pullOutput['vCurrent'], ENT_QUOTES, 'UTF-8'); ?></b> &rarr; <b><?php echo htmlspecialchars($pullOutput['vNew'], ENT_QUOTES, 'UTF-8'); ?></b>
      <?php if(!empty($pullOutput['dbRepaired'])) { echo ' — Datenbank repariert'; } ?>
    </div>
<?php } elseif($alreadyUpToDate) { ?>
    <div class="w3-panel w3-pale-green"><b>Bereits aktuell</b></div>
<?php } ?>
<?php if(count($pullLines) > 0) { ?>
    <div class="w3-padding w3-code">
<?php foreach($pullLines as $line) {
    $cls = '';
    if(stripos($line, 'error') !== false || stripos($line, 'fatal') !== false || stripos($line, 'conflict') !== false) {
        $cls = 'w3-pale-red';
    }
    elseif(stripos($line, 'Already up to date') !== false || stripos($line, 'Bereits aktuell') !== false) {
        $cls = 'w3-pale-green';
    }
?>
      <div class="<?php echo $cls; ?>"><?php echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8'); ?></div>
<?php } ?>
    </div>
<?php } ?>
  </div>
<?php } ?>

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
