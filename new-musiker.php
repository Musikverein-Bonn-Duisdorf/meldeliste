<?php
session_start();
$_SESSION['page']='newmusiker';
include "common/header.php";
$fill = false;
if(isset($_POST['id'])) {
    $n = new User;
    $n->load_by_id($_POST['id']);
    if($n->Index > 0) {
        $fill = true;
    }
}
$edit = 1;
if(isset($_POST['mode'])) {
    if($_POST['mode'] == "useredit") {
        $edit = 2;
    }
    if($_SESSION['admin']) {
        $edit = 3;
    }
}

if($edit!=3) {
    $disabled = 'disabled';
}
else {
    $disabled = '';
}
?>
<div class="w3-container w3-dark-gray">
  <h2>neuen Musiker anlegen</h2>
</div>
<div class="w3-panel w3-mobile w3-center w3-col s3 l4">
</div>
<div class="w3-panel w3-mobile w3-center w3-border w3-col s6 l4">
  <form class="w3-container w3-margin" action="musiker.php" method="POST">
    <label>Vorname</label>
    <input class="w3-input w3-border w3-light-gray w3-margin-bottom w3-mobile" name="Vorname" type="text" placeholder="Vorname" <?php if($fill) echo "value=\"".$n->Vorname."\" ".$disabled; ?>>
    <label>Nachname</label>
    <input class="w3-input w3-border w3-light-gray w3-margin-bottom w3-mobile" name="Nachname" type="text" placeholder="Nachname" <?php if($fill) echo "value=\"".$n->Nachname."\" ".$disabled; ?>>
    <label>Emailadresse</label>
    <input class="w3-input w3-border w3-light-gray w3-margin-bottom w3-mobile" name="Email" type="email" placeholder="Email" <?php if($fill) echo "value=\"".$n->Email."\""; ?>>
<?php
if($edit != 2) {
?>
<label>Loginname</label>
<input class="w3-input w3-border w3-light-gray w3-margin-bottom w3-mobile" name="login" type="text" placeholder="Loginname" <?php if($fill) echo "value=\"".$n->login."\" ".$disabled; ?>>
<?php
}
?>
    <label>Instrument</label>
<select class="w3-input w3-border w3-light-gray w3-margin-bottom w3-mobile" name="Instrument" <?php echo $disabled; ?>>
      <?php
  if($fill) {
    instrumentOption($n->Instrument);
  }
  else {
    instrumentOption(0);
  }
?>
    </select>
    <div class="w3-container w3-mobile w3-left">
      <input class="w3-check" type="checkbox" name="Mitglied" value="1" <?php if($fill && (bool)$n->Mitglied) echo "checked ".$disabled; ?>>
      <label>Mitglied</label>
    </div>
    <div class="w3-container w3-mobile w3-margin-bottom w3-left">
      <input class="w3-check" type="checkbox" name="getMail" value="1" <?php if($fill && (bool)$n->getMail) echo "checked ".$disabled; ?>>
      <label>Mailverteiler</label>
    </div>
    <input type="hidden" name="Index" <?php if($fill) echo "value=\"".$n->Index."\""; ?>>
    <div class="w3-container w3-mobile">
    <input class="w3-btn w3-blue w3-border w3-margin w3-mobile" type="submit" name="insert" value="speichern">
    <?php
      if($fill && $edit != 2) {
      ?>
    <input class="w3-btn w3-blue w3-border w3-margin w3-mobile" type="submit" name="delete" value="löschen">
    <input class="w3-btn w3-blue w3-border w3-margin w3-mobile" type="submit" name="passwd" value="Password generieren">
    <?php
}
      ?>
    </div>
  </form>
</div>
<div class="w3-panel w3-mobile w3-center w3-col s3 l4">
</div>
<?php
include "common/footer.php";
?>
