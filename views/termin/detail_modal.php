<?php
/**
 * Termin detail modal (profile-shell layout).
 * Expects: $termin, $userId, $instrument
 */
$t = $termin;
$btnEdit = $GLOBALS['optionsDB']['colorBtnEdit'];
$btnSubmit = $GLOBALS['optionsDB']['colorBtnSubmit'];
$inputBg = $GLOBALS['optionsDB']['colorInputBackground'];
$canEdit = requirePermission('perm_editAppmnts');
$canResponse = requirePermission('perm_editResponse');
$canMail = requirePermission('perm_sendEmail');
$h = function ($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};
$dash = '—';
$val = function ($s) use ($h, $dash) {
    $s = (string)$s;
    return $s !== '' ? $h($s) : $dash;
};

$mapsQuery = '';
if(!empty($GLOBALS['googlemapsapi']) && ($t->Ort1 || $t->Ort2)) {
    $mapsQuery = rawurlencode(trim(implode(' ', array_filter(array(
        (string)$t->Ort1,
        (string)$t->Ort2,
        (string)$t->Ort3,
        (string)$t->Ort4,
    )))));
}
?>
<div class="profile-shell modal-shell termin-detail-modal">
  <header class="profile-hero">
    <div class="profile-hero-text">
      <p class="profile-kicker">Termin</p>
      <h2 class="profile-title"><?php echo $h($t->Name !== '' && $t->Name !== null ? $t->Name : 'Termin'); ?></h2>
    </div>
    <div class="profile-hero-actions">
<?php if($canEdit) { ?>
      <div class="profile-actions">
        <div class="profile-actions-primary">
          <form action="new-termin.php" method="POST">
            <button class="w3-btn profile-btn-primary <?php echo $h($btnEdit); ?> w3-border w3-mobile" type="submit" name="id" value="<?php echo (int)$t->Index; ?>">Bearbeiten</button>
          </form>
        </div>
        <details class="profile-actions-more">
          <summary>Weitere Aktionen</summary>
          <div class="profile-actions-secondary">
            <form action="new-termin.php" method="POST">
              <button class="w3-btn <?php echo $h($btnEdit); ?> w3-border w3-mobile" type="submit" name="copy" value="<?php echo (int)$t->Index; ?>">Kopieren</button>
            </form>
<?php if($canResponse) { ?>
            <form action="tracking.php" method="POST">
              <button class="w3-btn <?php echo $h($btnEdit); ?> w3-border w3-mobile" type="submit" name="termin" value="<?php echo (int)$t->Index; ?>">Anwesenheitsliste</button>
            </form>
<?php } ?>
<?php if($canMail) { ?>
            <a class="w3-btn <?php echo $h($btnEdit); ?> w3-border w3-mobile" href="mail.php?new=1&amp;termin=<?php echo (int)$t->Index; ?>">Email an Teilnehmer</a>
<?php } ?>
<?php if($t->Shifts) { ?>
            <form action="edit-shifts.php" method="POST">
              <button class="w3-btn <?php echo $h($btnEdit); ?> w3-border w3-mobile" type="submit" name="Termin" value="<?php echo (int)$t->Index; ?>">Schichten bearbeiten</button>
            </form>
<?php } ?>
          </div>
        </details>
      </div>
<?php } elseif($canResponse || $canMail) { ?>
      <div class="profile-actions">
        <div class="profile-actions-primary">
<?php if($canResponse) { ?>
          <form action="tracking.php" method="POST">
            <button class="w3-btn profile-btn-primary <?php echo $h($btnEdit); ?> w3-border w3-mobile" type="submit" name="termin" value="<?php echo (int)$t->Index; ?>">Anwesenheitsliste</button>
          </form>
<?php } elseif($canMail) { ?>
          <a class="w3-btn profile-btn-primary <?php echo $h($btnEdit); ?> w3-border w3-mobile" href="mail.php?new=1&amp;termin=<?php echo (int)$t->Index; ?>">Email an Teilnehmer</a>
<?php } ?>
        </div>
<?php if($canResponse && $canMail) { ?>
        <details class="profile-actions-more">
          <summary>Weitere Aktionen</summary>
          <div class="profile-actions-secondary">
            <a class="w3-btn <?php echo $h($btnEdit); ?> w3-border w3-mobile" href="mail.php?new=1&amp;termin=<?php echo (int)$t->Index; ?>">Email an Teilnehmer</a>
          </div>
        </details>
<?php } ?>
      </div>
<?php } ?>
      <button type="button" class="modal-close w3-button" onclick="closeModal()" aria-label="Schließen">&times;</button>
    </div>
  </header>

  <div class="termin-grid">
    <section class="profile-col" aria-labelledby="termin-detail-was">
      <h3 id="termin-detail-was" class="profile-col-title">Was</h3>
      <div class="profile-field">
        <span class="profile-label">Veranstaltung</span>
        <div class="profile-value"><?php echo $val($t->Name); ?></div>
      </div>
      <div class="profile-field">
        <span class="profile-label">Beschreibung</span>
        <div class="profile-value"><?php echo $val($t->Beschreibung); ?></div>
      </div>
    </section>

    <section class="profile-col" aria-labelledby="termin-detail-wann">
      <h3 id="termin-detail-wann" class="profile-col-title">Wann</h3>
      <div class="profile-field">
        <span class="profile-label">Datum</span>
        <div class="profile-value"><?php echo $h($t->getGermanDate()); ?></div>
      </div>
      <div class="termin-field-pair">
        <div class="profile-field">
          <span class="profile-label">Beginn</span>
          <div class="profile-value"><?php echo $t->Uhrzeit ? $h(sql2time($t->Uhrzeit)) : $dash; ?></div>
        </div>
        <div class="profile-field">
          <span class="profile-label">Ende</span>
          <div class="profile-value"><?php echo $t->Uhrzeit2 ? $h(sql2time($t->Uhrzeit2)) : $dash; ?></div>
        </div>
      </div>
