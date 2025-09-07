<?php
session_start();
$_Session['Page']='help';
$_SESSION['adminpage']=false;
include "common/header.php";
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar'] ;?>">
<h2>Medien</h2>
</div>
<div class="w3-container w3-margin-top">
Hier findest du Konzertaufnahmen, Fotos und weitere Medien für den internen Gebrauch:
  <div class="w3-container">
    <form class="w3-container w3-row" action="https://www.youtube.com/playlist?list=PLcnXPttCC4m4ZrvzYxJf4_tTIBwRjhDDo" target="_blank" method="POST">
	<button class="w3-btn w3-border w3-margin-top w3-border-black s12 m12 l12 <?php echo $GLOBALS['optionsDB']['colorBtnEdit'] ;?>" type="submit"><i class="fa-brands fa-youtube"></i> Youtube-Kanal</button>
    </form>
    <form class="w3-container w3-row" action="https://cloud.musikverein-bonn-duisdorf.de/index.php/s/CorLLnP3Znnxd8E" target="_blank" method="POST">
	<button class="w3-btn w3-border w3-margin-top w3-border-black s12 m12 l12 <?php echo $GLOBALS['optionsDB']['colorBtnEdit'] ;?>" type="submit"><i class="fas fa-cloud"></i> Fotos</button>
    </form>
    <form class="w3-container w3-row" action="https://cloud.musikverein-bonn-duisdorf.de/index.php/s/3nz6GTtJWCLESfx" target="_blank" method="POST">
	<button class="w3-btn w3-border w3-margin-top w3-border-black s12 m12 l12 <?php echo $GLOBALS['optionsDB']['colorBtnEdit'] ;?>" type="submit"><i class="fas fa-cloud"></i> Videos</button>
    </form>
  </div>
</div>
<?php
include "common/footer.php";
?>
