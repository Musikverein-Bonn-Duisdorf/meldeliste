<?php
ob_start();
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();

include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
requireLoggedInOrRedirect();

if(isset($_POST['id']) && isset($_SESSION['userid']) && (int)$_SESSION['userid'] === (int)$_POST['id']) {
    $_SESSION['page'] = 'me';
    $_SESSION['adminpage'] = false;
}
elseif(isset($_GET['id']) && isset($_SESSION['userid']) && (int)$_GET['id'] === (int)$_SESSION['userid']
    && isset($_GET['mode']) && $_GET['mode'] === 'useredit') {
    $_SESSION['page'] = 'me';
    $_SESSION['adminpage'] = false;
}
else {
    $_SESSION['page'] = 'newmusiker';
    $_SESSION['adminpage'] = true;
}

$canEditUsers = requirePermission('perm_editUsers');
$userid = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;

$isSelfProfileEdit = (isset($_POST['mode']) && $_POST['mode'] === 'useredit')
    || (isset($_GET['mode']) && $_GET['mode'] === 'useredit');

include_once 'libs/form-response.php';

if(isset($_POST['insert']) && ($isSelfProfileEdit || !$canEditUsers)) {
    $profileResult = handleSelfProfilePost($userid);
    if($profileResult['flash']) {
        setFlash($profileResult['flash']['type'], $profileResult['flash']['message']);
    }
    elseif($profileResult['successMessage']) {
        setFlash('success', $profileResult['successMessage']);
    }
    redirectAfterPost('new-musiker.php?id='.$userid.'&mode=useredit');
}

if($canEditUsers && isUserFormPost() && !$isSelfProfileEdit) {
    applyNewMusikerFormPostRedirect('musiker.php');
}

include 'common/header.php';

$returnTo = safeReturnUrl(
    isset($_GET['return_to']) ? $_GET['return_to'] : (isset($_POST['return_to']) ? $_POST['return_to'] : ''),
    'musiker.php'
);

$fill = false;
$n = new User;
if(isset($_GET['id']) && (int)$_GET['id'] > 0) {
    if(isset($_GET['mode']) && $_GET['mode'] === 'useredit' && (int)$_GET['id'] !== $userid) {
        die('<div class="w3-panel w3-red w3-padding"><b>Kein Zugriff auf dieses Profil.</b></div>');
    }
    $n->load_by_id((int)$_GET['id']);
    if($n->Index > 0) {
        $fill = true;
    }
}
elseif(isset($_POST['id'])) {
    $n->load_by_id((int)$_POST['id']);
    if($n->Index > 0) {
        $fill = true;
    }
}
elseif(isset($_POST['Index']) && $canEditUsers) {
    $n->load_by_id((int)$_POST['Index']);
    if($n->Index > 0) {
        $fill = true;
    }
}

$edit = 1;
if($isSelfProfileEdit) {
    $edit = 2;
}
elseif($canEditUsers) {
    $edit = 3;
}

if($edit == 2 && $fill && (int)$n->Index !== $userid) {
    $logentry = new Log;
    $logentry->error(sprintf(
        'Fremdes Profil geöffnet verweigert | Session: <b>%d</b>, Ziel: <b>%d</b>',
        $userid,
        (int)$n->Index
    ));
    die('<div class="w3-panel w3-red w3-padding"><b>Kein Zugriff auf dieses Profil.</b></div>');
}

$disabled = ($edit != 3) ? 'disabled' : '';
$formAction = '';
?>
<div class="w3-container w3-margin-bottom <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2>Profil bearbeiten</h2>
</div>
<div class="w3-panel w3-mobile w3-center w3-col s3 l4">
</div>
<div class="w3-card <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-mobile w3-center w3-border w3-padding w3-col s6 l4">
<?php echo renderFlashHtml(); ?>
  <form action="<?php echo htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8'); ?>" method="POST">
<?php if($edit == 2) { ?>
    <input type="hidden" name="mode" value="useredit">
    <input type="hidden" name="id" value="<?php echo (int)$userid; ?>">
<?php } elseif($canEditUsers) { ?>
    <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8'); ?>">
<?php } ?>
    <label>Vorname</label>
    <input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="Vorname" type="text" placeholder="Vorname" <?php if($fill) echo "value=\"".htmlspecialchars((string)$n->Vorname, ENT_QUOTES, 'UTF-8')."\""; ?> <?php echo $disabled; ?>>
    <label>Nachname</label>
    <input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="Nachname" type="text" placeholder="Nachname" <?php if($fill) echo "value=\"".htmlspecialchars((string)$n->Nachname, ENT_QUOTES, 'UTF-8')."\""; ?> <?php echo $disabled; ?>>
