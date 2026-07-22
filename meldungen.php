<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page']='meldungen';
$_SESSION['adminpage']=true;
include "common/header.php";
if(!requirePermission("perm_showResponse")) {
    denyAccess();
}

$chunk = listChunkTermine('future', 'response', '', 50, (int)$_SESSION['userid']);
adminListPageBegin('Meldungen', 'Rückmeldungen');
adminListSearchField('Termine suchen (Titel, Ort, Datum, Beschreibung)…', array(
    'onkeyup' => 'filterTermine()',
    'label' => 'Termine suchen',
));
?>
<div id="Liste">
<?php echo $chunk['html']; ?>
<?php echo listChunkRenderSentinel('meldungen', $chunk['nextCursor'], $chunk['hasMore'], 'filterTermine'); ?>
</div>
<?php
adminListPageEnd();
?>
<script src="js/filterTermine.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script src="js/infiniteScroll.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<?php
include "common/footer.php";
?>
