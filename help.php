<?php
session_start();
$_SESSION['page']='help';
include "common/header.php";
?>
<div class="w3-container <?php echo $GLOBALS['commonColors']['titlebar'] ;?>">
<h2>Hilfe</h2>
</div>
<div class="w3-container w3-margin-top">
Release: <?php echo "<b>".$GLOBALS['version']['String']."</b> (".$GLOBALS['version']['Date'].")"; ?>
</div>
<div class="w3-container w3-margin-top">
<a href="mailto:<?php echo $GLOBALS['site']['AdminEmail']; ?>">Nachricht an Admin</a>
</div>

<?php
include "common/help.inc";
?>

<?php
include "common/footer.php";
?>
