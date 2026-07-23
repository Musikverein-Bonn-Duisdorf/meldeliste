<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page']='inventory-types';
$_SESSION['adminpage']=true;
include "common/header.php";
if(!requirePermission("perm_editInventories")) {
    denyAccess();
}

$msg = '';
$err = '';

if(isset($_POST['insert'])) {
    $n = new Inventory;
    $n->fill_from_array($_POST);
    $n->Protected = 0;
    if(!$n->is_valid()) {
        $err = 'Prefix und Beschriftung sind Pflicht (Prefix nur A–Z / 0–9).';
    }
    elseif($n->prefixInUse($n->Prefix)) {
        $err = 'Prefix bereits vergeben.';
    }
    elseif($n->save()) {
        $msg = 'Typ angelegt.';
    }
    else {
        $err = 'Speichern fehlgeschlagen.';
    }
}
if(isset($_POST['update'])) {
    $n = new Inventory;
    $n->load_by_id($_POST['Index']);
    $protected = (int)$n->Protected;
    $n->fill_from_array($_POST);
    $n->Protected = $protected; // keep protected flag
    if(!$n->is_valid()) {
        $err = 'Prefix und Beschriftung sind Pflicht (Prefix nur A–Z / 0–9).';
    }
    elseif($n->prefixInUse($n->Prefix, (int)$n->Index)) {
        $err = 'Prefix bereits vergeben.';
    }
    elseif($n->save()) {
        $msg = 'Typ aktualisiert.';
    }
    else {
        $err = 'Aktualisieren fehlgeschlagen.';
    }
}
if(isset($_POST['delete'])) {
    $n = new Inventory;
    $n->load_by_id($_POST['Index']);
    if($n->delete()) {
        $msg = 'Typ gelöscht.';
    }
    else {
        $err = 'Löschen nicht möglich (geschützt oder noch in Verwendung).';
    }
}
?>
<?php
adminListPageBegin('Inventar', 'Inventar-Typen');
?>
<?php if($msg) { ?><div class="w3-panel w3-green w3-padding"><?php echo htmlspecialchars($msg); ?></div><?php } ?>
<?php if($err) { ?><div class="w3-panel w3-red w3-padding"><?php echo htmlspecialchars($err); ?></div><?php } ?>

<div class="w3-row w3-padding w3-teal type-edit-header">
  <div class="w3-col l2 m2 s3"><b>Prefix</b></div>
  <div class="w3-col l3 m3 s4"><b>Beschriftung</b></div>
  <div class="w3-col l2 m2 s2"><b>Sortierung</b></div>
  <div class="w3-col l2 m2 s3"><b>Status</b></div>
  <div class="w3-col l3 m3 s12"><b>Aktionen</b></div>
</div>

<?php
$sql = sprintf('SELECT * FROM `%sInventory` ORDER BY `Sortierung`, `Typ`;', $GLOBALS['dbprefix']);
$dbr = mysqli_query($GLOBALS['conn'], $sql);
sqlerror();
while($row = mysqli_fetch_array($dbr)) {
    $t = new Inventory;
    $t->fill_from_array($row);
    $usage = $t->usageCount();
    $id = (int)$t->Index;
?>
<div class="w3-row w3-padding w3-border-bottom w3-border-black <?php echo $GLOBALS['optionsDB']['HoverEffect']; ?>">
  <form method="post" class="w3-row">
    <input type="hidden" name="Index" value="<?php echo $id; ?>" />
    <div class="w3-col l2 m2 s3">
      <input class="w3-input <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="Prefix" value="<?php echo htmlspecialchars($t->Prefix); ?>" />
    </div>
    <div class="w3-col l3 m3 s4">
      <input class="w3-input <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="Typ" value="<?php echo htmlspecialchars($t->Typ); ?>" />
    </div>
    <div class="w3-col l2 m2 s2">
      <input class="w3-input <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="Sortierung" type="number" value="<?php echo (int)$t->Sortierung; ?>" />
    </div>
    <div class="w3-col l2 m2 s3 w3-small">
      <?php
        if((int)$t->Protected) echo 'geschützt';
        echo ' · Inv: '.$usage['inventories'];
        if($usage['instruments']) echo ' · Instr: '.$usage['instruments'];
      ?>
    </div>
    <div class="w3-col l3 m3 s12">
      <button class="w3-button w3-blue" type="submit" name="update" value="1">Speichern</button>
      <?php if($t->canDelete()) { ?>
      <button class="w3-button w3-red" type="submit" name="delete" value="1" onclick="return confirm('Typ wirklich löschen?');">Löschen</button>
      <?php } ?>
    </div>
  </form>
</div>
<?php } ?>

<div class="w3-card w3-margin w3-padding">
  <h3>Neuen Typ anlegen</h3>
  <form method="post" class="w3-row">
    <div class="w3-col l3 m4 s12 w3-padding">
      <label>Prefix</label>
      <input class="w3-input <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="Prefix" placeholder="MARSCH" required />
    </div>
    <div class="w3-col l4 m4 s12 w3-padding">
      <label>Beschriftung</label>
      <input class="w3-input <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="Typ" placeholder="Marschtasche" required />
    </div>
    <div class="w3-col l2 m2 s12 w3-padding">
      <label>Sortierung</label>
      <input class="w3-input <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="Sortierung" type="number" value="1" />
    </div>
    <div class="w3-col l3 m2 s12 w3-padding">
      <label>&nbsp;</label><br />
      <button class="w3-button w3-green" type="submit" name="insert" value="1">Anlegen</button>
    </div>
  </form>
</div>
<?php
adminListPageEnd();
include "common/footer.php";
?>
