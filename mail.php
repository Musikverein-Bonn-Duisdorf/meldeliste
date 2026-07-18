<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page'] = 'mail';
$_SESSION['adminpage'] = true;

include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));

if(!loggedIn()) {
    header('Location: login.php');
    exit;
}
if(!empty($_SESSION['singleUsePW'])) {
    header('Location: changePW.php');
    exit;
}
if(!requirePermission('perm_sendEmail')) {
    denyAccess('Keine Berechtigung für den Email-Versand.');
}

MailJob::ensureSchema();

$discordAvailable = Discord::isConfigured();

$msg = '';
$preview = false;
$job = null;
$jobId = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$terminParam = isset($_GET['termin']) ? (int)$_GET['termin'] : (isset($_POST['termin']) ? (int)$_POST['termin'] : 0);

// Neue Email: Draft mit fester ID anlegen (vor HTML-Ausgabe!)
if(isset($_GET['new'])) {
    $created = MailJob::createDraft((int)$_SESSION['userid'], $terminParam);
    if($created && $created->Index) {
        header('Location: mail.php?id='.(int)$created->Index);
        exit;
    }
    $msg = '<div class="w3-container w3-red"><h3>Entwurf konnte nicht angelegt werden.</h3></div>';
}

// Bestehende Email als Entwurf kopieren
if(isset($_GET['copy'])) {
    $src = new MailJob;
    $src->load_by_id((int)$_GET['copy']);
    if($src->Index) {
        $copied = $src->copyAsDraft((int)$_SESSION['userid']);
        if($copied && $copied->Index) {
            header('Location: mail.php?id='.(int)$copied->Index);
            exit;
        }
    }
    $msg = '<div class="w3-container w3-red"><h3>Kopieren als Entwurf fehlgeschlagen.</h3></div>';
}

// Entwurf laden
if($jobId > 0) {
    $job = new MailJob;
    $job->load_by_id($jobId);
    if(!$job->Index) {
        $job = null;
        $msg = '<div class="w3-container w3-red"><h3>Email-ID '.$jobId.' nicht gefunden.</h3></div>';
    }
}

// Entwurf löschen
if(isset($_POST['delete_draft']) && $job && $job->Status === 'draft') {
    $job->deleteDraft();
    header('Location: mail.php');
    exit;
}

// Versand abbrechen (queued/processing)
if(isset($_POST['cancel_job'])) {
    $cancelId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $cancelJob = new MailJob;
    $cancelJob->load_by_id($cancelId);
    if($cancelJob->Index && $cancelJob->cancel()) {
        header('Location: mail.php?cancelled='.$cancelId);
        exit;
    }
    $msg = '<div class="w3-container '.$GLOBALS['optionsDB']['colorLogError'].'"><h3>Abbruch nicht möglich.</h3></div>';
}

// Löschen (Entwurf oder noch an niemanden versendet)
if(isset($_POST['delete_job'])) {
    $delId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $delJob = new MailJob;
    $delJob->load_by_id($delId);
    if($delJob->Index && $delJob->deleteCompletely()) {
        header('Location: mail.php?deleted='.$delId);
        exit;
    }
    $msg = '<div class="w3-container '.$GLOBALS['optionsDB']['colorLogError'].'"><h3>Löschen nicht möglich (bereits an Empfänger versendet).</h3></div>';
}

// Speichern / Vorschau / Senden
if($job && $job->Status === 'draft' && (isset($_POST['save']) || isset($_POST['preview']) || isset($_POST['send']))) {
    $job->Subject = isset($_POST['Betreff']) ? (string)$_POST['Betreff'] : '';
    $rawBody = isset($_POST['Text']) ? (string)$_POST['Text'] : '';
    $job->BodyText = function_exists('sanitizeMailHtml') ? sanitizeMailHtml($rawBody) : $rawBody;
    $job->Gruss = isset($_POST['gruss']) ? (int)$_POST['gruss'] : 1;
    $job->Termin = isset($_POST['termin']) ? (int)$_POST['termin'] : (int)$job->Termin;
    if($job->Termin) {
        $job->RecipientSpec = null;
        $job->PostDiscord = ($discordAvailable && isset($_POST['postDiscord'])) ? 1 : 0;
    }
    else {
        $specIn = MailJob::defaultRecipientSpecArray();
        if(isset($_POST['recipientSpec'])) {
            $decoded = json_decode((string)$_POST['recipientSpec'], true);
            if(is_array($decoded)) {
                $specIn = $decoded;
            }
        }
        $job->setRecipientSpecArray($specIn);
        $job->PostDiscord = ($discordAvailable && isset($_POST['postDiscord'])) ? 1 : 0;
    }
    $job->ensureAttachmentDir();
    $job->save();

    if(isset($_POST['preview']) || isset($_POST['send'])) {
        $preview = true;
    }
    if(isset($_POST['save'])) {
        $msg = '<div class="w3-container '.$GLOBALS['optionsDB']['colorLogEmail'].'"><h3>Entwurf #'.(int)$job->Index.' gespeichert.</h3></div>';
    }
    if(isset($_POST['send'])) {
        $mail = new Usermail;
        $mail->source = 'mail';
        $count = $mail->enqueueDraft($job, true);
        if($count > 0) {
            $job->load_by_id($job->Index);
            if((int)$job->PostDiscord) {
                $job->publishToDiscord(isset($_SESSION['Vorname']) ? $_SESSION['Vorname'] : '');
            }
            header('Location: mail.php?queued='.(int)$job->Index.'&n='.$count);
            // First batch immediately; overview must not wait for SMTP (MELD-66).
            Usermail::finishResponseThenProcessQueue();
            exit;
        }
        $msg = '<div class="w3-container '.$GLOBALS['optionsDB']['colorLogError'].'"><h3>Keine gültigen Emailadressen gefunden.</h3></div>';
        $job->load_by_id($job->Index);
    }
}

