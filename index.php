<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page']='home';
$_SESSION['adminpage']=false;
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
    $n->load_by_id($_POST['Index']);
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
<script src="js/getStatus.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script src="js/melde.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script src="js/meldeFT.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script src="js/meldeshift.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script src="js/changeInstrument.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>

<?php
if(isset($_POST['proxy'])) {
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorLogWarning']; ?>">
    <h2>Bevorstehende Termine <?php echo $proxy->getName();?></h2>
</div>
<?php
} else {
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
    <h2>Bevorstehende Termine</h2>
</div>
<?php
}
$chunkUser = isset($user) ? (int)$user : (int)$_SESSION['userid'];
$chunk = listChunkTermine('future', 'basic', '', 50, $chunkUser);
?>
<div class="w3-container w3-padding-16" style="clear:both;">
<input class="w3-input w3-border w3-padding" type="text" placeholder="Termine suchen (Titel, Ort, Datum, Beschreibung)…" id="filterString" onkeyup="filterTermine()">
</div>
<div id="Liste">
<?php echo $chunk['html']; ?>
<?php echo listChunkRenderSentinel('termine', $chunk['nextCursor'], $chunk['hasMore'], 'filterTermine', ' data-extra="user='.$chunkUser.'"'); ?>
</div>
<script src="js/filterTermine.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script src="js/infiniteScroll.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<?php
include "common/footer.php";
?>
