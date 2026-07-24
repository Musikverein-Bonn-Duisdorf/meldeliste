<?php
$mailUnread = 0;
if(isset($_SESSION['userid'])) {
    $mailUnread = MailOutbox::countUnreadForUser((int)$_SESSION['userid']);
}
$mailUnreadLabel = $mailUnread > 99 ? '99+' : (string)$mailUnread;
$navUser = new User;
$navUser->load_by_id($_SESSION['userid']);
$navHasInventories = $navUser->hasInventories();
$ssoArchiv = !empty($optionsDB['urlNotenarchiv'])
    ? 'sso.php?redirect='.rawurlencode(trim((string)$optionsDB['urlNotenarchiv']))
    : '';
$ssoMit = !empty($optionsDB['urlMitgliederverwaltung'])
    ? 'sso.php?redirect='.rawurlencode(trim((string)$optionsDB['urlMitgliederverwaltung']))
    : '';
$isAdminNav = isAdmin();
if($isAdminNav) {
    $showPersonen = requirePermission('perm_showUsers');
    $showTermine = requirePermission('perm_editAppmnts');
    $showMeldungen = requirePermission('perm_showResponse');
    $showKommunikation = requirePermission('perm_sendEmail');
    $showInventar = requirePermission('perm_showInventories') || requirePermission('perm_editInventories');
    $showRegister = requirePermission('perm_editRegisters');
    $showSystem = requirePermission('perm_editConfig') || requirePermission('perm_showLog') || requirePermission('perm_editPermissions');
}
$navColor = htmlspecialchars((string)$optionsDB['colorNav'], ENT_QUOTES, 'UTF-8');
$navAdminColor = htmlspecialchars((string)$optionsDB['colorNavAdmin'], ENT_QUOTES, 'UTF-8');
?>
<div class="app-titlebar <?php echo htmlspecialchars((string)$optionsDB['colorTitle'], ENT_QUOTES, 'UTF-8'); ?>">
  <h1 class="app-titlebar-name w3-hide-small"><?php echo htmlspecialchars((string)$optionsDB['WebSiteName'], ENT_QUOTES, 'UTF-8'); ?></h1>
  <h1 class="app-titlebar-name w3-hide-large w3-hide-medium"><?php echo htmlspecialchars((string)$optionsDB['WebSiteNameShort'], ENT_QUOTES, 'UTF-8'); ?></h1>
  <?php if(!empty($optionsDB['MasterPage'])) { ?>
  <a class="app-titlebar-logo" href="<?php echo htmlspecialchars($optionsDB['MasterPage'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" title="Vereinshomepage">
    <img src="imgs/Logo.png" alt="Vereinshomepage">
  </a>
  <?php } else { ?>
  <img class="app-titlebar-logo" src="imgs/Logo.png" alt="">
  <?php } ?>
  <p class="app-titlebar-user"><?php
      echo htmlspecialchars((string)$_SESSION['username'], ENT_QUOTES, 'UTF-8');
      if(isAdmin()) echo ' (Admin)';
  ?></p>
