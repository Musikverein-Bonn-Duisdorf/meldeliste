<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page'] = 'register-types';
$_SESSION['adminpage'] = true;
include 'common/header.php';
if(!requirePermission('perm_editRegisters')) {
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

$inputCls = $GLOBALS['optionsDB']['colorInputBackground'];
adminListPageBegin('Register', 'Register');
?>
<?php if($msg) { ?><div class="w3-panel w3-green w3-padding"><?php echo htmlspecialchars($msg); ?></div><?php } ?>
<?php if($err) { ?><div class="w3-panel w3-red w3-padding"><?php echo htmlspecialchars($err); ?></div><?php } ?>

<div class="admin-list-intro">
  <p>Register steuern Gruppierung, Orchester-Sitzplan und Farben in der <a href="register.php">Registerübersicht</a>.</p>
  <p><a href="instrument-types.php">Instrument-Typen verwalten</a></p>
  <p class="w3-small"><b>Reihe</b> = Abstand vom Dirigenten (0 = Dirigent). <b>ArcMin/ArcMax</b> = Winkelbereich (0° links, 90° vorne, 180° rechts). Nach dem Speichern aktualisiert sich die Vorschau.</p>
</div>

<div class="w3-center orchestra-svg-wrap">
<?php echo printRegisterLayoutPreview(); ?>
</div>

<div class="type-edit-header type-edit-form type-edit-form--register w3-padding w3-teal">
  <div class="type-edit-field"><b>Farbe</b></div>
  <div class="type-edit-field type-edit-field--grow"><b>Name</b></div>
  <div class="type-edit-field"><b>Sort.</b></div>
  <div class="type-edit-field"><b>Reihe</b></div>
  <div class="type-edit-field"><b>ArcMin</b></div>
  <div class="type-edit-field"><b>ArcMax</b></div>
  <div class="type-edit-field type-edit-field--grow"><b>Nutzung</b></div>
  <div class="type-edit-field type-edit-field--actions"><b>Aktionen</b></div>
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
<div class="type-edit-row w3-padding w3-border-bottom w3-border-black <?php echo $GLOBALS['optionsDB']['HoverEffect']; ?>"<?php echo $rowStyle; ?>>
  <form method="post" class="type-edit-form type-edit-form--register">
    <input type="hidden" name="Index" value="<?php echo $id; ?>" />
    <div class="type-edit-field">
      <label class="type-edit-label">Farbe</label>
      <input type="color" name="Color" value="<?php echo htmlspecialchars($hex !== '' ? $hex : '#cccccc', ENT_QUOTES, 'UTF-8'); ?>" title="Farbe" />
    </div>
    <div class="type-edit-field type-edit-field--grow">
      <label class="type-edit-label">Name</label>
      <input class="w3-input <?php echo $inputCls; ?>" name="Name" value="<?php echo htmlspecialchars(html_entity_decode((string)$t->Name, ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>" <?php echo $protected ? 'readonly' : 'required'; ?> />
    </div>
    <div class="type-edit-field">
      <label class="type-edit-label">Sortierung</label>
      <input class="w3-input <?php echo $inputCls; ?>" name="Sortierung" type="number" value="<?php echo (int)$t->Sortierung; ?>" />
    </div>
    <div class="type-edit-field">
      <label class="type-edit-label">Reihe</label>
      <input class="w3-input <?php echo $inputCls; ?>" name="Row" type="number" value="<?php echo (int)$t->Row; ?>" />
    </div>
    <div class="type-edit-field">
      <label class="type-edit-label">ArcMin</label>
      <input class="w3-input <?php echo $inputCls; ?>" name="ArcMin" type="number" step="any" value="<?php echo htmlspecialchars((string)$t->ArcMin, ENT_QUOTES, 'UTF-8'); ?>" />
    </div>
    <div class="type-edit-field">
      <label class="type-edit-label">ArcMax</label>
      <input class="w3-input <?php echo $inputCls; ?>" name="ArcMax" type="number" step="any" value="<?php echo htmlspecialchars((string)$t->ArcMax, ENT_QUOTES, 'UTF-8'); ?>" />
    </div>
    <div class="type-edit-field type-edit-field--grow type-edit-field--meta w3-small">
      <span class="type-edit-label">Nutzung</span>
      Instr: <?php echo (int)$usage['instruments']; ?>
      · Mitgl.: <?php echo (int)$usage['members']; ?>
      <?php if($protected) echo ' · geschützt'; ?>
    </div>
    <div class="type-edit-field type-edit-field--actions">
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
  <form method="post" class="type-edit-form type-edit-form--register type-edit-form--create">
    <div class="type-edit-field type-edit-field--grow">
      <label class="type-edit-label type-edit-label--always">Name</label>
      <input class="w3-input <?php echo $inputCls; ?>" name="Name" required />
    </div>
    <div class="type-edit-field">
      <label class="type-edit-label type-edit-label--always">Sortierung</label>
      <input class="w3-input <?php echo $inputCls; ?>" name="Sortierung" type="number" value="1" />
    </div>
    <div class="type-edit-field">
      <label class="type-edit-label type-edit-label--always">Reihe</label>
      <input class="w3-input <?php echo $inputCls; ?>" name="Row" type="number" value="1" />
    </div>
    <div class="type-edit-field">
      <label class="type-edit-label type-edit-label--always">ArcMin</label>
      <input class="w3-input <?php echo $inputCls; ?>" name="ArcMin" type="number" step="any" value="0" />
    </div>
    <div class="type-edit-field">
      <label class="type-edit-label type-edit-label--always">ArcMax</label>
      <input class="w3-input <?php echo $inputCls; ?>" name="ArcMax" type="number" step="any" value="90" />
    </div>
    <div class="type-edit-field">
      <label class="type-edit-label type-edit-label--always">Farbe</label>
      <input type="color" name="Color" value="#98C261" />
    </div>
    <div class="type-edit-field type-edit-field--actions">
      <button class="w3-button w3-green" type="submit" name="insert" value="1">Anlegen</button>
    </div>
  </form>
</div>
<?php
adminListPageEnd();
include 'common/footer.php';
?>
