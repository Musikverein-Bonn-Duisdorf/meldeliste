<?php
/**
 * MELD-249: Forced intro / changelog multipage modal.
 * Expects: $introKind (intro|changelog), $introPages (list), $introCsrf (string)
 */
if(empty($introKind) || empty($introPages) || !is_array($introPages)) {
    return;
}
$introKind = $introKind === 'changelog' ? 'changelog' : 'intro';
$kicker = $introKind === 'changelog' ? 'Änderungen' : 'Einführung';
$title0 = isset($introPages[0]['title']) ? (string)$introPages[0]['title'] : $kicker;
$csrf = isset($introCsrf) ? (string)$introCsrf : '';
$pagesJson = htmlspecialchars(json_encode(array_values($introPages), JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
ob_start();
?>
<div id="introModal" class="w3-modal" role="dialog" aria-modal="true" aria-labelledby="introModalTitle"
     data-intro-kind="<?php echo htmlspecialchars($introKind, ENT_QUOTES, 'UTF-8'); ?>"
     data-intro-csrf="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>"
     data-intro-pages="<?php echo $pagesJson; ?>"
     style="display:block;">
  <div class="w3-modal-content">
    <div class="profile-shell modal-shell intro-modal-shell">
      <header class="profile-hero">
        <div class="profile-hero-text">
          <p class="profile-kicker"><?php echo htmlspecialchars($kicker, ENT_QUOTES, 'UTF-8'); ?></p>
          <h2 class="profile-title" id="introModalTitle"><?php echo htmlspecialchars($title0, ENT_QUOTES, 'UTF-8'); ?></h2>
        </div>
        <div class="profile-hero-actions">
          <button type="button" class="modal-close w3-button" id="introModalClose" aria-label="Schließen">&times;</button>
        </div>
      </header>
      <div class="intro-modal-body confirm-delete-body">
        <div id="introModalContent" class="intro-modal-content"></div>
        <p class="w3-small w3-opacity" id="introModalProgress" aria-live="polite"></p>
        <div id="introModalAckWrap" class="profile-field" hidden>
          <label class="profile-label intro-modal-ack-label" for="introModalAck">
            <input type="checkbox" id="introModalAck"> gelesen und verstanden
          </label>
        </div>
        <div class="profile-actions profile-actions--confirm intro-modal-actions">
          <div class="profile-actions-primary">
            <button type="button" class="w3-btn profile-btn-primary w3-border w3-mobile" id="introModalNext">Weiter</button>
            <button type="button" class="w3-btn profile-btn-primary w3-border w3-mobile" id="introModalConfirm" hidden disabled>Bestätigen</button>
          </div>
          <button type="button" class="w3-btn w3-border w3-mobile" id="introModalPrev" hidden>Zurück</button>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="<?php echo assetUrl('js/introModal.js'); ?>"></script>
<?php
deferPageModalHtml(ob_get_clean());
