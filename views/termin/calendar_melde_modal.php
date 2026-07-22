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
      <button type="button" class="modal-close w3-button" onclick="closeModal()" aria-label="Schließen">&times;</button>
    </div>
  </header>

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

<?php if($hasShifts) { ?>
  <p class="profile-value" style="margin:0.75rem 0;">Schichten — Meldung unter Weitere Optionen</p>
<?php } elseif($capacityFull) { ?>
  <p class="profile-value" style="margin:0.75rem 0;">Alle Plätze belegt</p>
<?php } elseif($buttonsHtml !== null && $buttonsHtml !== '') { ?>
  <div class="profile-field calendar-melde-buttons" id="calendarMeldeBtns<?php echo (int)$t->Index; ?>">
    <span class="profile-label">Rückmeldung</span>
    <div class="w3-row"><?php echo $buttonsHtml; ?></div>
  </div>
<?php } ?>

  <div class="profile-actions" style="margin-top:1rem;">
    <div class="profile-actions-primary">
      <button type="button" class="w3-btn profile-btn-primary <?php echo $h($btnEdit); ?> w3-border w3-mobile"
              onclick="openModal('termin', <?php echo (int)$t->Index; ?>)">
        <i class="fas fa-info-circle" aria-hidden="true"></i> Weitere Optionen
      </button>
    </div>
  </div>
</div>
