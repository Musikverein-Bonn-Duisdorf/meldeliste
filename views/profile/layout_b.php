<?php
/**
 * Layout B: geführte Schritte (Tabs) — Profil · Benachrichtigungen · Geräte & Kalender.
 */
$step = isset($profileStep) ? $profileStep : 'notify';
?>
<div class="profile-panel profile-layout-b w3-padding w3-border <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" data-profile-layout="b">
  <nav class="profile-steps" role="tablist" aria-label="Profil-Schritte">
    <button type="button" class="profile-step<?php echo $step === 'profil' ? ' is-active' : ''; ?>" role="tab" aria-selected="<?php echo $step === 'profil' ? 'true' : 'false'; ?>" data-profile-step="profil">1 · Profil</button>
    <button type="button" class="profile-step<?php echo $step === 'notify' ? ' is-active' : ''; ?>" role="tab" aria-selected="<?php echo $step === 'notify' ? 'true' : 'false'; ?>" data-profile-step="notify">2 · Benachrichtigungen</button>
    <button type="button" class="profile-step<?php echo $step === 'geraete' ? ' is-active' : ''; ?>" role="tab" aria-selected="<?php echo $step === 'geraete' ? 'true' : 'false'; ?>" data-profile-step="geraete"<?php echo ($showAppLoginLink || $showCalendarSubscribe) ? '' : ' disabled'; ?>>3 · Geräte &amp; Kalender</button>
  </nav>

  <form class="profile-form" action="<?php echo htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8'); ?>" method="POST">
    <?php include __DIR__.'/form_hidden.php'; ?>
    <input type="hidden" name="Index" <?php if($fill) echo 'value="'.(int)$n->Index.'"'; ?>>

    <div class="profile-step-panel<?php echo $step === 'profil' ? ' is-active' : ''; ?>" data-profile-panel="profil" role="tabpanel"<?php echo $step === 'profil' ? '' : ' hidden'; ?>>
      <h3>Stammdaten</h3>
      <?php include __DIR__.'/fields_stammdaten.php'; ?>
<?php if($canEditUsers) { ?>
      <div class="profile-step-admin">
        <?php
        // Admin flags only on profil step in layout B — render Mitglied/RegisterLead via prefs partial would duplicate notify.
        // Keep notify fields always in DOM on notify step; admin flags also on profil for clarity.
        ?>
        <div class="profile-prefs">
          <div class="profile-pref">
            <input type="hidden" name="Mitglied" value="0">
            <input class="w3-check" type="checkbox" name="Mitglied" value="1" id="pref-Mitglied-b" <?php echo $checked('Mitglied'); ?>>
            <label for="pref-Mitglied-b">Mitglied</label>
          </div>
<?php   if(!empty($GLOBALS['optionsDB']['showRegisterLead'])) { ?>
          <div class="profile-pref">
            <input type="hidden" name="RegisterLead" value="0">
            <input class="w3-check" type="checkbox" name="RegisterLead" value="1" id="pref-RegisterLead-b" <?php echo $checked('RegisterLead'); ?>>
            <label for="pref-RegisterLead-b">Registerführer</label>
          </div>
<?php   } ?>
        </div>
      </div>
<?php } ?>
      <?php include __DIR__.'/fields_actions.php'; ?>
      <div class="profile-step-nav">
        <button type="button" class="w3-btn w3-border w3-mobile" data-profile-goto="notify">Weiter</button>
      </div>
    </div>

    <div class="profile-step-panel<?php echo $step === 'notify' ? ' is-active' : ''; ?>" data-profile-panel="notify" role="tabpanel"<?php echo $step === 'notify' ? '' : ' hidden'; ?>>
      <h3>Benachrichtigungen</h3>
<?php
$canEditUsersSaved = $canEditUsers;
$canEditUsers = false; // admin flags already on profil step
include __DIR__.'/fields_prefs.php';
$canEditUsers = $canEditUsersSaved;
?>
      <?php include __DIR__.'/fields_actions.php'; ?>
      <div class="profile-step-nav">
        <button type="button" class="w3-btn w3-border w3-mobile" data-profile-goto="profil">Zurück</button>
<?php if($showAppLoginLink || $showCalendarSubscribe) { ?>
        <button type="button" class="w3-btn w3-border w3-mobile" data-profile-goto="geraete">Weiter</button>
<?php } ?>
      </div>
    </div>
  </form>

  <div class="profile-step-panel<?php echo $step === 'geraete' ? ' is-active' : ''; ?>" data-profile-panel="geraete" role="tabpanel"<?php echo $step === 'geraete' ? '' : ' hidden'; ?>>
    <h3>Geräte &amp; Kalender</h3>
<?php if(!$showAppLoginLink && !$showCalendarSubscribe) { ?>
    <p class="w3-small">Keine Geräte- oder Kalenderoptionen für dieses Profil.</p>
<?php } ?>
<?php if($showAppLoginLink) { ?>
    <div class="profile-b-block w3-center">
      <h4><i class="fas fa-qrcode" aria-hidden="true"></i> App-Login</h4>
      <p class="w3-small">Mit der Meldeliste-App scannen oder Link öffnen.</p>
      <div id="app-login-qr" class="w3-margin-bottom profile-qr" style="display:inline-block;" data-alink-url="<?php echo htmlspecialchars($appLoginUrl, ENT_QUOTES, 'UTF-8'); ?>"></div>
      <div class="w3-small w3-break"><a href="<?php echo htmlspecialchars($appLoginUrl, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($appLoginUrl, ENT_QUOTES, 'UTF-8'); ?></a></div>
    </div>
<?php } ?>
<?php if($showCalendarSubscribe) { ?>
    <div class="profile-b-block">
      <h4><i class="fas fa-calendar-plus" aria-hidden="true"></i> Kalender abonnieren</h4>
<?php
        $calendarSubscribeUid = 'profile-cal-b';
        $calendarSubscribeInModal = true;
        include dirname(__DIR__).'/calendar/subscribe.php';
?>
    </div>
<?php } ?>
    <div class="profile-step-nav">
      <button type="button" class="w3-btn w3-border w3-mobile" data-profile-goto="notify">Zurück</button>
    </div>
  </div>
</div>
