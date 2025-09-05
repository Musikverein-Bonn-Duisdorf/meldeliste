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
  Ganz ehrlich, was erwartest du hier?
</div>
<?php
include "common/footer.php";
?>
