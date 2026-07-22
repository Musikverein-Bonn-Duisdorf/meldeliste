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
$returnToken = !empty($_POST['return_token'])
    ? (string)$_POST['return_token']
    : issueReturnToken($returnTo);

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
$checked = function ($field) use ($fill, $n) {
    if(!$fill) {
        // New user: match DB defaults (on)
        return in_array($field, array(
            'getMail', 'notifyInbox', 'notifyAppMail',
            'notifyAppTerminNew', 'notifyAppTerminChange', 'notifyAppTerminSoon',
        ), true) ? 'checked ' : '';
    }
    return (bool)$n->$field ? 'checked ' : '';
};
?>
<div class="w3-container w3-margin-bottom <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2>Profil bearbeiten</h2>
</div>
<div class="w3-container w3-margin-bottom" style="max-width:72rem;margin-left:auto;margin-right:auto;">
<?php echo renderFlashHtml(); ?>
  <form action="<?php echo htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8'); ?>" method="POST">
<?php if($edit == 2) { ?>
    <input type="hidden" name="mode" value="useredit">
    <input type="hidden" name="id" value="<?php echo (int)$userid; ?>">
<?php } elseif($canEditUsers) { ?>
    <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="return_token" value="<?php echo htmlspecialchars($returnToken, ENT_QUOTES, 'UTF-8'); ?>">
<?php } ?>
    <div class="w3-row-padding">
      <div class="w3-col l6 m12 s12 w3-margin-bottom">
        <div class="w3-padding w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>">
          <h3>Stammdaten</h3>
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
        </div>
      </div>

      <div class="w3-col l6 m12 s12 w3-margin-bottom">
        <div class="w3-padding w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>">
          <h3>Einstellungen</h3>
          <p class="w3-small w3-left-align"><a href="help.php#help-profil">Hilfe: Benachrichtigungen</a></p>
          <div class="w3-margin-bottom w3-left-align">
            <input type="hidden" name="getMail" value="0">
            <input class="w3-check" type="checkbox" name="getMail" value="1" id="pref-getMail" <?php echo $checked('getMail'); ?>>
            <label for="pref-getMail">E-Mail</label>
          </div>
          <div class="w3-margin-bottom w3-left-align">
            <input type="hidden" name="notifyInbox" value="0">
            <input class="w3-check" type="checkbox" name="notifyInbox" value="1" id="pref-notifyInbox" <?php echo $checked('notifyInbox'); ?>>
            <label for="pref-notifyInbox">Nachrichten</label>
          </div>
          <div class="w3-margin-bottom w3-left-align">
            <input type="hidden" name="notifyAppMail" value="0">
            <input class="w3-check" type="checkbox" name="notifyAppMail" value="1" id="pref-notifyAppMail" <?php echo $checked('notifyAppMail'); ?>>
            <label for="pref-notifyAppMail">App: Nachrichten</label>
          </div>
          <div class="w3-margin-bottom w3-left-align">
            <input type="hidden" name="notifyAppTerminNew" value="0">
            <input class="w3-check" type="checkbox" name="notifyAppTerminNew" value="1" id="pref-notifyAppTerminNew" <?php echo $checked('notifyAppTerminNew'); ?>>
            <label for="pref-notifyAppTerminNew">App: neuer Termin</label>
          </div>
          <div class="w3-margin-bottom w3-left-align">
            <input type="hidden" name="notifyAppTerminChange" value="0">
            <input class="w3-check" type="checkbox" name="notifyAppTerminChange" value="1" id="pref-notifyAppTerminChange" <?php echo $checked('notifyAppTerminChange'); ?>>
            <label for="pref-notifyAppTerminChange">App: Termin geändert</label>
          </div>
          <div class="w3-margin-bottom w3-left-align">
            <input type="hidden" name="notifyAppTerminSoon" value="0">
            <input class="w3-check" type="checkbox" name="notifyAppTerminSoon" value="1" id="pref-notifyAppTerminSoon" <?php echo $checked('notifyAppTerminSoon'); ?>>
            <label for="pref-notifyAppTerminSoon">App: Termin bald</label>
          </div>