if(isset($_GET['queued'])) {
    $n = isset($_GET['n']) ? (int)$_GET['n'] : 0;
    $qid = (int)$_GET['queued'];
    $msg = '<div class="w3-container '.$GLOBALS['optionsDB']['colorLogEmail'].'"><h3>'.$n.' Nachrichten aus Email-ID '.$qid.' in die Warteschlange gestellt.</h3></div>';
    $job = null;
}
if(isset($_GET['cancelled'])) {
    $cid = (int)$_GET['cancelled'];
    $msg = '<div class="w3-container '.$GLOBALS['optionsDB']['colorWarning'].'"><h3>Versand von Email-ID '.$cid.' abgebrochen.</h3></div>';
    $job = null;
}
if(isset($_GET['deleted'])) {
    $did = (int)$_GET['deleted'];
    $msg = '<div class="w3-container '.$GLOBALS['optionsDB']['colorLogEmail'].'"><h3>Email-ID '.$did.' gelöscht.</h3></div>';
    $job = null;
}

$recipientSpec = $job ? $job->getRecipientSpecArray() : MailJob::defaultRecipientSpecArray();
// Neue / leere Entwürfe: Alle Musiker vorauswählen und im Job speichern
// (mailGroups zählen mit — sonst würden reine Gruppen-Chips überschrieben)
if(
    $job
    && $job->Status === 'draft'
    && !(int)$job->Termin
    && AudienceSpec::isEmpty($recipientSpec)
) {
    $recipientSpec = MailJob::defaultRecipientSpecArray();
    $job->setRecipientSpecArray($recipientSpec);
    $job->save();
}
$termin = $job ? (int)$job->Termin : $terminParam;
$gruss = $job ? (int)$job->Gruss : 1;
$betreff = $job ? (string)$job->Subject : '';
$textRaw = $job ? (string)$job->BodyText : '';
$textPreview = $job ? $job->applyGreeting(isset($_SESSION['Vorname']) ? $_SESSION['Vorname'] : '') : '';
$anrede = 'Hallo {VORNAME},';
$postDiscord = ($discordAvailable && $job) ? ((int)$job->PostDiscord === 1) : false;

// Catalog for chip autocomplete
MailGroup::ensureSchema();
$mailRecipientCatalog = AudienceSpec::buildCatalog(array(
    'forMail' => true,
    'includeMailGroups' => true,
));

$allJobs = MailJob::listJobs(null, 300);

include_once 'common/header.php';
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2>Email versenden</h2>
</div>
<?php echo $msg; ?>

<div class="w3-container w3-padding">
  <a class="w3-button <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?>" href="mail.php?new=1<?php echo $terminParam ? '&termin='.(int)$terminParam : ''; ?>">Neue Email</a>
  <?php if($job) { ?>
  <a class="w3-button w3-margin-left" href="mail.php">Zur Übersicht</a>
  <?php } ?>
</div>