</div>
<?php if(getBranchName() != 'master' || !empty($optionsDB['showBranchBannerAlways'])) { ?>
<div class="w3-yellow w3-padding app-banner"><i class="fas fa-code-branch"></i>
<?php echo 'branch: <b>'.htmlspecialchars(getBranchName(), ENT_QUOTES, 'UTF-8').'</b>'; ?>
</div>
<?php } ?>
<?php
if(requirePermission('perm_editConfig')) {
    try {
        $schemaMgr = new DatabaseManager();
        if($schemaMgr->isSchemaOutdated()) {
            $inst = (int)$schemaMgr->getInstalledSchemaVersion();
            $exp = (int)$schemaMgr->getExpectedSchemaVersion();
            echo '<div class="w3-orange w3-padding app-banner"><i class="fas fa-database"></i> '
                .'Neue Datenbank-Version verfügbar (installiert: <b>'.$inst.'</b>, Soll: <b>'.$exp.'</b>). '
                .'Bitte im <a href="updater.php"><b>Updater</b></a> „Datenbank reparieren“ ausführen '
                .'oder „Update durchführen“ (aktualisiert die DB bei Bedarf mit).'
                .'</div>';
        }
    }
    catch(Throwable $e) {
        // Schema-Check darf die Navigation nicht abbrechen
    }
}
?>
<div class="app-shell">
<nav class="app-nav <?php echo $navColor; ?>" aria-label="Hauptnavigation">
  <div class="app-nav-primary">
    <a class="app-nav-item <?php getPage('home', 'termine'); ?>" href="<?php echo htmlspecialchars((string)$GLOBALS['optionsDB']['WebSiteURL'], ENT_QUOTES, 'UTF-8'); ?>" title="Termine">
      <i class="far fa-calendar-alt" aria-hidden="true"></i><span class="nav-label">Termine</span>
    </a>
    <a class="app-nav-item <?php getPage('calendar', 'termine'); ?>" href="calendar.php" title="Kalender">
      <i class="fas fa-calendar" aria-hidden="true"></i><span class="nav-label">Kalender</span>
    </a>
    <a class="app-nav-item app-nav-item--badge <?php getPage('meinemails', 'kommunikation'); ?>" href="meine-mails.php" title="Meine Nachrichten<?php echo $mailUnread > 0 ? ' ('.$mailUnreadLabel.' neu)' : ''; ?>">
      <i class="fas fa-envelope" aria-hidden="true"></i><span class="nav-label">Nachrichten</span><?php
      if($mailUnread > 0) {
          echo '<span class="app-nav-badge w3-badge w3-tiny '.$GLOBALS['optionsDB']['colorLogEmail'].'">'
              .htmlspecialchars($mailUnreadLabel, ENT_QUOTES, 'UTF-8')
              .'</span>';
      }
      ?>
    </a>
    <a class="app-nav-item <?php getPage('meinregister', 'register'); ?>" href="mein-register.php" title="Mein Register">
      <i class="fas fa-users" aria-hidden="true"></i>
      <span class="nav-label"><span class="nav-label-long">Mein Register</span><span class="nav-label-short">Register</span></span>
    </a>
<?php if($navHasInventories) { ?>
    <a class="app-nav-item app-nav-item--secondary <?php getPage('myinventories', 'inventar'); ?>" href="myinventories.php" title="Mein Inventar">
      <i class="fas fa-shirt" aria-hidden="true"></i><span class="nav-label">Mein Inventar</span>
    </a>
<?php } ?>
    <form class="app-nav-form app-nav-item--secondary" action="new-musiker.php" method="POST">
      <button class="app-nav-item <?php getPage('me', 'nutzer'); ?>" type="submit" title="Mein Profil">
        <i class="fas fa-user" aria-hidden="true"></i>
        <span class="nav-label"><span class="nav-label-long">Mein Profil</span><span class="nav-label-short">Profil</span></span>
      </button>
      <input type="hidden" name="id" value="<?php echo (int)$_SESSION['userid']; ?>">
      <input type="hidden" name="mode" value="useredit">
    </form>
    <a class="app-nav-item app-nav-item--secondary <?php getPage('media', 'system'); ?>" href="media.php" title="Medien">
      <i class="fas fa-photo-film" aria-hidden="true"></i><span class="nav-label">Medien</span>
    </a>
<?php if($ssoArchiv !== '') { ?>
    <a class="app-nav-item app-nav-item--secondary <?php echo htmlspecialchars(navGroupClass('system'), ENT_QUOTES, 'UTF-8'); ?>" href="<?php echo htmlspecialchars($ssoArchiv, ENT_QUOTES, 'UTF-8'); ?>" title="Notenarchiv">
      <i class="fas fa-book" aria-hidden="true"></i><span class="nav-label">Notenarchiv</span>
    </a>
<?php } ?>
<?php if($ssoMit !== '') { ?>
    <a class="app-nav-item app-nav-item--secondary <?php echo htmlspecialchars(navGroupClass('nutzer'), ENT_QUOTES, 'UTF-8'); ?>" href="<?php echo htmlspecialchars($ssoMit, ENT_QUOTES, 'UTF-8'); ?>" title="Mitgliederverwaltung">
      <i class="fas fa-id-card" aria-hidden="true"></i><span class="nav-label">Mitgliederverw.</span>
    </a>
<?php } ?>
    <a class="app-nav-item app-nav-item--secondary <?php getPage('help', 'system'); ?>" href="help.php" title="Hilfe">
      <i class="fas fa-circle-question" aria-hidden="true"></i><span class="nav-label">Hilfe</span>
    </a>
  </div>

  <div class="app-nav-more-wrap">
    <button type="button" class="app-nav-item app-nav-more-toggle <?php echo htmlspecialchars(navGroupClass('system'), ENT_QUOTES, 'UTF-8'); ?>" id="appNavMoreToggle" aria-expanded="false" aria-controls="appNavMorePanel" title="Mehr">
      <i class="fas fa-ellipsis-h" aria-hidden="true"></i><span class="nav-label">Mehr</span>
    </button>
    <div class="app-nav-more-backdrop" id="appNavMoreBackdrop" hidden></div>
    <div class="app-nav-more-panel <?php echo $navColor; ?>" id="appNavMorePanel" role="dialog" aria-label="Weitere Navigation">
      <div class="app-nav-more-head">
        <span>Mehr</span>
        <button type="button" class="app-nav-more-close" id="appNavMoreClose" aria-label="Schließen">&times;</button>
      </div>
      <div class="app-nav-more-body">
