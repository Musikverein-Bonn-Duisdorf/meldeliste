<?php
session_start();
$_SESSION['page']='newtermin';
$_SESSION['adminpage']=true;
include "common/header.php";
if(!requirePermission("perm_editAppmnts")) die();

$fill = false;
if(isset($_POST['id'])) {
    $n = new Termin;
    $n->load_by_id($_POST['id']);
    if($n->Index > 0) {
        $fill = true;
    }
}
if(isset($_POST['copy'])) {
    $n = new Termin;
    $n->load_by_id($_POST['copy']);
    if($n->Index > 0) {
        $fill = true;
    }
    $n->Index=NULL;
}
?>
<div class="w3-container w3-margin-bottom <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
    <h2>neuen Termin erstellen</h2>
</div>
<div class="w3-panel w3-mobile w3-center w3-col s3 l4">
</div>
<div class="w3-card <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-mobile w3-center w3-border w3-padding w3-col s6 l4">
<form action="termine.php" method="POST">
  <label>Datum (mehrtägig <input id="endcheck" type="checkbox" <?php if($fill && $n->EndDatum) echo "checked"; ?> onclick="endToggle();"></input>)</label>
    <input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="Datum" type="date" <?php if($fill) echo "value=\"".$n->Datum."\""; ?>>
<script type="text/javascript">
            function endToggle() {
                if(document.getElementById("endcheck").checked) {
                    document.getElementById("endlabel").style.display="block";
                    document.getElementById("endinput").style.display="block";
                }
                else {
                    document.getElementById("endlabel").style.display="none";
                    document.getElementById("endinput").style.display="none";
                }
            }
            </script>
    <label id="endlabel" <?php if($fill && $n->EndDatum) echo "style=\"display: block;\""; else echo "style=\"display: none;\""; ?>>Enddatum</label>
    <input id="endinput" class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="EndDatum" type="date" <?php if($fill) echo "value=\"".$n->EndDatum."\""; ?> <?php if($fill && $n->EndDatum) echo "style=\"display: block;\""; else echo "style=\"display: none;\""; ?>>
    <label>Beginn (optional) <b onclick="clearInput('Uhrzeit')">&#10006;</b></label>
    <input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="Uhrzeit" type="time" <?php if($fill) echo "value=\"".$n->Uhrzeit."\""; ?>>
    <label>Ende (optional) <b onclick="clearInput('Uhrzeit2')">&#10006;</b></label>
    <input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="Uhrzeit2" type="time" <?php if($fill) echo "value=\"".$n->Uhrzeit2."\""; ?>>
<?php
if($GLOBALS['optionsDB']['showVehicle'] || $GLOBALS['optionsDB']['showTravelTime']) {
?>
    <label>Abfahrt</label>
<?php
}
if($GLOBALS['optionsDB']['showVehicle']) {
?>
<select class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="Vehicle">
      <?php
  if($fill) {
    VehicleOption($n->Vehicle);
  }
  else {
    VehicleOption(0);
  }
?>
    </select>
<?php
}
if($GLOBALS['optionsDB']['showTravelTime']) {
?>
<input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="Abfahrt" type="time" <?php if($fill) echo "value=\"".$n->Abfahrt."\""; ?>>
<?php } ?>
    <label>Veranstaltung</label>
    <input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="Name" type="text" placeholder="Name" <?php if($fill) echo "value=\"".$n->Name."\""; ?>>
    <label>Beschreibung</label>
    <input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="Beschreibung" type="text" placeholder="Beschreibung" <?php if($fill) echo "value=\"".$n->Beschreibung."\""; ?>>
<label>Veranstaltungsort (z.B. Rochuskirche)</label>
    <input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="Ort1" type="text" placeholder="Ort" <?php if($fill) echo "value=\"".$n->Ort1."\""; ?>>
<label>Straße, Hausnummer</label>
    <input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="Ort2" type="text" placeholder="Ort" <?php if($fill) echo "value=\"".$n->Ort2."\""; ?>>
    <label>Stadtteil</label>
    <input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="Ort3" type="text" placeholder="Ort" <?php if($fill) echo "value=\"".$n->Ort3."\""; ?>>
    <label>Stadt</label>
    <input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="Ort4" type="text" placeholder="Ort" <?php if($fill) echo "value=\"".$n->Ort4."\""; ?>>
    <label>Kapazit&auml;t</label>
    <input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="Capacity" type="number" min="0" step="1" <?php if($fill) echo "value=\"".$n->Capacity."\""; ?>>
    <div class="w3-col l6 m6 s12 w3-mobile w3-margin-bottom w3-left">
    <input class="w3-check" type="checkbox" name="Auftritt" value="1" <?php if($fill && (bool)$n->Auftritt) echo "checked"; ?>>
    <label>Auftritt</label>
    </div>
    <div class="w3-col l6 m6 s12 w3-mobile w3-margin-bottom w3-left">
    <input class="w3-check" type="checkbox" name="Shifts" value="1" <?php if($fill && (bool)$n->Shifts) echo "checked"; ?>>
    <label>Schichtdienst</label>
    </div>
    <div class="w3-col l6 m6 s12 w3-mobile w3-margin-bottom w3-left">
    <input class="w3-check" type="checkbox" name="published" value="1" <?php if($fill && (bool)$n->published) echo "checked"; ?>>
    <label>sichtbar</label>
    </div>
    <div class="w3-col l6 m6 s12 w3-mobile w3-margin-bottom w3-left">
    <input type="hidden" name="open" value="0">
    <input class="w3-check" type="checkbox" name="open" value="1" <?php if($fill && (bool)$n->open) echo "checked"; ?>>
    <label>Anmeldung offen</label>
    </div>
    <input class="w3-btn w3-col l6 m6 s12 <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?> w3-border w3-margin-bottom w3-mobile" type="submit" name="insert" value="speichern">
    <?php
      if($fill) {
      ?>
    <input type="hidden" name="Index" <?php if($fill) echo "value=\"".$n->Index."\""; ?>>
    <input type="hidden" name="new" <?php if($fill) echo "value=\"".$n->new."\""; ?>>
          <?php
      }
?>
</form>
<?php if($fill) { ?>
<button class="w3-btn w3-col l6 m6 s12 <?php echo $GLOBALS['optionsDB']['colorBtnDelete']; ?> w3-border w3-margin-bottom w3-mobile" onclick="document.getElementById('delmodal').style.display='block'">l&ouml;schen</button>
</div>

<div id="delmodal" class="w3-modal">
  <div class="w3-modal-content w3-card">
    <header class="w3-container w3-row <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
      <span onclick="document.getElementById('delmodal').style.display='none'" 
      class="w3-button w3-display-topright">&times;</span>
<h2>L&ouml;schen best&auml;tigen</h2>
</header>
<div class="w3-container w3-row w3-center w3-padding w3-margin w3-card <?php echo $GLOBALS['optionsDB']['colorWarning']; ?>">Sind Sie sicher, dass sie <b><?php echo $n->Name." (".germanDate($n->Datum,1); ?>)</b> l&ouml;schen wollen?</div>
<div class="w3-container w3-mobile">
<form action="termine.php" method="POST">
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
<?php } ?>
	   </div>
	 <div class="w3-row">&nbsp;</div>
      <script>
function clearInput(name) {
  var x = document.getElementsByName(name);
  for(i=0; i<x.length; i++) {
      x[i].value = '';
  }
}
</script>
      
<?php
include "common/footer.php";
?>
