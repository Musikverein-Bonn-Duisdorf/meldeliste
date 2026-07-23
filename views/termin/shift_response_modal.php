<?php
/**
 * Shift response list modal (MELD-149, aligned with termin response modal).
 * Expects: $terminName, $shiftName, $shiftTime, $yesHtml, $maybeHtml, $noHtml,
 * optional $countYes, $countMaybe, $countNo
 */
$h = function ($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};
$subtitle = trim($shiftName.($shiftTime !== '' ? ' · '.$shiftTime : ''));
$countYes = isset($countYes) ? (int)$countYes : 0;
$countMaybe = isset($countMaybe) ? (int)$countMaybe : 0;
$countNo = isset($countNo) ? (int)$countNo : 0;
$yesColor = $GLOBALS['optionsDB']['colorBtnYes'];
$maybeColor = $GLOBALS['optionsDB']['colorBtnMaybe'];
$noColor = $GLOBALS['optionsDB']['colorBtnNo'];
?>
<div class="profile-shell modal-shell shift-response-modal termin-response-modal">
  <header class="profile-hero">
    <div class="profile-hero-text">
      <p class="profile-kicker">Schicht/Aufgabe</p>
      <h2 class="profile-title"><?php echo $h($terminName !== '' ? $terminName : 'Termin'); ?></h2>
<?php if($subtitle !== '') { ?>
      <p class="profile-subtitle"><?php echo $h($subtitle); ?></p>
<?php } ?>
    </div>
    <div class="profile-hero-actions">
      <button type="button" class="modal-close w3-button" onclick="closeModal()" aria-label="Schließen">&times;</button>
    </div>
  </header>

  <div class="melde-response-modal-lists">
    <section class="melde-response-section" aria-labelledby="shift-yes">
      <h3 id="shift-yes" class="melde-response-section-title">
        <span>Zusagen</span>
        <span class="melde-response-chip <?php echo $h($yesColor); ?>">&#10004; <?php echo $countYes; ?></span>
      </h3>
      <div class="melde-response-list">
<?php echo $yesHtml !== '' ? $yesHtml : '<div class="melde-response-empty">—</div>'; ?>
      </div>
    </section>
    <section class="melde-response-section" aria-labelledby="shift-maybe">
      <h3 id="shift-maybe" class="melde-response-section-title">
        <span>Unsicher</span>
        <span class="melde-response-chip <?php echo $h($maybeColor); ?>">? <?php echo $countMaybe; ?></span>
      </h3>
      <div class="melde-response-list">
<?php echo $maybeHtml !== '' ? $maybeHtml : '<div class="melde-response-empty">—</div>'; ?>
      </div>
    </section>
    <section class="melde-response-section" aria-labelledby="shift-no">
      <h3 id="shift-no" class="melde-response-section-title">
        <span>Absagen</span>
        <span class="melde-response-chip <?php echo $h($noColor); ?>">&#10008; <?php echo $countNo; ?></span>
      </h3>
      <div class="melde-response-list">
<?php echo $noHtml !== '' ? $noHtml : '<div class="melde-response-empty">—</div>'; ?>
      </div>
    </section>
  </div>
</div>
