<?php
session_start();
$_SESSION['page'] = 'instrument-types';
$_SESSION['adminpage'] = true;
include 'common/header.php';
if(!requirePermission('perm_editInstruments')) {
    denyAccess();
}

$msg = '';
$err = '';

if(isset($_POST['insert'])) {
    $n = new Instrument;
    $n->fill_from_array($_POST);
    $n->Spielbar = isset($_POST['Spielbar']) ? 1 : 0;
    if(!$n->is_valid()) {
        $err = 'Name und Register sind Pflicht.';
    }
    elseif($n->save()) {
        $msg = 'Instrument-Typ angelegt.';
    }
    else {
        $err = 'Speichern fehlgeschlagen.';
    }
}
if(isset($_POST['update'])) {
    $n = new Instrument;
    $n->load_by_id($_POST['Index']);
    $n->fill_from_array($_POST);
    $n->Spielbar = isset($_POST['Spielbar']) ? 1 : 0;
    if(!$n->is_valid()) {
        $err = 'Name und Register sind Pflicht.';
    }
    elseif($n->save()) {
        $msg = 'Instrument-Typ aktualisiert.';
    }
    else {
        $err = 'Aktualisieren fehlgeschlagen.';
    }
}
if(isset($_POST['delete'])) {
    $n = new Instrument;
    $n->load_by_id($_POST['Index']);
    if($n->delete()) {
        $msg = 'Instrument-Typ gelöscht.';
    }
    else {
        $err = 'Löschen nicht möglich (noch in Verwendung).';
    }
}

?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2>Instrument-Typen</h2>
</div>
<?php if($msg) { ?><div class="w3-panel w3-green w3-padding"><?php echo htmlspecialchars($msg); ?></div><?php } ?>
<?php if($err) { ?><div class="w3-panel w3-red w3-padding"><?php echo htmlspecialchars($err); ?></div><?php } ?>

<div class="w3-container w3-padding">
  <p>Instrument-Typen (z.B. Flöte, Trompete) gehören zu einem Register. Farbe erscheint in dieser Übersicht; Register-Farben steuern die Orchesterdarstellung.</p>
  <p><a href="register-types.php">Register verwalten</a></p>
</div>

<div class="w3-row w3-padding w3-teal w3-hide-small">
  <div class="w3-col l1"><b>Farbe</b></div>
  <div class="w3-col l3"><b>Name</b></div>
  <div class="w3-col l2"><b>Register</b></div>
  <div class="w3-col l1"><b>Sort.</b></div>
  <div class="w3-col l1"><b>Spielbar</b></div>
  <div class="w3-col l2"><b>Nutzung</b></div>
  <div class="w3-col l2"><b>Aktionen</b></div>
</div>

<?php
$sql = sprintf(
    'SELECT `%sInstrument`.* FROM `%sInstrument` LEFT JOIN (SELECT `Index` AS `rIndex`, `Sortierung` AS `rSort` FROM `%sRegister`) `%sRegister` ON `rIndex` = `Register` ORDER BY COALESCE(`rSort`, 9999), `Sortierung`, `Name`;',
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix']
);
$dbr = mysqli_query($GLOBALS['conn'], $sql);
sqlerror();
while($row = mysqli_fetch_array($dbr)) {
    $t = new Instrument;
    $t->fill_from_array($row);
    $usage = $t->usageCount();
    $id = (int)$t->Index;
    $hex = normalizeHexColor($t->Color);
    $headerStyle = $t->headerStyle();
    $rowStyle = $headerStyle !== '' ? ' style="border-left:6px solid '.$hex.';"' : '';
?>
<div class="w3-row w3-padding w3-border-bottom w3-border-black <?php echo $GLOBALS['optionsDB']['HoverEffect']; ?>"<?php echo $rowStyle; ?>>
  <form method="post" class="w3-row">
    <input type="hidden" name="Index" value="<?php echo $id; ?>" />
    <div class="w3-col l1 m2 s4">
      <input type="color" name="Color" value="<?php echo htmlspecialchars($hex !== '' ? $hex : '#cccccc', ENT_QUOTES, 'UTF-8'); ?>" title="Farbe" />
    </div>
    <div class="w3-col l3 m4 s8">
      <input class="w3-input <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="Name" value="<?php echo htmlspecialchars(html_entity_decode((string)$t->Name, ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>" required />
    </div>
    <div class="w3-col l2 m3 s6">
      <select class="w3-select <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="Register" required>
        <?php RegisterOption($t->Register); ?>
      </select>
    </div>
    <div class="w3-col l1 m1 s3">
      <input class="w3-input <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="Sortierung" type="number" value="<?php echo (int)$t->Sortierung; ?>" />
    </div>
    <div class="w3-col l1 m1 s3 w3-padding">
      <input class="w3-check" type="checkbox" name="Spielbar" value="1" <?php echo (int)$t->Spielbar ? 'checked' : ''; ?> />
    </div>
    <div class="w3-col l2 m4 s12 w3-small">
      User: <?php echo (int)$usage['users']; ?>
      · Inv: <?php echo (int)$usage['inventories']; ?>
      <?php if($usage['meldungen'] || $usage['aushilfen']) {
          echo ' · M/A: '.((int)$usage['meldungen'] + (int)$usage['aushilfen']);
      } ?>
    </div>
    <div class="w3-col l2 m3 s12">
      <button class="w3-button w3-blue" type="submit" name="update" value="1">Speichern</button>
      <?php if($t->canDelete()) { ?>
      <button class="w3-button w3-red" type="submit" name="delete" value="1" onclick="return confirm('Instrument-Typ wirklich löschen?');">Löschen</button>
      <?php } ?>
    </div>
  </form>
</div>
<?php } ?>

<div class="w3-card w3-margin w3-padding">
  <h3>Neuen Instrument-Typ anlegen</h3>
  <form method="post" class="w3-row">
    <div class="w3-col l3 m4 s12 w3-padding">
      <label>Name</label>
      <input class="w3-input <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="Name" required />
    </div>
    <div class="w3-col l3 m4 s12 w3-padding">
      <label>Register</label>
      <select class="w3-select <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="Register" required>
        <?php RegisterOption(0); ?>
      </select>
    </div>
    <div class="w3-col l2 m2 s6 w3-padding">
      <label>Sortierung</label>
      <input class="w3-input <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="Sortierung" type="number" value="1" />
    </div>
    <div class="w3-col l1 m2 s3 w3-padding">
      <label>Farbe</label>
      <input type="color" name="Color" value="#cccccc" />
    </div>
    <div class="w3-col l1 m2 s3 w3-padding">
      <label>Spielbar</label><br />
      <input class="w3-check" type="checkbox" name="Spielbar" value="1" checked />
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
