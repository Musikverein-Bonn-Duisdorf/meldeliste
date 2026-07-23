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

if(isset($_POST['save_all']) || isset($_POST['delete'])) {
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
    if(isset($_POST['save_all'])) {
        $rows = (isset($_POST['shifts']) && is_array($_POST['shifts'])) ? $_POST['shifts'] : array();
        $saved = 0;
        $created = 0;
        $errors = 0;
        foreach($rows as $key => $row) {
            if(!is_array($row)) {
                continue;
            }
            $name = isset($row['Name']) ? trim((string)$row['Name']) : '';
            $isNew = ($key === 'new' || strpos((string)$key, 'new_') === 0);
            if($isNew) {
                if($name === '') {
                    continue;
                }
                $s = new Shift;
                $s->Termin = $terminId;
                $s->Name = $name;
                $s->Start = isset($row['Start']) ? $row['Start'] : '';
                $s->End = isset($row['End']) ? $row['End'] : '';
                $s->Bedarf = isset($row['Bedarf']) ? $row['Bedarf'] : 0;
                if($s->save() === false || (int)$s->Index < 1) {
                    $errors++;
                    continue;
                }
                $created++;
                continue;
            }
            $id = (int)$key;
            if($id < 1) {
                continue;
            }
            if($name === '') {
                $errors++;
                continue;
            }
            $s = new Shift;
            $s->load_by_id($id);
            if(!(int)$s->Index || (int)$s->Termin !== $terminId) {
                $errors++;
                continue;
            }
            $s->Name = $name;
            $s->Start = isset($row['Start']) ? $row['Start'] : '';
            $s->End = isset($row['End']) ? $row['End'] : '';
            $s->Bedarf = isset($row['Bedarf']) ? $row['Bedarf'] : 0;
            $s->Termin = $terminId;
            if(!$s->hasChanges()) {
                continue;
            }
            if($s->save() === false) {
                $errors++;
                continue;
            }
            $saved++;
        }
        if($errors > 0 && ($saved + $created) === 0) {
            setFlash('error', 'Schichten & Aufgaben konnten nicht gespeichert werden.');
        }
        elseif($errors > 0) {
            setFlash('success', 'Schichten & Aufgaben teilweise gespeichert ('.$saved.' geändert, '.$created.' neu, '.$errors.' Fehler).');
        }
        else {
            setFlash('success', 'Schichten & Aufgaben gespeichert'
                .(($saved + $created) > 0 ? ' ('.$saved.' geändert, '.$created.' neu).' : '.'));
        }
    }
    if(isset($_POST['delete'])) {
        $s = new Shift;
        $s->load_by_id($_POST['delete']);
        if((int)$s->Index > 0 && (int)$s->Termin === $terminId) {
            $s->delete();
            setFlash('success', 'Schicht/Aufgabe gelöscht.');
        }
        else {
            setFlash('error', 'Schicht/Aufgabe nicht gefunden.');
        }
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
    setFlash('error', 'Kein Termin zum Bearbeiten der Schichten & Aufgaben.');
    redirectAfterPost('index.php');
}

include "common/header.php";
$shiftSub = htmlspecialchars($n->Name.' ('.germanDate($n->Datum, 1).')', ENT_QUOTES, 'UTF-8');
adminListPageBegin('Termine', 'Schichten & Aufgaben bearbeiten', array('permKey' => 'perm_editAppmnts'));
?>
<div class="admin-list-intro">
  <p class="profile-value"><?php echo $shiftSub; ?></p>
</div>
<?php echo renderFlashHtml(); ?>

<section class="shift-edit-list" aria-label="Schichten und Aufgaben">
<?php echo $n->printShiftEdit(); ?>
</section>
<script src="<?php echo assetUrl('js/shiftEdit.js'); ?>"></script>
<?php
adminListPageEnd();
include "common/footer.php";
?>
