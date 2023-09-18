<div class="w3-container <?php echo $optionsDB['colorTitle']; ?>">
  <h1 class="w3-hide-small"><?php echo $optionsDB['WebSiteName']; ?></h1>
  <h1 class="w3-hide-large w3-hide-medium"><?php echo $optionsDB['WebSiteNameShort']; ?></h1>
  <p><?php
      echo $_SESSION['username'];
      if($_SESSION['admin']) echo " (Admin)";
      ?></p>
</div>
<div class="w3-bar <?php echo $optionsDB['colorNav']; ?>">
  <button onclick="showAll()" class="w3-bar-item w3-button w3-mobile w3-hide-large w3-hide-medium material-icons">menu</button>
  <a title="Home" alt="Home" href="<?php echo $GLOBALS['optionsDB']['WebSiteURL']; ?>" class="stdhide w3-hide-small w3-bar-item w3-button w3-mobile <?php getPage('home');?>"><i class="fas fa-home"></i></a>
  <?php if($GLOBALS['optionsDB']['showAppmntPage']) { ?>
  <a title="Termine" alt="Termine" href="termine.php" class="stdhide w3-hide-small w3-bar-item w3-button w3-mobile <?php getPage('termine');?>"><i class="far fa-calendar-alt"></i></a>
  <?php } ?>
  <a title="Mein Register" alt="Mein Register" href="mein-register.php" class="stdhide w3-hide-small w3-bar-item w3-button w3-mobile <?php getPage('meinregister');?>"><i class="fas fa-users"></i></a>
<?php
$u = new User;
$u->load_by_id($_SESSION['userid']);
if($u->hasInstruments()) { ?>
    <a title="Instrumente" alt="Meine Instrumente" href="myinstruments.php" class="stdhide w3-hide-small w3-bar-item w3-button w3-mobile <?php getPage('myinstruments');?>"><i class="fas fa-drum"></i></a>
<?php } ?>
  <form action="new-musiker.php" method="POST">
    <button title="Mein Profil" alt="Mein Profil" type="submit" class="stdhide w3-hide-small w3-bar-item w3-button w3-mobile <?php getPage('me');?>">
      <i class="fas fa-user"></i>
    </button>
    <input type="hidden" name="id" value="<?php echo $_SESSION['userid']; ?>" />
    <input type="hidden" name="mode" value="useredit" />
  </form>
  <?php if($_SESSION['admin']) {?>
  <div class="stdhide w3-hide-small w3-dropdown-hover w3-mobile">
    <button title="Admin" alt="Admin" class="w3-button w3-mobile w3-hide-small <?php getAdminPage($_SESSION['page']); ?>"><i class="fas fa-wrench"></i></button>
    <div class="w3-dropdown-content w3-bar-block w3-card-4 <?php echo $optionsDB['colorNavAdmin']; ?> w3-mobile">
      <a title="Meldungen - Archiv" alt="archiv" href="archiv.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('archiv');?>"><i class="fas fa-history"></i> Archiv: Meldungen</a>
      <a title="Termine - Archiv" alt="termine archiv" href="termine-archiv.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('termine-archiv');?>"><i class="fas fa-history"></i> Archiv: Termine</a>
      <a title="Meldungen" alt="Meldungen" href="meldungen.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('meldungen');?>"><i class="fas fa-comment-dots"></i> Meldungen</a>
      <a title="im Auftrag melden" alt="im Auftrag melden" href="public-entry.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('public-entry');?>"><i class="fas fa-comments"></i> im Auftrag melden</a>
      <a title="Registerübersicht" alt="Registerübersicht" href="register.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('register');?>"><i class="fas fa-list"></i> Registerübersicht</a>
      <a title="Musikerliste" alt="Musikerliste" href="musiker.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('musiker');?>"><i class="fas fa-users"></i> Musikerliste</a>
      <?php if($GLOBALS['optionsDB']['showMembers']) { ?>
      <a title="Mitgliederliste" alt="Mitgliederliste" href="mitglied.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('mitglied');?>"><i class="fas fa-users"></i> Mitgliederliste</a>
      <?php } ?>
      <?php if($GLOBALS['optionsDB']['showNonMembers']) { ?>
      <a title="Nicht-Mitgliederliste" alt="Nicht-Mitgliederliste" href="no-mitglied.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('nomitglied');?>"><i class="fas fa-users"></i> Nicht-Mitgliederliste</a>
      <?php } ?>
      <a title="neuen Musiker anlegen" alt="neuen Musiker anlegen" href="new-musiker.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('newmusiker');?>"><i class="fas fa-plus-circle"></i> Musiker anlegen</a>
      <a title="neuen Termin erstellen" alt="neuen Termin erstellen" href="new-termin.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('newtermin');?>"><i class="fas fa-plus-circle"></i> Termin erstellen</a>
      <a title="Email versenden" alt="Email versenden" href="mail.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('mail');?>"><i class="fas fa-envelope-open-text"></i> Email versenden</a>
      <a title="Userliste" alt="Userliste" href="users.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('users');?>"><i class="fas fa-users"></i> Userliste</a>
      <a title="Konfiguration" alt="Konfiguration" href="config-menu.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('config');?>"><i class="fas fa-cogs"></i> Konfiguration</a>
      <a title="Instrumente" alt="Instrumente" href="instruments.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('instruments');?>"><i class="fas fa-drum"></i> Instrumente</a>
      <a title="Statistik" alt="Statistik" href="evaluate.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('evaluate');?>"><i class="fas fa-chart-pie"></i> Statistik</a>
      <a title="Logfile anschauen" alt="Logfile anschauen" href="log.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('log');?>"><i class="fas fa-poll"></i> Log</a>
    </div>
  </div>
  <?php } ?>
  <a title="Homepage des Vereins" alt="Homepage des Vereins" href="<?php echo $optionsDB['MasterPage']; ?>" class="stdhide w3-hide-small w3-bar-item w3-button w3-mobile <?php echo $optionsDB['colorNav']; ?>" target="_blank"><img src="<?php echo $optionsDB['MasterPageIcon']; ?>" /> Vereinshomepage</a>
  <a title="Hilfe" alt="Hilfe" href="help.php" class="stdhide w3-hide-small w3-bar-item w3-button w3-mobile <?php getPage('help');?>"><i class="fas fa-info"></i></a>
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
  function showAll() {
  var x = document.getElementsByClassName('stdhide');
  for(var i=0; i<x.length; i++) {
			   if (x[i].className.indexOf("w3-show") == -1) {
			   x[i].className = x[i].className.replace("w3-hide-small", "w3-show");
			   } else { 
			   x[i].className = x[i].className.replace("w3-show", "w3-hide-small");
			   }
			   }
			   }
			   <?php if(!isset($_SESSION['MessageOfTheDay'])){
			    $_SESSION['MessageOfTheDay']=true;
			    ?>
			   document.getElementById('MessageOfTheDay').style.display='block';
			   <?php } ?>
			   </script>