<?php if($navHasInventories) { ?>
        <a class="app-nav-item app-nav-more-only-mobile <?php getPage('myinventories', 'inventar'); ?>" href="myinventories.php" title="Mein Inventar">
          <i class="fas fa-shirt" aria-hidden="true"></i><span class="nav-label">Mein Inventar</span>
        </a>
<?php } ?>
        <form class="app-nav-form app-nav-more-only-mobile" action="new-musiker.php" method="POST">
          <button class="app-nav-item <?php getPage('me', 'nutzer'); ?>" type="submit" title="Mein Profil">
            <i class="fas fa-user" aria-hidden="true"></i>
            <span class="nav-label"><span class="nav-label-long">Mein Profil</span><span class="nav-label-short">Profil</span></span>
          </button>
          <input type="hidden" name="id" value="<?php echo (int)$_SESSION['userid']; ?>">
          <input type="hidden" name="mode" value="useredit">
        </form>
        <a class="app-nav-item app-nav-more-only-mobile <?php getPage('media', 'system'); ?>" href="media.php" title="Medien">
          <i class="fas fa-photo-film" aria-hidden="true"></i><span class="nav-label">Medien</span>
        </a>
<?php if($ssoArchiv !== '') { ?>
        <a class="app-nav-item app-nav-more-only-mobile <?php echo htmlspecialchars(navGroupClass('system'), ENT_QUOTES, 'UTF-8'); ?>" href="<?php echo htmlspecialchars($ssoArchiv, ENT_QUOTES, 'UTF-8'); ?>" title="Notenarchiv">
          <i class="fas fa-book" aria-hidden="true"></i><span class="nav-label">Notenarchiv</span>
        </a>
<?php } ?>
<?php if($ssoMit !== '') { ?>
        <a class="app-nav-item app-nav-more-only-mobile <?php echo htmlspecialchars(navGroupClass('nutzer'), ENT_QUOTES, 'UTF-8'); ?>" href="<?php echo htmlspecialchars($ssoMit, ENT_QUOTES, 'UTF-8'); ?>" title="Mitgliederverwaltung">
          <i class="fas fa-id-card" aria-hidden="true"></i><span class="nav-label">Mitgliederverw.</span>
        </a>
<?php } ?>
        <a class="app-nav-item app-nav-more-only-mobile <?php getPage('help', 'system'); ?>" href="help.php" title="Hilfe">
          <i class="fas fa-circle-question" aria-hidden="true"></i><span class="nav-label">Hilfe</span>
        </a>
