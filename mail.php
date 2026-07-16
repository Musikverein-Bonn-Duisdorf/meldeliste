<?php
session_start();
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
    die('Keine Berechtigung.');
}

MailJob::ensureSchema();

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
    $job->BodyText = isset($_POST['Text']) ? (string)$_POST['Text'] : '';
    $job->Gruss = isset($_POST['gruss']) ? (int)$_POST['gruss'] : 1;
    $job->Termin = isset($_POST['termin']) ? (int)$_POST['termin'] : (int)$job->Termin;
    if($job->Termin) {
        $job->MemberOnly = 0;
        $job->Register = 0;
    }
    else {
        $job->MemberOnly = (isset($_POST['to']) && $_POST['to'] === 'aktiv') ? 1 : 0;
        if(!isset($_POST['allReg'])) {
            $job->Register = isset($_POST['register']) ? (int)$_POST['register'] : 0;
        }
        else {
            $job->Register = 0;
        }
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
            header('Location: mail.php?queued='.(int)$job->Index.'&n='.$count);
            exit;
        }
        $msg = '<div class="w3-container '.$GLOBALS['optionsDB']['colorLogError'].'"><h3>Keine gültigen Emailadressen gefunden.</h3></div>';
        $job->load_by_id($job->Index);
    }
}

if(isset($_GET['queued'])) {
    $n = isset($_GET['n']) ? (int)$_GET['n'] : 0;
    $qid = (int)$_GET['queued'];
    $msg = '<div class="w3-container '.$GLOBALS['optionsDB']['colorLogEmail'].'"><h3>'.$n.' Nachrichten aus Email-ID '.$qid.' in die Warteschlange gestellt.</h3><p>Sofort im Nutzer-Posteingang sichtbar; Versand per PHPMailer asynchron (<code>processMailQueue</code>).</p></div>';
    $job = null;
}
if(isset($_GET['cancelled'])) {
    $cid = (int)$_GET['cancelled'];
    $msg = '<div class="w3-container '.$GLOBALS['optionsDB']['colorWarning'].'"><h3>Versand von Email-ID '.$cid.' abgebrochen.</h3><p>Bereits versendete Nachrichten bleiben erhalten; offene Empfänger wurden aus der Queue entfernt.</p></div>';
    $job = null;
}
if(isset($_GET['deleted'])) {
    $did = (int)$_GET['deleted'];
    $msg = '<div class="w3-container '.$GLOBALS['optionsDB']['colorLogEmail'].'"><h3>Email-ID '.$did.' gelöscht.</h3></div>';
    $job = null;
}

$memberonly = $job ? (bool)$job->MemberOnly : false;
$register = $job ? (int)$job->Register : 0;
$termin = $job ? (int)$job->Termin : $terminParam;
$gruss = $job ? (int)$job->Gruss : 1;
$betreff = $job ? (string)$job->Subject : '';
$textRaw = $job ? (string)$job->BodyText : '';
$textPreview = $job ? $job->applyGreeting(isset($_SESSION['Vorname']) ? $_SESSION['Vorname'] : '') : '';
$anrede = 'Hallo {VORNAME},';
$allReg = ($register === 0);

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
  <p>Emails haben eine feste ID. Anhänge liegen getrennt pro Email. Mehrere Admins können parallel an Entwürfen arbeiten.</p>
  <div class="w3-responsive">
  <table class="w3-table w3-bordered w3-striped w3-hoverable">
    <thead>
      <tr class="<?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
        <th>ID</th>
        <th>Datum</th>
        <th>Von</th>
        <th>Betreff</th>
        <th>Status</th>
        <th>Empfänger</th>
        <th>Aktion</th>
      </tr>
    </thead>
    <tbody>
