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
?>

<?php if($GLOBALS['optionsDB']['showOrchestraView']) { ?>
<div class="w3-center orchestra-svg-wrap">
<?php echo printOrchestra(0); ?>
</div>
<?php } ?>

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
adminListPageEnd();
include "common/footer.php";
?>
