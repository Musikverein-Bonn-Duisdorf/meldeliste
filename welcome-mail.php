<?php
/**
 * MELD-240: Edit stored welcome-mail subject/body (TinyMCE) for User::newmail().
 */
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page'] = 'welcome-mail';
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
if(!requirePermission('perm_editConfig')) {
    denyAccess('Keine Berechtigung für die Konfiguration.');
}

$preview = false;
$subject = isset($GLOBALS['optionsDB']['newMailSubject'])
    ? (string)$GLOBALS['optionsDB']['newMailSubject']
    : 'Willkommen';
$body = isset($GLOBALS['optionsDB']['newMailText'])
    ? (string)$GLOBALS['optionsDB']['newMailText']
    : '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!csrf_verify(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
        setFlash('error', 'Ungültige Anfrage (CSRF).');
        header('Location: welcome-mail.php');
        exit;
    }
    $subject = isset($_POST['subject']) ? trim((string)$_POST['subject']) : '';
    $rawBody = isset($_POST['body']) ? (string)$_POST['body'] : '';
    $body = function_exists('sanitizeMailHtml') ? sanitizeMailHtml($rawBody) : $rawBody;
    if($subject === '') {
        $subject = 'Willkommen';
    }

    if(isset($_POST['preview'])) {
        $preview = true;
    }
    elseif(isset($_POST['save'])) {
        setConfigParamRawValue('newMailSubject', $subject);
        setConfigParamRawValue('newMailText', $body);
        $GLOBALS['optionsDB'] = loadconfig();
        $optionsDB = $GLOBALS['optionsDB'];
        setFlash('success', 'Willkommens-Mail gespeichert.');
        header('Location: welcome-mail.php');
        exit;
    }
}

include 'common/header.php';

$anrede = 'Hallo {VORNAME},';
$btnSubmit = $GLOBALS['optionsDB']['colorBtnSubmit'];
$btnEdit = $GLOBALS['optionsDB']['colorBtnEdit'];
?>
<div class="profile-shell">
  <div class="profile-hero">
    <p class="profile-kicker">Kommunikation</p>
    <h1 class="profile-title">Willkommens-Mail</h1>
    <div class="profile-actions">
      <a class="w3-btn w3-border w3-mobile" href="config-menu.php">Konfiguration</a>
      <a class="w3-btn w3-border w3-mobile" href="mail.php">Email versenden</a>
      <a class="w3-btn w3-border w3-mobile" href="help.php#admin-mail">Hilfe</a>
    </div>
  </div>

  <form class="profile-grid" name="welcomeMailForm" method="post" action="welcome-mail.php">
    <?php echo csrf_field(); ?>
    <div class="profile-col">
      <div class="profile-field">
        <label class="profile-label" for="welcome-subject">Betreff</label>
        <input class="w3-input w3-border <?php echo htmlspecialchars((string)$GLOBALS['optionsDB']['colorInputBackground'], ENT_QUOTES, 'UTF-8'); ?>"
               type="text" id="welcome-subject" name="subject"
               value="<?php echo htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'); ?>">
      </div>
      <div class="profile-field">
        <label class="profile-label" for="mail-body">Text</label>
        <textarea id="mail-body" name="body" class="w3-input w3-border"><?php
          echo htmlspecialchars($body, ENT_QUOTES, 'UTF-8');
        ?></textarea>
      </div>
      <div class="mail-compose-actions">
        <button class="w3-btn <?php echo htmlspecialchars($btnEdit, ENT_QUOTES, 'UTF-8'); ?> w3-margin-bottom w3-mobile" name="save" value="1">Speichern</button>
        <button class="w3-btn <?php echo htmlspecialchars($btnSubmit, ENT_QUOTES, 'UTF-8'); ?> w3-margin-bottom w3-mobile" name="preview" value="1">Vorschau</button>
      </div>
<?php if($preview) { ?>
      <div class="w3-container w3-mobile w3-border w3-border-black w3-left-align w3-margin-bottom"><b>Betreff:</b> <?php echo htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'); ?></div>
      <div class="mail-compose-preview"><?php
        $previewInner = formatMailBodyForDisplay($body);
        echo class_exists('MailTemplate')
            ? MailTemplate::wrap($previewInner, array('greeting' => $anrede))
            : '<div class="mail-body-content"><p>'.$anrede.'</p>'.$previewInner.'</div>';
      ?></div>
<?php } ?>
    </div>
  </form>
</div>
<?php
$tinymceSelector = '#mail-body';
include __DIR__.'/views/mail/tinymce_compose.php';
include 'common/footer.php';