<?php if($canEditUsers) { ?>
          <hr>
          <div class="w3-margin-bottom w3-left-align">
            <input type="hidden" name="Mitglied" value="0">
            <input class="w3-check" type="checkbox" name="Mitglied" value="1" id="pref-Mitglied" <?php echo $checked('Mitglied'); ?>>
            <label for="pref-Mitglied">Mitglied</label>
          </div>
<?php   if($GLOBALS['optionsDB']['showRegisterLead']) { ?>
          <div class="w3-margin-bottom w3-left-align">
            <input type="hidden" name="RegisterLead" value="0">
            <input class="w3-check" type="checkbox" name="RegisterLead" value="1" id="pref-RegisterLead" <?php echo $checked('RegisterLead'); ?>>
            <label for="pref-RegisterLead">Registerführer</label>
          </div>
<?php   } ?>
<?php } ?>
        </div>
      </div>
    </div>

    <input type="hidden" name="Index" <?php if($fill) echo "value=\"".(int)$n->Index."\""; ?>>
    <div class="w3-row-padding">
      <input class="w3-btn w3-col l3 m6 s12 <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?> w3-border w3-margin-bottom w3-mobile" type="submit" name="insert" value="speichern">
<?php if($fill && $edit != 2) { ?>
      <input class="w3-btn w3-col l3 m6 s12 <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?> w3-border w3-margin-bottom w3-mobile" type="submit" name="passwd" value="Zufallspasswort generieren">
      <input class="w3-btn w3-col l3 m6 s12 <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?> w3-border w3-margin-bottom w3-mobile" type="submit" name="newmail" value="Email mit Link senden">
      <input class="w3-btn w3-col l3 m6 s12 <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?> w3-border w3-margin-bottom w3-mobile" type="submit" name="deactivate" value="Deaktivieren">
<?php } ?>
    </div>
  </form>
<?php if($fill && $canEditUsers) { ?>
  <button class="w3-btn w3-col l3 m6 s12 <?php echo $GLOBALS['optionsDB']['colorBtnDelete']; ?> w3-border w3-margin-bottom w3-mobile" onclick="document.getElementById('delmodal').style.display='block'">l&ouml;schen</button>
<?php } ?>

<?php
// App-Login QR below the profile form
$showAppLoginLink = $fill && (int)$n->Index > 0
    && ($isSelfProfileEdit || (int)$n->Index === $userid || $canEditUsers);
if($showAppLoginLink) {
    $appLoginUrl = $n->getLink();
?>
  <div class="w3-row-padding w3-margin-top">
    <div class="w3-col l6 m12 s12 w3-margin-bottom">
      <div class="w3-padding w3-border w3-center <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>">
        <h3><i class="fas fa-qrcode" aria-hidden="true"></i> App-Login</h3>
        <p class="w3-small">Mit der Meldeliste-App scannen oder Link öffnen.</p>
        <div id="app-login-qr" class="w3-margin-bottom" style="display:inline-block;" data-alink-url="<?php echo htmlspecialchars($appLoginUrl, ENT_QUOTES, 'UTF-8'); ?>"></div>
        <div class="w3-small w3-break"><a href="<?php echo htmlspecialchars($appLoginUrl, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($appLoginUrl, ENT_QUOTES, 'UTF-8'); ?></a></div>
      </div>
    </div>
<?php
    $showCalendarSubscribe = $fill && (int)$n->Index > 0
        && ($isSelfProfileEdit || (int)$n->Index === $userid || $canEditUsers)
        && $n->activeLink;
    if($showCalendarSubscribe) {
?>
    <div class="w3-col l6 m12 s12 w3-margin-bottom">
<?php
        $calendarSubscribeUid = 'profile-cal';
        include __DIR__.'/views/calendar/subscribe.php';
?>
    </div>
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
    <input type="hidden" name="return_token" value="<?php echo htmlspecialchars($returnToken, ENT_QUOTES, 'UTF-8'); ?>">
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
<?php
include 'common/footer.php';
?>