<?php if(($t->Abfahrt && !empty($GLOBALS['optionsDB']['showTravelTime'])) || !empty($GLOBALS['optionsDB']['showVehicle'])) { ?>
      <div class="termin-field-pair">
<?php if(!empty($GLOBALS['optionsDB']['showVehicle'])) { ?>
        <div class="profile-field">
          <span class="profile-label">Fahrzeug</span>
          <div class="profile-value"><?php echo $val($t->vName); ?></div>
        </div>
<?php } ?>
<?php if($t->Abfahrt && !empty($GLOBALS['optionsDB']['showTravelTime'])) { ?>
        <div class="profile-field">
          <span class="profile-label">Abfahrt</span>
          <div class="profile-value"><?php echo $h(sql2time($t->Abfahrt)); ?></div>
        </div>
<?php } ?>
      </div>
<?php } ?>
    </section>

    <section class="profile-col" aria-labelledby="termin-detail-wo">
      <h3 id="termin-detail-wo" class="profile-col-title">Wo</h3>
      <div class="profile-field">
        <span class="profile-label">Ort</span>
        <div class="profile-value"><?php echo $val($t->Ort1); ?></div>
      </div>
      <div class="profile-field">
        <span class="profile-label">Straße</span>
        <div class="profile-value"><?php echo $val($t->Ort2); ?></div>
      </div>
      <div class="termin-field-pair">
        <div class="profile-field">
          <span class="profile-label">Stadtteil</span>
          <div class="profile-value"><?php echo $val($t->Ort3); ?></div>
        </div>
        <div class="profile-field">
          <span class="profile-label">Stadt</span>
          <div class="profile-value"><?php echo $val($t->Ort4); ?></div>
        </div>
      </div>
<?php if($mapsQuery !== '') { ?>
      <div class="profile-field termin-detail-map">
        <span class="profile-label">Karte</span>
        <div class="profile-value profile-value--map">
          <iframe title="Karte" width="100%" height="180" frameborder="0" style="border:0" src="https://www.google.com/maps/embed/v1/place?key=<?php echo $h($GLOBALS['googlemapsapi']); ?>&amp;q=<?php echo $h($mapsQuery); ?>" allowfullscreen></iframe>
        </div>
      </div>
<?php } ?>
    </section>

    <section class="profile-col" aria-labelledby="termin-detail-optionen">
      <h3 id="termin-detail-optionen" class="profile-col-title">Optionen</h3>
<?php if($t->Capacity) { ?>
      <div class="profile-field">
        <span class="profile-label">Kapazität</span>
        <div class="profile-value"><?php echo (int)$t->Capacity; ?></div>
      </div>
<?php } ?>
      <div class="profile-field">
        <span class="profile-label">Besetzung</span>
        <div class="profile-value"><?php echo bool2string($t->Auftritt); ?></div>
      </div>
      <div class="profile-field">
        <span class="profile-label">Schichtdienst</span>
        <div class="profile-value"><?php echo bool2string($t->Shifts); ?></div>
      </div>
<?php if($t->Auftritt) { ?>
      <div class="profile-field">
        <span class="profile-label">Instrument</span>
        <div class="termin-detail-instrument">
          <select id="iSelect<?php echo (int)$t->Index; ?>" class="w3-input w3-border profile-control <?php echo $h($inputBg); ?>" name="Instrument"><?php echo instrumentOption($instrument ? $instrument : 0); ?></select>
          <button type="button" class="w3-button <?php echo $h($btnEdit); ?>" onclick="changeInstrument(<?php echo (int)$userId; ?>, <?php echo (int)$t->Index; ?>);" aria-label="Instrument speichern"><i class="fas fa-save"></i></button>
        </div>
      </div>
<?php } ?>
<?php if($canEdit) { ?>
      <div class="termin-field-pair">
        <div class="profile-field">
          <span class="profile-label">Anmeldung offen</span>
          <div class="profile-value"><?php echo bool2string($t->open); ?></div>
        </div>
        <div class="profile-field">
          <span class="profile-label">Neu</span>
          <div class="profile-value"><?php echo bool2string($t->new); ?></div>
        </div>
      </div>
      <div class="profile-field">
        <span class="profile-label">ID</span>
        <div class="profile-value"><?php echo (int)$t->Index; ?></div>
      </div>
<?php } ?>
    </section>
  </div>
<?php if($canEdit) { ?>
  <div class="termin-detail-audience">
    <div class="profile-field termin-visibility">
      <span class="profile-label">Sichtbar für</span>
      <div class="profile-value profile-value--chips">
<?php
    $visForChips = $t->getVisibilitySpecArray();
    foreach($t->getGuestMusiciansArray() as $gid) {
        $gid = (int)$gid;
        if($gid > 0 && !in_array($gid, $visForChips['users'], true)) {
            $visForChips['users'][] = $gid;
        }
    }
    echo AudienceSpec::renderChipsHtml($visForChips, array(
        'allowMailGroups' => true,
        'ariaLabel' => 'sichtbar für',
    ));
?>
      </div>
    </div>
  </div>
<?php } ?>
</div>
