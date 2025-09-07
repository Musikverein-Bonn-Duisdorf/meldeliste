<?php
session_start();
$_Session['Page']='help';
$_SESSION['adminpage']=false;
include "common/header.php";
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar'] ;?>">
<h2>Medien</h2>
</div>
<div class="w3-container">
<h3>Hier findest du Konzertaufnahmen, Fotos und weitere Medien für den internen Gebrauch</h3>
</div>
  <div class="w3-container w3-row-padding w3-margin-top">
    <form class="w3-col s12 m6 l4" action="https://www.youtube.com/playlist?list=PLcnXPttCC4m4ZrvzYxJf4_tTIBwRjhDDo" target="_blank">
	<button class="w3-btn w3-border w3-border-black <?php echo $GLOBALS['optionsDB']['colorBtnEdit'] ;?>" type="submit"><i class="fa-brands fa-youtube"></i> Youtube-Kanal</button>
    </form>
    <form class="w3-col s12 m6 l4" action="https://cloud.musikverein-bonn-duisdorf.de/index.php/s/CorLLnP3Znnxd8E" target="_blank">
	<button class="w3-btn w3-border w3-border-black <?php echo $GLOBALS['optionsDB']['colorBtnEdit'] ;?>" type="submit"><i class="fas fa-cloud"></i> Fotos</button>
    </form>
    <form class="w3-col s12 m6 l4" action="https://cloud.musikverein-bonn-duisdorf.de/index.php/s/3nz6GTtJWCLESfx" target="_blank">
	<button class="w3-btn w3-border w3-border-black <?php echo $GLOBALS['optionsDB']['colorBtnEdit'] ;?>" type="submit"><i class="fas fa-cloud"></i> Videos</button>
    </form>
  </div>
<?php
include "common/footer.php";
?>
