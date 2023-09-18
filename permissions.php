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
<div class="w3-container w3-margin-bottom <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2>Berechtigungen bearbeiten</h2>
</div>
<?php
include "common/footer.php";
?>
