<?php
session_start();
$_SESSION['page'] = 'meinemails';
$_SESSION['adminpage'] = false;

include 'common/include.php';
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

include "common/header.php";

$statusLabels = array(
    'pending' => 'Warteschlange',
    'sending' => 'Wird gesendet',
    'sent' => 'Gesendet',
    'failed' => 'Fehlgeschlagen',
);

$sql = sprintf(
    'SELECT * FROM `%sMailOutbox` WHERE `User` = %d AND `DeletedByUser` = 0 ORDER BY `Index` DESC LIMIT 200;',
    $GLOBALS['dbprefix'],
    $userId
);
$dbr = mysqli_query($GLOBALS['conn'], $sql);
sqlerror();
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2>Meine Nachrichten</h2>
</div>
<div class="w3-container w3-padding">
  <p>Hier siehst du die an dich gerichteten Emails der Meldeliste. Du kannst Einträge ausblenden; der Versandstatus bleibt intern erhalten.</p>
</div>
<?php
if(!$dbr || mysqli_num_rows($dbr) === 0) {
    echo '<div class="w3-container w3-padding"><p>Keine Nachrichten vorhanden.</p></div>';
}
else {
    while($row = mysqli_fetch_assoc($dbr)) {
        $id = (int)$row['Index'];
        $status = isset($statusLabels[$row['Status']]) ? $statusLabels[$row['Status']] : $row['Status'];
        $created = !empty($row['SentAt']) ? $row['SentAt'] : $row['Created'];
        $subject = htmlspecialchars((string)$row['Subject'], ENT_QUOTES, 'UTF-8');
        $preview = htmlspecialchars(substr(preg_replace('/\s+/', ' ', strip_tags((string)$row['BodyText'])), 0, 160), ENT_QUOTES, 'UTF-8');
        $body = nl2br(htmlspecialchars((string)$row['BodyText'], ENT_QUOTES, 'UTF-8'));
        $detailId = 'mailDetail'.$id;
        echo '<div class="w3-card w3-margin w3-padding">';
        echo '<div class="w3-row">';
        echo '<div class="w3-col l8 m8 s12"><h4 class="w3-margin-top">'.$subject.'</h4>';
        echo '<p class="w3-small">'.$status;
        if($created) {
            echo ' · '.htmlspecialchars((string)$created, ENT_QUOTES, 'UTF-8');
        }
        echo '</p>';
        $plainLen = strlen(strip_tags((string)$row['BodyText']));
        echo '<p>'.$preview.($plainLen > 160 ? '…' : '').'</p>';
        echo '</div>';
        echo '<div class="w3-col l4 m4 s12 w3-right-align w3-padding">';
        echo '<button type="button" class="w3-button '.$GLOBALS['optionsDB']['colorBtnEdit'].' w3-margin-bottom" onclick="document.getElementById(\''.$detailId.'\').style.display=\'block\'">Anzeigen</button> ';
        echo '<form method="post" action="meine-mails.php" style="display:inline;" onsubmit="return confirm(\'Nachricht ausblenden?\');">';
        echo '<input type="hidden" name="id" value="'.$id.'" />';
        echo '<button type="submit" name="delete" value="1" class="w3-button '.$GLOBALS['optionsDB']['colorBtnNo'].'">Ausblenden</button>';
        echo '</form>';
        echo '</div></div>';
        echo '<div id="'.$detailId.'" class="w3-modal">';
        echo '<div class="w3-modal-content w3-card-4">';
        echo '<header class="w3-container '.$GLOBALS['optionsDB']['colorTitleBar'].'">';
        echo '<span onclick="document.getElementById(\''.$detailId.'\').style.display=\'none\'" class="w3-button w3-display-topright">&times;</span>';
        echo '<h3>'.$subject.'</h3></header>';
        echo '<div class="w3-container w3-padding"><p>'.$body.'</p></div>';
        echo '<footer class="w3-container w3-padding"><button type="button" class="w3-button" onclick="document.getElementById(\''.$detailId.'\').style.display=\'none\'">Schließen</button></footer>';
        echo '</div></div>';
        echo '</div>';
    }
}
?>
<?php
include "common/footer.php";
?>