<?php if(!$job) { ?>
<div class="w3-container w3-padding">
  <div class="mail-list">
    <div class="mail-list-header <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
      <div>Betreff</div>
      <div>Status</div>
      <div>Aktion</div>
    </div>
<?php
if(!count($allJobs)) {
    echo '<div class="mail-list-item"><div class="mail-list-primary">Noch keine Emails vorhanden.</div></div>';
}
$userNameCache = array();
$mailSendingIds = array();
foreach($allJobs as $rowJob) {
    $id = (int)$rowJob->Index;
    $subject = $rowJob->Subject !== '' && $rowJob->Subject !== null
        ? htmlspecialchars((string)$rowJob->Subject, ENT_QUOTES, 'UTF-8')
        : '<em>(ohne Betreff)</em>';
    $createdRaw = (string)$rowJob->listTimestamp();
    $created = htmlspecialchars((string)germanDate($createdRaw, true), ENT_QUOTES, 'UTF-8');
    if(strlen($createdRaw) >= 16) {
        $created .= ' '.htmlspecialchars(sql2timeRaw(substr($createdRaw, 11, 8)), ENT_QUOTES, 'UTF-8');
    }
    $byId = (int)$rowJob->CreatedBy;
    if($byId > 0) {
        if(!isset($userNameCache[$byId])) {
            $u = new User;
            $u->load_by_id($byId);
            $userNameCache[$byId] = $u->Index ? $u->getName() : ('User '.$byId);
        }
        $byName = htmlspecialchars($userNameCache[$byId], ENT_QUOTES, 'UTF-8');
    }
    else {
        $byName = 'System';
    }
    $status = htmlspecialchars($rowJob->statusLabel(), ENT_QUOTES, 'UTF-8');
    $statusCls = $rowJob->statusClass();
    $counts = '';
    if($rowJob->Status !== 'draft') {
        $counts = (int)$rowJob->Sent.'/'.(int)$rowJob->Total;
        if((int)$rowJob->Failed > 0) {
            $counts .= ' ('.(int)$rowJob->Failed.' Fehler)';
        }
    }
    else {
        $counts = '—';
    }
    $isSending = in_array((string)$rowJob->Status, array('queued', 'processing'), true);
    if($isSending) {
        $mailSendingIds[] = $id;
    }
    echo '<div class="mail-list-item" data-mail-id="'.$id.'"'.($isSending ? ' data-mail-sending="1"' : '').'>';
    echo '<div class="mail-list-primary"><a href="mail.php?id='.$id.'">'.$subject.'</a></div>';
    echo '<div class="mail-list-meta">#'.$id.' · '.$created.' · '.$byName.'</div>';
    echo '<div class="mail-list-status"><span class="w3-tag mail-status-tag '.$statusCls.'">'.$status.'</span>';
    echo ' <span class="mail-counts-cell">'.htmlspecialchars($counts, ENT_QUOTES, 'UTF-8').'</span></div>';
    echo '<div class="mail-list-actions mail-actions-cell">';
    if($rowJob->Status === 'draft') {
        echo '<a class="w3-button w3-small '.$GLOBALS['optionsDB']['colorBtnEdit'].'" href="mail.php?id='.$id.'">Bearbeiten</a>';
    }
    if($rowJob->canCancel()) {
        echo '<span class="mail-cancel-wrap">';
        echo '<form method="post" action="mail.php" onsubmit="return confirm(\'Versand von Email-ID '.$id.' wirklich abbrechen?\');">';
        echo '<input type="hidden" name="id" value="'.$id.'" />';
        echo '<button type="submit" name="cancel_job" value="1" class="w3-button w3-small '.$GLOBALS['optionsDB']['colorWarning'].'">Abbrechen</button>';
        echo '</form>';
        echo '</span>';
    }
    if($rowJob->canDelete()) {
        $delConfirm = $rowJob->Status === 'draft'
            ? 'Entwurf #'.$id.' wirklich löschen?'
            : 'Email-ID '.$id.' wirklich löschen? (noch an niemanden per PHPMailer versendet)';
        echo '<span class="mail-delete-wrap">';
        echo '<form method="post" action="mail.php" onsubmit="return confirm(\''.htmlspecialchars($delConfirm, ENT_QUOTES, 'UTF-8').'\');">';
        echo '<input type="hidden" name="id" value="'.$id.'" />';
        echo '<button type="submit" name="delete_job" value="1" class="w3-button w3-small '.$GLOBALS['optionsDB']['colorBtnNo'].'">Löschen</button>';
        echo '</form>';
        echo '</span>';
    }
    echo '<a class="w3-button w3-small '.$GLOBALS['optionsDB']['colorBtnSubmit'].'" href="mail.php?copy='.$id.'">Als Entwurf kopieren</a>';
    echo '</div>';
    echo '</div>';
}
?>
  </div>
</div>
<?php if(count($mailSendingIds)) { ?>
<script>
(function() {
  var pollIds = <?php echo json_encode(array_values($mailSendingIds)); ?>;
  if(!pollIds.length) return;

  function applyJob(job) {
    var row = document.querySelector('.mail-list-item[data-mail-id="' + job.id + '"]');
    if(!row) return;
    var tag = row.querySelector('.mail-status-tag');
    if(tag) {
      tag.className = 'w3-tag mail-status-tag ' + (job.statusClass || '');
      tag.textContent = job.statusLabel || job.status;
    }
    var counts = row.querySelector('.mail-counts-cell');
    if(counts) {
      counts.textContent = job.counts || '';
    }
    var cancelWrap = row.querySelector('.mail-cancel-wrap');
    if(cancelWrap) {
      cancelWrap.style.display = job.canCancel ? '' : 'none';
    }
    var deleteWrap = row.querySelector('.mail-delete-wrap');
    if(deleteWrap) {
      deleteWrap.style.display = job.canDelete ? '' : 'none';
    }
    if(job.sending) {
      row.setAttribute('data-mail-sending', '1');
    }
    else {
      row.removeAttribute('data-mail-sending');
      pollIds = pollIds.filter(function(id) { return id !== job.id; });
    }
  }

  function poll() {
    if(!pollIds.length) return;
    var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    xhr.onreadystatechange = function() {
      if(xhr.readyState !== 4 || xhr.status !== 200) return;
      try {
        var data = JSON.parse(xhr.responseText);
        if(!data || !data.jobs) return;
        data.jobs.forEach(applyJob);
      }
      catch(e) {}
    };
    xhr.open('GET', 'mailStatus.php?ids=' + encodeURIComponent(pollIds.join(',')), true);
    xhr.send();
  }

  setInterval(poll, 1000);
  poll();
})();
</script>
<?php } ?>
<?php } ?>

