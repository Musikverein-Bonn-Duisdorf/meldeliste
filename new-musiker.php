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

$profileLayout = 'a';
$layoutQuery = '';

if(isset($_POST['insert']) && ($isSelfProfileEdit || !$canEditUsers)) {
    $profileResult = handleSelfProfilePost($userid);
    if($profileResult['flash']) {
        setFlash($profileResult['flash']['type'], $profileResult['flash']['message']);
    }
    elseif($profileResult['successMessage']) {
        setFlash('success', $profileResult['successMessage']);
    }
    redirectAfterPost('new-musiker.php?id='.$userid.'&mode=useredit'.$layoutQuery);
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
/** Admin legt an / bearbeitet fremdes (oder eigenes) Nutzerprofil — nicht „Mein Profil“. */
$adminUserEdit = $canEditUsers && (int)$edit === 3;
$showAdminFlags = $adminUserEdit;
$formAction = '';
$checked = function ($field) use ($fill, $n) {
    if(!$fill) {
        return in_array($field, array(
            'Active', 'getMail', 'notifyInbox', 'notifyAppMail',
            'notifyAppTerminNew', 'notifyAppTerminChange',
        ), true) ? 'checked ' : '';
    }
    return (bool)$n->$field ? 'checked ' : '';
};
$showAppLoginLink = $fill && (int)$n->Index > 0
    && ($isSelfProfileEdit || (int)$n->Index === $userid || $canEditUsers);
$showCalendarSubscribe = $fill && (int)$n->Index > 0
    && ($isSelfProfileEdit || (int)$n->Index === $userid || $canEditUsers)
    && $n->activeLink;
$appLoginUrl = $showAppLoginLink ? $n->getLink() : '';
$inputBg = $GLOBALS['optionsDB']['colorInputBackground'];
$btnSubmit = $GLOBALS['optionsDB']['colorBtnSubmit'];
$btnDelete = $GLOBALS['optionsDB']['colorBtnDelete'];

$baseQs = array();
if($fill && (int)$n->Index > 0) {
    $baseQs['id'] = (int)$n->Index;
}
if($isSelfProfileEdit || $edit == 2) {
    $baseQs['mode'] = 'useredit';
}
if(!empty($_GET['return_to'])) {
    $baseQs['return_to'] = (string)$_GET['return_to'];
}
?>
<div class="w3-container w3-margin-bottom profile-page">
<?php echo renderFlashHtml(); ?>
<?php include __DIR__.'/views/profile/layout_a.php'; ?>
</div>
<script src="js/qrcode.min.js"></script>
<script src="js/profile-layout.js"></script>
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
    <input type="hidden" name="profile_layout" value="a">
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
