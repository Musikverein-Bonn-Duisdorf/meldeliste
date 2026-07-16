<?php
session_start();
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

$sqlList = sprintf(
    'SELECT o.*, j.`CreatedBy` AS `SenderId`
     FROM `%sMailOutbox` o
     LEFT JOIN `%sMailJob` j ON j.`Index` = o.`Job`
     WHERE o.`User` = %d AND o.`DeletedByUser` = 0 AND o.`Status` IN ("pending", "sending", "sent")
     ORDER BY o.`Created` DESC, o.`Index` DESC
     LIMIT 200;',
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $userId
);
$dbr = mysqli_query($GLOBALS['conn'], $sqlList);
sqlerror();

$userNameCache = array();
$formatMailDate = function($raw) {
    $raw = (string)$raw;
    if($raw === '') return '';
    $out = (string)germanDate($raw, true);
    if(strlen($raw) >= 16) {
        $out .= ' '.sql2timeRaw(substr($raw, 11, 8));
    }
    return $out;
};
$resolveSender = function($senderId) use (&$userNameCache) {
    $senderId = (int)$senderId;
    if($senderId <= 0) return 'System';
    if(!isset($userNameCache[$senderId])) {
        $u = new User;
        $u->load_by_id($senderId);
        $userNameCache[$senderId] = $u->Index ? $u->getName() : ('User '.$senderId);
    }
    return $userNameCache[$senderId];
};
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2>Meine Nachrichten</h2>
</div>
<div class="w3-container w3-padding">
  <p>Hier siehst du die an dich gerichteten Emails der Meldeliste. Ungelesene Einträge sind farblich markiert.</p>
</div>

<?php if($viewMail) {
    $subj = htmlspecialchars((string)$viewMail->Subject, ENT_QUOTES, 'UTF-8');
    $body = formatMailBodyForDisplay((string)$viewMail->BodyText);
    $when = htmlspecialchars($formatMailDate($viewMail->Created), ENT_QUOTES, 'UTF-8');
    $job = new MailJob;
    $job->load_by_id((int)$viewMail->Job);
    $sender = htmlspecialchars($resolveSender((int)$job->CreatedBy), ENT_QUOTES, 'UTF-8');
?>
<div class="w3-container w3-padding">
  <div class="w3-card w3-padding">
    <h3 class="w3-margin-top"><?php echo $subj !== '' ? $subj : '<em>(ohne Betreff)</em>'; ?></h3>
    <p class="w3-small"><?php echo $when; ?> · von <?php echo $sender; ?></p>
    <div class="w3-padding-16"><?php echo $body; ?></div>
    <div class="w3-padding-16">
      <a class="w3-button <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?>" href="meine-mails.php">Zur Übersicht</a>
      <form method="post" action="meine-mails.php" style="display:inline;" onsubmit="return confirm('Nachricht ausblenden?');">
        <input type="hidden" name="id" value="<?php echo (int)$viewMail->Index; ?>" />
        <button type="submit" name="delete" value="1" class="w3-button <?php echo $GLOBALS['optionsDB']['colorBtnNo']; ?>">Ausblenden</button>
      </form>
    </div>
  </div>
</div>
<?php } ?>

<div class="w3-container w3-padding">
  <div class="w3-responsive">
  <table class="w3-table w3-bordered w3-hoverable">
    <thead>
      <tr class="<?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
        <th>Datum</th>
        <th>Absender</th>
        <th>Betreff</th>
        <th></th>
        <th>Aktion</th>
      </tr>
    </thead>
    <tbody>
<?php
if(!$dbr || mysqli_num_rows($dbr) === 0) {
    echo '<tr><td colspan="5">Keine Nachrichten vorhanden.</td></tr>';
}
else {
    while($row = mysqli_fetch_assoc($dbr)) {
        $id = (int)$row['Index'];
        $unread = empty($row['ReadAt']);
        $when = htmlspecialchars($formatMailDate($row['Created']), ENT_QUOTES, 'UTF-8');
        $sender = htmlspecialchars($resolveSender(isset($row['SenderId']) ? $row['SenderId'] : 0), ENT_QUOTES, 'UTF-8');
        $subject = $row['Subject'] !== '' && $row['Subject'] !== null
            ? htmlspecialchars((string)$row['Subject'], ENT_QUOTES, 'UTF-8')
            : '<em>(ohne Betreff)</em>';
        $rowCls = $unread ? 'w3-pale-yellow' : '';
        $neu = $unread
            ? '<span class="w3-tag '.$GLOBALS['optionsDB']['colorLogEmail'].'">neu</span>'
            : '';
        if($unread) {
            $subject = '<strong>'.$subject.'</strong>';
        }
        echo '<tr class="'.$rowCls.'">';
        echo '<td>'.$when.'</td>';
        echo '<td>'.$sender.'</td>';
        echo '<td><a href="meine-mails.php?id='.$id.'">'.$subject.'</a></td>';
        echo '<td>'.$neu.'</td>';
        echo '<td>';
        echo '<a class="w3-button w3-small '.$GLOBALS['optionsDB']['colorBtnEdit'].'" href="meine-mails.php?id='.$id.'">Anzeigen</a> ';
        echo '<form method="post" action="meine-mails.php" style="display:inline;" onsubmit="return confirm(\'Nachricht ausblenden?\');">';
        echo '<input type="hidden" name="id" value="'.$id.'" />';
        echo '<button type="submit" name="delete" value="1" class="w3-button w3-small '.$GLOBALS['optionsDB']['colorBtnNo'].'">Ausblenden</button>';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }
}
?>
    </tbody>
  </table>
  </div>
</div>
<?php
include "common/footer.php";
?>
