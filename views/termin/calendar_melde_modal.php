<?php
/**
 * Calendar quick-response modal (profile-shell).
 * Expects: $termin (Termin), $timeInfo (string), $buttonsHtml (string|null), $capacityFull (bool)
 */
$t = $termin;
$btnEdit = $GLOBALS['optionsDB']['colorBtnEdit'];
$h = function ($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};
$ort = trim((string)$t->Ort1);
$hasShifts = (bool)$t->Shifts;
?>
<div class="profile-shell modal-shell calendar-melde-modal" data-termin-id="<?php echo (int)$t->Index; ?>">
  <header class="profile-hero">
    <div class="profile-hero-text">
      <p class="profile-kicker">Meldung</p>
      <h2 class="profile-title"><?php echo $h($t->Name !== '' && $t->Name !== null ? $t->Name : 'Termin'); ?></h2>
    </div>
    <div class="profile-hero-actions">
      <div class="profile-actions">
        <div class="profile-actions-primary">
          <button type="button" class="w3-btn profile-btn-primary <?php echo $h($btnEdit); ?> w3-border"
                  onclick="openModal('termin', <?php echo (int)$t->Index; ?>)">
            Weitere Optionen
          </button>
        </div>
      </div>
      <button type="button" class="modal-close w3-button" onclick="closeModal()" aria-label="Schließen">&times;</button>
    </div>
  </header>

  <div class="termin-grid">
    <section class="profile-col" aria-labelledby="cal-melde-meta">
      <h3 id="cal-melde-meta" class="profile-col-title">Termin</h3>
      <div class="profile-field">
        <span class="profile-label">Zeit</span>
        <div class="profile-value"><?php echo $h($timeInfo); ?></div>
      </div>
<?php if($ort !== '') { ?>
      <div class="profile-field">
        <span class="profile-label">Ort</span>
        <div class="profile-value"><?php echo $h($ort); ?></div>
      </div>
<?php } ?>
    </section>

    <section class="profile-col" aria-labelledby="cal-melde-response">
      <h3 id="cal-melde-response" class="profile-col-title">Rückmeldung</h3>
<?php if($hasShifts) { ?>
      <p class="profile-value">Schichten &amp; Aufgaben — bitte unter Weitere Optionen melden.</p>
<?php } elseif($capacityFull) { ?>
      <p class="profile-value">Alle Plätze belegt.</p>
<?php } elseif($buttonsHtml !== null && $buttonsHtml !== '') { ?>
      <div class="profile-field calendar-melde-buttons" id="calendarMeldeBtns<?php echo (int)$t->Index; ?>">
        <div class="melde-btns melde-btns--modal"><?php echo $buttonsHtml; ?></div>
      </div>
<?php } else { ?>
      <p class="profile-value">Keine Meldeoptionen verfügbar.</p>
<?php } ?>
    </section>
  </div>
</div>