<?php if($isAdminNav) { ?>
        <div class="admin-nav app-nav-admin">
          <div class="app-nav-admin-title"><i class="fas fa-wrench" aria-hidden="true"></i><span class="nav-label">Admin</span></div>
          <div class="w3-bar-block <?php echo $navAdminColor; ?>">
<?php if($showPersonen) { ?>
            <div class="w3-dropdown-hover w3-mobile admin-nav-group<?php echo adminNavGroupActiveClass(array('musiker', 'newmusiker')); ?>">
              <button type="button" class="w3-button w3-mobile w3-block w3-left-align <?php echo adminNavPermClass('perm_showUsers'); ?>">Personen <i class="fas fa-caret-right admin-nav-caret"></i></button>
              <div class="w3-dropdown-content w3-bar-block w3-card-4 <?php echo $navAdminColor; ?> w3-mobile">
                <a title="Personenliste" href="musiker.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPagePerm('musiker', 'perm_showUsers'); ?>"><i class="fas fa-users"></i> Personenliste</a>
<?php if(requirePermission('perm_editUsers')) { ?>
                <a title="Musiker anlegen" href="new-musiker.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPagePerm('newmusiker', 'perm_editUsers'); ?>"><i class="fas fa-plus-circle"></i> Musiker anlegen</a>
<?php } ?>
              </div>
            </div>
<?php } ?>
<?php if($showTermine) { ?>
            <div class="w3-dropdown-hover w3-mobile admin-nav-group<?php echo adminNavGroupActiveClass(array('newtermin', 'termine-archiv', 'shifts')); ?>">
              <button type="button" class="w3-button w3-mobile w3-block w3-left-align <?php echo adminNavPermClass('perm_editAppmnts'); ?>">Termine <i class="fas fa-caret-right admin-nav-caret"></i></button>
              <div class="w3-dropdown-content w3-bar-block w3-card-4 <?php echo $navAdminColor; ?> w3-mobile">
                <a title="Termin erstellen" href="new-termin.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPagePerm('newtermin', 'perm_editAppmnts'); ?>"><i class="fas fa-plus-circle"></i> Termin erstellen</a>
                <a title="Archiv: Termine" href="termine-archiv.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPagePerm('termine-archiv', 'perm_editAppmnts'); ?>"><i class="fas fa-history"></i> Archiv: Termine</a>
              </div>
            </div>
<?php } ?>
<?php if($showMeldungen) { ?>
            <div class="w3-dropdown-hover w3-mobile admin-nav-group<?php echo adminNavGroupActiveClass(array('meldungen', 'archiv', 'public-entry')); ?>">
              <button type="button" class="w3-button w3-mobile w3-block w3-left-align <?php echo adminNavPermClass('perm_showResponse'); ?>">Meldungen <i class="fas fa-caret-right admin-nav-caret"></i></button>
              <div class="w3-dropdown-content w3-bar-block w3-card-4 <?php echo $navAdminColor; ?> w3-mobile">
                <a title="Meldungen" href="meldungen.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPagePerm('meldungen', 'perm_showResponse'); ?>"><i class="fas fa-comment-dots"></i> Meldungen</a>
                <a title="Archiv: Meldungen" href="archiv.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPagePerm('archiv', 'perm_showResponse'); ?>"><i class="fas fa-history"></i> Archiv: Meldungen</a>
<?php if(requirePermission('perm_editResponse')) { ?>
                <a title="im Auftrag melden" href="public-entry.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPagePerm('public-entry', 'perm_editResponse'); ?>"><i class="fas fa-comments"></i> im Auftrag melden</a>
<?php } ?>
              </div>
            </div>
<?php } ?>
<?php if($showKommunikation) { ?>
            <div class="w3-dropdown-hover w3-mobile admin-nav-group<?php echo adminNavGroupActiveClass(array('mail', 'groups')); ?>">
              <button type="button" class="w3-button w3-mobile w3-block w3-left-align <?php echo adminNavPermClass('perm_sendEmail'); ?>">Kommunikation <i class="fas fa-caret-right admin-nav-caret"></i></button>
              <div class="w3-dropdown-content w3-bar-block w3-card-4 <?php echo $navAdminColor; ?> w3-mobile">
                <a title="Email versenden" href="mail.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPagePerm('mail', 'perm_sendEmail'); ?>"><i class="fas fa-envelope-open-text"></i> Email versenden</a>
                <a title="Gruppen" href="groups.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPagePerm('groups', 'perm_sendEmail'); ?>"><i class="fas fa-users"></i> Gruppen</a>
              </div>
            </div>
<?php } ?>
<?php if($showInventar) { ?>
            <div class="w3-dropdown-hover w3-mobile admin-nav-group<?php echo adminNavGroupActiveClass(array('inventories', 'newinventory', 'inventory-types')); ?>">
              <button type="button" class="w3-button w3-mobile w3-block w3-left-align <?php echo adminNavPermClass('perm_showInventories'); ?>">Inventar <i class="fas fa-caret-right admin-nav-caret"></i></button>
              <div class="w3-dropdown-content w3-bar-block w3-card-4 <?php echo $navAdminColor; ?> w3-mobile">
<?php if(requirePermission('perm_showInventories')) { ?>
                <a title="Inventar" href="inventories.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPagePerm('inventories', 'perm_showInventories'); ?>"><i class="fas fa-shirt"></i> Inventar</a>
<?php } ?>
<?php if(requirePermission('perm_editInventories')) { ?>
                <a title="Inventar anlegen" href="new-inventory.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPagePerm('newinventory', 'perm_editInventories'); ?>"><i class="fas fa-plus-circle"></i> Inventar anlegen</a>
                <a title="Inventar-Typen" href="inventory-types.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPagePerm('inventory-types', 'perm_editInventories'); ?>"><i class="fas fa-tags"></i> Inventar-Typen</a>
<?php } ?>
              </div>
            </div>
<?php } ?>
<?php if($showRegister) { ?>
            <div class="w3-dropdown-hover w3-mobile admin-nav-group<?php echo adminNavGroupActiveClass(array('register-types', 'instrument-types')); ?>">
              <button type="button" class="w3-button w3-mobile w3-block w3-left-align <?php echo adminNavPermClass('perm_editRegisters'); ?>">Register <i class="fas fa-caret-right admin-nav-caret"></i></button>
              <div class="w3-dropdown-content w3-bar-block w3-card-4 <?php echo $navAdminColor; ?> w3-mobile">
                <a title="Register" href="register-types.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPagePerm('register-types', 'perm_editRegisters'); ?>"><i class="fas fa-layer-group"></i> Register</a>
                <a title="Instrument-Typen" href="instrument-types.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPagePerm('instrument-types', 'perm_editRegisters'); ?>"><i class="fas fa-guitar"></i> Instrument-Typen</a>
              </div>
            </div>
<?php } ?>
<?php if($showSystem) { ?>
            <div class="w3-dropdown-hover w3-mobile admin-nav-group<?php echo adminNavGroupActiveClass(array('permissions', 'config', 'evaluate', 'log', 'backup', 'updater')); ?>">
              <button type="button" class="w3-button w3-mobile w3-block w3-left-align <?php echo adminNavPermClass('perm_editConfig'); ?>">System <i class="fas fa-caret-right admin-nav-caret"></i></button>
              <div class="w3-dropdown-content w3-bar-block w3-card-4 <?php echo $navAdminColor; ?> w3-mobile">
<?php if(requirePermission('perm_editPermissions')) { ?>
                <a title="Berechtigungen" href="permissions.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPagePerm('permissions', 'perm_editPermissions'); ?>"><i class="fas fa-lock"></i> Berechtigungen</a>
<?php } ?>
<?php if(requirePermission('perm_editConfig')) { ?>
                <a title="Konfiguration" href="config-menu.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPagePerm('config', 'perm_editConfig'); ?>"><i class="fas fa-cogs"></i> Konfiguration</a>
<?php } ?>
<?php if(requirePermission('perm_showLog')) { ?>
                <a title="Statistik" href="evaluate.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPagePerm('evaluate', 'perm_showLog'); ?>"><i class="fas fa-chart-pie"></i> Statistik</a>
                <a title="Log" href="log.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPagePerm('log', 'perm_showLog'); ?>"><i class="fas fa-poll"></i> Log</a>
<?php } ?>
<?php if(requirePermission('perm_editConfig')) { ?>
                <a title="Backup" href="backup.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPagePerm('backup', 'perm_editConfig'); ?>"><i class="fas fa-database"></i> Backup</a>
                <a title="Updater" href="updater.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPagePerm('updater', 'perm_editConfig'); ?>"><i class="fas fa-code-branch"></i> Updater</a>
<?php } ?>
              </div>
            </div>
<?php } ?>
          </div>
        </div>
<?php } ?>
        <a class="app-nav-item <?php echo htmlspecialchars(navGroupClass('system'), ENT_QUOTES, 'UTF-8'); ?>" href="logout.php" title="Ausloggen">
          <i class="fas fa-sign-out-alt" aria-hidden="true"></i><span class="nav-label">Ausloggen</span>
        </a>
      </div>
    </div>
  </div>
