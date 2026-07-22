<?php
ob_start();
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page'] = 'users';
$_SESSION['adminpage'] = true;

include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
requireLoggedInOrRedirect();

if(!requirePermission('perm_showUsers')) {
    denyAccess();
}

include_once 'libs/form-response.php';
applyUserFormPostRedirect('users.php', array('allowNewUser' => false));

include 'common/header.php';

$sql = sprintf('SELECT COUNT(`Index`) AS `Count` FROM `%sUser` WHERE `Deleted` != 1;',
    $GLOBALS['dbprefix']
);
$dbr = mysqli_query($conn, $sql);
sqlerror();
$row = mysqli_fetch_array($dbr);
$nMusiker = $row['Count'];
?>
<?php echo renderFlashHtml(); ?>
<?php adminListPageBegin('Personen', 'User ('.$nMusiker.')'); ?>
<?php adminListSearchField('Nach User suchen…', array('onkeyup' => 'filterLog()')); ?>
<div id="listHeader" class="list-header w3-row w3-hide-small">
  <div class="w3-col l1 m2 s12 w3-container list-sort" data-sort="index" data-type="number">ID</div>
  <div class="w3-col l3 m5 s12 w3-container list-sort" data-sort="nachname" data-type="string">Name</div>
  <div class="w3-col l3 m5 s12 w3-container list-sort" data-sort="email" data-type="string">E-Mail</div>
  <div class="w3-col l2 m6 s12 w3-container list-sort" data-sort="lastlogin" data-type="date">Letzter Login</div>
  <div class="w3-col l2 m6 s12 w3-container list-sort" data-sort="lastvisit" data-type="date">Letzte Teilnahme</div>
</div>
<div id="Liste">
<?php
$chunk = listChunkUsers('users', 0, 50);
echo $chunk['html'];
echo listChunkRenderSentinel('users', $chunk['nextCursor'], $chunk['hasMore'], 'filterLog');
?>
</div>
<?php adminListPageEnd(); ?>
<script src="js/filterLog.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script src="js/sortList.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script src="js/infiniteScroll.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script>bindListSort({ headerId: 'listHeader', mode: 'server', defaultKey: 'nachname', defaultDir: 'asc', defaultType: 'string' });</script>

<?php
include 'common/footer.php';
?>
