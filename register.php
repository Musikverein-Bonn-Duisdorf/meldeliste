<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page']='register';
$_SESSION['adminpage']=true;
include "common/header.php";
if(!requirePermission("perm_showUsers")) {
    denyAccess();
}

adminListPageBegin('Personen', 'Registerübersicht');
adminListSearchField('Nach Musiker suchen…', array('onkeyup' => 'filterMusiker()'));
?>

<?php if($GLOBALS['optionsDB']['showOrchestraView']) { ?>
<div class="w3-center orchestra-svg-wrap">
<?php echo printOrchestra(0); ?>
</div>
<?php } ?>

<div id="Liste">
<?php
    $sql = sprintf('SELECT `Index` FROM `%sRegister` WHERE `Name` != "keins" ORDER BY `Sortierung`;',
        $GLOBALS['dbprefix']
            );
$dbr = mysqli_query($conn, $sql);
sqlerror();
while($row = mysqli_fetch_array($dbr)) {
    $M = new Register;
    $M->load_by_id($row['Index']);
    $M->memberTable();
}
?>
</div>
<?php
adminListPageEnd();
?>
<script src="js/filterMusiker.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<?php
include "common/footer.php";
?>
