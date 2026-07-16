<?php
session_start();
$_SESSION['page']='users';
$_SESSION['adminpage']=true;
include "common/header.php";
if(!requirePermission("perm_showUsers")) die();

if(isset($_POST['insert'])) {
    try {
        $n = new User;
        $n->load_by_id($_POST['Index']);
        $n->fill_from_array($_POST);
        $n->save();
        if(isset($_POST['pw1']) && isset($_POST['pw2'])) {
            if($_POST['pw1'] == $_POST['pw2'] && $_POST['pw1'] != '') {
                if(!$n->passwd($_POST['pw1'])) {
                    echo '<div class="w3-panel w3-red w3-padding"><b>Passwort konnte nicht gesetzt werden.</b> Bitte Loginname prüfen.</div>';
                }
            }
            elseif($_POST['pw1'] != '' || $_POST['pw2'] != '') {
                echo '<div class="w3-panel w3-red w3-padding"><b>Passwörter stimmen nicht überein.</b></div>';
            }
        }
    }
    catch(Throwable $e) {
        $logentry = new Log;
        $logentry->error(sprintf(
            "User speichern/Passwort Exception | User-ID: <b>%d</b>, Fehler: <b>%s</b>",
            isset($_POST['Index']) ? (int)$_POST['Index'] : 0,
            htmlspecialchars($e->getMessage())
        ));
        echo '<div class="w3-panel w3-red w3-padding"><b>Fehler:</b> '.htmlspecialchars($e->getMessage()).'</div>';
    }
}
if(isset($_POST['delete'])) {
    $n = new User;
    $n->load_by_id($_POST['Index']);
    $n->delete();
}
if(isset($_POST['passwd'])) {
    try {
        $n = new User;
        $n->load_by_id($_POST['Index']);
        $n->fill_from_array($_POST);
        if((int)$_POST['Index'] > 0) {
            if(!$n->passwd("")) {
                echo '<div class="w3-panel w3-red w3-padding"><b>Zufallspasswort konnte nicht erzeugt werden.</b> Bitte Loginname prüfen.</div>';
            }
        }
    }
    catch(Throwable $e) {
        $logentry = new Log;
        $logentry->error(sprintf(
            "Zufallspasswort Exception | User-ID: <b>%d</b>, Fehler: <b>%s</b>",
            isset($_POST['Index']) ? (int)$_POST['Index'] : 0,
            htmlspecialchars($e->getMessage())
        ));
        echo '<div class="w3-panel w3-red w3-padding"><b>Fehler beim Erzeugen des Passworts:</b> '.htmlspecialchars($e->getMessage()).'</div>';
    }
}
if(isset($_POST['newmail'])) {
    try {
        $n = new User;
        $n->load_by_id($_POST['Index']);
        $n->fill_from_array($_POST);
        if((int)$_POST['Index'] > 0) {
            $n->newmail();
        }
    }
    catch(Throwable $e) {
        $logentry = new Log;
        $logentry->error(sprintf(
            "newmail Exception | User-ID: <b>%d</b>, Fehler: <b>%s</b>",
            isset($_POST['Index']) ? (int)$_POST['Index'] : 0,
            htmlspecialchars($e->getMessage())
        ));
        echo '<div class="w3-panel w3-red w3-padding"><b>Fehler beim Mailversand:</b> '.htmlspecialchars($e->getMessage()).'</div>';
    }
}
    $sql = sprintf('SELECT COUNT(`Index`) AS `Count` FROM `%sUser` WHERE `Deleted` != 1;',
    $GLOBALS['dbprefix']
    );
$dbr = mysqli_query($conn, $sql);
sqlerror();
$row = mysqli_fetch_array($dbr);
$nMusiker = $row['Count'];
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
    <h2>Liste aller User (<?php echo $nMusiker; ?>)</h2>
</div>

<div>
<input class="w3-input w3-border w3-padding" type="text" placeholder="Nach Musiker suchen..." id="filterString" onkeyup="filterLog()">
</div>
<div id="Liste">
<?php
$chunk = listChunkUsers('users', 0, 50);
echo $chunk['html'];
echo listChunkRenderSentinel('users', $chunk['nextCursor'], $chunk['hasMore'], 'filterLog');
?>
</div>
<script src="js/filterLog.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script src="js/infiniteScroll.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>

<?php
include "common/footer.php";
?>
