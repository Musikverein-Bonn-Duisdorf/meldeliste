<?php
session_start();
$_SESSION['page']='newtermin';
include "common/header.php";
$fill = false;
if(isset($_POST['id'])) {
    $n = new Termin;
    $n = $n->load_by_id($_POST['id']);
    if($n->Index > 0) {
        $fill = true;
    }
}
?>
<div class="w3-container w3-dark-gray">
    <h2>neuen Termin erstellen</h2>
</div>
<div class="w3-panel w3-mobile w3-center w3-col s3 l4">
</div>
<div class="w3-panel w3-mobile w3-center w3-border w3-col s6 l4">
<form class="w3-container w3-margin" action="termine.php" method="POST">
    <label>Datum</label>
    <input class="w3-input w3-border w3-light-gray w3-margin-bottom w3-mobile" name="Datum" type="date" <?php if($fill) echo "value=\"".$n->Datum."\""; ?>>
    <label>Beginn</label>
    <input class="w3-input w3-border w3-light-gray w3-margin-bottom w3-mobile" name="Uhrzeit" type="time" <?php if($fill) echo "value=\"".$n->Uhrzeit."\""; ?>>
    <label>Ende</label>
    <input class="w3-input w3-border w3-light-gray w3-margin-bottom w3-mobile" name="Uhrzeit2" type="time" <?php if($fill) echo "value=\"".$n->Uhrzeit2."\""; ?>>
    <label>Veranstaltung</label>
    <input class="w3-input w3-border w3-light-gray w3-margin-bottom w3-mobile" name="Name" type="text" placeholder="Name" <?php if($fill) echo "value=\"".$n->Name."\""; ?>>
    <label>Beschreibung</label>
    <input class="w3-input w3-border w3-light-gray w3-margin-bottom w3-mobile" name="Beschreibung" type="text" placeholder="Beschreibung" <?php if($fill) echo "value=\"".$n->Beschreibung."\""; ?>>
    <label>Ort</label>
    <input class="w3-input w3-border w3-light-gray w3-margin-bottom w3-mobile" name="Ort1" type="text" placeholder="Ort" <?php if($fill) echo "value=\"".$n->Ort1."\""; ?>>
    <label>Ort (Detail 1)</label>
    <input class="w3-input w3-border w3-light-gray w3-margin-bottom w3-mobile" name="Ort2" type="text" placeholder="Ort" <?php if($fill) echo "value=\"".$n->Ort2."\""; ?>>
    <label>Ort (Detail 2)</label>
    <input class="w3-input w3-border w3-light-gray w3-margin-bottom w3-mobile" name="Ort3" type="text" placeholder="Ort" <?php if($fill) echo "value=\"".$n->Ort3."\""; ?>>
    <label>Ort (Detail 3)</label>
    <input class="w3-input w3-border w3-light-gray w3-margin-bottom w3-mobile" name="Ort4" type="text" placeholder="Ort" <?php if($fill) echo "value=\"".$n->Ort4."\""; ?>>
    <input class="w3-check" type="checkbox" name="Auftritt" value="1" <?php if($fill && (bool)$n->Auftritt) echo "checked"; ?>>
    <label>Auftritt</label>
    <input class="w3-check" type="checkbox" name="published" value="1" <?php if($fill && (bool)$n->published) echo "checked"; ?>>
    <label>sichtbar</label>
    <div class="w3-container w3-mobile">
    <?php
      if($fill) {
      ?>
    <input type="hidden" name="Index" <?php if($fill) echo "value=\"".$n->Index."\""; ?>>
    <input class="w3-btn w3-blue w3-border w3-margin w3-mobile" type="submit" name="delete" value="löschen">
          <?php
      }
?>
    <input class="w3-btn w3-blue w3-border w3-margin w3-mobile" type="submit" name="insert" value="speichern">
    </div>
</form>
</div>
<div class="w3-panel w3-mobile w3-center w3-col s3 l4">
</div>
<?php
include "common/footer.php";
?>
