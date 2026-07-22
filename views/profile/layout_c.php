<?php
/**
 * Layout C: kompakt — Prefs zuerst; Stammdaten aufklappbar; App/Kalender als Sheet.
 */
$displayName = $fill ? trim($n->Vorname.' '.$n->Nachname) : 'Neues Profil';
$displayInstrument = $fill ? (string)$n->getInstrument() : '';
$mailHint = array();
if($fill) {
    if((int)$n->getMail) $mailHint[] = 'E-Mail';
    if((int)$n->notifyInbox) $mailHint[] = 'Nachrichten';
}
$mailHintLabel = count($mailHint) ? implode(' · ', $mailHint) : 'keine Kanäle';
?>
<div class="profile-panel profile-layout-c w3-padding w3-border <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" data-profile-layout="c">
  <header class="profile-c-hero">
    <div class="profile-c-hero-text">
      <h3 class="profile-c-name"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></h3>
      <p class="profile-c-meta w3-small">
<?php if($displayInstrument !== '') { ?>
        <span><?php echo htmlspecialchars($displayInstrument, ENT_QUOTES, 'UTF-8'); ?></span>
        <span aria-hidden="true"> · </span>
<?php } ?>
        <span><?php echo htmlspecialchars($mailHintLabel, ENT_QUOTES, 'UTF-8'); ?></span>
      </p>
    </div>
    <div class="profile-c-hero-actions">
      <button type="button" class="w3-btn w3-border w3-mobile" data-profile-c-toggle="edit">Bearbeiten</button>
<?php if($showAppLoginLink) { ?>
      <button type="button" class="w3-btn w3-border w3-mobile" data-profile-sheet="app">App verbinden</button>
<?php } ?>
<?php if($showCalendarSubscribe) { ?>
      <button type="button" class="w3-btn w3-border w3-mobile" data-profile-sheet="cal">Kalender</button>
<?php } ?>
    </div>
  </header>

  <form class="profile-form" action="<?php echo htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8'); ?>" method="POST">
    <?php include __DIR__.'/form_hidden.php'; ?>
    <input type="hidden" name="Index" <?php if($fill) echo 'value="'.(int)$n->Index.'"'; ?>>

    <section class="profile-c-prefs" aria-labelledby="profile-c-prefs-title">
      <h3 id="profile-c-prefs-title">Benachrichtigungen</h3>
      <?php include __DIR__.'/fields_prefs.php'; ?>
      <?php include __DIR__.'/fields_actions.php'; ?>
    </section>

    <section class="profile-c-edit" data-profile-c-edit hidden>
      <h3>Stammdaten</h3>
      <?php include __DIR__.'/fields_stammdaten.php'; ?>
<?php if($fill && $edit != 2) { ?>
      <p class="w3-small w3-text-gray">Admin-Aktionen stehen in der Leiste unter „speichern“.</p>
<?php } ?>
      <button type="button" class="w3-btn w3-border w3-mobile" data-profile-c-toggle="edit">Schließen</button>
    </section>
  </form>
</div>

<?php if($showAppLoginLink) { ?>
<div id="profile-sheet-app" class="profile-sheet" hidden>
  <div class="profile-sheet-backdrop" data-profile-sheet-close></div>
  <div class="profile-sheet-panel w3-border <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" role="dialog" aria-modal="true" aria-labelledby="profile-sheet-app-title">
    <header class="profile-sheet-head">
      <h3 id="profile-sheet-app-title"><i class="fas fa-qrcode" aria-hidden="true"></i> App-Login</h3>
      <button type="button" class="w3-button" data-profile-sheet-close aria-label="Schließen">&times;</button>
    </header>
    <div class="profile-sheet-body w3-center">
      <p class="w3-small">Mit der Meldeliste-App scannen oder Link öffnen.</p>
      <div id="app-login-qr" class="w3-margin-bottom profile-qr" style="display:inline-block;" data-alink-url="<?php echo htmlspecialchars($appLoginUrl, ENT_QUOTES, 'UTF-8'); ?>"></div>
      <div class="w3-small w3-break"><a href="<?php echo htmlspecialchars($appLoginUrl, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($appLoginUrl, ENT_QUOTES, 'UTF-8'); ?></a></div>
    </div>
  </div>
</div>
<?php } ?>

<?php if($showCalendarSubscribe) { ?>
<div id="profile-sheet-cal" class="profile-sheet" hidden>
  <div class="profile-sheet-backdrop" data-profile-sheet-close></div>
  <div class="profile-sheet-panel w3-border <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" role="dialog" aria-modal="true" aria-labelledby="profile-sheet-cal-title">
    <header class="profile-sheet-head">
      <h3 id="profile-sheet-cal-title"><i class="fas fa-calendar-plus" aria-hidden="true"></i> Kalender abonnieren</h3>
      <button type="button" class="w3-button" data-profile-sheet-close aria-label="Schließen">&times;</button>
    </header>
    <div class="profile-sheet-body">
<?php
        $calendarSubscribeUid = 'profile-cal-c';
        $calendarSubscribeInModal = true;
        include dirname(__DIR__).'/calendar/subscribe.php';
?>
    </div>
  </div>
</div>
<?php } ?>
