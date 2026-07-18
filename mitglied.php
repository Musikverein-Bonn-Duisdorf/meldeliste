<?php
ob_start();
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page'] = 'mitglied';
$_SESSION['adminpage'] = true;

include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
requireLoggedInOrRedirect();

if(!requirePermission('perm_showUsers')) {
    denyAccess();
}

include_once 'libs/form-response.php';
applyUserFormPostRedirect('mitglied.php', array('allowNewUser' => false));

include 'common/header.php';

$sql = sprintf('SELECT COUNT(`Index`) AS `Count` FROM `%sUser` WHERE `Mitglied` = 1 AND `Instrument` > 0 AND `Deleted` != 1;',
    $GLOBALS['dbprefix']
);
$dbr = mysqli_query($conn, $sql);
sqlerror();
$row = mysqli_fetch_array($dbr);
$nMusiker = $row['Count'];
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
    <h2>Liste aller aktiven Mitglieder (<?php echo $nMusiker; ?>)</h2>
</div>
<?php echo renderFlashHtml(); ?>

<div>
<input class="w3-input w3-border w3-padding" type="text" placeholder="Nach Musiker suchen..." id="filterString" onkeyup="filterMusiker()">
</div>
<div id="listHeader" class="list-header w3-row w3-hide-small">
  <div class="w3-col l3 m6 s12 w3-container list-sort" data-sort="nachname" data-type="string">Name</div>
  <div class="w3-col l2 m6 s12 w3-container list-sort" data-sort="instrument" data-type="string">Instrument</div>
  <div class="w3-col l3 m12 s12 w3-container list-sort" data-sort="email" data-type="string">E-Mail</div>
  <div class="w3-col l2 m6 s12 w3-container list-sort" data-sort="lastlogin" data-type="date">Letzter Login</div>
  <div class="w3-col l2 m6 s12 w3-container list-sort" data-sort="lastvisit" data-type="date">Letzte Teilnahme</div>
</div>
<div id="Liste">
<?php
$chunk = listChunkUsers('mitglied', 0, 50);
echo $chunk['html'];
echo listChunkRenderSentinel('mitglied', $chunk['nextCursor'], $chunk['hasMore'], 'filterMusiker');
?>
</div>
<script src="js/filterMusiker.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script src="js/sortList.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script src="js/infiniteScroll.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script>bindListSort({ headerId: 'listHeader', mode: 'server', defaultKey: 'nachname', defaultDir: 'asc', defaultType: 'string' });</script>

<?php
include 'common/footer.php';
?>