<?php if($canEditUsers) { ?>
    <label>Mitglieds-Nr. (optional)</label>
    <input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="RefID" type="number" placeholder="Vereins-Nr." <?php if($fill) echo "value=\"".htmlspecialchars((string)$n->RefID, ENT_QUOTES, 'UTF-8')."\""; ?> <?php echo $disabled; ?>>
<?php } elseif($fill) { ?>
    <input type="hidden" name="Nachname" value="<?php echo htmlspecialchars((string)$n->Nachname, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="Vorname" value="<?php echo htmlspecialchars((string)$n->Vorname, ENT_QUOTES, 'UTF-8'); ?>">
<?php } ?>

    <label>Emailadressen</label>
    <input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="Email" type="email" placeholder="Email" <?php if($fill) echo "value=\"".htmlspecialchars((string)$n->Email, ENT_QUOTES, 'UTF-8')."\""; ?>>
    <input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="Email2" type="email" placeholder="Email 2 (optional)" <?php if($fill) echo "value=\"".htmlspecialchars((string)$n->Email2, ENT_QUOTES, 'UTF-8')."\""; ?>>
<?php if($edit != 2) { ?>
    <label class="w3-text-gray">Loginname (optional)</label>
    <input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="login" type="text" placeholder="Loginname" <?php if($fill) echo "value=\"".htmlspecialchars((string)$n->login, ENT_QUOTES, 'UTF-8')."\""; ?> <?php echo $disabled; ?>>
<?php } ?>
<?php if($fill && ($n->login || $edit == 3)) { ?>
    <label class="w3-text-gray">neues Passwort (optional)</label>
    <input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="pw1" type="password" placeholder="*****" autocomplete="new-password">
    <label class="w3-text-gray">neues Passwort wiederholen (optional)</label>
    <input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="pw2" type="password" placeholder="*****" autocomplete="new-password">
<?php } ?>
    <label>Instrument</label>
    <select class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="Instrument" <?php echo $disabled; ?>>
<?php
  if($fill) {
      echo instrumentOption($n->Instrument);
  }
  else {
      echo instrumentOption(0);
  }
?>
    </select>
