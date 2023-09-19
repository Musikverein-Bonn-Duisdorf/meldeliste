<?php
session_start();
$_SESSION['page']='permissions';
$_SESSION['adminpage']=true;
include "common/header.php";
if(!requirePermission("perm_editPermissions")) die();

if(isset($_POST['id'])) {
    $n = new User;
    $n->load_by_id($_POST['id']);
    if($n->Index > 0) {
        $fill = true;
    }
}
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2>Berechtigungen bearbeiten</h2>
</div>
    <?php
    $sql = sprintf('SELECT `Index` FROM `%sUser` WHERE `Deleted` != 1 ORDER BY `Nachname`, `Vorname`;',
                   $GLOBALS['dbprefix']
    );
$dbr = mysqli_query($conn, $sql);
sqlerror();
$perm = new Permissions;
echo $perm->printHeaderLine();
while($row = mysqli_fetch_array($dbr)) {
    $perm = new Permissions;
    $perm->load_by_user($row['Index']);
    echo $perm->printEditLine();
}
    ?>
<?php
include "common/footer.php";
?>
