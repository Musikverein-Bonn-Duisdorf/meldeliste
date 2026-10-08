<?php
/**
 * MELD-240: Edit stored welcome-mail subject/body/gruss (same compose chrome as mail.php).
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
$gruss = isset($GLOBALS['optionsDB']['newMailGruss'])
    ? (int)$GLOBALS['optionsDB']['newMailGruss']
    : 3;
if($gruss < 0 || $gruss > 4) {
    $gruss = 3;
}

$sessionVorname = isset($_SESSION['Vorname']) ? (string)$_SESSION['Vorname'] : '';
$anrede = 'Hallo {VORNAME},';
$inputBg = (string)$GLOBALS['optionsDB']['colorInputBackground'];

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!csrf_verify(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
        setFlash('error', 'Ungültige Anfrage (CSRF).');
        header('Location: welcome-mail.php');
        exit;
    }
    $subject = isset($_POST['Betreff']) ? trim((string)$_POST['Betreff']) : '';
    $rawBody = isset($_POST['Text']) ? (string)$_POST['Text'] : '';
    $body = function_exists('sanitizeMailHtml') ? sanitizeMailHtml($rawBody) : $rawBody;
    $gruss = isset($_POST['gruss']) ? (int)$_POST['gruss'] : 3;
    if($gruss < 0 || $gruss > 4) {
        $gruss = 3;
    }
    if($subject === '') {
        $subject = 'Willkommen';
    }

    if(isset($_POST['preview'])) {
        $preview = true;
        $previewJob = new MailJob();
        $previewJob->BodyText = $body;
        $previewJob->Gruss = $gruss;
        $textPreview = $previewJob->applyGreeting($sessionVorname);
    }
    elseif(isset($_POST['save'])) {
        setConfigParamRawValue('newMailSubject', $subject);
        setConfigParamRawValue('newMailText', $body);
        setConfigParamRawValue('newMailGruss', (string)$gruss);
        $GLOBALS['optionsDB'] = loadconfig();
        $optionsDB = $GLOBALS['optionsDB'];
        setFlash('success', 'Willkommens-Mail gespeichert.');
        header('Location: welcome-mail.php');
        exit;
    }
}

include 'common/header.php';
?>
<div class="w3-row">
<div class="w3-col s12 m1 l1 w3-hide-small">&nbsp;</div>
<div class="w3-panel w3-mobile w3-border w3-col s12 m10 l10 mail-compose-panel" style="text-align:left;">
  <p class="w3-left-align"><b>Willkommens-Mail</b>
    <a class="w3-right w3-small" href="config-menu.php">Konfiguration</a>
  </p>
  <form name="welcomeMailForm" class="w3-container w3-margin" action="welcome-mail.php" method="POST">
    <?php echo csrf_field(); ?>
    <label>Betreff</label>
    <input class="w3-input w3-border <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?> w3-margin-bottom w3-mobile"
           name="Betreff" placeholder="Hier Betreff einfügen"
           value="<?php echo htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'); ?>"/>

    <label>Text</label>
    <input class="w3-input w3-border <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?> w3-mobile"
           name="anrede" value="<?php echo htmlspecialchars($anrede, ENT_QUOTES, 'UTF-8'); ?>" disabled/>

    <textarea id="mail-body" rows="12" cols="50"
              class="w3-input w3-border <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?> w3-mobile"
              name="Text" placeholder="Hier Emailtext einfügen"><?php
      echo htmlspecialchars($body, ENT_QUOTES, 'UTF-8');
    ?></textarea>
    <select class="w3-select w3-margin-bottom" name="gruss">
      <option value="0" <?php if($gruss === 0) echo 'selected'; ?>>Keine</option>
      <option value="1" <?php if($gruss === 1) echo 'selected'; ?>>Viele Grüße, <?php echo htmlspecialchars($sessionVorname, ENT_QUOTES, 'UTF-8'); ?></option>
      <option value="2" <?php if($gruss === 2) echo 'selected'; ?>>Viele Grüße, der Vorstand</option>
      <option value="3" <?php if($gruss === 3) echo 'selected'; ?>>Viele Grüße, <?php echo htmlspecialchars((string)$GLOBALS['optionsDB']['MailGreetings'], ENT_QUOTES, 'UTF-8'); ?></option>
      <option value="4" <?php if($gruss === 4) echo 'selected'; ?>><?php echo htmlspecialchars($sessionVorname, ENT_QUOTES, 'UTF-8'); ?></option>
    </select>

    <div class="mail-compose-actions">
      <button class="w3-btn <?php echo htmlspecialchars((string)$GLOBALS['optionsDB']['colorBtnEdit'], ENT_QUOTES, 'UTF-8'); ?> w3-margin-bottom w3-mobile" name="save" value="1">Speichern</button>
      <button class="w3-btn <?php echo htmlspecialchars((string)$GLOBALS['optionsDB']['colorBtnSubmit'], ENT_QUOTES, 'UTF-8'); ?> w3-margin-bottom w3-mobile" name="preview" value="1">Vorschau</button>
    </div>

<?php if($preview) { ?>
    <div class="w3-container w3-mobile w3-border w3-border-black w3-left-align w3-margin-bottom"><b>Betreff:</b> <?php echo htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'); ?></div>
    <div class="mail-compose-preview"><?php
      $previewInner = function_exists('stripMailBodyGreeting')
          ? stripMailBodyGreeting($textPreview, $sessionVorname)
          : $textPreview;
      echo class_exists('MailTemplate')
          ? MailTemplate::wrap(
              formatMailBodyForDisplay($previewInner),
              array('greeting' => $anrede)
          )
          : '<div class="mail-body-content"><p>'.$anrede.'</p>'.formatMailBodyForDisplay($textPreview).'</div>';
    ?></div>
<?php } ?>
  </form>

<?php
$tinymceSelector = '#mail-body';
include __DIR__.'/views/mail/tinymce_compose.php';
?>
</div>
<div class="w3-col s12 m1 l1 w3-hide-small">&nbsp;</div>
</div>
<?php
include 'common/footer.php';
