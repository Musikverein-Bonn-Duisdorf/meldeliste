<div class="w3-container <?php echo $optionsDB['colorTitle']; ?>">
  <h1 class="w3-hide-small" style="width: calc(100vw - 150px);"><?php echo $optionsDB['WebSiteName']; ?></h1>
  <h1 class="w3-hide-large w3-hide-medium" style="width: calc(100vw - 150px);"><?php echo $optionsDB['WebSiteNameShort']; ?></h1>
  <img src="imgs/Logo.png" style="max-height:100px; max-width:100px; position: absolute; top: 10px; right: 10px;">
  <p><?php
      echo $_SESSION['username'];
      if(isAdmin()) echo " (Admin)";
      ?></p>
      <?php if(requirePermission("perm_showUsers")) {
          ?>
  <!-- <div class="w3-container w3-margin-bottom w3-mobile"> -->
          <?php
           // getCurrentBirthdays();
          ?>
    <!--             </div> -->
  <?php } ?>
</div>
<?php if(getBranchName() != "master") { ?>
<div class="w3-yellow w3-padding"><i class="fas fa-code-branch"></i>
<?php echo "development branch: <b>".getBranchName()."</b>"; ?>
</div>
<?php } ?>
<?php
if(requirePermission('perm_editConfig')) {
    try {
        $schemaMgr = new DatabaseManager();
        if($schemaMgr->isSchemaOutdated()) {
            $inst = (int)$schemaMgr->getInstalledSchemaVersion();
            $exp = (int)$schemaMgr->getExpectedSchemaVersion();
            echo '<div class="w3-orange w3-padding"><i class="fas fa-database"></i> '
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
<div class="w3-bar <?php echo $optionsDB['colorNav']; ?>">
  <button onclick="showAll()" class="w3-bar-item w3-button w3-mobile w3-hide-large w3-hide-medium material-icons">menu</button>
  <a title="Home" alt="Home" href="<?php echo $GLOBALS['optionsDB']['WebSiteURL']; ?>" class="stdhide w3-hide-small w3-bar-item w3-button w3-mobile <?php getPage('home');?>"><i class="fas fa-home"></i></a>
  <?php
  $mailUnread = 0;
  if(isset($_SESSION['userid'])) {
      $mailUnread = MailOutbox::countUnreadForUser((int)$_SESSION['userid']);
  }
  $mailUnreadLabel = $mailUnread > 99 ? '99+' : (string)$mailUnread;
  ?>
  <a title="Meine Nachrichten<?php echo $mailUnread > 0 ? ' ('.$mailUnreadLabel.' neu)' : ''; ?>" alt="Meine Nachrichten" href="meine-mails.php" class="stdhide w3-hide-small w3-bar-item w3-button w3-mobile <?php getPage('meinemails');?>" style="position:relative;">
    <i class="fas fa-envelope"></i><?php
    if($mailUnread > 0) {
        echo '<span class="w3-badge w3-tiny '.$GLOBALS['optionsDB']['colorLogEmail'].'" style="position:absolute;top:0;right:0;padding:1px 5px;font-size:10px;line-height:1.2;">'
            .htmlspecialchars($mailUnreadLabel, ENT_QUOTES, 'UTF-8')
            .'</span>';
    }
    ?></a>
  <?php if($GLOBALS['optionsDB']['showAppmntPage']) { ?>
  <a title="Termine" alt="Termine" href="termine.php" class="stdhide w3-hide-small w3-bar-item w3-button w3-mobile <?php getPage('termine');?>"><i class="far fa-calendar-alt"></i></a>
  <?php } ?>
  <a title="Mein Register" alt="Mein Register" href="mein-register.php" class="stdhide w3-hide-small w3-bar-item w3-button w3-mobile <?php getPage('meinregister');?>"><i class="fas fa-users"></i></a>
<?php
$u = new User;
$u->load_by_id($_SESSION['userid']);
if($u->hasInventories()) { ?>
    <a title="Inventar" alt="Mein Inventar" href="myinventories.php" class="stdhide w3-hide-small w3-bar-item w3-button w3-mobile <?php getPage('myinventories');?>"><i class="fas fa-shirt"></i></a>
<?php } ?>
  <form action="new-musiker.php" method="POST">
    <button title="Mein Profil" alt="Mein Profil" type="submit" class="stdhide w3-hide-small w3-bar-item w3-button w3-mobile <?php getPage('me');?>">
      <i class="fas fa-user"></i>
    </button>
    <input type="hidden" name="id" value="<?php echo $_SESSION['userid']; ?>" />
    <input type="hidden" name="mode" value="useredit" />
  </form>
  <?php if(isAdmin()) {
    $showMeldungen = requirePermission("perm_showResponse");
    $showPersonen = requirePermission("perm_showUsers") || requirePermission("perm_editPermissions");
    $showTermine = requirePermission("perm_editAppmnts");
    $showKommunikation = requirePermission("perm_sendEmail");
    $showInventar = requirePermission("perm_showInventories") || requirePermission("perm_editInventories");
    $showSystem = requirePermission("perm_editConfig") || requirePermission("perm_showLog");
  ?>
  <div class="stdhide w3-hide-small w3-dropdown-hover w3-mobile admin-nav">
    <button title="Admin" alt="Admin" class="w3-button w3-mobile w3-hide-small <?php getAdminPage($_SESSION['page']); ?>"><i class="fas fa-wrench"></i></button>
    <div class="w3-dropdown-content w3-bar-block w3-card-4 <?php echo $optionsDB['colorNavAdmin']; ?> w3-mobile">
      <?php if($showMeldungen) { ?>
      <div class="w3-dropdown-hover w3-mobile admin-nav-group">
        <button type="button" class="w3-button w3-mobile w3-block w3-left-align">Meldungen <i class="fas fa-caret-right admin-nav-caret"></i></button>
        <div class="w3-dropdown-content w3-bar-block w3-card-4 <?php echo $optionsDB['colorNavAdmin']; ?> w3-mobile">
          <a title="Meldungen" alt="Meldungen" href="meldungen.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('meldungen');?>"><i class="fas fa-comment-dots"></i> Meldungen</a>
          <a title="Meldungen - Archiv" alt="archiv" href="archiv.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('archiv');?>"><i class="fas fa-history"></i> Archiv: Meldungen</a>
          <?php if(requirePermission("perm_editResponse")) { ?>
          <a title="im Auftrag melden" alt="im Auftrag melden" href="public-entry.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('public-entry');?>"><i class="fas fa-comments"></i> im Auftrag melden</a>
          <?php } ?>
        </div>
      </div>
      <?php } ?>

      <?php if($showPersonen) { ?>
      <div class="w3-dropdown-hover w3-mobile admin-nav-group">
        <button type="button" class="w3-button w3-mobile w3-block w3-left-align">Personen <i class="fas fa-caret-right admin-nav-caret"></i></button>
        <div class="w3-dropdown-content w3-bar-block w3-card-4 <?php echo $optionsDB['colorNavAdmin']; ?> w3-mobile">
          <?php if(requirePermission("perm_showUsers")) { ?>
          <a title="Registerübersicht" alt="Registerübersicht" href="register.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('register');?>"><i class="fas fa-list"></i> Registerübersicht</a>
          <a title="Musikerliste" alt="Musikerliste" href="musiker.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('musiker');?>"><i class="fas fa-users"></i> Musikerliste</a>
          <a title="Userliste" alt="Userliste" href="users.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('users');?>"><i class="fas fa-users"></i> Userliste</a>
          <?php if($GLOBALS['optionsDB']['showMembers']) { ?>
          <a title="Mitgliederliste" alt="Mitgliederliste" href="mitglied.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('mitglied');?>"><i class="fas fa-users"></i> Mitgliederliste</a>
          <?php } ?>
          <?php if($GLOBALS['optionsDB']['showNonMembers']) { ?>
          <a title="Nicht-Mitgliederliste" alt="Nicht-Mitgliederliste" href="no-mitglied.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('nomitglied');?>"><i class="fas fa-users"></i> Nicht-Mitgliederliste</a>
          <?php } ?>
          <?php if(requirePermission("perm_editUsers")) { ?>
          <a title="neuen Musiker anlegen" alt="neuen Musiker anlegen" href="new-musiker.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('newmusiker');?>"><i class="fas fa-plus-circle"></i> Musiker anlegen</a>
          <?php } ?>
          <?php } ?>
          <?php if(requirePermission("perm_editPermissions")) { ?>
          <a title="Berechtigungen" alt="Berechtigungen" href="permissions.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('permissions');?>"><i class="fas fa-lock"></i> Berechtigungen</a>
          <?php } ?>
        </div>
      </div>
      <?php } ?>

      <?php if($showTermine) { ?>
      <div class="w3-dropdown-hover w3-mobile admin-nav-group">
        <button type="button" class="w3-button w3-mobile w3-block w3-left-align">Termine <i class="fas fa-caret-right admin-nav-caret"></i></button>
        <div class="w3-dropdown-content w3-bar-block w3-card-4 <?php echo $optionsDB['colorNavAdmin']; ?> w3-mobile">
          <a title="neuen Termin erstellen" alt="neuen Termin erstellen" href="new-termin.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('newtermin');?>"><i class="fas fa-plus-circle"></i> Termin erstellen</a>
          <a title="Termine - Archiv" alt="termine archiv" href="termine-archiv.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('termine-archiv');?>"><i class="fas fa-history"></i> Archiv: Termine</a>
        </div>
      </div>
      <?php } ?>

      <?php if($showKommunikation) { ?>
      <div class="w3-dropdown-hover w3-mobile admin-nav-group">
        <button type="button" class="w3-button w3-mobile w3-block w3-left-align">Kommunikation <i class="fas fa-caret-right admin-nav-caret"></i></button>
        <div class="w3-dropdown-content w3-bar-block w3-card-4 <?php echo $optionsDB['colorNavAdmin']; ?> w3-mobile">
          <a title="Email versenden" alt="Email versenden" href="mail.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('mail');?>"><i class="fas fa-envelope-open-text"></i> Email versenden</a>
        </div>
      </div>
      <?php } ?>

      <?php if($showInventar) { ?>
      <div class="w3-dropdown-hover w3-mobile admin-nav-group">
        <button type="button" class="w3-button w3-mobile w3-block w3-left-align">Inventar <i class="fas fa-caret-right admin-nav-caret"></i></button>
        <div class="w3-dropdown-content w3-bar-block w3-card-4 <?php echo $optionsDB['colorNavAdmin']; ?> w3-mobile">
          <?php if(requirePermission("perm_showInventories")) { ?>
          <a title="Inventar" alt="Inventar" href="inventories.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('inventories');?>"><i class="fas fa-shirt"></i> Inventar</a>
          <a title="Versicherung" alt="Versicherung" href="insurance.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('insurance');?>"><i class="fas fa-file-invoice-dollar"></i> Versicherung</a>
          <?php } ?>
          <?php if(requirePermission("perm_editInventories")) { ?>
          <a title="Inventar-Typen" alt="Inventar-Typen" href="inventory-types.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('inventory-types');?>"><i class="fas fa-tags"></i> Inventar-Typen</a>
          <?php } ?>
        </div>
      </div>
      <?php } ?>

      <?php if($showSystem) { ?>
      <div class="w3-dropdown-hover w3-mobile admin-nav-group">
        <button type="button" class="w3-button w3-mobile w3-block w3-left-align">System <i class="fas fa-caret-right admin-nav-caret"></i></button>
        <div class="w3-dropdown-content w3-bar-block w3-card-4 <?php echo $optionsDB['colorNavAdmin']; ?> w3-mobile">
          <?php if(requirePermission("perm_editConfig")) { ?>
          <a title="Konfiguration" alt="Konfiguration" href="config-menu.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('config');?>"><i class="fas fa-cogs"></i> Konfiguration</a>
          <a title="Updater" alt="Updater" href="updater.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('updater');?>"><i class="fas fa-code-branch"></i> Updater</a>
          <?php } ?>
          <?php if(requirePermission("perm_showLog")) { ?>
          <a title="Statistik" alt="Statistik" href="evaluate.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('evaluate');?>"><i class="fas fa-chart-pie"></i> Statistik</a>
          <a title="Logfile anschauen" alt="Logfile anschauen" href="log.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('log');?>"><i class="fas fa-poll"></i> Log</a>
          <?php } ?>
        </div>
      </div>
      <?php } ?>
    </div>
  </div>
  <?php } ?>
  <a title="Homepage des Vereins" alt="Homepage des Vereins" href="<?php echo $optionsDB['MasterPage']; ?>" class="stdhide w3-hide-small w3-bar-item w3-button w3-mobile <?php echo $optionsDB['colorNav']; ?>" target="_blank"><img src="<?php echo $optionsDB['MasterPageIcon']; ?>"  style="height: 1em; vertical-align: middle; margin-right: 0.3em;"/> Vereinshomepage</a>
  <a title="Medien" alt="Medien" href="media.php" class="stdhide w3-hide-small w3-bar-item w3-button w3-mobile <?php echo $optionsDB['colorNav']; ?>"><i class="fa-brands fa-youtube"></i> Medien</a>
  <a title="Hilfe" alt="Hilfe" href="help.php" class="stdhide w3-hide-small w3-bar-item w3-button w3-mobile <?php getPage('help');?>"><i class="fas fa-circle-question"></i></a>
  <a title="Ausloggen" alt="Ausloggen" href="logout.php" class="stdhide w3-hide-small w3-bar-item w3-button <?php echo $optionsDB['colorNav']; ?> w3-mobile"><i class="fas fa-sign-out-alt"></i></a>
</div>
<?php
 if($optionsDB['showMessageOfTheDay']) {
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
 }
 ?>
<script type="text/javascript">
  function resetAdminNavGroups() {
    var groups = document.querySelectorAll('.admin-nav-group.admin-nav-open');
    for(var i = 0; i < groups.length; i++) {
      groups[i].classList.remove('admin-nav-open');
    }
  }

  function showAll() {
    var x = document.getElementsByClassName('stdhide');
    var closing = false;
    for(var i = 0; i < x.length; i++) {
      if(x[i].className.indexOf("w3-show") == -1) {
        x[i].className = x[i].className.replace("w3-hide-small", "w3-show");
      }
      else {
        x[i].className = x[i].className.replace("w3-show", "w3-hide-small");
        closing = true;
      }
    }
    if(closing) {
      resetAdminNavGroups();
    }
  }

  document.addEventListener('click', function(ev) {
    if(window.matchMedia && !window.matchMedia('(max-width: 600px)').matches) {
      return;
    }
    var btn = ev.target.closest ? ev.target.closest('.admin-nav-group > .w3-button') : null;
    if(!btn) return;
    var group = btn.parentNode;
    if(!group || !group.classList || !group.classList.contains('admin-nav-group')) return;
    ev.preventDefault();
    ev.stopPropagation();
    var open = group.classList.contains('admin-nav-open');
    resetAdminNavGroups();
    if(!open) {
      group.classList.add('admin-nav-open');
    }
  });

  <?php if(!isset($_SESSION['MessageOfTheDay'])){
    $_SESSION['MessageOfTheDay']=true;
  ?>
  document.getElementById('MessageOfTheDay').style.display='block';
  <?php } ?>
</script>