<?php if($job && $job->Status === 'draft') { ?>
<div class="w3-row">
<div class="w3-col s12 m1 l1 w3-hide-small">&nbsp;</div>
<div class="w3-panel w3-mobile w3-border w3-col s12 m10 l10 mail-compose-panel" style="text-align:left;">
  <p class="w3-left-align"><b>Entwurf</b></p>
  <form name="mailform" class="w3-container w3-margin" action="mail.php?id=<?php echo (int)$job->Index; ?>" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?php echo (int)$job->Index; ?>" />
    <label>Empfänger</label>
    <?php
         if($termin) {
             $t=new Termin;
             $t->load_by_id($termin);
    ?>
             <div class="w3-mobile w3-margin-bottom w3-padding">Alle Teilnehmer von <?php echo htmlspecialchars($t->Name." (".$t->getGermanDate().")", ENT_QUOTES, 'UTF-8'); ?>
               <span id="mailRecipientCount" class="mail-recipient-count" data-termin="<?php echo (int)$termin; ?>" aria-live="polite"></span>
             </div>
    <input type="hidden" name="termin" value="<?php echo (int)$termin; ?>" />
    <script>
    (function() {
      var el = document.getElementById('mailRecipientCount');
      if(!el) return;
      var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
      xhr.onreadystatechange = function() {
        if(xhr.readyState !== 4 || xhr.status !== 200) return;
        try {
          var data = JSON.parse(xhr.responseText);
          var n = data && typeof data.count === 'number' ? data.count : 0;
          el.textContent = n === 1 ? '1 Empfänger' : (n + ' Empfänger');
        } catch(e) {}
      };
      xhr.open('GET', 'mailRecipientCount.php?termin=' + encodeURIComponent(el.getAttribute('data-termin') || '0'), true);
      xhr.send();
    })();
    </script>
    <?php
         }
         else {
    ?>
    <div class="w3-mobile w3-margin-bottom w3-padding w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>">
      <div id="mailRecipientChips" class="mail-recipient-chips" aria-live="polite"></div>
      <input type="text" id="mailRecipientInput" class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" placeholder="Gruppe, Rolle, Register oder Person tippen…" autocomplete="off" />
      <div id="mailRecipientSuggest" class="mail-recipient-suggest" hidden></div>
      <input type="hidden" name="recipientSpec" id="mailRecipientSpec" value="<?php echo htmlspecialchars(json_encode($recipientSpec), ENT_QUOTES, 'UTF-8'); ?>" />
      <p class="w3-small w3-margin-top mail-recipient-count-line">
        <span id="mailRecipientCount" class="mail-recipient-count" aria-live="polite">…</span>
      </p>
    </div>
<script type="application/json" id="mailRecipientCatalog"><?php echo json_encode($mailRecipientCatalog, JSON_UNESCAPED_UNICODE); ?></script>
<script src="js/mailRecipients.js?<?php echo isset($GLOBALS['version']['Hash']) ? $GLOBALS['version']['Hash'] : '0'; ?>-<?php echo @filemtime(__DIR__.'/js/mailRecipients.js'); ?>"></script>
<script>
(function() {
  if(typeof MailRecipientChips !== 'undefined') {
    MailRecipientChips.init({
      catalogEl: document.getElementById('mailRecipientCatalog'),
      chipsEl: document.getElementById('mailRecipientChips'),
      inputEl: document.getElementById('mailRecipientInput'),
      suggestEl: document.getElementById('mailRecipientSuggest'),
      hiddenEl: document.getElementById('mailRecipientSpec'),
      countEl: document.getElementById('mailRecipientCount'),
      jobId: <?php echo (int)$job->Index; ?>,
      onChange: function() {
        if(typeof window.syncDiscordDefault === 'function') window.syncDiscordDefault();
      }
    });
  }
})();
</script>
<script>
(function() {
  var form = document.mailform;
  if(!form) return;
  window.syncDiscordDefault = function() {
    var cb = document.getElementById('postDiscord');
    if(!cb) return;
    var isTermin = <?php echo $termin ? 'true' : 'false'; ?>;
    if(isTermin) { cb.checked = false; return; }
    var specEl = document.getElementById('mailRecipientSpec');
    try {
      var spec = JSON.parse(specEl ? specEl.value : '{}');
      var isDefaultAll = Array.isArray(spec.groups)
        && spec.groups.length === 1
        && spec.groups[0] === 'musicians'
        && (!spec.registers || !spec.registers.length)
        && (!spec.users || !spec.users.length);
      cb.checked = !!isDefaultAll;
    } catch(e) {
      cb.checked = false;
    }
  };
  window.syncDiscordDefault();
})();
</script>
<?php } ?>
<?php if($discordAvailable && $termin) { ?>
<script>
window.syncDiscordDefault = function() {
  var cb = document.getElementById('postDiscord');
  if(cb) cb.checked = false;
};
</script>
<?php } elseif(!$termin && !$discordAvailable) { ?>
<script>
window.syncDiscordDefault = function() {};
</script>
<?php } ?>
    <label>Betreff</label>
    <input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="Betreff" placeholder="Hier Betreff einfügen" value="<?php echo htmlspecialchars($betreff, ENT_QUOTES, 'UTF-8'); ?>"/>

    <label>Text</label>
    <input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-mobile" name="anrede" value="Hallo {VORNAME}," disabled/>

    <textarea id="mail-body" rows="12" cols="50" class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-mobile" name="Text" placeholder="Hier Emailtext einfügen"><?php echo htmlspecialchars($textRaw, ENT_QUOTES, 'UTF-8'); ?></textarea>
    <select class="w3-select w3-margin-bottom" name="gruss">
      <option value="1" <?php if($gruss==1) echo "selected"; ?>>Viele Grüße, <?php echo htmlspecialchars($_SESSION['Vorname'], ENT_QUOTES, 'UTF-8'); ?></option>
      <option value="2" <?php if($gruss==2) echo "selected"; ?>>Viele Grüße, der Vorstand</option>
      <option value="3" <?php if($gruss==3) echo "selected"; ?>>Viele Grüße, <?php echo htmlspecialchars($GLOBALS['optionsDB']['MailGreetings'], ENT_QUOTES, 'UTF-8'); ?></option>
      <option value="4" <?php if($gruss==4) echo "selected"; ?>><?php echo htmlspecialchars($_SESSION['Vorname'], ENT_QUOTES, 'UTF-8'); ?></option>
    </select>
    <?php if($discordAvailable) { ?>
    <div class="w3-mobile w3-margin-bottom w3-padding w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>">
      <input class="w3-check" type="checkbox" name="postDiscord" id="postDiscord" value="1" <?php if($postDiscord) echo "checked"; ?>>
      <label for="postDiscord">Auch auf Discord posten</label>
    </div>
    <?php } ?>
    <div class="mail-compose-actions">
    <button class="w3-btn <?php echo $GLOBALS['optionsDB']['colorBtnEdit']; ?> w3-margin-bottom w3-mobile" name="save" value="1">Entwurf speichern</button>
    <button class="w3-btn <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?> w3-margin-bottom w3-mobile" name="preview" value="1">Vorschau</button>
    </div>

    <?php if($preview) { ?>
                         <div class="w3-container w3-mobile w3-border w3-border-black w3-left-align w3-margin-bottom"><b>Betreff:</b> <?php echo htmlspecialchars($betreff, ENT_QUOTES, 'UTF-8'); ?></div>
                         <div class="w3-row w3-mobile w3-border w3-border-black w3-left-align"><?php echo "<div class=\"w3-container ".$GLOBALS['optionsDB']['colorTitle']." w3-mobile\"><h1 class=\"mail-preview-title\">".$GLOBALS['optionsDB']['WebSiteName']."</h1></div><div class=\"w3-container mail-body-content\"><p>".$anrede."</p>".formatMailBodyForDisplay($textPreview)."</div>"; ?></div>
        <div class="mail-compose-actions">
        <button class="w3-btn <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?> w3-margin-top w3-mobile" name="send" value="1">In Warteschlange stellen</button>
        </div>
    <?php } ?>
  </form>

  <script src="js/tinymce/tinymce.min.js?<?php echo isset($GLOBALS['version']['Hash']) ? htmlspecialchars($GLOBALS['version']['Hash'], ENT_QUOTES, 'UTF-8') : '0'; ?>-<?php echo @filemtime(__DIR__.'/js/tinymce/tinymce.min.js'); ?>"></script>
  <script>
  (function() {
    var isNarrow = window.matchMedia && window.matchMedia('(max-width: 600px)').matches;
    tinymce.init({
    selector: '#mail-body',
    license_key: 'gpl',
    menubar: false,
    branding: false,
    promotion: false,
    height: isNarrow ? 280 : 400,
    plugins: 'lists link autolink table searchreplace code charmap nonbreaking',
    toolbar: isNarrow
      ? 'undo redo | bold italic underline | bullist numlist | link | removeformat'
      : 'undo redo | blocks | fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright | bullist numlist | outdent indent | blockquote | table | hr | link | charmap | searchreplace | code | removeformat',
    toolbar_mode: isNarrow ? 'scrolling' : 'wrap',
    block_formats: 'Absatz=p; Überschrift 2=h2; Überschrift 3=h3; Überschrift 4=h4',
    table_toolbar: 'tableprops tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol',
    table_appearance_options: false,
    table_default_attributes: { border: '1' },
    table_default_styles: { 'border-collapse': 'collapse', width: '100%' },
    font_family_formats: 'Arial=arial,helvetica,sans-serif; Georgia=georgia,serif; Times New Roman=times new roman,times,serif; Verdana=verdana,geneva,sans-serif; Courier New=courier new,courier,monospace',
    font_size_formats: '10pt 12pt 14pt 16pt 18pt 24pt 36pt',
    color_map: [
      '000000', 'Schwarz',
      '333333', 'Dunkelgrau',
      'FFFFFF', 'Weiß',
      'E53935', 'Rot',
      'FB8C00', 'Orange',
      'FDD835', 'Gelb',
      '43A047', 'Grün',
      '1E88E5', 'Blau',
      '8E24AA', 'Violett',
      '6D4C41', 'Braun'
    ],
    link_default_target: '_blank',
    link_assume_external_targets: true,
    convert_urls: false,
    relative_urls: false,
    entity_encoding: 'raw',
    content_style: 'body { font-family: Arial, Helvetica, sans-serif; font-size: 14pt; } img, table { max-width: 100%; }',
    setup: function (editor) {
      var form = document.querySelector('form[name="mailform"]') || editor.getElement().form;
      if (form) {
        form.addEventListener('submit', function () {
          editor.save();
        });
      }
    }
  });
  })();
  </script>

  <form method="post" action="mail.php?id=<?php echo (int)$job->Index; ?>" onsubmit="return confirm('Entwurf #<?php echo (int)$job->Index; ?> wirklich löschen?');">
    <input type="hidden" name="id" value="<?php echo (int)$job->Index; ?>" />
    <div class="mail-compose-actions">
    <button class="w3-btn <?php echo $GLOBALS['optionsDB']['colorBtnNo']; ?> w3-margin" name="delete_job" value="1">Entwurf löschen</button>
    </div>
  </form>

  <form id="uploadform" name="uploadform" action="uploadfile.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="job" value="<?php echo (int)$job->Index; ?>" />
    <label>Anhang</label>
    <div class="mail-attach-row">
	<input id="attachment" class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" type="file" name="attachment[]" onchange="showUploadButton()"/>
	<button id="upload" class="w3-btn <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?> w3-margin-bottom w3-mobile" name="Upload" style="display:none" type="submit" role="button">Upload</button>
    </div>
  </form>
<script>
function showUploadButton() {
    var fileinput = document.getElementById("attachment");
    var upload = document.getElementById("upload");
    if('files' in fileinput && fileinput.files.length != 0) {
	upload.style.display='block';
    }
}
const form = document.getElementById('uploadform');
form.addEventListener('submit', (event) => {
    event.preventDefault();
    var xmlhttp = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
    xmlhttp.onreadystatechange=function() {
	if (xmlhttp.readyState==4 && xmlhttp.status==200) {
	    var attdiv = document.getElementById("attachments");
	    var attline = document.createElement('div');
	    attline.innerHTML = xmlhttp.responseText;
	    while(attline.firstChild) {
	        attdiv.appendChild(attline.firstChild);
	    }
	}
    };
    xmlhttp.open("POST","uploadfile.php",true);
    xmlhttp.send(new FormData(form));
});
function delFile(hash) {
    var xmlhttp = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
    xmlhttp.onreadystatechange=function() {
	if (xmlhttp.readyState==4 && xmlhttp.status==200) {
	    var attdiv = document.getElementById(hash);
            if(attdiv) attdiv.remove();
	}
    };
    xmlhttp.open("GET","delfile.php?job=<?php echo (int)$job->Index; ?>&hash="+encodeURIComponent(hash),true);
    xmlhttp.send();
}
</script>

<div class="w3-row" id="attachments">
<?php
    $job->ensureAttachmentDir();
    $dir = (string)$job->AttachmentPath;
    if($dir && is_dir($dir)) {
        $files = scandir($dir);
        foreach($files as $file) {
            if($file == "." || $file == ".." || $file == "README") continue;
            if(!is_file($dir.'/'.$file)) continue;
            $hash = md5_file($dir.'/'.$file);
            echo "<div class=\"mail-attach-row\" id=\"".$hash."\"><div class=\"w3-green w3-padding mail-attach-name\">".htmlspecialchars($file)."</div><button type=\"button\" class=\"w3-text-red fas fa-times w3-padding\" onclick=\"delFile('".$hash."')\" aria-label=\"Anhang entfernen\"></button></div>\n";
        }
    }
?>
</div>

</div>
</div>
<?php } elseif($job && $job->Status !== 'draft') {
    $viewSubject = htmlspecialchars((string)$job->Subject, ENT_QUOTES, 'UTF-8');
    $viewBody = formatMailBodyForDisplay((string)$job->BodyText);
    $createdRaw = (string)$job->listTimestamp();
    $createdView = htmlspecialchars((string)germanDate($createdRaw, true), ENT_QUOTES, 'UTF-8');
    if(strlen($createdRaw) >= 16) {
        $createdView .= ' '.htmlspecialchars(sql2timeRaw(substr($createdRaw, 11, 8)), ENT_QUOTES, 'UTF-8');
    }
    $byId = (int)$job->CreatedBy;
    if($byId > 0) {
        $byUser = new User;
        $byUser->load_by_id($byId);
        $byName = $byUser->Index ? htmlspecialchars($byUser->getName(), ENT_QUOTES, 'UTF-8') : ('User '.$byId);
    }
    else {
        $byName = 'System';
    }
    $outboxRows = $job->listOutboxRows();
    $outboxStatusLabel = array(
        'pending' => 'Warteschlange',
        'sending' => 'Wird gesendet',
        'sent' => 'Versendet',
        'failed' => 'Fehler',
        'cancelled' => 'Abgebrochen',
    );
    $outboxStatusClass = array(
        'pending' => isset($GLOBALS['optionsDB']['colorWarning']) ? $GLOBALS['optionsDB']['colorWarning'] : 'w3-yellow',
        'sending' => isset($GLOBALS['optionsDB']['colorWarning']) ? $GLOBALS['optionsDB']['colorWarning'] : 'w3-yellow',
        'sent' => isset($GLOBALS['optionsDB']['colorLogEmail']) ? $GLOBALS['optionsDB']['colorLogEmail'] : 'w3-green',
        'failed' => isset($GLOBALS['optionsDB']['colorLogError']) ? $GLOBALS['optionsDB']['colorLogError'] : 'w3-red',
        'cancelled' => isset($GLOBALS['optionsDB']['colorBtnNo']) ? $GLOBALS['optionsDB']['colorBtnNo'] : 'w3-grey',
    );
?>
<div class="w3-container w3-padding">
  <div class="w3-card w3-padding w3-margin-bottom">
    <p class="w3-small" id="mail-detail-meta">
      Email-ID <?php echo (int)$job->Index; ?>
      · <span id="mail-detail-status" class="w3-tag <?php echo $job->statusClass(); ?>"><?php echo htmlspecialchars($job->statusLabel(), ENT_QUOTES, 'UTF-8'); ?></span>
      · <?php echo $createdView; ?>
      · von <?php echo $byName; ?>
      · Empfänger <span id="mail-detail-counts"><?php echo (int)$job->Sent; ?>/<?php echo (int)$job->Total; ?><?php if((int)$job->Failed > 0) echo ' ('.(int)$job->Failed.' Fehler)'; ?></span>
    </p>
    <h3 class="w3-margin-top"><?php echo $viewSubject !== '' ? $viewSubject : '<em>(ohne Betreff)</em>'; ?></h3>
    <div class="w3-padding-16 w3-border-top mail-body-content"><?php echo $viewBody !== '' ? $viewBody : '<em>(kein Text)</em>'; ?></div>
    <div class="w3-padding-16 mail-detail-actions">
      <a class="w3-button <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?>" href="mail.php?copy=<?php echo (int)$job->Index; ?>">Als Entwurf kopieren</a>
      <?php if($job->canCancel()) { ?>
      <form method="post" action="mail.php" onsubmit="return confirm('Versand von Email-ID <?php echo (int)$job->Index; ?> wirklich abbrechen?');">
        <input type="hidden" name="id" value="<?php echo (int)$job->Index; ?>" />
        <button type="submit" name="cancel_job" value="1" class="w3-button <?php echo $GLOBALS['optionsDB']['colorWarning']; ?>">Abbrechen</button>
      </form>
      <?php } ?>
      <?php if($job->canDelete()) { ?>
      <form method="post" action="mail.php" onsubmit="return confirm('Email-ID <?php echo (int)$job->Index; ?> wirklich löschen?');">
        <input type="hidden" name="id" value="<?php echo (int)$job->Index; ?>" />
        <button type="submit" name="delete_job" value="1" class="w3-button <?php echo $GLOBALS['optionsDB']['colorBtnNo']; ?>">Löschen</button>
      </form>
      <?php } ?>
      <a class="w3-button" href="mail.php">Zur Übersicht</a>
    </div>
  </div>

<?php
    $jobTerminId = (int)$job->Termin;
    $recipientSpecView = $job->getRecipientSpecArray();
    $verteilerHtml = '';
    if($jobTerminId > 0) {
        $tView = new Termin;
        $tView->load_by_id($jobTerminId);
        if($tView->Index) {
            $verteilerHtml = 'Alle Teilnehmer von '
                .htmlspecialchars($tView->Name.' ('.$tView->getGermanDate().')', ENT_QUOTES, 'UTF-8');
        }
        else {
            $verteilerHtml = 'Alle Teilnehmer von Termin #'.$jobTerminId;
        }
    }
    else {
        $verteilerHtml = AudienceSpec::renderChipsHtml($recipientSpecView, array(
            'allowMailGroups' => true,
            'ariaLabel' => 'Verteiler',
            'emptyHtml' => '<span class="w3-text-gray">—</span>',
        ));
    }
?>
  <h4>Verteiler</h4>
  <div class="w3-margin-bottom w3-padding-small"><?php echo $verteilerHtml; ?></div>

  <h4>Empfänger</h4>
  <div class="mail-list">
    <div class="mail-list-header <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
      <div>Empfänger</div>
      <div>Status</div>
      <div></div>
    </div>
<?php
if(!count($outboxRows)) {
    echo '<div class="mail-list-item"><div class="mail-list-primary">Keine Empfänger-Einträge.</div></div>';
}
else {
    foreach($outboxRows as $or) {
        $ou = new User;
        $ou->load_by_id((int)$or['User']);
        $ouName = $ou->Index
            ? htmlspecialchars($ou->getName(), ENT_QUOTES, 'UTF-8')
            : ('User '.(int)$or['User']);
        $st = (string)$or['Status'];
        $stLabel = isset($outboxStatusLabel[$st]) ? $outboxStatusLabel[$st] : $st;
        $stCls = isset($outboxStatusClass[$st]) ? $outboxStatusClass[$st] : 'w3-light-grey';
        $sentRaw = !empty($or['SentAt']) ? (string)$or['SentAt'] : '';
        $sentView = '—';
        if($sentRaw !== '') {
            $sentView = htmlspecialchars((string)germanDate($sentRaw, true), ENT_QUOTES, 'UTF-8');
            if(strlen($sentRaw) >= 16) {
                $sentView .= ' '.htmlspecialchars(sql2timeRaw(substr($sentRaw, 11, 8)), ENT_QUOTES, 'UTF-8');
            }
        }
        $err = !empty($or['LastError'])
            ? htmlspecialchars((string)$or['LastError'], ENT_QUOTES, 'UTF-8')
            : '';
        $email = htmlspecialchars((string)$or['ToEmail'], ENT_QUOTES, 'UTF-8');
        echo '<div class="mail-list-item">';
        echo '<div class="mail-list-primary">'.$ouName.'</div>';
        echo '<div class="mail-list-meta">'.$email;
        if($sentView !== '—') {
            echo ' · '.$sentView;
        }
        if($err !== '') {
            echo '<br><span class="w3-text-red w3-small">'.$err.'</span>';
        }
        echo '</div>';
        echo '<div class="mail-list-status"><span class="w3-tag '.$stCls.'">'.htmlspecialchars($stLabel, ENT_QUOTES, 'UTF-8').'</span></div>';
        echo '<div class="mail-list-actions"></div>';
        echo '</div>';
    }
}
?>
  </div>
</div>
<?php if(in_array((string)$job->Status, array('queued', 'processing'), true)) { ?>
<script>
(function() {
  var jobId = <?php echo (int)$job->Index; ?>;
  var timer = setInterval(function() {
    var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    xhr.onreadystatechange = function() {
      if(xhr.readyState !== 4 || xhr.status !== 200) return;
      try {
        var data = JSON.parse(xhr.responseText);
        if(!data || !data.jobs || !data.jobs.length) return;
        var job = data.jobs[0];
        var statusEl = document.getElementById('mail-detail-status');
        var countsEl = document.getElementById('mail-detail-counts');
        if(statusEl) {
          statusEl.className = 'w3-tag ' + (job.statusClass || '');
          statusEl.textContent = job.statusLabel || job.status;
        }
        if(countsEl) {
          countsEl.textContent = job.counts || '';
        }
        if(!job.sending) {
          clearInterval(timer);
          window.location.reload();
        }
      }
      catch(e) {}
    };
    xhr.open('GET', 'mailStatus.php?ids=' + jobId, true);
    xhr.send();
  }, 1000);
})();
</script>
<?php } ?>
<?php } ?>

<?php
 include "common/footer.php";
?>
