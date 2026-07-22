<?php
ob_start();
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page']='termine-archiv';
$_SESSION['adminpage']=true;

include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
requireLoggedInOrRedirect();

if(isset($_POST['proxy'])) {
    $user = $_POST['proxy'];
    $proxy = new User;
    $proxy->load_by_id($user);
}
else {
    $user = $_SESSION['userid'];
}

$mutated = false;
if(isset($_POST['insert'])) {
    $n = new Termin;
    $n->fill_from_array($_POST);
    $n->save();
    setFlash('success', 'Termin gespeichert.');
    $mutated = true;
}
if(isset($_POST['delete'])) {
    $n = new Termin;
    $n->fill_from_array($_POST);
    $n->delete();
    setFlash('success', 'Termin gelöscht.');
    $mutated = true;
}
if($mutated) {
    redirectAfterPost(resolvePostReturnUrl('termine-archiv.php'));
}

include "common/header.php";
?>
<script src="<?php echo assetUrl('js/getStatus.js'); ?>"></script>
<script src="<?php echo assetUrl('js/melde.js'); ?>"></script>
<script src="<?php echo assetUrl('js/meldeshift.js'); ?>"></script>
<script src="<?php echo assetUrl('js/changeInstrument.js'); ?>"></script>

<?php echo renderFlashHtml(); ?>
<?php
$title = isset($_POST['proxy'])
    ? ('Terminübersicht '.$proxy->getName())
    : 'Archiv: Termine';
adminListPageBegin('Termine', $title);
$chunk = listChunkTermine('past', 'basic', '', 50, isset($user) ? (int)$user : (int)$_SESSION['userid']);
adminListSearchField('Termine suchen (Titel, Ort, Datum, Beschreibung)…', array(
    'onkeyup' => 'filterTermine()',
    'label' => 'Termine suchen',
));
?>
<div id="Liste">
<?php echo $chunk['html']; ?>
<?php echo listChunkRenderSentinel('termineArchiv', $chunk['nextCursor'], $chunk['hasMore'], 'filterTermine'); ?>
</div>
<?php adminListPageEnd(); ?>
<script src="<?php echo assetUrl('js/filterTermine.js'); ?>"></script>
<script src="<?php echo assetUrl('js/infiniteScroll.js'); ?>"></script>
<?php
include "common/footer.php";
?>
