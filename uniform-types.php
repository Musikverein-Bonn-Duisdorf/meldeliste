<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page']='uniform-types';
$_SESSION['adminpage']=true;
include "common/header.php";
if(!requirePermission("perm_editAppmnts")) {
    denyAccess();
}

$msg = '';
$err = '';

$applyUploadedThumb = function (Uniform $n) use (&$err) {
    if(empty($_FILES['thumb']['tmp_name']) || !is_uploaded_file((string)$_FILES['thumb']['tmp_name'])) {
        return;
    }
    if(!$n->storeThumb($_FILES['thumb'])) {
        $err = 'Vorschau konnte nicht gespeichert werden.';
    }
};

if(isset($_POST['deleteThumb'])) {
    $n = new Uniform;
    $n->load_by_id($_POST['Index']);
    if((int)$n->Index && $n->deleteThumb()) {
        $msg = 'Vorschau entfernt.';
    }
    else {
        $err = 'Vorschau konnte nicht entfernt werden.';
    }
}
if(isset($_POST['insert'])) {
    $n = new Uniform;
    $n->fill_from_array($_POST);
    if(!$n->is_valid()) {
        $err = 'Name ist Pflicht.';
    }
    elseif($n->save()) {
        $msg = 'Kategorie angelegt.';
        $applyUploadedThumb($n);
    }
    else {
        $err = 'Speichern fehlgeschlagen.';
    }
}
if(isset($_POST['update'])) {
    $n = new Uniform;
    $n->load_by_id($_POST['Index']);
    $thumbFile = $n->ThumbFile;
    $n->fill_from_array($_POST);
    $n->ThumbFile = $thumbFile;
    if(!$n->is_valid()) {
        $err = 'Name ist Pflicht.';
    }
    elseif($n->save()) {
        $msg = 'Kategorie aktualisiert.';
        $applyUploadedThumb($n);
    }
    else {
        $err = 'Aktualisieren fehlgeschlagen.';
    }
}
if(isset($_POST['delete'])) {
    $n = new Uniform;
    $n->load_by_id($_POST['Index']);
    if($n->delete()) {
        $msg = 'Kategorie gelöscht.';
    }
    else {
        $err = 'Löschen nicht möglich (noch an Terminen verwendet).';
    }
}
?>
<?php
adminListPageBegin('Termine', 'Kleidung');
?>
<?php if($msg) { echo renderFlashHtml(array('type' => 'success', 'message' => $msg)); } ?>
<?php if($err) { echo renderFlashHtml(array('type' => 'error', 'message' => $err)); } ?>

<div class="w3-row w3-padding w3-teal type-edit-header">
  <div class="w3-col l2 m3 s12"><b>Vorschau</b></div>
  <div class="w3-col l4 m4 s6"><b>Name</b></div>
  <div class="w3-col l2 m2 s3"><b>Sortierung</b></div>
  <div class="w3-col l1 m1 s3"><b>Termine</b></div>
  <div class="w3-col l3 m12 s12"><b>Aktionen</b></div>
</div>

<?php
foreach(Uniform::allOrdered() as $t) {
    $id = (int)$t->Index;
    $usage = $t->usageCount();
    $thumbUrl = $t->thumbAbsolutePath() ? Uniform::thumbUrl($id) : '';
?>
<div class="w3-row w3-padding w3-border-bottom w3-border-black <?php echo $GLOBALS['optionsDB']['HoverEffect']; ?>">
  <form method="post" enctype="multipart/form-data" class="w3-row">
    <input type="hidden" name="Index" value="<?php echo $id; ?>" />
    <div class="w3-col l2 m3 s12 inv-type-thumb-cell">
      <?php if($thumbUrl !== '') { ?>
      <img class="inv-thumb" src="<?php echo htmlspecialchars($thumbUrl); ?>" alt="" width="56" height="56">
      <?php } ?>
      <input class="inv-type-thumb-file" type="file" name="thumb" accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp" aria-label="Vorschau">
      <?php if($thumbUrl !== '') { ?>
      <button class="w3-button w3-small w3-border" type="submit" name="deleteThumb" value="1">Entfernen</button>
      <?php } ?>
    </div>
    <div class="w3-col l4 m4 s6">
      <input class="w3-input <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="Name" value="<?php echo htmlspecialchars($t->Name); ?>" />
    </div>
    <div class="w3-col l2 m2 s3">
      <input class="w3-input <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="Sortierung" type="number" value="<?php echo (int)$t->Sortierung; ?>" />
    </div>
    <div class="w3-col l1 m1 s3 w3-small">
      <?php echo (int)$usage; ?>
    </div>
    <div class="w3-col l3 m12 s12">
      <button class="w3-button w3-blue" type="submit" name="update" value="1">Speichern</button>
      <?php if($t->canDelete()) { ?>
      <button class="w3-button w3-red" type="submit" name="delete" value="1" data-confirm="Kategorie wirklich löschen?" data-confirm-ok="Löschen">Löschen</button>
      <?php } ?>
    </div>
  </form>
</div>
<?php } ?>

<div class="w3-card w3-margin w3-padding">
  <h3>Neue Kategorie</h3>
  <form method="post" enctype="multipart/form-data" class="w3-row">
    <div class="w3-col l4 m5 s12 w3-padding">
      <label>Name</label>
      <input class="w3-input <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="Name" placeholder="Uniform komplett" required />
    </div>
    <div class="w3-col l2 m2 s12 w3-padding">
      <label>Sortierung</label>
      <input class="w3-input <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="Sortierung" type="number" value="1" />
    </div>
    <div class="w3-col l4 m5 s12 w3-padding">
      <label>Vorschau</label>
      <input class="w3-input inv-type-thumb-file" type="file" name="thumb" accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp" />
    </div>
    <div class="w3-col l2 m2 s12 w3-padding">
      <label>&nbsp;</label><br />
      <button class="w3-button w3-green" type="submit" name="insert" value="1">Anlegen</button>
    </div>
  </form>
</div>
<?php
adminListPageEnd();
include "common/footer.php";
?>
