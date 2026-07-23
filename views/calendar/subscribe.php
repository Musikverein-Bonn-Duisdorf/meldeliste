<?php
/**
 * Personal calendar subscription block (MELD-127).
 * Expects $n = User with activeLink, or $calendarHttps / $calendarWebcal strings.
 * Set $calendarSubscribeInModal = true to omit outer panel (content for a modal).
 */
if(!isset($calendarHttps) || !isset($calendarWebcal)) {
    if(!isset($n) || !is_object($n) || !(int)$n->Index) {
        return;
    }
    if($n->activeLink === null || $n->activeLink === '') {
        return;
    }
    $calendarHttps = $n->getCalendarLink();
    $calendarWebcal = $n->getCalendarWebcalLink();
}
$calendarHttps = (string)$calendarHttps;
$calendarWebcal = (string)$calendarWebcal;
if($calendarHttps === '') {
    return;
}
$uid = isset($calendarSubscribeUid) ? (string)$calendarSubscribeUid : 'cal-sub';
$uidEsc = htmlspecialchars($uid, ENT_QUOTES, 'UTF-8');
$inModal = !empty($calendarSubscribeInModal);
$quiet = !empty($calendarSubscribeQuiet) || $inModal;
$inputBg = htmlspecialchars($GLOBALS['optionsDB']['colorInputBackground'], ENT_QUOTES, 'UTF-8');
$btnSubmit = htmlspecialchars($GLOBALS['optionsDB']['colorBtnSubmit'], ENT_QUOTES, 'UTF-8');
if(!$inModal) {
?>
<div class="w3-panel w3-border w3-padding w3-margin-top calendar-subscribe-block <?php echo $inputBg; ?>">
  <h3><i class="fas fa-calendar-plus" aria-hidden="true"></i> Persönlichen Kalender abonnieren</h3>
<?php } else { ?>
<div class="calendar-subscribe-block calendar-subscribe-block--modal">
<?php } ?>
<?php if(!$quiet) { ?>
  <p class="w3-small">
    Trage den Link in Google Kalender, Apple Kalender oder Outlook als <b>Kalender-URL / Abonnement</b> ein.
    Neue und geänderte Termine erscheinen automatisch — das Aktualisierungsintervall steuert dein Kalender (oft erst nach einigen Stunden).
    Zu- und Absagen weiterhin hier in der Meldeliste.
  </p>
<?php } ?>
  <div class="profile-field">
    <label class="profile-label" for="<?php echo $uidEsc; ?>-https">HTTPS</label>
    <input id="<?php echo $uidEsc; ?>-https"
           class="w3-input w3-border profile-control calendar-subscribe-url <?php echo $inputBg; ?>"
           type="text" readonly
           value="<?php echo htmlspecialchars($calendarHttps, ENT_QUOTES, 'UTF-8'); ?>">
  </div>
  <div class="profile-field">
    <label class="profile-label" for="<?php echo $uidEsc; ?>-webcal">webcal</label>
    <input id="<?php echo $uidEsc; ?>-webcal"
           class="w3-input w3-border profile-control calendar-subscribe-url <?php echo $inputBg; ?>"
           type="text" readonly
           value="<?php echo htmlspecialchars($calendarWebcal, ENT_QUOTES, 'UTF-8'); ?>">
  </div>
  <div class="profile-actions calendar-subscribe-actions">
    <div class="profile-actions-primary">
      <button type="button"
              class="w3-btn profile-btn-primary <?php echo $btnSubmit; ?> w3-border"
              data-copy-target="<?php echo $uidEsc; ?>-https">HTTPS kopieren</button>
    </div>
    <button type="button"
            class="w3-btn w3-border"
            data-copy-target="<?php echo $uidEsc; ?>-webcal">webcal kopieren</button>
  </div>
  <p class="profile-inline-link"><a href="help.php#help-kalender-abo">Hilfe: Kalender</a></p>
</div>
<script>
(function () {
  document.querySelectorAll('[data-copy-target]').forEach(function (btn) {
    if(btn.getAttribute('data-copy-bound') === '1') return;
    btn.setAttribute('data-copy-bound', '1');
    btn.addEventListener('click', function () {
      var id = btn.getAttribute('data-copy-target');
      var el = id ? document.getElementById(id) : null;
      if(!el) return;
      var text = el.value || '';
      if(navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function () {
          btn.textContent = 'Kopiert';
          setTimeout(function () {
            btn.textContent = id.indexOf('webcal') >= 0 ? 'webcal kopieren' : 'HTTPS kopieren';
          }, 1500);
        });
        return;
      }
      el.select();
      try { document.execCommand('copy'); } catch (e) {}
    });
  });
})();
</script>
