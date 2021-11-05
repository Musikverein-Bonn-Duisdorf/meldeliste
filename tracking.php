<?php
session_start();
$_SESSION['page']='tracking';
$_SESSION['adminpage']=true;
include "common/header.php";
$termin = new Termin;
$termin->load_by_id($_POST['termin']);
?>
<script src="js/getStatus.js"></script>
<script src="js/track.js"></script>
<script src="js/meldeshift.js"></script>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
         <h2>Anwesenheitsliste - <?php echo $termin->Name." (".$termin->Datum.")"; ?></h2>
</div>
<?php
echo $termin->TrackingTable();
?>
<?php
include "common/footer.php";
?>
