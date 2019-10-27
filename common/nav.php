<div class="w3-container <?php echo $commonColors['Title']; ?>">
<h1 class="w3-hide-small"><?php echo $site['WebSiteName']; ?></h1>
<h1 class="w3-hide-large w3-hide-medium"><?php echo $site['WebSiteNameShort']; ?></h1>
<p><?php echo $_SESSION['username'] ?></p>
</div>
<div class="w3-bar <?php echo $commonColors['navbar']; ?>">
  <button onclick="showAll()" class="w3-bar-item w3-button w3-mobile w3-hide-large w3-hide-medium material-icons">menu</button>
  <a title="Home" alt="Home" href="<?php echo $GLOBALS['site']['WebSiteURL']; ?>" class="stdhide w3-hide-small w3-bar-item w3-button w3-mobile <?php getPage('home');?>"><i class="fas fa-home"></i></a>
<?php if($GLOBALS['options']['showAppmntPage']) { ?>
     <a title="Termine" alt="Termine" href="termine.php" class="stdhide w3-hide-small w3-bar-item w3-button w3-mobile <?php getPage('termine');?>"><i class="far fa-calendar-alt"></i></a>
<?php } ?>
  <a title="Mein Register" alt="Mein Register" href="mein-register.php" class="stdhide w3-hide-small w3-bar-item w3-button w3-mobile <?php getPage('meinregister');?>"><i class="fas fa-users"></i></a>
    <form action="new-musiker.php" method="POST">
      <button title="Mein Profil" alt="Mein Profil" type="submit" class="stdhide w3-hide-small w3-bar-item w3-button w3-mobile <?php getPage('me');?>">
	<i class="fas fa-user"></i>
      </button>
      <input type="hidden" name="id" value="<?php echo $_SESSION['userid']; ?>" />
      <input type="hidden" name="mode" value="useredit" />
    </form>
<?php if($_SESSION['admin']) {?>
<div class="stdhide w3-hide-small w3-dropdown-hover w3-mobile">
  <button title="Admin" alt="Admin" class="w3-button w3-mobile w3-hide-small <?php echo $commonColors['navadmin']; ?>">Admin</button>
  <div class="w3-dropdown-content w3-bar-block w3-card-4 <?php echo $commonColors['navadmin']; ?> w3-mobile">
    <a title="Meldungen" alt="Meldungen" href="meldungen.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('meldungen');?>">Meldungen</a>
    <a title="im Auftrag melden" alt="im Auftrag melden" href="public-entry.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('public-entry');?>">im Auftrag melden</a>
    <a title="Musikerliste" alt="Musikerliste" href="musiker.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('musiker');?>">Musikerliste</a>
    <a title="Registerübersicht" alt="Registerübersicht" href="register.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('register');?>">Registerübersicht</a>
<?php if($GLOBALS['options']['showMembers']) { ?>
<a title="Mitgliederliste" alt="Mitgliederliste" href="mitglied.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('mitglied');?>">Mitgliederliste</a>
<?php } ?>
<?php if($GLOBALS['options']['showNonMembers']) { ?>
         <a title="Nicht-Mitgliederliste" alt="Nicht-Mitgliederliste" href="no-mitglied.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('nomitglied');?>">Nicht-Mitgliederliste</a>
<?php } ?>
         <a title="neuen Musiker anlegen" alt="neuen Musiker anlegen" href="new-musiker.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('newmusiker');?>">neuen Musiker anlegen</a>
	    <a title="neuen Termin erstellen" alt="neuen Termin erstellen" href="new-termin.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('newtermin');?>">neuen Termin erstellen</a>
	    <a title="Email versenden" alt="Email versenden" href="mail.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('mail');?>">Email versenden</a>
	    <a title="Logfile anschauen" alt="Logfile anschauen" href="log.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('log');?>">Log</a>
	    <a title="Konfiguration" alt="Konfiguration" href="config-menu.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('config');?>">Konfiguration</a>
	</div>
    </div>
<?php } ?>
    <a title="Homepage des Musikvereins" alt="Homepage des Musikvereins" href="<?php echo $site['MasterPage']; ?>" class="stdhide w3-hide-small w3-bar-item w3-button w3-mobile <?php echo $commonColors['navmainpage']; ?>" target="_blank"><img src="<?php echo $site['favicon']; ?>" /> Vereinshomepage</a>
    <a title="Hilfe" alt="Hilfe" href="help.php" class="stdhide w3-hide-small w3-bar-item w3-button w3-mobile <?php getPage('help');?>"><i class="fas fa-info"></i></a>
    <a title="Ausloggen" alt="Ausloggen" href="logout.php" class="stdhide w3-hide-small w3-bar-item w3-button <?php echo $commonColors['navlogout']; ?> w3-mobile"><i class="fas fa-sign-out-alt"></i></a>
</div>

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
</script>
