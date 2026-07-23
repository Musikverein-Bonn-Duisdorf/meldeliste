<?php
ob_start();
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page']='shifts';
$_SESSION['adminpage']=true;

include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
requireLoggedInOrRedirect();
if(!requirePermission("perm_editAppmnts")) {
    denyAccess();
}

$terminId = 0;
if(isset($_GET['Termin'])) {
    $terminId = (int)$_GET['Termin'];
}
elseif(isset($_POST['Termin'])) {
    $terminId = (int)$_POST['Termin'];
}

if(isset($_POST['save']) || isset($_POST['delete'])) {
    if($terminId < 1) {
        setFlash('error', 'Kein Termin angegeben.');
        redirectAfterPost('index.php');
    }
    $n = new Termin;
    $n->load_by_id($terminId);
    if(!$n->Index) {
        setFlash('error', 'Termin nicht gefunden.');
        redirectAfterPost('index.php');
    }
    if(isset($_POST['save'])) {
        $s = new Shift;
        $s->load_by_id($_POST['save']);
        $s->fill_from_array($_POST);
        $s->save();
        setFlash('success', 'Schicht gespeichert.');
    }
    if(isset($_POST['delete'])) {
        $s = new Shift;
        $s->load_by_id($_POST['delete']);
        $s->delete();
        setFlash('success', 'Schicht gelöscht.');
    }
    redirectAfterPost('edit-shifts.php?Termin='.$terminId);
}

// Opening via POST only → PRG to GET (browser back-safe)
if($_SERVER['REQUEST_METHOD'] === 'POST' && $terminId > 0) {
    redirectAfterPost('edit-shifts.php?Termin='.$terminId);
}

$n = new Termin;
$n->load_by_id($terminId);
if(!$n->Index) {
    setFlash('error', 'Kein Termin zum Bearbeiten der Schichten.');
    redirectAfterPost('index.php');
}

include "common/header.php";
$shiftSub = htmlspecialchars($n->Name.' ('.germanDate($n->Datum, 1).')', ENT_QUOTES, 'UTF-8');
adminListPageBegin('Termine', 'Schichten bearbeiten', array('permKey' => 'perm_editAppmnts'));
?>
<div class="admin-list-intro">
  <p><?php echo $shiftSub; ?></p>
</div>
<?php echo renderFlashHtml(); ?>

<?php echo $n->printShiftEdit(); ?>
<div class="admin-list-intro w3-margin-top">
  <p>neue Schicht anlegen</p>
</div>
<?php echo $n->shiftEditLine(0); ?>
<?php
adminListPageEnd();
include "common/footer.php";
?>
