<?php
session_start();
$_SESSION['page'] = 'groups';
$_SESSION['adminpage'] = true;
include 'common/header.php';
if(!requirePermission('perm_sendEmail')) {
    denyAccess();
}

MailGroup::ensureSchema();

$msg = '';
$err = '';

if(isset($_POST['delete']) && isset($_POST['Index'])) {
    $g = new MailGroup();
    $g->load_by_id((int)$_POST['Index']);
    if((int)$g->Index && $g->delete()) {
        $msg = 'Gruppe gelöscht.';
    }
    else {
        $err = 'Löschen fehlgeschlagen.';
    }
}

$groups = MailGroup::listAll();
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2>Gruppen</h2>
</div>
<?php if($msg) { ?><div class="w3-panel w3-green w3-padding"><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>
<?php if($err) { ?><div class="w3-panel w3-red w3-padding"><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>

<div class="w3-container w3-padding">
  <p>Benannte Gruppen mit Rollen, Registern und Personen – nutzbar beim Mailversand und bei der Termin-Sichtbarkeit.</p>
  <a class="w3-button <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?>" href="group-edit.php">Neue Gruppe</a>
  <a class="w3-button w3-margin-left" href="mail.php">Email versenden</a>
</div>

<div class="w3-container w3-padding">
  <div class="mail-list">
    <div class="mail-list-header <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
      <div>Name</div>
      <div>Mitglieder</div>
      <div>Aktion</div>
    </div>
<?php
if(!count($groups)) {
    echo '<div class="mail-list-item"><div class="mail-list-primary">Noch keine Gruppen angelegt.</div></div>';
}
foreach($groups as $g) {
    $id = (int)$g->Index;
    $count = (int)$g->memberCount(false);
    $countMail = (int)$g->memberCount(true);
?>
    <div class="mail-list-item">
      <div class="mail-list-primary"><?php echo htmlspecialchars((string)$g->Name, ENT_QUOTES, 'UTF-8'); ?></div>
      <div><?php echo $count; ?> <span class="w3-small w3-text-gray">(<?php echo $countMail; ?> mit Mail)</span></div>
      <div>
        <a class="w3-button w3-small w3-blue" href="group-edit.php?id=<?php echo $id; ?>">Bearbeiten</a>
        <form method="post" style="display:inline;" onsubmit="return confirm('Gruppe wirklich löschen?');">
          <input type="hidden" name="Index" value="<?php echo $id; ?>" />
          <button class="w3-button w3-small w3-red" type="submit" name="delete" value="1">Löschen</button>
        </form>
      </div>
    </div>
<?php } ?>
  </div>
</div>
<?php
include 'common/footer.php';
?>
