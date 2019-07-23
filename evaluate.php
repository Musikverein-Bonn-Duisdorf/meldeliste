<?php
session_start();
$_SESSION['page']='evaluate';
include "common/header.php";
?>
<div id="header" class="w3-container <?php echo $GLOBALS['commonColors']['titlebar']; ?>">
<h2>Datenauswertung</h2>
</div>
<?php
$now = date("Y-m-d");
?>
<?php
include "common/footer.php";
?>