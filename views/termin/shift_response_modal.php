<?php
/**
 * Shift response list modal (profile-shell).
 * Expects: $terminName, $shiftName, $shiftTime, $yesHtml, $maybeHtml, $noHtml
 */
$h = function ($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};
$subtitle = trim($shiftName.($shiftTime !== '' ? ' · '.$shiftTime : ''));
?>
<div class="profile-shell modal-shell shift-response-modal">
  <header class="profile-hero">
    <div class="profile-hero-text">
      <p class="profile-kicker">Schicht</p>
      <h2 class="profile-title"><?php echo $h($terminName !== '' ? $terminName : 'Termin'); ?></h2>
<?php if($subtitle !== '') { ?>
      <p class="profile-value" style="font-weight:400;margin:0.25rem 0 0;"><?php echo $h($subtitle); ?></p>
<?php } ?>
    </div>
    <div class="profile-hero-actions">
      <button type="button" class="modal-close w3-button" onclick="closeModal()" aria-label="Schließen">&times;</button>
    </div>
  </header>

  <div class="profile-grid profile-grid--3">
    <section class="profile-col" aria-labelledby="shift-yes">
      <h3 id="shift-yes" class="profile-col-title">Zusagen</h3>
      <div class="shift-response-list"><?php echo $yesHtml; ?></div>
    </section>
    <section class="profile-col" aria-labelledby="shift-maybe">
      <h3 id="shift-maybe" class="profile-col-title">Unsicher</h3>
      <div class="shift-response-list"><?php echo $maybeHtml; ?></div>
    </section>
    <section class="profile-col" aria-labelledby="shift-no">
      <h3 id="shift-no" class="profile-col-title">Absagen</h3>
      <div class="shift-response-list"><?php echo $noHtml; ?></div>
    </section>
  </div>
</div>
