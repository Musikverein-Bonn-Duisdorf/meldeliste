<?php
session_start();
$_SESSION['page']='home';
$_SESSION['adminpage']=false;
include "common/header.php";
?>
<script src="js/getStatus.js"></script>
<script src="js/melde.js"></script>
<script src="js/meldeshift.js"></script>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar'] ;?>">
<h2>Home</h2>
</div>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar'] ;?>">
<h3>Bevorstehende Termine</h3>
</div>
<?php
$indent=0;
$modal = new div;
$modal->indent = $indent;
$modal->id="load";
$modal->class="w3-modal";
$modalcontent = new div;
$indent++;
$modalcontent->indent=$indent;
$modalcontent->class="w3-modal-content w3-center";
echo $modal->open();
echo $modalcontent->open();
?>

<i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i>
<span class="sr-only">Loading...</span>
      <script>
           document.getElementById('load').style.display="block";
           <script>    
<?php
echo $modalcontent->close();
echo $modal->close();
include "common/footer.php";
?>