<?php
if(!count($allJobs)) {
    echo '<tr><td colspan="7">Noch keine Emails vorhanden.</td></tr>';
}
$userNameCache = array();
foreach($allJobs as $rowJob) {
    $id = (int)$rowJob->Index;
    $subject = $rowJob->Subject !== '' && $rowJob->Subject !== null
        ? htmlspecialchars((string)$rowJob->Subject, ENT_QUOTES, 'UTF-8')
        : '<em>(ohne Betreff)</em>';
    $createdRaw = (string)$rowJob->Created;
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
    echo '<tr>';
    echo '<td>'.$id.'</td>';
    echo '<td>'.$created.'</td>';
    echo '<td>'.$byName.'</td>';
    echo '<td><a href="mail.php?id='.$id.'">'.$subject.'</a></td>';
    echo '<td><span class="w3-tag '.$statusCls.'">'.$status.'</span></td>';
    echo '<td>'.htmlspecialchars($counts, ENT_QUOTES, 'UTF-8').'</td>';
    echo '<td>';
    if($rowJob->Status === 'draft') {
        echo '<a class="w3-button w3-small '.$GLOBALS['optionsDB']['colorBtnEdit'].'" href="mail.php?id='.$id.'">Bearbeiten</a> ';
    }
    if($rowJob->canCancel()) {
        echo '<form method="post" action="mail.php" style="display:inline;" onsubmit="return confirm(\'Versand von Email-ID '.$id.' wirklich abbrechen?\');">';
        echo '<input type="hidden" name="id" value="'.$id.'" />';
        echo '<button type="submit" name="cancel_job" value="1" class="w3-button w3-small '.$GLOBALS['optionsDB']['colorWarning'].'">Abbrechen</button>';
        echo '</form> ';
    }
    if($rowJob->canDelete()) {
        $delConfirm = $rowJob->Status === 'draft'
            ? 'Entwurf #'.$id.' wirklich löschen?'
            : 'Email-ID '.$id.' wirklich löschen? (noch an niemanden per PHPMailer versendet)';
        echo '<form method="post" action="mail.php" style="display:inline;" onsubmit="return confirm(\''.htmlspecialchars($delConfirm, ENT_QUOTES, 'UTF-8').'\');">';
        echo '<input type="hidden" name="id" value="'.$id.'" />';
        echo '<button type="submit" name="delete_job" value="1" class="w3-button w3-small '.$GLOBALS['optionsDB']['colorBtnNo'].'">Löschen</button>';
        echo '</form> ';
    }
    echo '<a class="w3-button w3-small '.$GLOBALS['optionsDB']['colorBtnSubmit'].'" href="mail.php?copy='.$id.'">Als Entwurf kopieren</a>';
    echo '</td>';
    echo '</tr>';
}
?>
    </tbody>
  </table>
  </div>
</div>
<?php } ?>

