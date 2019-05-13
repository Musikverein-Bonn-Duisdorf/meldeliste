<div class="w3-container <?php echo $commonColors['Title']; ?>">
         <h1><?php echo $commonStrings['WebSiteName']; ?></h1>
<p><?php echo $_SESSION['username'] ?></p>
</div>
<div class="w3-bar w3-teal">
    <a href="/MVD" class="w3-bar-item w3-button w3-mobile<?php getPage('home');?>">Home</a>
    <a href="termine.php" class="w3-bar-item w3-button w3-mobile<?php getPage('termine');?>">Termine</a>
    <a href="#" class="w3-bar-item w3-button w3-mobile<?php getPage('me');?>">Mein Profil</a>
<?php if($_SESSION['admin']) {?>
    <div class="w3-dropdown-hover w3-mobile">
	<button class="w3-button w3-mobile w3-blue-gray">Admin</button>
	<div class="w3-dropdown-content w3-bar-block w3-card-4 w3-gray w3-mobile">
     <a href="meldungen.php" class="w3-bar-item w3-button w3-blue-gray w3-mobile<?php getPage('meldungen');?>">Meldungen</a>
	    <a href="musiker.php" class="w3-bar-item w3-button w3-blue-gray w3-mobile<?php getPage('musiker');?>">Musikerliste</a>
	    <a href="new-musiker.php" class="w3-bar-item w3-button w3-blue-gray w3-mobile<?php getPage('newmusiker');?>">neuen Musiker anlegen</a>
	    <a href="new-termin.php" class="w3-bar-item w3-button w3-blue-gray w3-mobile<?php getPage('newtermin');?>">neuen Termin erstellen</a>
	    <a href="#" class="w3-bar-item w3-button w3-blue-gray w3-mobile<?php getPage('mail');?>">Email versenden</a>
	</div>
    </div>
<?php } ?>
    <a href="logout.php" class="w3-bar-item w3-button w3-gray w3-mobile">Logout</a>
</div>