</nav>
<div class="app-main">
<?php
if(!empty($optionsDB['showMessageOfTheDay'])) {
?>
<div class="w3-container w3-card w3-padding <?php echo $GLOBALS['optionsDB']['colorWarning']; ?>">
  <div class="w3-col l3 m3 s2 w3-center">
    <i class="fas fa-exclamation-triangle"></i>
  </div>
  <div class="w3-col l6 m6 s8 w3-center">
    <?php echo $optionsDB['MessageOfTheDayShort']; ?>
  </div>
  <div class="w3-col l3 m3 s2 w3-center">
    <i class="fas fa-exclamation-triangle"></i>
  </div>
</div>
<?php
    ob_start();
?>
<div id="MessageOfTheDay" class="w3-modal">
  <div class="w3-modal-content">
    <div class="w3-container">
      <?php echo $optionsDB['MessageOfTheDay']; ?>
      <div class="w3-center"><button class="w3-btn w3-blue w3-padding w3-center" onclick="document.getElementById('MessageOfTheDay').style.display='none'">Verstanden</button></div>
      <div class="w3-container">&nbsp;</div>
    </div>
  </div>
</div>
<?php
    if(!isset($_SESSION['MessageOfTheDay'])) {
        $_SESSION['MessageOfTheDay'] = true;
?>
<script>document.getElementById('MessageOfTheDay').style.display='block';</script>
<?php
    }
    deferPageModalHtml(ob_get_clean());
}
?>
<script src="<?php echo assetUrl('js/app-nav.js'); ?>"></script>
