<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page']='tracking';
$_SESSION['adminpage']=true;
include "common/header.php";
if(!requirePermission("perm_showResponse")) {
    denyAccess();
}

$termin = new Termin;
$termin->load_by_id($_POST['termin']);
?>
<script src="<?php echo assetUrl('js/getStatus.js'); ?>"></script>
<script src="<?php echo assetUrl('js/track.js'); ?>"></script>
<script src="<?php echo assetUrl('js/meldeshift.js'); ?>"></script>
<?php
adminListPageBegin('Anwesenheit', 'Anwesenheitsliste — '.$termin->Name.' ('.germanDate($termin->Datum, 1).')');
echo $termin->TrackingTable();
adminListPageEnd();
include "common/footer.php";
?>
