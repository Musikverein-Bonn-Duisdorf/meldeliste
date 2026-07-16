<?php
session_start();
$_SESSION['page']='update';
$_SESSION['adminpage']=true;
include "common/header.php";
if(!requirePermission("perm_editConfig")) die();
if(empty($_SESSION['admin'])) {
    die("<div class=\"w3-panel w3-red w3-padding\"><b>Admin-Zugang erforderlich.</b></div>");
}

$oldversion = "NULL";

$logentry = new Log;
$logentry->info("Updating server from version ".$oldversion." to ".$GLOBALS['version']['String']);
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar'] ;?>">
    <h2>Updater</h2>
</div>
<div class="w3-container w3-padding <?php echo $GLOBALS['optionsDB']['colorTitleBar'] ;?>">
         Updating from version <b><?php echo $oldversion; ?></b> to <b><?php echo $GLOBALS['version']['String']; ?></b>...
</div>
<?php

include_once "dbintegrity.php";
$manager = new DatabaseManager();
$manager->repair();
DBRenderReport($manager->getReport());

?>

<?php
include "common/footer.php";
?>
