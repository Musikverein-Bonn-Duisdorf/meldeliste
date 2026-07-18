<?php
ob_start();
session_start();
$_SESSION['page'] = 'musiker';
$_SESSION['adminpage'] = true;

include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
requireLoggedInOrRedirect();

if(!requirePermission('perm_showUsers')) {
    $logentry = new Log;
    $logentry->error(sprintf(
        'Zugriff auf musiker.php verweigert | User-ID: <b>%d</b>',
        isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0
    ));
    denyAccess('Keine Berechtigung. Eigenes Profil bitte über „Mein Profil“ speichern.');
}

include_once 'libs/form-response.php';
applyUserFormPostRedirect('musiker.php', array('allowNewUser' => true));

include 'common/header.php';

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
<?php echo renderFlashHtml(); ?>

<?php if($GLOBALS['optionsDB']['showOrchestraView']) { ?>
<div class="w3-center orchestra-svg-wrap">
<?php echo printOrchestra(0); ?>
</div>
<?php } ?>

<div>
<input class="w3-input w3-border w3-padding" type="text" placeholder="Nach Musiker suchen..." id="filterString" onkeyup="filterMusiker()">
</div>
<div id="Liste">
<?php
$chunk = listChunkUsers('musiker', 0, 50);
echo $chunk['html'];
echo listChunkRenderSentinel('musiker', $chunk['nextCursor'], $chunk['hasMore'], 'filterMusiker');
?>
</div>
<script src="js/filterMusiker.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script src="js/infiniteScroll.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>

<?php
include 'common/footer.php';
?>
