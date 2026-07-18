<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page']='newtermin';
$_SESSION['adminpage']=true;
include "common/header.php";
if(!requirePermission("perm_editAppmnts")) {
    denyAccess();
}

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
$inputBg = $GLOBALS['optionsDB']['colorInputBackground'];
?>
<div class="w3-container w3-margin-bottom <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
    <h2>neuen Termin erstellen</h2>
</div>

<div class="w3-container termin-form">
<form class="termin-form-card w3-border w3-padding <?php echo $inputBg; ?>" action="index.php" method="POST">

  <div class="termin-form-columns">
  <section class="termin-form-section">
    <h3 class="termin-form-heading">Was</h3>
    <label>Veranstaltung</label>
    <input class="w3-input w3-border <?php echo $inputBg; ?> w3-margin-bottom w3-mobile" name="Name" type="text" placeholder="Name" <?php if($fill) echo "value=\"".$n->Name."\""; ?>>
    <label>Beschreibung</label>
    <input class="w3-input w3-border <?php echo $inputBg; ?> w3-margin-bottom w3-mobile" name="Beschreibung" type="text" placeholder="Beschreibung" <?php if($fill) echo "value=\"".$n->Beschreibung."\""; ?>>
  </section>

  <section class="termin-form-section">
    <h3 class="termin-form-heading">Wann</h3>
    <div class="w3-row">
      <div class="w3-col s12 m6 l6 termin-form-field">
        <label>Datum <span class="w3-small w3-text-gray">(mehrtägig <input id="endcheck" type="checkbox" <?php if($fill && $n->EndDatum) echo "checked"; ?> onclick="endToggle();"></input>)</span></label>
        <input class="w3-input w3-border <?php echo $inputBg; ?> w3-margin-bottom w3-mobile" name="Datum" type="date" <?php if($fill) echo "value=\"".$n->Datum."\""; ?>>
      </div>
      <div class="w3-col s12 m6 l6 termin-form-field">
        <label id="endlabel" <?php if($fill && $n->EndDatum) echo "style=\"display: block;\""; else echo "style=\"display: none;\""; ?>>Enddatum</label>
        <input id="endinput" class="w3-input w3-border <?php echo $inputBg; ?> w3-margin-bottom w3-mobile" name="EndDatum" type="date" <?php if($fill) echo "value=\"".$n->EndDatum."\""; ?> <?php if($fill && $n->EndDatum) echo "style=\"display: block;\""; else echo "style=\"display: none;\""; ?>>
      </div>
    </div>
    <div class="w3-row">
      <div class="w3-col s12 m6 l6 termin-form-field">
        <label>Beginn (optional) <b class="termin-form-clear" onclick="clearInput('Uhrzeit')">&#10006;</b></label>
        <input class="w3-input w3-border <?php echo $inputBg; ?> w3-margin-bottom w3-mobile" name="Uhrzeit" type="time" <?php if($fill) echo "value=\"".$n->Uhrzeit."\""; ?>>
      </div>
      <div class="w3-col s12 m6 l6 termin-form-field">
        <label>Ende (optional) <b class="termin-form-clear" onclick="clearInput('Uhrzeit2')">&#10006;</b></label>
        <input class="w3-input w3-border <?php echo $inputBg; ?> w3-margin-bottom w3-mobile" name="Uhrzeit2" type="time" <?php if($fill) echo "value=\"".$n->Uhrzeit2."\""; ?>>
      </div>
    </div>