<?php if($job && $job->Status === 'draft') { ?>
<div class="w3-panel w3-mobile w3-center w3-col s1 m1 l4"></div>
<div class="w3-panel w3-mobile w3-center w3-border w3-col s10 m10 l4">
  <p class="w3-left-align"><b>Email-ID <?php echo (int)$job->Index; ?></b> (Entwurf) — Anhänge nur für diese Email.</p>
  <form name="mailform" class="w3-container w3-margin" action="mail.php?id=<?php echo (int)$job->Index; ?>" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?php echo (int)$job->Index; ?>" />
    <label>Empfänger</label>
    <?php
         if($termin) {
             $t=new Termin;
             $t->load_by_id($termin);
    ?>
             <div class="w3-mobile w3-margin-bottom w3-padding">Alle Teilnehmer von <?php echo htmlspecialchars($t->Name." (".$t->getGermanDate().")", ENT_QUOTES, 'UTF-8'); ?></div>
    <input type="hidden" name="termin" value="<?php echo (int)$termin; ?>" />
    <?php
         }
         else {
    ?>
    <div class="w3-mobile w3-margin-bottom w3-padding w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>">
      <div class="w3-mobile">
	<input class="w3-radio w3-mobile" type="radio" name="to" value="aktiv" <?php if($memberonly) echo "checked"; ?> />
	<label>aktive Vereinsmitglieder</label>
    </div>
    <div class="w3-mobile">
	<input class="w3-radio w3-mobile" type="radio" name="to" value="all" <?php if(!$memberonly) echo "checked"; ?> />
	<label>alle Musiker</label>
      </div>
    </div>
    <label>Register</label>
    <div class="w3-mobile w3-margin-bottom w3-padding w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>">
    <input class="w3-check" type="checkbox" name="allReg" <?php if($allReg) echo "checked"; ?>>
    <label>alle Register</label>
    <select id="register" class="w3-select w3-margin-top" name="register" <?php if($allReg) echo 'style="display:none"'; ?>>
    <?php RegisterOption($register); ?>
    </select>
    </div>
<script>
    var rad = document.mailform.allReg;
    var select = document.getElementById("register");
    rad.onclick = function () {
	if(this.checked) {
	    select.style.display = 'none';
	}
	else {
	    select.style.display = 'block';
	}
    };
</script>
<?php } ?>
    <label>Betreff</label>
    <input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="Betreff" placeholder="Hier Betreff einfügen" value="<?php echo htmlspecialchars($betreff, ENT_QUOTES, 'UTF-8'); ?>"/>

    <label>Text</label>
    <input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-mobile" name="anrede" value="Hallo {VORNAME}," disabled/>

    <textarea rows="10" cols="50" class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-mobile" name="Text" placeholder="Hier Emailtext einfügen"><?php echo htmlspecialchars($textRaw, ENT_QUOTES, 'UTF-8'); ?></textarea>
    <select class="w3-select w3-margin-bottom" name="gruss">
      <option value="1" <?php if($gruss==1) echo "selected"; ?>>Viele Grüße, <?php echo htmlspecialchars($_SESSION['Vorname'], ENT_QUOTES, 'UTF-8'); ?></option>
      <option value="2" <?php if($gruss==2) echo "selected"; ?>>Viele Grüße, der Vorstand</option>
      <option value="3" <?php if($gruss==3) echo "selected"; ?>>Viele Grüße, <?php echo htmlspecialchars($GLOBALS['optionsDB']['MailGreetings'], ENT_QUOTES, 'UTF-8'); ?></option>
      <option value="4" <?php if($gruss==4) echo "selected"; ?>><?php echo htmlspecialchars($_SESSION['Vorname'], ENT_QUOTES, 'UTF-8'); ?></option>
    </select>
    <button class="w3-btn <?php echo $GLOBALS['optionsDB']['colorBtnEdit']; ?> w3-margin-bottom w3-mobile" name="save" value="1">Entwurf speichern</button>
    <button class="w3-btn <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?> w3-margin-bottom w3-mobile" name="preview" value="1">Vorschau</button>

    <?php if($preview) { ?>
                         <div class="w3-container w3-mobile w3-border w3-border-black w3-left-align w3-margin-bottom"><b>Betreff:</b> <?php echo htmlspecialchars($betreff, ENT_QUOTES, 'UTF-8'); ?></div>
                         <div class="w3-row w3-mobile w3-border w3-border-black w3-left-align"><?php echo "<div class=\"w3-container ".$GLOBALS['optionsDB']['colorTitle']." w3-mobile\"><h1>".$GLOBALS['optionsDB']['WebSiteName']."</h1></div><div class=\"w3-container\"><p>".$anrede."<br /><br />\n\n".nl2br($textPreview); ?></p></div></div>
        <button class="w3-btn <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?> w3-margin-top w3-mobile" name="send" value="1">In Warteschlange stellen</button>
        <p class="w3-small">Der Versand erfolgt asynchron per Cron (<code>processMailQueue</code>).</p>
    <?php } ?>
  </form>

  <form method="post" action="mail.php?id=<?php echo (int)$job->Index; ?>" onsubmit="return confirm('Entwurf #<?php echo (int)$job->Index; ?> wirklich löschen?');">
    <input type="hidden" name="id" value="<?php echo (int)$job->Index; ?>" />
    <button class="w3-btn <?php echo $GLOBALS['optionsDB']['colorBtnNo']; ?> w3-margin" name="delete_job" value="1">Entwurf löschen</button>
  </form>

  <form id="uploadform" name="uploadform" action="uploadfile.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="job" value="<?php echo (int)$job->Index; ?>" />
    <label>Anhang (nur Email #<?php echo (int)$job->Index; ?>)</label>
    <div class="w3-row">
	<input id="attachment" class="w3-input w3-col l6 w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" type="file" name="attachment[]" onchange="showUploadButton()"/>
	<div class="w3-col l2">&nbsp;</div>
	<button id="upload" class="w3-btn w3-col l4 <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?> w3-margin-bottom w3-mobile" name="Upload" style="display:none" type="submit" role="button">Upload</button>
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
            echo "<div class=\"w3-row\" id=\"".$hash."\"><div class=\"w3-green w3-col l6 w3-padding\">".htmlspecialchars($file)."</div><button class=\"w3-text-red fas fa-times w3-col l1 w3-padding\" onclick=\"delFile('".$hash."')\"></button><div class=\"w3-col l5 w3-padding\">&nbsp;</div></div>\n";
        }
    }
