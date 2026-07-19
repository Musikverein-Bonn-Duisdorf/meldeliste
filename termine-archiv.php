<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page']='termine-archiv';
$_SESSION['adminpage']=true;
include "common/header.php";
if(isset($_POST['proxy'])) {
    $user = $_POST['proxy'];
    $proxy = new User;
    $proxy->load_by_id($user);
}
else {
    $user = $_SESSION['userid'];
}

if(isset($_POST['insert'])) {
    $n = new Termin;
    $n->fill_from_array($_POST);
    $n->save();
}
if(isset($_POST['delete'])) {
    $n = new Termin;
    $n->fill_from_array($_POST);
    $n->delete();
}
if(isset($_POST['insertAushilfe'])) {
        $aushilfe = new Aushilfe;
        $aushilfe->fill_from_array($_POST);
        $aushilfe->save();
}
if(isset($_POST['deleteAushilfe'])) {
        $aushilfe = new Aushilfe;
        $aushilfe->load_by_id((int)$_POST['Index']);
        if($aushilfe->Index && requirePermission('perm_editAppmnts')) {
            $aushilfe->delete();
        }
}
?>
<script src="<?php echo assetUrl('js/getStatus.js'); ?>"></script>
<script src="<?php echo assetUrl('js/melde.js'); ?>"></script>
<script src="<?php echo assetUrl('js/meldeshift.js'); ?>"></script>
<script src="<?php echo assetUrl('js/changeInstrument.js'); ?>"></script>

<?php
if(isset($_POST['proxy'])) {
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorLogWarning']; ?>">
    <h2>Termin&uuml;bersicht <?php echo $proxy->getName();?></h2>
</div>
<?php
} else {
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
<h2>Termin&uuml;bersicht</h2>
</div>
<?php
}
$chunk = listChunkTermine('past', 'basic', '', 50, isset($user) ? (int)$user : (int)$_SESSION['userid']);
?>
<div class="w3-container w3-padding-16" style="clear:both;">
<input class="w3-input w3-border w3-padding" type="text" placeholder="Termine suchen (Titel, Ort, Datum, Beschreibung)…" id="filterString" onkeyup="filterTermine()">
</div>
<div id="Liste">
<?php echo $chunk['html']; ?>
<?php echo listChunkRenderSentinel('termineArchiv', $chunk['nextCursor'], $chunk['hasMore'], 'filterTermine'); ?>
</div>
<script src="<?php echo assetUrl('js/filterTermine.js'); ?>"></script>
<script src="<?php echo assetUrl('js/infiniteScroll.js'); ?>"></script>
<?php
include "common/footer.php";
?>
