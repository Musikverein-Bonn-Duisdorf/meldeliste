<?php
session_start();
$_SESSION['page']='shifts';
$_SESSION['adminpage']=true;
include "common/header.php";
requireAdmin();

if(isset($_POST['Termin'])) {
    $n = new Termin;
    $n->load_by_id($_POST['Termin']);

    if(isset($_POST['save'])) {
        $s = new Shift;
        $s->load_by_id($_POST['save']);
        $s->fill_from_array($_POST);
        $s->save();
    }
    if(isset($_POST['delete'])) {
        $s = new Shift;
        $s->load_by_id($_POST['delete']);
        $s->delete();
    }
}
?>
<div class="w3-container w3-margin-bottom <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
    <h2>Schichten bearbeiten</h2>
<p><?php echo $n->Name." (".germanDate($n->Datum, 1).")"; ?></p>
</div>

<?php echo $n->printShiftEdit(); ?>
<div class="w3-container w3-margin-bottom w3-margin-top <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
<p>neue Schicht anlegen</p>
</div>
<?php echo $n->shiftEditLine(0); ?>

<?php
include "common/footer.php";
?>
