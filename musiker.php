<?php
session_start();
$_SESSION['page']='musiker';
$_SESSION['adminpage']=true;
include "common/header.php";
if(!requirePermission("perm_showUsers")) {
    $logentry = new Log;
    $logentry->error(sprintf(
        "Zugriff auf musiker.php verweigert | User-ID: <b>%d</b>",
        isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0
    ));
    die('<div class="w3-panel w3-red w3-padding"><b>Keine Berechtigung.</b> Eigenes Profil bitte über „Mein Profil“ speichern.</div>');
}

if(isset($_POST['insert'])) {
    try {
        $n = new User;
        $id = isset($_POST['Index']) ? (int)$_POST['Index'] : 0;
        if($id > 0) {
            $n->load_by_id($id);
        }
        $n->fill_from_array($_POST);
        // New user: empty Index must not force update path
        if($id < 1) {
            $n->Index = null;
        }
        if(!$n->save()) {
            echo '<div class="w3-panel w3-red w3-padding"><b>Musiker konnte nicht gespeichert werden.</b> Vorname und Nachname sind Pflicht.</div>';
        }
        elseif(isset($_POST['pw1']) && isset($_POST['pw2'])) {
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
            "Musiker speichern/Passwort Exception | User-ID: <b>%d</b>, Fehler: <b>%s</b>",
            isset($_POST['Index']) ? (int)$_POST['Index'] : 0,
            htmlspecialchars($e->getMessage())
        ));
        echo '<div class="w3-panel w3-red w3-padding"><b>Fehler beim Speichern:</b> '.htmlspecialchars($e->getMessage()).'</div>';
    }
}
if(isset($_POST['deactivate'])) {
    $n = new User;
    $n->load_by_id($_POST['Index']);
    $n->Instrument=0;
    $n->save();
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
$sql = sprintf('SELECT COUNT(`Index`) AS `Count` FROM `%sUser` INNER JOIN (SELECT `Index` AS `iIndex`, `Register` FROM `%sInstrument`) `%sInstrument` ON `Instrument` = `iIndex` INNER JOIN (SELECT `Index` AS `rIndex`, `Name` AS `rName` FROM `%sRegister`) `%sRegister` ON `Register` = `rIndex` WHERE `rName` != "keins" AND `Deleted` != 1;',
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix']
    );
$dbr = mysqli_query($conn, $sql);
sqlerror();
$row = mysqli_fetch_array($dbr);
$nMusiker = $row['Count'];
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
    <h2>Liste aller Musiker (<?php echo $nMusiker; ?>)</h2>
</div>

<?php if($GLOBALS['optionsDB']['showOrchestraView']) { ?>
<div class="w3-center w3-container w3-hide-small">
<?php echo printOrchestra(0, 1); ?>
</div>
<div class="w3-center w3-container w3-hide-large w3-hide-medium">
<?php echo printOrchestra(0, 0.4); ?>
</div>
<?php } ?>

<div>
<input class="w3-input w3-border w3-padding" type="text" placeholder="Nach Musiker suchen..." id="filterString" onkeyup="filterMusiker()">
</div>
<div id="Liste">
<?php
$sql = sprintf('SELECT `Index` FROM `%sUser` INNER JOIN (SELECT `Index` AS `iIndex`, `Register` FROM `%sInstrument`) `%sInstrument` ON `Instrument` = `iIndex` INNER JOIN (SELECT `Index` AS `rIndex`, `Name` AS `rName` FROM `%sRegister`) `%sRegister` ON `Register` = `rIndex` WHERE `rName` != "keins" AND `Deleted` != 1 ORDER BY `Nachname`, `Vorname`;',
$GLOBALS['dbprefix'],
$GLOBALS['dbprefix'],
$GLOBALS['dbprefix'],
$GLOBALS['dbprefix'],
$GLOBALS['dbprefix']
);
$dbr = mysqli_query($conn, $sql);
sqlerror();
while($row = mysqli_fetch_array($dbr)) {
    $M = new User;
    $M->load_by_id($row['Index']);
    $M->printTableLine();
}
?>
</div>
<script src="js/filterMusiker.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>

<?php
include "common/footer.php";
?>
