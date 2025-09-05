<?php
session_start();
$_SESSION['page']='help';
$_SESSION['adminpage']=false;
include "common/header.php";
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar'] ;?>">
<h2>Medien</h2>
</div>
<div class="w3-container w3-margin-top">
Hier findest du Konzertaufnahmen, Fotos und weitere Medien für den internen Gebrauch:
  <ul>
    <li><a href="https://www.youtube.com/playlist?list=PLcnXPttCC4m4ZrvzYxJf4_tTIBwRjhDDo"><i class="fa-brands fa-youtube"></i> Youtube-Kanel</a></li>
    <li><a href="https://cloud.musikverein-bonn-duisdorf.de/index.php/s/CorLLnP3Znnxd8E"><i class="fas fa-cloud"></i> Fotos</a></li>
    <li><a href="https://cloud.musikverein-bonn-duisdorf.de/index.php/s/3nz6GTtJWCLESfx"><i class="fas fa-cloud"></i> Videos</a></li>
  </ul>
</div>
<?php
include "common/footer.php";
?>
