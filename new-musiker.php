<?php
session_start();
$_SESSION['page']='newmusiker';
include "common/header.php";
?>
<div class="w3-container w3-dark-gray">
  <h1>neuen Musiker anlegen</h1>
</div>
<div class="w3-panel w3-mobile w3-center w3-col s3 l4">
</div>
<div class="w3-panel w3-mobile w3-center w3-border w3-col s6 l4">
  <form class="w3-container w3-margin" action="musiker.php" method="POST">
    <label>Vorname</label>
    <input class="w3-input w3-border w3-light-gray w3-margin-bottom w3-mobile" name="Vorname" type="text" placeholder="Vorname">
    <label>Nachname</label>
    <input class="w3-input w3-border w3-light-gray w3-margin-bottom w3-mobile" name="Nachname" type="text" placeholder="Nachname">
    <label>Emailadresse</label>
    <input class="w3-input w3-border w3-light-gray w3-margin-bottom w3-mobile" name="Email" type="email" placeholder="Email">
    <label>Stimme</label>
    <input class="w3-input w3-border w3-light-gray w3-margin-bottom w3-mobile" type="number" name="Stimme" min="1" max="15" value="1">
    <label>Instrument</label>
    <select class="w3-input w3-border w3-light-gray w3-margin-bottom w3-mobile" name="Instrument">
      <?php instrumentOption(); ?>
    </select>
    <div class="w3-container w3-mobile w3-left">
      <input class="w3-check" type="checkbox" name="Mitglied" value="1">
      <label>Mitglied</label>
    </div>
    <div class="w3-container w3-mobile w3-margin-bottom w3-left">
      <input class="w3-check" type="checkbox" name="getMail" value="1">
      <label>Mailverteiler</label>
    </div>
    <input class="w3-btn w3-blue w3-border w3-mobile w3-right" type="submit" name="insert" value="speichern">
  </form>
</div>
<div class="w3-panel w3-mobile w3-center w3-col s3 l4">
</div>
<?php
include "common/footer.php";
?>
