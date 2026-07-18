<?php
session_start();
$_SESSION['page'] = 'register-types';
$_SESSION['adminpage'] = true;
include 'common/header.php';
if(!requirePermission('perm_editInstruments')) {
    denyAccess();
}

$msg = '';
$err = '';

if(isset($_POST['insert'])) {
    $n = new Register;
    $n->fill_from_array($_POST);
    if(!$n->is_valid()) {
        $err = 'Name ist Pflicht.';
    }
    elseif($n->save()) {
        $msg = 'Register angelegt.';
    }
    else {
        $err = 'Speichern fehlgeschlagen.';
    }
}
if(isset($_POST['update'])) {
    $n = new Register;
    $n->load_by_id($_POST['Index']);
    $protected = $n->isProtectedName();
    $oldName = $n->Name;
    $n->fill_from_array($_POST);
    if($protected) {
        $n->Name = $oldName;
    }
    if(!$n->is_valid()) {
        $err = 'Name ist Pflicht.';
    }
    elseif($n->save()) {
        $msg = 'Register aktualisiert.';
    }
    else {
        $err = 'Aktualisieren fehlgeschlagen.';
    }
}
if(isset($_POST['delete'])) {
    $n = new Register;
    $n->load_by_id($_POST['Index']);
    if($n->delete()) {
        $msg = 'Register gelöscht.';
    }
    else {
        $err = 'Löschen nicht möglich (geschützt oder noch Instrumente zugeordnet).';
    }
}
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2>Register</h2>
</div>
<?php if($msg) { ?><div class="w3-panel w3-green w3-padding"><?php echo htmlspecialchars($msg); ?></div><?php } ?>
<?php if($err) { ?><div class="w3-panel w3-red w3-padding"><?php echo htmlspecialchars($err); ?></div><?php } ?>

<div class="w3-container w3-padding">
  <p>Register steuern Gruppierung, Orchester-Sitzplan und Farben in der <a href="register.php">Registerübersicht</a>.</p>
  <p><a href="instrument-types.php">Instrument-Typen verwalten</a></p>
</div>

<div class="w3-row w3-padding w3-teal w3-hide-small">
  <div class="w3-col l1"><b>Farbe</b></div>
  <div class="w3-col l2"><b>Name</b></div>
  <div class="w3-col l1"><b>Sort.</b></div>
  <div class="w3-col l1"><b>Reihe</b></div>
  <div class="w3-col l1"><b>ArcMin</b></div>
  <div class="w3-col l1"><b>ArcMax</b></div>
  <div class="w3-col l2"><b>Nutzung</b></div>
  <div class="w3-col l3"><b>Aktionen</b></div>
</div>

<?php
$sql = sprintf('SELECT * FROM `%sRegister` ORDER BY `Sortierung`, `Name`;', $GLOBALS['dbprefix']);
$dbr = mysqli_query($GLOBALS['conn'], $sql);
sqlerror();
while($row = mysqli_fetch_array($dbr)) {
    $t = new Register;
    $t->fill_from_array($row);
    $usage = $t->usageCount();
    $id = (int)$t->Index;
    $hex = normalizeHexColor($t->Color);
    $rowStyle = $hex !== '' ? ' style="border-left:6px solid '.$hex.';"' : '';
    $protected = $t->isProtectedName();
?>
<div class="w3-row w3-padding w3-border-bottom w3-border-black <?php echo $GLOBALS['optionsDB']['HoverEffect']; ?>"<?php echo $rowStyle; ?>>
  <form method="post" class="w3-row">
    <input type="hidden" name="Index" value="<?php echo $id; ?>" />
    <div class="w3-col l1 m2 s4">
      <input type="color" name="Color" value="<?php echo htmlspecialchars($hex !== '' ? $hex : '#cccccc', ENT_QUOTES, 'UTF-8'); ?>" title="Farbe" />
    </div>
    <div class="w3-col l2 m4 s8">
      <input class="w3-input <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="Name" value="<?php echo htmlspecialchars(html_entity_decode((string)$t->Name, ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>" <?php echo $protected ? 'readonly' : 'required'; ?> />
    </div>
    <div class="w3-col l1 m2 s4">
      <input class="w3-input <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="Sortierung" type="number" value="<?php echo (int)$t->Sortierung; ?>" />
    </div>
    <div class="w3-col l1 m2 s4">
      <input class="w3-input <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="Row" type="number" value="<?php echo (int)$t->Row; ?>" />
    </div>
    <div class="w3-col l1 m2 s4">
      <input class="w3-input <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="ArcMin" type="number" step="any" value="<?php echo htmlspecialchars((string)$t->ArcMin, ENT_QUOTES, 'UTF-8'); ?>" />
    </div>
    <div class="w3-col l1 m2 s4">
      <input class="w3-input <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="ArcMax" type="number" step="any" value="<?php echo htmlspecialchars((string)$t->ArcMax, ENT_QUOTES, 'UTF-8'); ?>" />
    </div>
    <div class="w3-col l2 m4 s12 w3-small">
      Instr: <?php echo (int)$usage['instruments']; ?>
      · Mitgl.: <?php echo (int)$usage['members']; ?>
      <?php if($protected) echo ' · geschützt'; ?>
    </div>
    <div class="w3-col l3 m4 s12">
      <button class="w3-button w3-blue" type="submit" name="update" value="1">Speichern</button>
      <?php if($t->canDelete()) { ?>
      <button class="w3-button w3-red" type="submit" name="delete" value="1" onclick="return confirm('Register wirklich löschen?');">Löschen</button>
      <?php } ?>
    </div>
  </form>
</div>
<?php } ?>

<div class="w3-card w3-margin w3-padding">
  <h3>Neues Register anlegen</h3>
  <form method="post" class="w3-row">
    <div class="w3-col l3 m4 s12 w3-padding">
      <label>Name</label>
      <input class="w3-input <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="Name" required />
    </div>
    <div class="w3-col l1 m2 s4 w3-padding">
      <label>Sortierung</label>
      <input class="w3-input <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="Sortierung" type="number" value="1" />
    </div>
    <div class="w3-col l1 m2 s4 w3-padding">
      <label>Reihe</label>
      <input class="w3-input <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="Row" type="number" value="1" />
    </div>
    <div class="w3-col l1 m2 s4 w3-padding">
      <label>ArcMin</label>
      <input class="w3-input <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="ArcMin" type="number" step="any" value="0" />
    </div>
    <div class="w3-col l1 m2 s4 w3-padding">
      <label>ArcMax</label>
      <input class="w3-input <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="ArcMax" type="number" step="any" value="90" />
    </div>
    <div class="w3-col l1 m2 s4 w3-padding">
      <label>Farbe</label>
      <input type="color" name="Color" value="#98C261" />
    </div>
    <div class="w3-col l2 m2 s12 w3-padding">
      <label>&nbsp;</label><br />
      <button class="w3-button w3-green" type="submit" name="insert" value="1">Anlegen</button>
    </div>
  </form>
</div>
<?php
include 'common/footer.php';
?>
