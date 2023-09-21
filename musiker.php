<?php
session_start();
$_SESSION['page']='musiker';
$_SESSION['adminpage']=true;
include "common/header.php";
if(!requirePermission("perm_showUsers")) die();
include "libs/form-response.php";

$sql = sprintf('SELECT COUNT(`Index`) AS `Count` FROM `%sUser` INNER JOIN (SELECT `Index` AS `iIndex`, `Register` FROM `%sInstrument`) `%sInstrument` ON `Instrument` = `iIndex` INNER JOIN (SELECT `Index` AS `rIndex`, `Name` AS `rName` FROM `%sRegister`) `%sRegister` ON `Register` = `rIndex` WHERE `rName` != "keins" AND `Deleted` != 1;',
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix']
    );
$dbr = mysqli_query($conn, $sql);
sqlerror();
$row = mysqli_fetch_array($dbr);
$nMusiker = $row['Count'];
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
    <h2>Liste aller Musiker (<?php echo $nMusiker; ?>)</h2>
</div>

<?php if($GLOBALS['optionsDB']['showOrchestraView']) { ?>
<div class="w3-center w3-container w3-hide-small">
<?php echo printOrchestra(0, 1); ?>
</div>
<div class="w3-center w3-container w3-hide-large w3-hide-medium">
<?php echo printOrchestra(0, 0.4); ?>
</div>
<?php } ?>

<div>
<input class="w3-input w3-border w3-padding" type="text" placeholder="Nach Musiker suchen..." id="filterString" onkeyup="filterMusiker()">
</div>
<div id="Liste">
<?php
$sql = sprintf('SELECT `Index` FROM `%sUser` INNER JOIN (SELECT `Index` AS `iIndex`, `Register` FROM `%sInstrument`) `%sInstrument` ON `Instrument` = `iIndex` INNER JOIN (SELECT `Index` AS `rIndex`, `Name` AS `rName` FROM `%sRegister`) `%sRegister` ON `Register` = `rIndex` WHERE `rName` != "keins" AND `Deleted` != 1 ORDER BY `Nachname`, `Vorname`;',
$GLOBALS['dbprefix'],
$GLOBALS['dbprefix'],
$GLOBALS['dbprefix'],
$GLOBALS['dbprefix'],
$GLOBALS['dbprefix']
);
$dbr = mysqli_query($conn, $sql);
sqlerror();
while($row = mysqli_fetch_array($dbr)) {
    $M = new User;
    $M->load_by_id($row['Index']);
    $M->printTableLine();
}
?>
</div>
<script src="js/filterMusiker.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>

<?php
include "common/footer.php";
?>
