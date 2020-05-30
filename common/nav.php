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
    <a title="Meldungen" alt="Meldungen" href="meldungen.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('meldungen');?>">Meldungen</a>
    <a title="im Auftrag melden" alt="im Auftrag melden" href="public-entry.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('public-entry');?>">im Auftrag melden</a>
    <a title="Musikerliste" alt="Musikerliste" href="musiker.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('musiker');?>">Musikerliste</a>
    <a title="Registerübersicht" alt="Registerübersicht" href="register.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('register');?>">Registerübersicht</a>
<?php if($GLOBALS['optionsDB']['showMembers']) { ?>
<a title="Mitgliederliste" alt="Mitgliederliste" href="mitglied.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('mitglied');?>">Mitgliederliste</a>
<?php } ?>
<?php if($GLOBALS['optionsDB']['showNonMembers']) { ?>
         <a title="Nicht-Mitgliederliste" alt="Nicht-Mitgliederliste" href="no-mitglied.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('nomitglied');?>">Nicht-Mitgliederliste</a>
<?php } ?>
        <a title="neuen Musiker anlegen" alt="neuen Musiker anlegen" href="new-musiker.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('newmusiker');?>">neuen Musiker anlegen</a>
	    <a title="neuen Termin erstellen" alt="neuen Termin erstellen" href="new-termin.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('newtermin');?>">neuen Termin erstellen</a>
	    <a title="Email versenden" alt="Email versenden" href="mail.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('mail');?>">Email versenden</a>
	    <a title="Logfile anschauen" alt="Logfile anschauen" href="log.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('log');?>">Log</a>
	    <a title="Konfiguration" alt="Konfiguration" href="config-menu.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('config');?>">Konfiguration</a>
	    <a title="Statistik" alt="Statistik" href="evaluate.php" class="w3-bar-item w3-button w3-mobile <?php getAdminPage('evaluate');?>">Statistik</a>
	</div>
    </div>
<?php } ?>
    <a title="Homepage des Vereins" alt="Homepage des Vereins" href="<?php echo $optionsDB['MasterPage']; ?>" class="stdhide w3-hide-small w3-bar-item w3-button w3-mobile <?php echo $optionsDB['colorNav']; ?>" target="_blank"><img src="<?php echo $optionsDB['MasterPageIcon']; ?>" /> Vereinshomepage</a>
    <a title="Hilfe" alt="Hilfe" href="help.php" class="stdhide w3-hide-small w3-bar-item w3-button w3-mobile <?php getPage('help');?>"><i class="fas fa-info"></i></a>
    <a title="Ausloggen" alt="Ausloggen" href="logout.php" class="stdhide w3-hide-small w3-bar-item w3-button <?php echo $optionsDB['colorNav']; ?> w3-mobile"><i class="fas fa-sign-out-alt"></i></a>
</div>
        <?php
        if($optionsDB['MessageOfTheDayShort']) {
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
