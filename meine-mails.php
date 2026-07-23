<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page'] = 'meinemails';
$_SESSION['adminpage'] = false;

include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
if(!loggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['userid'];
MailJob::ensureSchema();

if(isset($_POST['delete']) && isset($_POST['id'])) {
    $out = new MailOutbox;
    $out->load_by_id((int)$_POST['id']);
    if($out->Index && (int)$out->User === $userId) {
        $out->softDeleteForUser($userId);
    }
    header('Location: meine-mails.php');
    exit;
}

$viewId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$viewMail = null;
if($viewId > 0) {
    $viewMail = new MailOutbox;
    $viewMail->load_by_id($viewId);
    if(!$viewMail->Index || (int)$viewMail->User !== $userId || (int)$viewMail->DeletedByUser === 1) {
        $viewMail = null;
    }
    else {
        $viewMail->markRead($userId);
    }
}

include_once "common/header.php";

$userNameCache = array();
$formatMailDate = function($raw) {
    return mailListFormatDate($raw);
};
$resolveSender = function($senderId) use (&$userNameCache) {
    return mailListResolveUserName($senderId, $userNameCache);
};

$mailListChunk = null;
if(!$viewMail) {
    $mailListChunk = listChunkUserMails($userId, '', 50);
}

adminListPageBegin('Kommunikation', 'Meine Nachrichten');
?>
<?php if($viewMail) {
    $subj = htmlspecialchars((string)$viewMail->Subject, ENT_QUOTES, 'UTF-8');
    $body = formatMailBodyForDisplay((string)$viewMail->BodyText);
    $when = htmlspecialchars($formatMailDate($viewMail->Created), ENT_QUOTES, 'UTF-8');
    $job = new MailJob;
    $job->load_by_id((int)$viewMail->Job);
    $sender = htmlspecialchars($resolveSender((int)$job->CreatedBy), ENT_QUOTES, 'UTF-8');
?>
<div class="w3-container">
  <div class="w3-card w3-padding">
    <h3 class="w3-margin-top"><?php echo $subj !== '' ? $subj : '<em>(ohne Betreff)</em>'; ?></h3>
    <p class="w3-small"><?php echo $when; ?> · von <?php echo $sender; ?>
<?php if((string)$viewMail->Status === 'failed') { ?>
      · <span class="w3-tag <?php echo isset($GLOBALS['optionsDB']['colorLogError']) ? $GLOBALS['optionsDB']['colorLogError'] : 'w3-red'; ?>">E-Mail fehlgeschlagen</span>
<?php } ?>
    </p>
    <div class="w3-padding-16 mail-body-content"><?php echo $body; ?></div>
    <div class="w3-padding-16 mail-detail-actions">
      <a class="w3-button <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?>" href="meine-mails.php">Zur Übersicht</a>
      <form method="post" action="meine-mails.php" onsubmit="return confirm('Nachricht ausblenden?');">
        <input type="hidden" name="id" value="<?php echo (int)$viewMail->Index; ?>" />
        <button type="submit" name="delete" value="1" class="w3-button <?php echo $GLOBALS['optionsDB']['colorBtnNo']; ?>">Ausblenden</button>
      </form>
    </div>
  </div>
</div>
<?php } else {
    adminListSearchField('Nachrichten suchen (Betreff, Absender)…', array('onkeyup' => 'filterMail()'));
?>
<div class="mail-list" id="Liste">
    <div class="mail-list-header <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
      <div>Betreff</div>
      <div></div>
      <div>Aktion</div>
    </div>
<?php
if($mailListChunk['html'] === '') {
    echo '<div class="mail-list-item mail-list-empty"><div class="mail-list-primary">Keine Nachrichten vorhanden.</div></div>';
}
else {
    echo $mailListChunk['html'];
}
echo listChunkRenderSentinel('meineMails', $mailListChunk['nextCursor'], $mailListChunk['hasMore'], 'filterMail');
?>
</div>
<script src="js/listRowSearch.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script src="js/filterMail.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script src="js/infiniteScroll.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<?php } ?>
<?php
adminListPageEnd();
include "common/footer.php";
?>
