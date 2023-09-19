<?php
session_start();
$_SESSION['page']='tracking';
$_SESSION['adminpage']=true;
include "common/header.php";
if(!requirePermission("perm_showResponse")) die();

$termin = new Termin;
$termin->load_by_id($_POST['termin']);
?>
<script src="js/getStatus.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script src="js/track.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script src="js/meldeshift.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
         <h2>Anwesenheitsliste - <?php echo $termin->Name." (".germanDate($termin->Datum, 1).")"; ?></h2>
</div>
<?php
echo $termin->TrackingTable();
?>
<?php
include "common/footer.php";
?>
