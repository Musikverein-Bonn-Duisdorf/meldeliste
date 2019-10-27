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
}
if($_SESSION['admin']) {
    $edit = 3;
}

$disabled = '';
if($edit!=3) {
    $disabled = 'disabled';
}
else {
    $disabled = '';
}
?>
<div class="w3-container <?php echo $GLOBALS['commonColors']['titlebar']; ?>">
  <h2>Profil bearbeiten</h2>
</div>
<div class="w3-panel w3-mobile w3-center w3-col s3 l4">
</div>
<div class="w3-panel w3-mobile w3-center w3-border w3-col s6 l4">
  <form class="w3-container w3-margin" action="musiker.php" method="POST">
    <label>Vorname</label>
    <input class="w3-input w3-border <?php echo $GLOBALS['commonColors']['inputs']; ?> w3-margin-bottom w3-mobile" name="Vorname" type="text" placeholder="Vorname" <?php if($fill) echo "value=\"".$n->Vorname."\" ".$disabled; ?>>
    <label>Nachname</label>
    <input class="w3-input w3-border <?php echo $GLOBALS['commonColors']['inputs']; ?> w3-margin-bottom w3-mobile" name="Nachname" type="text" placeholder="Nachname" <?php if($fill) echo "value=\"".$n->Nachname."\" ".$disabled; ?>>
    <label>Emailadresse</label>
    <input class="w3-input w3-border <?php echo $GLOBALS['commonColors']['inputs']; ?> w3-margin-bottom w3-mobile" name="Email" type="email" placeholder="Email" <?php if($fill) echo "value=\"".$n->Email."\""; ?>>
<?php
if($edit != 2) {
?>
    <label class="w3-text-gray">Loginname (optional)</label>
<input class="w3-input w3-border <?php echo $GLOBALS['commonColors']['inputs']; ?> w3-margin-bottom w3-mobile" name="login" type="text" placeholder="Loginname" <?php if($fill) echo "value=\"".$n->login."\" ".$disabled; ?>>
<?php
}
if($n->login || $edit == 3) {
?>
<label class="w3-text-gray">neues Passwort (optional)</label>
<input class="w3-input w3-border <?php echo $GLOBALS['commonColors']['inputs']; ?> w3-margin-bottom w3-mobile" name="pw1" type="password" placeholder="*****">
<label class="w3-text-gray">neues Passwort wiederholen (optional)</label>
<input class="w3-input w3-border <?php echo $GLOBALS['commonColors']['inputs']; ?> w3-margin-bottom w3-mobile" name="pw2" type="password" placeholder="*****">
<?php
}
?>
    <label>Instrument</label>
<select class="w3-input w3-border <?php echo $GLOBALS['commonColors']['inputs']; ?> w3-margin-bottom w3-mobile" name="Instrument" <?php echo $disabled; ?>>
      <?php
  if($fill) {
    instrumentOption($n->Instrument);
  }
  else {
    instrumentOption(0);
  }
?>
    </select>
    <div class="w3-container w3-mobile w3-margin-bottom w3-left">
      <input type="hidden" name="getMail" value="0">
      <input class="w3-check" type="checkbox" name="getMail" value="1" <?php if($fill && (bool)$n->getMail) echo "checked "; ?>>
      <label>Mailverteiler</label>
    </div>
    <?php
      if($_SESSION['admin']) {
      ?>
    <div class="w3-container w3-mobile w3-left">
      <input type="hidden" name="Mitglied" value="0">
      <input class="w3-check" type="checkbox" name="Mitglied" value="1" <?php if($fill && (bool)$n->Mitglied){ echo "checked ";} ?>>
      <label>Mitglied</label>
    </div>
    <div class="w3-container w3-mobile w3-margin-bottom w3-left">
      <input type="hidden" name="Admin" value="0">
      <input class="w3-check" type="checkbox" name="Admin" value="1" <?php if($fill && (bool)$n->Admin) echo "checked "; ?>>
      <label>Admin</label>
    </div>
<?php   if($GLOBALS['options']['showRegisterLead']) { ?>
    <div class="w3-container w3-mobile w3-margin-bottom w3-left">
      <input type="hidden" name="RegisterLead" value="0">
      <input class="w3-check" type="checkbox" name="RegisterLead" value="1" <?php if($fill && (bool)$n->RegisterLead) echo "checked "; ?>>
      <label>Registerführer</label>
    </div>
    <?php
      }
      }
      ?>
    <input type="hidden" name="Index" <?php if($fill) echo "value=\"".$n->Index."\""; ?>>
    <div class="w3-container w3-mobile">
    <input class="w3-btn <?php echo $GLOBALS['commonColors']['submit']; ?> w3-border w3-margin w3-mobile" type="submit" name="insert" value="speichern">
    <?php
      if($fill && $edit != 2) {
      ?>
    <input class="w3-btn <?php echo $GLOBALS['commonColors']['submit']; ?> w3-border w3-margin w3-mobile" type="submit" name="delete" value="löschen">
    <input class="w3-btn <?php echo $GLOBALS['commonColors']['submit']; ?> w3-border w3-margin w3-mobile" type="submit" name="passwd" value="Zufallspasswort generieren">
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
