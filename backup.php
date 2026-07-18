<?php
session_start();
$_SESSION['page'] = 'backup';
$_SESSION['adminpage'] = true;

include_once 'common/header.php';

if(!requirePermission('perm_editConfig')) {
    denyAccess('Keine Berechtigung für Backup/Restore.');
}

require_once __DIR__.'/libs/backup.php';

$flash = null;
$restoreResult = null;

if(isset($_GET['download']) && $_GET['download'] === '1') {
    try {
        sendBackupDownload();
    }
    catch(Throwable $e) {
        $flash = array('type' => 'error', 'message' => 'Download fehlgeschlagen: '.$e->getMessage());
    }
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_confirm'])) {
    if(empty($_POST['confirm_text']) || trim((string)$_POST['confirm_text']) !== 'RESTORE') {
        $flash = array('type' => 'error', 'message' => 'Bitte zur Bestätigung RESTORE eintippen.');
    }
    elseif(empty($_FILES['backup_zip']) || !is_uploaded_file($_FILES['backup_zip']['tmp_name'])) {
        $flash = array('type' => 'error', 'message' => 'Keine ZIP-Datei hochgeladen.');
    }
    else {
        try {
            $restoreResult = restoreBackupZip($_FILES['backup_zip']['tmp_name'], true);
            if(!empty($restoreResult['errors'])) {
                $flash = array(
                    'type' => 'error',
                    'message' => 'Restore mit Fehlern: '.implode('; ', $restoreResult['errors']),
                );
            }
            else {
                $flash = array(
                    'type' => 'ok',
                    'message' => 'Restore erfolgreich ('.$restoreResult['statements'].' Statements'
                        .($restoreResult['repaired'] ? ', Schema repariert' : '')
                        .').',
                );
                $logentry = new Log;
                $logentry->info('<b>Database restore</b> from uploaded backup ZIP');
            }
        }
        catch(Throwable $e) {
            $flash = array('type' => 'error', 'message' => 'Restore fehlgeschlagen: '.$e->getMessage());
        }
    }
}

$manifest = buildBackupManifest();
$ver = $manifest['version'];
$schema = $manifest['schemaVersion'];
?>
<div id="header" class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2>Backup &amp; Restore</h2>
</div>

<div class="w3-container w3-margin-top">
<?php if($flash) {
    $panel = ($flash['type'] === 'ok') ? $GLOBALS['optionsDB']['colorSuccess'] : $GLOBALS['optionsDB']['colorLogError'];
    echo '<div class="w3-panel '.$panel.'"><p>'.htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8').'</p></div>';
} ?>

  <div class="w3-card w3-padding w3-margin-bottom">
    <h3>Aktueller Stand</h3>
    <p>
      Software-Version: <b><?php echo htmlspecialchars($ver['String'], ENT_QUOTES, 'UTF-8'); ?></b><br>
      Schema: installiert <b><?php echo (int)$schema['installed']; ?></b>, Soll <b><?php echo (int)$schema['expected']; ?></b><br>
      DB-Prefix: <code><?php echo htmlspecialchars($manifest['dbprefix'], ENT_QUOTES, 'UTF-8'); ?></code>
    </p>
  </div>

  <div class="w3-card w3-padding w3-margin-bottom">
    <h3>Backup herunterladen</h3>
    <p>ZIP mit <code>manifest.json</code> (Versionsinfo) und <code>database.sql</code> (alle Tabellen mit DB-Prefix).</p>
    <p>Remote-Cron-Beispiel:</p>
    <pre class="w3-code w3-border w3-padding" style="white-space:pre-wrap;">curl -fsS "https://HOST/cron.php?id=CRONID&amp;cmd=backup" -o "backup-$(date +%F).zip"</pre>
    <p><a class="w3-button w3-border <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?>" href="backup.php?download=1"><i class="fas fa-download"></i> Backup jetzt laden</a></p>
  </div>

  <div class="w3-card w3-padding w3-margin-bottom w3-pale-red">
    <h3>Restore (destruktiv)</h3>
    <p><b>Achtung:</b> Überschreibt die Datenbanktabellen mit dem Prefix. Vorher ein aktuelles Backup ziehen.</p>
    <form method="post" enctype="multipart/form-data" action="backup.php" onsubmit="return confirm('Wirklich Restore ausführen? Die aktuelle Datenbank wird überschrieben.');">
      <input type="hidden" name="restore_confirm" value="1">
      <label>Backup-ZIP</label>
      <input class="w3-input w3-border w3-margin-bottom" type="file" name="backup_zip" accept=".zip,application/zip" required>
      <label>Zur Bestätigung <code>RESTORE</code> eingeben</label>
      <input class="w3-input w3-border w3-margin-bottom" type="text" name="confirm_text" autocomplete="off" required>
      <button type="submit" class="w3-button w3-border w3-red">Backup einspielen</button>
    </form>
  </div>
</div>

<?php
include 'common/footer.php';
?>
