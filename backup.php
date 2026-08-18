<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page'] = 'backup';
$_SESSION['adminpage'] = true;

// Download must run before any HTML output (header.php)
if(isset($_GET['download']) && $_GET['download'] === '1') {
    include_once 'common/include.php';
    mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
    requireLoggedInOrRedirect();
    if(!requirePermission('perm_editConfig')) {
        denyAccess('Keine Berechtigung für Backup/Restore.');
    }
    require_once __DIR__.'/libs/backup.php';
    try {
        sendBackupDownload();
    }
    catch(Throwable $e) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Backup failed: '.$e->getMessage()."\n";
        exit(1);
    }
}

include_once 'common/header.php';

if(!requirePermission('perm_editConfig')) {
    denyAccess('Keine Berechtigung für Backup/Restore.');
}

require_once __DIR__.'/libs/backup.php';

$flash = null;
$restoreResult = null;

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_confirm'])) {
    if(!csrf_verify(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
        $flash = array('type' => 'error', 'message' => 'Ungültiges Sicherheits-Token.');
    }
    elseif(empty($_POST['confirm_text']) || trim((string)$_POST['confirm_text']) !== 'RESTORE') {
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
                $filesRestored = isset($restoreResult['filesRestored']) ? (int)$restoreResult['filesRestored'] : 0;
                $flash = array(
                    'type' => 'ok',
                    'message' => 'Restore erfolgreich ('.$restoreResult['statements'].' Statements'
                        .($restoreResult['repaired'] ? ', Schema repariert' : '')
                        .', '.$filesRestored.' Dateien).',
                );
                $logentry = new Log;
                $logentry->info('<b>Backup restore</b> from uploaded backup ZIP ('.$filesRestored.' files)');
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
adminListPageBegin('System', 'Backup & Restore', array('permKey' => 'perm_editConfig'));
?>
<?php if($flash) {
    $toastType = ($flash['type'] === 'ok') ? 'success' : 'error';
    echo renderFlashHtml(array('type' => $toastType, 'message' => $flash['message']));
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
    <p>ZIP mit <code>manifest.json</code>, <code>database.sql</code> (Tabellen mit DB-Prefix) und <code>uploads/</code> (Fotos, Dokumente, Verträge, Mail-Anhänge).</p>
    <p>CLI-Backup:</p>
    <pre class="w3-code w3-border w3-padding" style="white-space:pre-wrap;">php cron.php CRONID backup &gt; backup-$(date +%F).zip</pre>
    <p>Remote-HTTP (nur mit eigenem <code>$backupToken</code> in <code>config.php</code>, mind. 32 Zeichen; nicht der allgemeine Cron-<code>$cronID</code>):</p>
    <pre class="w3-code w3-border w3-padding" style="white-space:pre-wrap;">curl -fsS "https://HOST/cron.php?id=BACKUPTOKEN&amp;cmd=backup" -o backup.zip</pre>
    <p><a class="w3-button w3-border <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?>" href="backup.php?download=1"><i class="fas fa-download"></i> Backup jetzt laden</a></p>
  </div>

  <div class="w3-card w3-padding w3-margin-bottom w3-pale-red">
    <h3>Restore (destruktiv)</h3>
    <p><b>Achtung:</b> Überschreibt die Tabellen mit dem Prefix. Neue ZIPs ersetzen auch <code>uploads/</code>; ältere ZIPs ohne Dateien lassen <code>uploads/</code> unverändert. CLI: <code>php scripts/restoreBackup.php backup.zip --yes</code></p>
    <form method="post" enctype="multipart/form-data" action="backup.php" data-confirm="Wirklich Restore ausführen? Datenbank und ggf. uploads/ werden überschrieben." data-confirm-ok="Restore">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="restore_confirm" value="1">
      <label>Backup-ZIP</label>
      <input class="w3-input w3-border w3-margin-bottom" type="file" name="backup_zip" accept=".zip,application/zip" required>
      <label>Zur Bestätigung <code>RESTORE</code> eingeben</label>
      <input class="w3-input w3-border w3-margin-bottom" type="text" name="confirm_text" autocomplete="off" required>
      <button type="submit" class="w3-button w3-border w3-red">Backup einspielen</button>
    </form>
  </div>
<?php
adminListPageEnd();
include 'common/footer.php';
?>
