<?php
/**
 * Termin response detail modal (MELD-149).
 * Expects: $terminId, $filterRegister, $terminName, $showOrchestra,
 * $orchestraFull, $orchestraActive, $showChildrenHeader, $showGuestsHeader,
 * $whoYesHtml, $whoMaybeHtml, $whoNoHtml, $countYes, $countMaybe, $countNo
 */
$h = function ($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};
$countYes = isset($countYes) ? (int)$countYes : 0;
$countMaybe = isset($countMaybe) ? (int)$countMaybe : 0;
$countNo = isset($countNo) ? (int)$countNo : 0;
$yesColor = $GLOBALS['optionsDB']['colorBtnYes'];
$maybeColor = $GLOBALS['optionsDB']['colorBtnMaybe'];
$noColor = $GLOBALS['optionsDB']['colorBtnNo'];
?>
<div class="profile-shell modal-shell termin-response-modal"
     data-termin-id="<?php echo (int)$terminId; ?>"
     data-register="<?php echo (int)$filterRegister; ?>">
  <header class="profile-hero">
    <div class="profile-hero-text">
      <p class="profile-kicker">Meldungen</p>
      <h2 class="profile-title"><?php echo $h($terminName); ?></h2>
    </div>
    <div class="profile-hero-actions">
      <button type="button" class="modal-close w3-button" onclick="closeModal()" aria-label="Schließen">&times;</button>
    </div>
  </header>

<?php if($showOrchestra) { ?>
  <div class="orchestra-panel"
       data-color-yes="<?php echo $h($yesColor); ?>"
       data-color-no="<?php echo $h($noColor); ?>"
       data-color-maybe="<?php echo $h($maybeColor); ?>"
       data-color-disabled="<?php echo $h(isset($GLOBALS['optionsDB']['colorDisabled']) ? $GLOBALS['optionsDB']['colorDisabled'] : ''); ?>">
    <div class="orchestra-panel-header">
      <div class="orchestra-panel-title"><b>Besetzung</b></div>
      <div class="orchestra-panel-toggle">
        <label class="w3-small">
          <input type="checkbox" class="w3-check" onchange="toggleActiveOrchestra(this)">
          Nur aktive Besetzung
        </label>
      </div>
    </div>
    <div class="orchestra-layout orchestra-layout--full">
      <div class="orchestra-svg-wrap">
<?php echo $orchestraFull; ?>
      </div>
    </div>
    <div class="orchestra-layout orchestra-layout--active" hidden>
      <div class="orchestra-svg-wrap">
<?php echo $orchestraActive; ?>
      </div>
    </div>
  </div>
<?php } ?>

  <div class="melde-response-modal-lists">
    <section class="melde-response-section" aria-labelledby="resp-yes">
      <h3 id="resp-yes" class="melde-response-section-title">
        <span>Zusagen</span>
        <span class="melde-response-chip <?php echo $h($yesColor); ?>">&#10004; <?php echo $countYes; ?></span>
      </h3>
<?php if($showChildrenHeader || $showGuestsHeader) { ?>
      <p class="melde-response-section-hint">
<?php
    $hints = array();
    if($showChildrenHeader) $hints[] = 'Kinder';
    if($showGuestsHeader) $hints[] = 'Gäste';
    echo $h(implode(' · ', $hints).' erscheinen als Zusatz bei der Person');
?>
      </p>
<?php } ?>
      <div class="melde-response-list">
<?php echo $whoYesHtml !== '' ? $whoYesHtml : '<div class="melde-response-empty">—</div>'; ?>
      </div>
    </section>

    <section class="melde-response-section" aria-labelledby="resp-maybe">
      <h3 id="resp-maybe" class="melde-response-section-title">
        <span>Unsicher</span>
        <span class="melde-response-chip <?php echo $h($maybeColor); ?>">? <?php echo $countMaybe; ?></span>
      </h3>
      <div class="melde-response-list">
<?php echo $whoMaybeHtml !== '' ? $whoMaybeHtml : '<div class="melde-response-empty">—</div>'; ?>
      </div>
    </section>

    <section class="melde-response-section" aria-labelledby="resp-no">
      <h3 id="resp-no" class="melde-response-section-title">
        <span>Absagen</span>
        <span class="melde-response-chip <?php echo $h($noColor); ?>">&#10008; <?php echo $countNo; ?></span>
      </h3>
      <div class="melde-response-list">
<?php echo $whoNoHtml !== '' ? $whoNoHtml : '<div class="melde-response-empty">—</div>'; ?>
      </div>
    </section>
  </div>
</div>
