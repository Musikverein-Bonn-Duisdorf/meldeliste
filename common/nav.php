<div class="w3-container w3-teal">
    <h1><?php echo $commonStrings['WebSiteName']; ?></h1>
</div>
<div class="w3-bar w3-teal">
  <a href="/MVD" class="w3-bar-item w3-button w3-mobile<?php getPage('home');?>">Home</a>
  <a href="termine.php" class="w3-bar-item w3-button w3-mobile<?php getPage('termine');?>">Termine</a>
  <div class="w3-dropdown-hover w3-mobile">
    <button class="w3-button w3-mobile w3-blue-gray">Admin</button>
    <div class="w3-dropdown-content w3-bar-block w3-card-4 w3-gray w3-mobile">
     <a href="musiker.php" class="w3-bar-item w3-button w3-blue-gray w3-mobile<?php getPage('musiker');?>">Musiker</a>
      <a href="new-musiker.php" class="w3-bar-item w3-button w3-blue-gray w3-mobile<?php getPage('newmusiker');?>">neuen Musiker anlegen</a>
      <a href="new-termin.php" class="w3-bar-item w3-button w3-blue-gray w3-mobile<?php getPage('newtermin');?>">neuen Termin erstellen</a>
    </div>
  </div>
  <a href="logout.php" class="w3-bar-item w3-button w3-gray w3-mobile">Logout</a>
</div>