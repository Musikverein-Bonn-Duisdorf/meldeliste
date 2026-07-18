<?php
session_start();
$_SESSION['page']='register';
$_SESSION['adminpage']=true;
include "common/header.php";
if(!requirePermission("perm_showUsers")) {
    denyAccess();
}

?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
    <h2>Registerübersicht</h2>
</div>

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
?>

<?php
include "common/footer.php";
?>
