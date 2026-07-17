<?php
ob_start();
session_start();
$_SESSION['page'] = 'users';
$_SESSION['adminpage'] = true;

include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
requireLoggedInOrRedirect();

if(!requirePermission('perm_showUsers')) {
    die();
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
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
    <h2>Liste aller User (<?php echo $nMusiker; ?>)</h2>
</div>
<?php echo renderFlashHtml(); ?>

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
include 'common/footer.php';
?>