<?php if($fill && (int)$n->Index > 0) { ?>
    <p class="w3-left-align w3-small"><a href="user-voice.php?user=<?php echo (int)$n->Index; ?>">Stimme / Fallbacks (Notenarchiv)</a></p>
<?php } ?>
<?php if($fill && (int)$n->Index > 0) {
    $membership = AudienceSpec::membershipForUser((int)$n->Index);
    if(count($membership)) {
?>
    <label>Gruppenzugehörigkeit</label>
    <div class="mail-recipient-chips w3-margin-bottom" aria-label="Gruppenzugehörigkeit">
<?php
        foreach($membership as $chip) {
            $type = htmlspecialchars((string)$chip['type'], ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars((string)$chip['label'], ENT_QUOTES, 'UTF-8');
            echo '<span class="mail-recipient-chip mail-recipient-chip--'.$type.'">'.$label.'</span>';
        }
?>
    </div>
<?php
    }
} ?>
    <div class="w3-col l6 m6 s12 w3-mobile w3-margin-bottom w3-left">
      <input type="hidden" name="getMail" value="0">
      <input class="w3-check" type="checkbox" name="getMail" value="1" <?php if($fill && (bool)$n->getMail) echo "checked "; ?>>
      <label>Mailverteiler</label>
    </div>
<?php if($canEditUsers) { ?>
    <div class="w3-col l6 m6 s12 w3-mobile w3-margin-bottom w3-left">
      <input type="hidden" name="Mitglied" value="0">
      <input class="w3-check" type="checkbox" name="Mitglied" value="1" <?php if($fill && (bool)$n->Mitglied){ echo "checked ";} ?>>
      <label>Mitglied</label>
    </div>
<?php   if($GLOBALS['optionsDB']['showRegisterLead']) { ?>
    <div class="w3-col l6 m6 s12 w3-mobile w3-margin-bottom w3-left">
      <input type="hidden" name="RegisterLead" value="0">
      <input class="w3-check" type="checkbox" name="RegisterLead" value="1" <?php if($fill && (bool)$n->RegisterLead) echo "checked "; ?>>
      <label>Registerführer</label>
    </div>
<?php   } ?>
<?php } ?>
    <input type="hidden" name="Index" <?php if($fill) echo "value=\"".(int)$n->Index."\""; ?>>
    <input class="w3-btn w3-col l6 m6 s12 <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?> w3-border w3-margin-bottom w3-mobile" type="submit" name="insert" value="speichern">
<?php if($fill && $edit != 2) { ?>
    <input class="w3-btn w3-col l6 m6 s12 <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?> w3-border w3-margin-bottom w3-mobile" type="submit" name="passwd" value="Zufallspasswort generieren">
    <input class="w3-btn w3-col l6 m6 s12 <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?> w3-border w3-margin-bottom w3-mobile" type="submit" name="newmail" value="Email mit Link senden">
    <input class="w3-btn w3-col l6 m6 s12 <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?> w3-border w3-margin-bottom w3-mobile" type="submit" name="deactivate" value="Deaktivieren">
<?php } ?>
  </form>
<?php if($fill && $canEditUsers) { ?>
<button class="w3-btn w3-col l6 m6 s12 <?php echo $GLOBALS['optionsDB']['colorBtnDelete']; ?> w3-border w3-margin-bottom w3-mobile" onclick="document.getElementById('delmodal').style.display='block'">l&ouml;schen</button>
<?php } ?>
<?php
// App-Login-Link + QR (MELD-123): own profile or admin view of a user
$showAppLoginLink = $fill && (int)$n->Index > 0
    && ($isSelfProfileEdit || (int)$n->Index === $userid || $canEditUsers);
if($showAppLoginLink) {
    $appLoginUrl = $n->getLink();
?>
  <div class="w3-container w3-margin-top w3-margin-bottom w3-padding w3-left-align w3-border">
    <h3><i class="fas fa-qrcode" aria-hidden="true"></i> App-Login</h3>
    <p class="w3-small">Mit der Meldeliste-App scannen oder Link öffnen. Den Link kannst du auch manuell in der App einfügen.</p>
    <div id="app-login-qr" class="w3-margin-bottom" data-alink-url="<?php echo htmlspecialchars($appLoginUrl, ENT_QUOTES, 'UTF-8'); ?>"></div>
    <div class="w3-row w3-small"><a href="<?php echo htmlspecialchars($appLoginUrl, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($appLoginUrl, ENT_QUOTES, 'UTF-8'); ?></a></div>
<?php if($canEditUsers) { ?>
    <div class="w3-row w3-small w3-margin-top"><a href="<?php echo htmlspecialchars($n->getCalendarLink(), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($n->getCalendarLink(), ENT_QUOTES, 'UTF-8'); ?></a></div>
<?php } ?>
  </div>
  <script src="js/qrcode.min.js"></script>
  <script>
  (function () {
    var el = document.getElementById('app-login-qr');
    if (!el || typeof QRCode === 'undefined') return;
    var url = el.getAttribute('data-alink-url') || '';
    if (!url) return;
    new QRCode(el, { text: url, width: 192, height: 192, correctLevel: QRCode.CorrectLevel.M });
  })();
  </script>
<?php } ?>
</div>
<div class="w3-panel w3-mobile w3-center w3-col s3 l4">
</div>
<?php if($fill && $canEditUsers) { ?>
    <div id="delmodal" class="w3-modal">
    <div class="w3-modal-content w3-card">
    <header class="w3-container w3-row <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
    <span onclick="document.getElementById('delmodal').style.display='none'" class="w3-button w3-display-topright">&times;</span>
    <h2>L&ouml;schen best&auml;tigen</h2>
    </header>
    <div class="w3-container w3-row w3-center w3-padding w3-margin w3-card <?php echo $GLOBALS['optionsDB']['colorWarning']; ?>">Sind Sie sicher, dass sie <b><?php echo htmlspecialchars($n->Vorname." ".$n->Nachname); ?></b> l&ouml;schen wollen?</div>
    <div class="w3-container w3-mobile">
    <form action="" method="POST">
    <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="Index" value="<?php echo (int)$n->Index; ?>">
    <div class="w3-row">
    <div class="w3-col l4 m4 s2 w3-center">&nbsp;</div>
    <button class="w3-btn w3-col l4 m4 s8 w3-center <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?> w3-border w3-margin-bottom w3-mobile" type="submit" name="delete" value="delete">ja</button>
    <div class="w3-col l4 m4 s2 w3-center">&nbsp;</div>
    </div>
    </form>
    <div class="w3-row">
    <div class="w3-col l4 m4 s2 w3-center">&nbsp;</div>
    <button class="w3-btn w3-col l4 m4 s8 w3-center <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?> w3-border w3-margin-bottom w3-mobile" onclick="document.getElementById('delmodal').style.display='none'">nein</button>
    <div class="w3-col l4 m4 s2 w3-center">&nbsp;</div>
    </div>
    </div>
    </div>
    </div>
<?php } ?>
    <div class="w3-row">&nbsp;</div>
<?php
include 'common/footer.php';
?>