<?php if($GLOBALS['optionsDB']['showVehicle'] || $GLOBALS['optionsDB']['showTravelTime']) { ?>
    <div class="w3-row">
<?php if($GLOBALS['optionsDB']['showVehicle']) { ?>
      <div class="w3-col s12 m6 l6 termin-form-field">
        <label>Fahrzeug</label>
        <select class="w3-input w3-border <?php echo $inputBg; ?> w3-margin-bottom w3-mobile" name="Vehicle">
<?php
  if($fill) {
    VehicleOption($n->Vehicle);
  } else {
    VehicleOption(0);
  }
?>
        </select>
      </div>
<?php } ?>
<?php if($GLOBALS['optionsDB']['showTravelTime']) { ?>
      <div class="w3-col s12 m6 l6 termin-form-field">
        <label>Abfahrt</label>
        <input class="w3-input w3-border <?php echo $inputBg; ?> w3-margin-bottom w3-mobile" name="Abfahrt" type="time" <?php if($fill) echo "value=\"".$n->Abfahrt."\""; ?>>
      </div>
<?php } ?>
    </div>
<?php } ?>
  </section>

  <section class="termin-form-section">
    <h3 class="termin-form-heading">Wo</h3>
    <div class="w3-row">
      <div class="w3-col s12 m6 l6 termin-form-field">
        <label>Veranstaltungsort (z.B. Rochuskirche)</label>
        <input class="w3-input w3-border <?php echo $inputBg; ?> w3-margin-bottom w3-mobile" name="Ort1" type="text" placeholder="Ort" <?php if($fill) echo "value=\"".$n->Ort1."\""; ?>>
      </div>
      <div class="w3-col s12 m6 l6 termin-form-field">
        <label>Straße, Hausnummer</label>
        <input class="w3-input w3-border <?php echo $inputBg; ?> w3-margin-bottom w3-mobile" name="Ort2" type="text" placeholder="Ort" <?php if($fill) echo "value=\"".$n->Ort2."\""; ?>>
      </div>
    </div>
    <div class="w3-row">
      <div class="w3-col s12 m6 l6 termin-form-field">
        <label>Stadtteil</label>
        <input class="w3-input w3-border <?php echo $inputBg; ?> w3-margin-bottom w3-mobile" name="Ort3" type="text" placeholder="Ort" <?php if($fill) echo "value=\"".$n->Ort3."\""; ?>>
      </div>
      <div class="w3-col s12 m6 l6 termin-form-field">
        <label>Stadt</label>
        <input class="w3-input w3-border <?php echo $inputBg; ?> w3-margin-bottom w3-mobile" name="Ort4" type="text" placeholder="Ort" <?php if($fill) echo "value=\"".$n->Ort4."\""; ?>>
      </div>
    </div>
  </section>

  <section class="termin-form-section">
    <h3 class="termin-form-heading">Optionen</h3>
    <div class="w3-row">
      <div class="w3-col s12 m6 l6 termin-form-field">
        <label>Kapazit&auml;t</label>
        <input class="w3-input w3-border <?php echo $inputBg; ?> w3-margin-bottom w3-mobile" name="Capacity" type="number" min="0" step="1" <?php if($fill) echo "value=\"".$n->Capacity."\""; ?>>
      </div>
      <div class="w3-col s12 m6 l6 termin-form-field">
        <label>Freitext-Rückmeldung (optional)</label>
        <input class="w3-input w3-border <?php echo $inputBg; ?> w3-margin-bottom w3-mobile" name="defaultFreeText" type="text" <?php if($fill) echo "value=\"".$n->defaultFreeText."\""; ?>>
      </div>
    </div>
    <div class="w3-row termin-form-checks">
      <div class="w3-col s12 termin-form-check">
        <input type="hidden" name="Auftritt" value="0">
        <input class="w3-check" type="checkbox" name="Auftritt" value="1" <?php if($fill && (bool)$n->Auftritt) echo "checked"; ?>>
        <label>Besetzung</label>
        <span class="w3-small w3-text-gray termin-form-hint"> — Registeraufschlüsselung und Orchesterdarstellung</span>
      </div>
      <div class="w3-col s12 m4 l4 termin-form-check">
        <input type="hidden" name="Shifts" value="0">
        <input class="w3-check" type="checkbox" name="Shifts" value="1" <?php if($fill && (bool)$n->Shifts) echo "checked"; ?>>
        <label>Schichtdienst</label>
      </div>
      <div class="w3-col s12 m4 l4 termin-form-check">
        <input type="hidden" name="open" value="0">
        <input class="w3-check" type="checkbox" name="open" value="1" <?php if($fill && (bool)$n->open) echo "checked"; ?>>
        <label>Anmeldung offen</label>
      </div>
    </div>
    <div class="w3-margin-top">
      <label>sichtbar für</label>
      <div class="w3-mobile w3-margin-bottom w3-padding w3-border <?php echo $inputBg; ?>">
        <div id="terminVisibilityChips" class="mail-recipient-chips" aria-live="polite"></div>
        <input type="text" id="terminVisibilityInput" class="w3-input w3-border <?php echo $inputBg; ?>" placeholder="Gruppe, Rolle, Register oder Person tippen…" autocomplete="off" />
        <div id="terminVisibilitySuggest" class="mail-recipient-suggest" hidden></div>
<?php
  $visSpec = $fill ? $n->getVisibilitySpecArray() : AudienceSpec::defaultVisibilitySpec();
?>
        <input type="hidden" name="visibilitySpec" id="terminVisibilitySpec" value="<?php
          echo htmlspecialchars(json_encode($visSpec), ENT_QUOTES, 'UTF-8');
        ?>" />
        <p class="w3-small w3-margin-top mail-recipient-count-line">
          <span id="terminVisibilityCount" class="mail-recipient-count" aria-live="polite">…</span>
        </p>
      </div>
