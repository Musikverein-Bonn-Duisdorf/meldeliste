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
$inModal = !empty($calendarSubscribeInModal);
if(!$inModal) {
?>
<div class="w3-panel w3-border w3-padding w3-margin-top <?php echo htmlspecialchars($GLOBALS['optionsDB']['colorInputBackground'], ENT_QUOTES, 'UTF-8'); ?>">
  <h3><i class="fas fa-calendar-plus" aria-hidden="true"></i> Kalender abonnieren</h3>
<?php } ?>
  <p class="w3-small">
    Trage den Link in Google Kalender, Apple Kalender oder Outlook als <b>Kalender-URL / Abonnement</b> ein.
    Neue und geänderte Termine erscheinen automatisch — das Aktualisierungsintervall steuert dein Kalender (oft erst nach einigen Stunden).
    Zu- und Absagen weiterhin hier in der Meldeliste.
  </p>
  <p class="w3-small w3-break">
    <label for="<?php echo htmlspecialchars($uid, ENT_QUOTES, 'UTF-8'); ?>-https"><b>HTTPS</b></label><br>
    <input id="<?php echo htmlspecialchars($uid, ENT_QUOTES, 'UTF-8'); ?>-https" class="w3-input w3-border" type="text" readonly value="<?php echo htmlspecialchars($calendarHttps, ENT_QUOTES, 'UTF-8'); ?>">
  </p>
  <p class="w3-small w3-break">
    <label for="<?php echo htmlspecialchars($uid, ENT_QUOTES, 'UTF-8'); ?>-webcal"><b>webcal</b> (manche Apps)</label><br>
    <input id="<?php echo htmlspecialchars($uid, ENT_QUOTES, 'UTF-8'); ?>-webcal" class="w3-input w3-border" type="text" readonly value="<?php echo htmlspecialchars($calendarWebcal, ENT_QUOTES, 'UTF-8'); ?>">
  </p>
  <p class="w3-margin-top">
    <button type="button" class="w3-button w3-border <?php echo htmlspecialchars($GLOBALS['optionsDB']['colorBtnSubmit'], ENT_QUOTES, 'UTF-8'); ?>" data-copy-target="<?php echo htmlspecialchars($uid, ENT_QUOTES, 'UTF-8'); ?>-https">HTTPS kopieren</button>
    <button type="button" class="w3-button w3-border" data-copy-target="<?php echo htmlspecialchars($uid, ENT_QUOTES, 'UTF-8'); ?>-webcal">webcal kopieren</button>
  </p>
  <p class="w3-small"><a href="help.php#help-kalender-abo">Hilfe: Kalender abonnieren</a></p>
<?php if(!$inModal) { ?>
</div>
<?php } ?>
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
