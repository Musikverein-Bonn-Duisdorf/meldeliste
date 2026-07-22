<?php
/**
 * Profil: 3 Spalten in der Breite, ohne Instruktions-Texte.
 */
$profileTitle = $fill
    ? trim((string)$n->Vorname.' '.(string)$n->Nachname)
    : 'Neues Profil';
if($profileTitle === '') {
    $profileTitle = 'Profil';
}
if($edit == 2) {
    $profileKicker = 'Mein Profil';
}
elseif(!$fill) {
    $profileKicker = 'Neuer Nutzer';
}
else {
    $profileKicker = 'Nutzer bearbeiten';
}
?>
<div class="profile-shell profile-layout-a">
  <form class="profile-form" action="<?php echo htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8'); ?>" method="POST">
    <?php include __DIR__.'/form_hidden.php'; ?>
    <input type="hidden" name="Index" <?php if($fill) echo 'value="'.(int)$n->Index.'"'; ?>>

    <header class="profile-hero">
      <div class="profile-hero-text">
        <p class="profile-kicker"><?php echo htmlspecialchars($profileKicker, ENT_QUOTES, 'UTF-8'); ?></p>
        <h2 class="profile-title"><?php echo htmlspecialchars($profileTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
      </div>
      <div class="profile-hero-actions">
        <?php include __DIR__.'/fields_actions.php'; ?>
      </div>
    </header>

    <div class="profile-grid profile-grid--3">
      <section class="profile-col" aria-labelledby="profile-col-person">
        <h3 id="profile-col-person" class="profile-col-title">Person</h3>
        <?php include __DIR__.'/fields_person.php'; ?>
      </section>
      <section class="profile-col" aria-labelledby="profile-col-kontakt">
        <h3 id="profile-col-kontakt" class="profile-col-title">Kontakt</h3>
        <?php include __DIR__.'/fields_kontakt.php'; ?>
      </section>
      <section class="profile-col" aria-labelledby="profile-col-notify">
        <h3 id="profile-col-notify" class="profile-col-title">Benachrichtigungen</h3>
        <?php include __DIR__.'/fields_prefs.php'; ?>
      </section>
    </div>
  </form>

<?php if($showAppLoginLink || $showCalendarSubscribe) { ?>
  <div class="profile-extras">
<?php if($showAppLoginLink) { ?>
    <details class="profile-details" id="profile-app-login">
      <summary><i class="fas fa-qrcode" aria-hidden="true"></i> App-Login</summary>
      <div class="profile-details-body w3-center">
        <div id="app-login-qr" class="w3-margin-bottom profile-qr" style="display:inline-block;" data-alink-url="<?php echo htmlspecialchars($appLoginUrl, ENT_QUOTES, 'UTF-8'); ?>"></div>
        <div class="w3-small w3-break"><a href="<?php echo htmlspecialchars($appLoginUrl, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($appLoginUrl, ENT_QUOTES, 'UTF-8'); ?></a></div>
      </div>
    </details>
<?php } ?>
<?php if($showCalendarSubscribe) { ?>
    <details class="profile-details" id="profile-calendar">
      <summary><i class="fas fa-calendar-plus" aria-hidden="true"></i> Kalender</summary>
      <div class="profile-details-body">
<?php
        $calendarSubscribeUid = 'profile-cal-a';
        $calendarSubscribeInModal = true;
        include dirname(__DIR__).'/calendar/subscribe.php';
?>
      </div>
    </details>
<?php } ?>
  </div>
<?php } ?>
</div>
