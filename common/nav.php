<div class="w3-container <?php echo $commonColors['Title']; ?>">
<h1><?php echo $site['WebSiteName']; ?></h1>
<p><?php echo $_SESSION['username'] ?></p>
</div>
<div class="w3-bar <?php echo $commonColors['navbar']; ?>">
  <button onclick="showAll()" class="w3-bar-item w3-button w3-mobile w3-hide-large w3-hide-medium material-icons">menu</button>
  <a href="<?php echo $GLOBALS['site']['WebSiteURL']; ?>" class="stdhide w3-hide-small w3-bar-item w3-button w3-mobile <?php getPage('home');?>">Home</a>
  <a href="termine.php" class="stdhide w3-hide-small w3-bar-item w3-button w3-mobile <?php getPage('termine');?>">Termine</a>
    <form action="new-musiker.php" method="POST">
      <button type="submit" class="stdhide w3-hide-small w3-bar-item w3-button w3-mobile <?php getPage('me');?>">
	Mein Profil
      </button>
      <input type="hidden" name="id" value="<?php echo $_SESSION['userid']; ?>" />
      <input type="hidden" name="mode" value="useredit" />
    </form>
<?php if($_SESSION['admin']) {?>
    <div class="stdhide w3-hide-small w3-dropdown-hover w3-mobile">
	<button class="w3-button w3-mobile <?php echo $commonColors['navadmin']; ?>">Admin</button>
	<div class="w3-dropdown-content w3-bar-block w3-card-4 <?php echo $commonColors['navadmin']; ?> w3-mobile">
     <a href="meldungen.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('meldungen');?>">Meldungen</a>
     <a href="public-entry.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('public-entry');?>">im Auftrag melden</a>
	    <a href="musiker.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('musiker');?>">Musikerliste</a>
	    <a href="register.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('register');?>">Registerübersicht</a>
	    <a href="mitglied.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('mitglied');?>">Mitgliederliste</a>
	    <a href="no-mitglied.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('nomitglied');?>">Nicht-Mitgliederliste</a>
	    <a href="new-musiker.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('newmusiker');?>">neuen Musiker anlegen</a>
	    <a href="new-termin.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('newtermin');?>">neuen Termin erstellen</a>
	    <a href="mail.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('mail');?>">Email versenden</a>
	    <a href="log.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('log');?>">Log</a>
	</div>
    </div>
<?php } ?>
    <a href="<?php echo $site['MasterPage']; ?>" class="stdhide w3-hide-small w3-bar-item w3-button w3-mobile <?php echo $commonColors['navmainpage']; ?>" target="_blank">Vereinshomepage</a>
    <a href="help.php" class="stdhide w3-hide-small w3-bar-item w3-button w3-mobile <?php getPage('help');?>">Hilfe</a>
    <a href="logout.php" class="stdhide w3-hide-small w3-bar-item w3-button <?php echo $commonColors['navlogout']; ?> w3-mobile">Logout</a>
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
