<?php
session_start();
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
<script src="js/getStatus.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script src="js/melde.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script src="js/meldeshift.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script src="js/changeInstrument.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>

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
<div id="Liste">
<?php echo $chunk['html']; ?>
<?php echo listChunkRenderSentinel('termineArchiv', $chunk['nextCursor'], $chunk['hasMore']); ?>
</div>
<script src="js/infiniteScroll.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<?php
include "common/footer.php";
?>