<?php if(Discord::isConfigured()) {
    $postDiscordChecked = $fill
      ? ((int)$n->PostDiscord > 0 || AudienceSpec::isAlleUserSpec($visSpec))
      : true;
?>
      <div class="w3-margin-top termin-form-check">
        <input type="hidden" name="PostDiscord" value="0">
        <input class="w3-check" type="checkbox" name="PostDiscord" id="terminPostDiscord" value="1" <?php if($postDiscordChecked) echo "checked"; ?>>
        <label for="terminPostDiscord">Auch auf Discord posten</label>
      </div>
<?php } ?>
    </div>
  </section>
  </div>

  <div class="termin-form-actions w3-row">
    <div class="w3-col s12 <?php echo $fill ? 'm6 l6' : 'm12 l12'; ?> termin-form-action">
      <input class="w3-btn w3-block <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?> w3-border" type="submit" name="insert" value="speichern">
    </div>
<?php if($fill) { ?>
    <div class="w3-col s12 m6 l6 termin-form-action">
      <button type="button" class="w3-btn w3-block <?php echo $GLOBALS['optionsDB']['colorBtnDelete']; ?> w3-border" onclick="document.getElementById('delmodal').style.display='block'">l&ouml;schen</button>
    </div>
    <input type="hidden" name="Index" <?php if($fill) echo "value=\"".$n->Index."\""; ?>>
    <input type="hidden" name="new" <?php if($fill) echo "value=\"".$n->new."\""; ?>>
<?php } ?>
  </div>
</form>
</div>

<?php if($fill) { ?>
<div id="delmodal" class="w3-modal">
  <div class="w3-modal-content w3-card">
    <header class="w3-container w3-row <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
      <span onclick="document.getElementById('delmodal').style.display='none'"
      class="w3-button w3-display-topright">&times;</span>
      <h2>L&ouml;schen best&auml;tigen</h2>
    </header>
    <div class="w3-container w3-row w3-center w3-padding w3-margin w3-card <?php echo $GLOBALS['optionsDB']['colorWarning']; ?>">Sind Sie sicher, dass sie <b><?php echo $n->Name." (".germanDate($n->Datum,1); ?>)</b> l&ouml;schen wollen?</div>
    <div class="w3-container w3-mobile">
      <form action="index.php" method="POST">
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
<?php } ?>

<script type="text/javascript">
function endToggle() {
  if(document.getElementById("endcheck").checked) {
    document.getElementById("endlabel").style.display="block";
    document.getElementById("endinput").style.display="block";
  } else {
    document.getElementById("endlabel").style.display="none";
    document.getElementById("endinput").style.display="none";
  }
}
function clearInput(name) {
  var x = document.getElementsByName(name);
  for(var i=0; i<x.length; i++) {
    x[i].value = '';
  }
}
</script>
<?php
MailGroup::ensureSchema();
$terminVisibilityCatalog = AudienceSpec::buildCatalog(array(
    'forMail' => false,
    'includeMailGroups' => true,
));
?>
<script type="application/json" id="terminVisibilityCatalog"><?php echo json_encode($terminVisibilityCatalog, JSON_UNESCAPED_UNICODE); ?></script>
<script src="js/mailRecipients.js?<?php echo isset($GLOBALS['version']['Hash']) ? $GLOBALS['version']['Hash'] : '0'; ?>-<?php echo @filemtime(__DIR__.'/js/mailRecipients.js'); ?>"></script>
<script>
(function() {
  if(typeof MailRecipientChips === 'undefined') return;
  MailRecipientChips.init({
    catalogEl: document.getElementById('terminVisibilityCatalog'),
    chipsEl: document.getElementById('terminVisibilityChips'),
    inputEl: document.getElementById('terminVisibilityInput'),
    suggestEl: document.getElementById('terminVisibilitySuggest'),
    hiddenEl: document.getElementById('terminVisibilitySpec'),
    countEl: document.getElementById('terminVisibilityCount'),
    countUrl: 'mailRecipientCount.php',
    countLabel: 'sichtbar für',
    allowEmpty: true,
    defaultGroups: [],
    jobId: 0
  });
  var discordCb = document.getElementById('terminPostDiscord');
  if(!discordCb) return;
  function isAlleUserSpec(spec) {
    return spec
      && Array.isArray(spec.groups)
      && spec.groups.length === 1
      && spec.groups[0] === 'users'
      && (!spec.registers || !spec.registers.length)
      && (!spec.users || !spec.users.length)
      && (!spec.mailGroups || !spec.mailGroups.length);
  }
  function syncTerminDiscordDefault() {
    try {
      discordCb.checked = isAlleUserSpec(MailRecipientChips.spec);
    } catch(e) {}
  }
  var origRender = MailRecipientChips.render.bind(MailRecipientChips);
  MailRecipientChips.render = function() {
    origRender();
    syncTerminDiscordDefault();
  };
  syncTerminDiscordDefault();
})();
</script>

<?php
include "common/footer.php";
?>