?>
</div>

</div>
<?php } elseif($job && $job->Status !== 'draft') {
    $viewSubject = htmlspecialchars((string)$job->Subject, ENT_QUOTES, 'UTF-8');
    $viewBody = nl2br((string)$job->BodyText);
    $createdRaw = (string)$job->Created;
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
    <p class="w3-small">
      Email-ID <?php echo (int)$job->Index; ?>
      · <span class="w3-tag <?php echo $job->statusClass(); ?>"><?php echo htmlspecialchars($job->statusLabel(), ENT_QUOTES, 'UTF-8'); ?></span>
      · <?php echo $createdView; ?>
      · von <?php echo $byName; ?>
      · Empfänger <?php echo (int)$job->Sent; ?>/<?php echo (int)$job->Total; ?><?php if((int)$job->Failed > 0) echo ' ('.(int)$job->Failed.' Fehler)'; ?>
    </p>
    <h3 class="w3-margin-top"><?php echo $viewSubject !== '' ? $viewSubject : '<em>(ohne Betreff)</em>'; ?></h3>
    <div class="w3-padding-16 w3-border-top"><?php echo $viewBody !== '' ? $viewBody : '<em>(kein Text)</em>'; ?></div>
    <div class="w3-padding-16">
      <a class="w3-button <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?>" href="mail.php?copy=<?php echo (int)$job->Index; ?>">Als Entwurf kopieren</a>
      <?php if($job->canCancel()) { ?>
      <form method="post" action="mail.php" style="display:inline;" onsubmit="return confirm('Versand von Email-ID <?php echo (int)$job->Index; ?> wirklich abbrechen?');">
        <input type="hidden" name="id" value="<?php echo (int)$job->Index; ?>" />
        <button type="submit" name="cancel_job" value="1" class="w3-button <?php echo $GLOBALS['optionsDB']['colorWarning']; ?>">Abbrechen</button>
      </form>
      <?php } ?>
      <?php if($job->canDelete()) { ?>
      <form method="post" action="mail.php" style="display:inline;" onsubmit="return confirm('Email-ID <?php echo (int)$job->Index; ?> wirklich löschen?');">
        <input type="hidden" name="id" value="<?php echo (int)$job->Index; ?>" />
        <button type="submit" name="delete_job" value="1" class="w3-button <?php echo $GLOBALS['optionsDB']['colorBtnNo']; ?>">Löschen</button>
      </form>
      <?php } ?>
      <a class="w3-button" href="mail.php">Zur Übersicht</a>
    </div>
  </div>

  <h4>Empfänger</h4>
  <div class="w3-responsive">
  <table class="w3-table w3-bordered w3-striped w3-hoverable">
    <thead>
      <tr class="<?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
        <th>Empfänger</th>
        <th>Email</th>
        <th>Status</th>
        <th>Versendet</th>
        <th>Fehler</th>
      </tr>
    </thead>
    <tbody>
<?php
if(!count($outboxRows)) {
    echo '<tr><td colspan="5">Keine Empfänger-Einträge.</td></tr>';
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
            : '—';
        echo '<tr>';
        echo '<td>'.$ouName.'</td>';
        echo '<td>'.htmlspecialchars((string)$or['ToEmail'], ENT_QUOTES, 'UTF-8').'</td>';
        echo '<td><span class="w3-tag '.$stCls.'">'.htmlspecialchars($stLabel, ENT_QUOTES, 'UTF-8').'</span></td>';
        echo '<td>'.$sentView.'</td>';
        echo '<td class="w3-small">'.$err.'</td>';
        echo '</tr>';
    }
}
?>
    </tbody>
  </table>
  </div>
</div>
<?php } ?>

<?php
 include "common/footer.php";
?>
