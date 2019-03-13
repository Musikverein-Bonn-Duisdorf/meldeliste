<div class="w3-container w3-teal">
    <h1><?php echo $commonStrings['WebSiteName']; ?></h1>
</div>
<div class="w3-bar w3-teal">
  <a href="/MVD" class="w3-bar-item w3-button<?php getPage('home');?>">Home</a>
  <a href="musiker.php" class="w3-bar-item w3-button<?php getPage('musiker');?>">Musiker</a>
  <a href="termine.php" class="w3-bar-item w3-button<?php getPage('termine');?>">Termine</a>
  <a href="#" class="w3-bar-item w3-button<?php getPage('admin');?>">Admin</a>
  <div class="w3-dropdown-hover">
    <button class="w3-button">Dropdown</button>
    <div class="w3-dropdown-content w3-bar-block w3-card-4 w3-gray">
      <a href="#" class="w3-bar-item w3-button">Link 1</a>
      <a href="#" class="w3-bar-item w3-button">Link 2</a>
      <a href="#" class="w3-bar-item w3-button">Link 3</a>
    </div>
  </div>
</div>