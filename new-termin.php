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
$prefillDatum = '';
$n = null;
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
if(!$fill && isset($_GET['Datum'])) {
    $d = trim((string)$_GET['Datum']);
    if(preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
        $parts = explode('-', $d);
        if(checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0])) {
            $prefillDatum = $d;
        }
    }
}
$inputBg = $GLOBALS['optionsDB']['colorInputBackground'];
$btnSubmit = $GLOBALS['optionsDB']['colorBtnSubmit'];
$btnDelete = $GLOBALS['optionsDB']['colorBtnDelete'];

$isCopy = isset($_POST['copy']);
if($fill && $n && (int)$n->Index > 0) {
    $terminKicker = 'Termin bearbeiten';
    $terminTitle = trim((string)$n->Name) !== '' ? (string)$n->Name : 'Termin';
}
elseif($fill && $isCopy) {
    $terminKicker = 'Termin kopieren';
    $terminTitle = trim((string)$n->Name) !== '' ? (string)$n->Name : 'Kopie';
}
else {
    $terminKicker = 'Neuer Termin';
    $terminTitle = 'Neuer Termin';
}

$visSpec = ($fill && $n) ? $n->getVisibilitySpecArray() : AudienceSpec::defaultVisibilitySpec();
if($fill && $n) {
    // Gastmusiker (Active=0) erscheinen als normale Personen-Chips in derselben Maske.
    foreach($n->getGuestMusiciansArray() as $gid) {
        $gid = (int)$gid;
        if($gid > 0 && !in_array($gid, $visSpec['users'], true)) {
            $visSpec['users'][] = $gid;
        }
    }
}
?>
<div class="w3-container w3-margin-bottom termin-page">
<div class="profile-shell termin-shell">
<form class="profile-form" action="index.php" method="POST">

  <header class="<?php echo htmlspecialchars(adminHeroClass(array('kicker' => $terminKicker, 'permKey' => 'perm_editAppmnts')), ENT_QUOTES, 'UTF-8'); ?>">
    <div class="profile-hero-text">
      <p class="profile-kicker"><?php echo htmlspecialchars($terminKicker, ENT_QUOTES, 'UTF-8'); ?></p>
      <h2 class="profile-title"><?php echo htmlspecialchars($terminTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
    </div>
    <div class="profile-hero-actions">
      <div class="profile-actions">
        <div class="profile-actions-primary">
          <input class="w3-btn profile-btn-primary <?php echo htmlspecialchars($btnSubmit, ENT_QUOTES, 'UTF-8'); ?> w3-border w3-mobile" type="submit" name="insert" value="Speichern">
        </div>
<?php if($fill && $n && (int)$n->Index > 0) { ?>
        <details class="profile-actions-more">
          <summary>Weitere Aktionen</summary>
          <div class="profile-actions-secondary">
            <button type="button" class="w3-btn <?php echo htmlspecialchars($btnDelete, ENT_QUOTES, 'UTF-8'); ?> w3-border w3-mobile" onclick="document.getElementById('delmodal').style.display='block'">Löschen</button>
          </div>
        </details>
        <input type="hidden" name="Index" value="<?php echo (int)$n->Index; ?>">
        <input type="hidden" name="new" value="<?php echo htmlspecialchars((string)$n->new, ENT_QUOTES, 'UTF-8'); ?>">
<?php } ?>
      </div>
    </div>
  </header>

  <div class="termin-grid">
    <section class="profile-col" aria-labelledby="termin-col-was">
      <h3 id="termin-col-was" class="profile-col-title">Was</h3>
      <div class="profile-field">
        <label class="profile-label" for="termin-name">Veranstaltung</label>
        <input id="termin-name" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" name="Name" type="text" placeholder="Name" <?php if($fill) echo 'value="'.htmlspecialchars((string)$n->Name, ENT_QUOTES, 'UTF-8').'"'; ?>>
      </div>
      <div class="profile-field">
        <label class="profile-label" for="termin-beschreibung">Beschreibung</label>
        <input id="termin-beschreibung" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" name="Beschreibung" type="text" placeholder="Beschreibung" <?php if($fill) echo 'value="'.htmlspecialchars((string)$n->Beschreibung, ENT_QUOTES, 'UTF-8').'"'; ?>>
      </div>
    </section>

    <section class="profile-col" aria-labelledby="termin-col-wann">
      <h3 id="termin-col-wann" class="profile-col-title">Wann</h3>
      <div class="profile-field">
        <label class="profile-label" for="termin-datum">Datum</label>
        <input id="termin-datum" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" name="Datum" type="date"<?php
          if($fill) {
              echo ' value="'.htmlspecialchars((string)$n->Datum, ENT_QUOTES, 'UTF-8').'"';
          }
          elseif($prefillDatum !== '') {
              echo ' value="'.htmlspecialchars($prefillDatum, ENT_QUOTES, 'UTF-8').'"';
          }
        ?>>
      </div>
      <div class="profile-field">
        <label class="profile-pref" for="endcheck">
          <input id="endcheck" type="checkbox" class="w3-check" <?php if($fill && $n->EndDatum) echo 'checked'; ?> onclick="endToggle();">
          <span>Mehrtägig</span>
        </label>
      </div>
      <div class="profile-field" id="endfield" <?php if(!($fill && $n->EndDatum)) echo 'hidden'; ?>>
        <label class="profile-label" id="endlabel" for="endinput">Enddatum</label>
        <input id="endinput" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" name="EndDatum" type="date" <?php if($fill) echo 'value="'.htmlspecialchars((string)$n->EndDatum, ENT_QUOTES, 'UTF-8').'"'; ?>>
      </div>
      <div class="termin-field-pair">
        <div class="profile-field">
          <label class="profile-label" for="termin-uhrzeit">Beginn <b class="termin-form-clear" onclick="clearInput('Uhrzeit')" title="leeren">&#10006;</b></label>
          <input id="termin-uhrzeit" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" name="Uhrzeit" type="time" <?php if($fill) echo 'value="'.htmlspecialchars((string)$n->Uhrzeit, ENT_QUOTES, 'UTF-8').'"'; ?>>
        </div>
        <div class="profile-field">
          <label class="profile-label" for="termin-uhrzeit2">Ende <b class="termin-form-clear" onclick="clearInput('Uhrzeit2')" title="leeren">&#10006;</b></label>
          <input id="termin-uhrzeit2" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" name="Uhrzeit2" type="time" <?php if($fill) echo 'value="'.htmlspecialchars((string)$n->Uhrzeit2, ENT_QUOTES, 'UTF-8').'"'; ?>>
        </div>
      </div>
<?php if($GLOBALS['optionsDB']['showVehicle'] || $GLOBALS['optionsDB']['showTravelTime']) { ?>
      <div class="termin-field-pair">
<?php if($GLOBALS['optionsDB']['showVehicle']) { ?>
        <div class="profile-field">
          <label class="profile-label" for="termin-vehicle">Fahrzeug</label>
          <select id="termin-vehicle" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" name="Vehicle">
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
        <div class="profile-field">
          <label class="profile-label" for="termin-abfahrt">Abfahrt</label>
          <input id="termin-abfahrt" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" name="Abfahrt" type="time" <?php if($fill) echo 'value="'.htmlspecialchars((string)$n->Abfahrt, ENT_QUOTES, 'UTF-8').'"'; ?>>
        </div>
<?php } ?>
      </div>
<?php } ?>
    </section>

    <section class="profile-col" aria-labelledby="termin-col-wo">
      <h3 id="termin-col-wo" class="profile-col-title">Wo</h3>
      <div class="profile-field">
        <label class="profile-label" for="termin-ort1">Ort</label>
        <input id="termin-ort1" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" name="Ort1" type="text" placeholder="Ort" <?php if($fill) echo 'value="'.htmlspecialchars((string)$n->Ort1, ENT_QUOTES, 'UTF-8').'"'; ?>>
      </div>
      <div class="profile-field">
        <label class="profile-label" for="termin-ort2">Straße</label>
        <input id="termin-ort2" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" name="Ort2" type="text" placeholder="Straße" <?php if($fill) echo 'value="'.htmlspecialchars((string)$n->Ort2, ENT_QUOTES, 'UTF-8').'"'; ?>>
      </div>
      <div class="termin-field-pair">
        <div class="profile-field">
          <label class="profile-label" for="termin-ort3">Stadtteil</label>
          <input id="termin-ort3" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" name="Ort3" type="text" placeholder="Stadtteil" <?php if($fill) echo 'value="'.htmlspecialchars((string)$n->Ort3, ENT_QUOTES, 'UTF-8').'"'; ?>>
        </div>
        <div class="profile-field">
          <label class="profile-label" for="termin-ort4">Stadt</label>
          <input id="termin-ort4" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" name="Ort4" type="text" placeholder="Stadt" <?php if($fill) echo 'value="'.htmlspecialchars((string)$n->Ort4, ENT_QUOTES, 'UTF-8').'"'; ?>>
        </div>
      </div>
<?php
if($fill && $n && (int)$n->Index > 0 && !empty($GLOBALS['googlemapsapi']) && ($n->Ort1 || $n->Ort2)) {
    $mapsQuery = rawurlencode(trim(implode(' ', array_filter(array(
        (string)$n->Ort1,
        (string)$n->Ort2,
        (string)$n->Ort3,
        (string)$n->Ort4,
    )))));
?>
      <div class="profile-field termin-edit-map">
        <span class="profile-label">Karte</span>
        <div class="profile-value profile-value--map">
          <iframe title="Karte" width="100%" height="180" frameborder="0" style="border:0" src="https://www.google.com/maps/embed/v1/place?key=<?php echo htmlspecialchars((string)$GLOBALS['googlemapsapi'], ENT_QUOTES, 'UTF-8'); ?>&amp;q=<?php echo htmlspecialchars($mapsQuery, ENT_QUOTES, 'UTF-8'); ?>" allowfullscreen></iframe>
        </div>
      </div>
<?php } ?>
    </section>

    <section class="profile-col" aria-labelledby="termin-col-optionen">
      <h3 id="termin-col-optionen" class="profile-col-title">Optionen</h3>
      <div class="termin-field-pair">
        <div class="profile-field">
          <label class="profile-label" for="termin-capacity">Kapazität</label>
          <input id="termin-capacity" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" name="Capacity" type="number" min="0" step="1" <?php if($fill) echo 'value="'.htmlspecialchars((string)$n->Capacity, ENT_QUOTES, 'UTF-8').'"'; ?>>
        </div>
        <div class="profile-field">
          <label class="profile-label" for="termin-freetext">Freitext</label>
          <input id="termin-freetext" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" name="defaultFreeText" type="text" <?php if($fill) echo 'value="'.htmlspecialchars((string)$n->defaultFreeText, ENT_QUOTES, 'UTF-8').'"'; ?>>
        </div>
      </div>
      <div class="profile-prefs profile-prefs--grid termin-opts">
        <label class="profile-pref">
          <input type="hidden" name="Auftritt" value="0">
          <input class="w3-check" type="checkbox" name="Auftritt" value="1" <?php if($fill && (bool)$n->Auftritt) echo 'checked'; ?>>
          <span>Besetzung</span>
        </label>
        <label class="profile-pref">
          <input type="hidden" name="Shifts" value="0">
          <input class="w3-check" type="checkbox" name="Shifts" value="1" <?php if($fill && (bool)$n->Shifts) echo 'checked'; ?>>
          <span>Schichtdienst</span>
        </label>
        <label class="profile-pref">
          <input type="hidden" name="open" value="0">
          <input class="w3-check" type="checkbox" name="open" value="1" <?php if($fill && (bool)$n->open) echo 'checked'; ?>>
          <span>Anmeldung offen</span>
        </label>
<?php if(Discord::isConfigured()) {
    $postDiscordChecked = $fill
      ? ((int)$n->PostDiscord > 0 || AudienceSpec::isAlleUserSpec($visSpec))
      : true;
?>
        <label class="profile-pref">
          <input type="hidden" name="PostDiscord" value="0">
          <input class="w3-check" type="checkbox" name="PostDiscord" id="terminPostDiscord" value="1" <?php if($postDiscordChecked) echo 'checked'; ?>>
          <span>Discord</span>
        </label>
<?php } ?>
      </div>
      <div class="profile-field termin-visibility">
        <span class="profile-label">Sichtbar für</span>
        <div class="termin-visibility-box w3-padding w3-border <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>">
          <div id="terminVisibilityChips" class="mail-recipient-chips" aria-live="polite"></div>
          <input type="text" id="terminVisibilityInput" class="w3-input w3-border <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Gruppe, Rolle, Register, Person…" autocomplete="off">
          <div id="terminVisibilitySuggest" class="mail-recipient-suggest" hidden></div>
          <input type="hidden" name="visibilitySpec" id="terminVisibilitySpec" value="<?php
            echo htmlspecialchars(json_encode($visSpec), ENT_QUOTES, 'UTF-8');
          ?>">
          <p class="w3-small w3-margin-top mail-recipient-count-line">
            <span id="terminVisibilityCount" class="mail-recipient-count" aria-live="polite">…</span>
          </p>
        </div>
      </div>
<?php if($fill && $n && (int)$n->Index > 0) { ?>
      <div class="profile-field">
        <span class="profile-label">Neu</span>
        <div class="profile-value"><?php echo bool2string($n->new); ?></div>
      </div>
      <div class="profile-field">
        <span class="profile-label">Termin-ID</span>
        <div class="profile-value"><?php echo (int)$n->Index; ?></div>
      </div>
<?php } ?>
    </section>
  </div>
</form>
</div>
</div>

<?php if($fill && $n && (int)$n->Index > 0) {
    $delName = htmlspecialchars($n->Name.' ('.germanDate($n->Datum, 1).')', ENT_QUOTES, 'UTF-8');
    ob_start();
?>
<div id="delmodal" class="w3-modal" role="dialog" aria-modal="true" aria-labelledby="delmodalTitle" style="display:none;"
     onclick="if(event.target===this){ this.style.display='none'; }">
  <div class="w3-modal-content">
    <div class="profile-shell modal-shell confirm-delete-modal">
      <header class="profile-hero">
        <div class="profile-hero-text">
          <p class="profile-kicker">Termin</p>
          <h2 class="profile-title" id="delmodalTitle">Löschen bestätigen</h2>
        </div>
        <div class="profile-hero-actions">
          <button type="button" class="modal-close w3-button" id="delmodalClose" aria-label="Schließen">&times;</button>
        </div>
      </header>
      <div class="termin-grid">
        <section class="profile-col" aria-labelledby="delmodal-body">
          <h3 id="delmodal-body" class="profile-col-title">Bestätigung</h3>
          <p class="profile-value">
            Soll <b><?php echo $delName; ?></b> wirklich gelöscht werden?
          </p>
          <div class="profile-actions profile-actions--confirm">
            <div class="profile-actions-primary">
              <form action="index.php" method="POST">
                <input type="hidden" name="Index" value="<?php echo (int)$n->Index; ?>">
                <button class="w3-btn profile-btn-primary <?php echo htmlspecialchars($btnDelete, ENT_QUOTES, 'UTF-8'); ?> w3-border w3-mobile" type="submit" name="delete" value="delete">Löschen</button>
              </form>
            </div>
            <button type="button" class="w3-btn w3-border w3-mobile" id="delmodalCancel">Abbrechen</button>
          </div>
        </section>
      </div>
    </div>
  </div>
</div>
<script>
(function() {
  var modal = document.getElementById('delmodal');
  if(!modal) return;
  function closeDel() { modal.style.display = 'none'; }
  var closeBtn = document.getElementById('delmodalClose');
  var cancelBtn = document.getElementById('delmodalCancel');
  if(closeBtn) closeBtn.addEventListener('click', closeDel);
  if(cancelBtn) cancelBtn.addEventListener('click', closeDel);
  document.addEventListener('keydown', function(e) {
    if(e.key === 'Escape' && modal.style.display === 'block') closeDel();
  });
})();
</script>
<?php
    deferPageModalHtml(ob_get_clean());
} ?>

<script type="text/javascript">
function endToggle() {
  var on = document.getElementById('endcheck').checked;
  var field = document.getElementById('endfield');
  if(field) {
    if(on) field.removeAttribute('hidden');
    else field.setAttribute('hidden', '');
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
Group::ensureSchema();
$terminVisibilityCatalog = AudienceSpec::buildCatalog(array(
    'forMail' => false,
    'includeNamedGroups' => true,
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
      && (!spec.namedGroups || !spec.namedGroups.length);
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
