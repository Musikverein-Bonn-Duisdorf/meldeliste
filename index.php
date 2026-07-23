<?php
ob_start();
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page']='home';
$_SESSION['adminpage']=false;

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
    $n->load_by_id($_POST['Index']);
    $n->delete();
    setFlash('success', 'Termin gelöscht.');
    $mutated = true;
}
if($mutated) {
    redirectAfterPost(resolvePostReturnUrl('index.php'));
}

include "common/header.php";
?>
<script src="<?php echo assetUrl('js/getStatus.js'); ?>"></script>
<script src="<?php echo assetUrl('js/melde.js'); ?>"></script>
<script src="<?php echo assetUrl('js/meldeFT.js'); ?>"></script>
<script src="<?php echo assetUrl('js/meldeshift.js'); ?>"></script>
<script src="<?php echo assetUrl('js/changeInstrument.js'); ?>"></script>

<?php echo renderFlashHtml(); ?>
<?php
$homeTitle = 'Bevorstehende Termine';
if(isset($_POST['proxy']) && isset($proxy) && $proxy) {
    $homeTitle .= ' '.$proxy->getName();
}
adminListPageBegin('Termine', $homeTitle);
adminListSearchField('Termine suchen (Titel, Ort, Datum, Beschreibung)…', array(
    'onkeyup' => 'filterTermine()',
));
$chunkUser = isset($user) ? (int)$user : (int)$_SESSION['userid'];
$chunk = listChunkTermine('future', 'basic', '', 50, $chunkUser);
?>
<div id="Liste">
<?php echo $chunk['html']; ?>
<?php echo listChunkRenderSentinel('termine', $chunk['nextCursor'], $chunk['hasMore'], 'filterTermine', ' data-extra="user='.$chunkUser.'"'); ?>
</div>
<?php adminListPageEnd(); ?>
<script src="<?php echo assetUrl('js/filterTermine.js'); ?>"></script>
<script src="<?php echo assetUrl('js/infiniteScroll.js'); ?>"></script>
<?php
include "common/footer.php";
?>
