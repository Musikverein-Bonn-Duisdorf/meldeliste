<?php
session_start();
if(isset($_POST['id']) && $_SESSION['userid'] == $_POST['id']) {
    $_SESSION['page']='me';
    $_SESSION['adminpage']=false;
}
else {
    $_SESSION['page']='newmusiker';
    $_SESSION['adminpage']=true;
}
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
<div class="w3-container w3-margin-bottom <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2>Profil bearbeiten</h2>
</div>
<div class="w3-panel w3-mobile w3-center w3-col s3 l4">
</div>
<div class="w3-card <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-mobile w3-center w3-border w3-padding w3-col s6 l4">
  <form action="musiker.php" method="POST">
    <label>Vorname</label>
    <input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="Vorname" type="text" placeholder="Vorname" <?php if($fill) echo "value=\"".$n->Vorname."\" ".$disabled; ?>>
    <label>Nachname</label>
    <input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="Nachname" type="text" placeholder="Nachname" <?php if($fill) echo "value=\"".$n->Nachname."\" ".$disabled; ?>>
    <label>Emailadresse</label>
    <input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="Email" type="email" placeholder="Email" <?php if($fill) echo "value=\"".$n->Email."\""; ?>>
<?php
if($edit != 2) {
?>
    <label class="w3-text-gray">Loginname (optional)</label>
<input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="login" type="text" placeholder="Loginname" <?php if($fill) echo "value=\"".$n->login."\" ".$disabled; ?>>
<?php
}
if($fill && ($n->login || $edit == 3)) {
?>
<label class="w3-text-gray">neues Passwort (optional)</label>
<input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="pw1" type="password" placeholder="*****">
<label class="w3-text-gray">neues Passwort wiederholen (optional)</label>
<input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="pw2" type="password" placeholder="*****">
<?php
}
?>
    <label>Instrument</label>
<select class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="Instrument" <?php echo $disabled; ?>>
      <?php
  if($fill) {
      echo instrumentOption($n->Instrument);
  }
  else {
      echo instrumentOption(0);
  }
?>
    </select>
    <div class="w3-col l6 m6 s12 w3-mobile w3-margin-bottom w3-left">
      <input type="hidden" name="getMail" value="0">
      <input class="w3-check" type="checkbox" name="getMail" value="1" <?php if($fill && (bool)$n->getMail) echo "checked "; ?>>
      <label>Mailverteiler</label>
    </div>
    <?php
      if($_SESSION['admin']) {
      ?>
    <div class="w3-col l6 m6 s12 w3-mobile w3-margin-bottom w3-left">
      <input type="hidden" name="Mitglied" value="0">
      <input class="w3-check" type="checkbox" name="Mitglied" value="1" <?php if($fill && (bool)$n->Mitglied){ echo "checked ";} ?>>
      <label>Mitglied</label>
    </div>
    <div class="w3-col l6 m6 s12 w3-mobile w3-margin-bottom w3-left">
      <input type="hidden" name="Admin" value="0">
      <input class="w3-check" type="checkbox" name="Admin" value="1" <?php if($fill && (bool)$n->Admin) echo "checked "; ?>>
      <label>Admin</label>
    </div>
<?php   if($GLOBALS['optionsDB']['showRegisterLead']) { ?>
    <div class="w3-col l6 m6 s12 w3-mobile w3-margin-bottom w3-left">
      <input type="hidden" name="RegisterLead" value="0">
      <input class="w3-check" type="checkbox" name="RegisterLead" value="1" <?php if($fill && (bool)$n->RegisterLead) echo "checked "; ?>>
      <label>Registerführer</label>
    </div>
    <?php
      }
      }
      ?>
    <input type="hidden" name="Index" <?php if($fill) echo "value=\"".$n->Index."\""; ?>>
    <input class="w3-btn w3-col l6 m6 s12 <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?> w3-border w3-margin-bottom w3-mobile" type="submit" name="insert" value="speichern">
    <?php
      if($fill && $edit != 2) {
      ?>
    <input class="w3-btn w3-col l6 m6 s12 <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?> w3-border w3-margin-bottom w3-mobile" type="submit" name="passwd" value="Zufallspasswort generieren">
    <input class="w3-btn w3-col l6 m6 s12 <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?> w3-border w3-margin-bottom w3-mobile" type="submit" name="newmail" value="Email mit Link senden">
    <?php
}
      ?>
  </form>
<?php if($fill) { ?>
<button class="w3-btn w3-col l6 m6 s12 <?php echo $GLOBALS['optionsDB']['colorBtnDelete']; ?> w3-border w3-margin-bottom w3-mobile" onclick="document.getElementById('delmodal').style.display='block'">l&ouml;schen</button>
<?php } ?>
<?php if($fill) { ?>
         <div class="w3-row"><a href="<?php echo $n->getLink(); ?>"><?php echo $n->getLink(); ?></a></div>
<?php } ?>
</div>
<div class="w3-panel w3-mobile w3-center w3-col s3 l4">
</div>
<?php if($fill) { ?>
    <div id="delmodal" class="w3-modal">
    <div class="w3-modal-content w3-card">
    <header class="w3-container w3-row <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
    <span onclick="document.getElementById('delmodal').style.display='none'" class="w3-button w3-display-topright">&times;</span>
    <h2>L&ouml;schen best&auml;tigen</h2>
    </header>
    <div class="w3-container w3-row w3-center w3-padding w3-margin w3-card <?php echo $GLOBALS['optionsDB']['colorWarning']; ?>">Sind Sie sicher, dass sie <b><?php echo $n->Vorname." ".$n->Nachname; ?></b> l&ouml;schen wollen?</div>
    <div class="w3-container w3-mobile">
    <form action="musiker.php" method="POST">
    <input type="hidden" name="Index" <?php if($fill) echo "value=\"".$n->Index."\""; ?>>
    <div class="w3-row">
    <div class="w3-col l4 m4 s2 w3-center">&nbsp;</div>
    <button class="w3-btn w3-col l4 m4 s8 w3-center <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?> w3-border w3-margin-bottom w3-mobile" type="submit" name="delete" value="delete">ja</button>
    <div class="w3-col l4 m4 s2 w3-center">&nbsp;</div>
    </div>
    </form>
    <div class="w3-row">
    <div class="w3-col l4 m4 s2 w3-center">&nbsp;</div>
    <button class="w3-btn w3-col l4 m4 s8 w3-center <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?> w3-border w3-margin-bottom w3-mobile" onclick="document.getElementById('delmodal').style.display='none'">nein</button>
    <div class="w3-col l4 m4 s2 w3-center">&nbsp;</div>
    </div>
    </div>
    </div>
    </div>
<?php } ?>>
    <div class="w3-row">&nbsp;</div>
<?php
include "common/footer.php";
?>
