<?php
session_start();
$_SESSION['page']='users';
$_SESSION['adminpage']=true;
include "common/header.php";
if(!requirePermission("perm_showUsers")) die();
include "libs/form-response.php";

$sql = sprintf('SELECT COUNT(`Index`) AS `Count` FROM `%sUser` WHERE `Deleted` != 1;',
$GLOBALS['dbprefix']
);
$dbr = mysqli_query($conn, $sql);
sqlerror();
$row = mysqli_fetch_array($dbr);
$nMusiker = $row['Count'];
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
    <h2>Liste aller User (<?php echo $nMusiker; ?>)</h2>
</div>

<div>
<input class="w3-input w3-border w3-padding" type="text" placeholder="Nach Musiker suchen..." id="filterString" onkeyup="filterLog()">
</div>
<div id="Liste">
<?php
$sql = sprintf('SELECT `Index` FROM `%sUser` WHERE `Deleted` != 1 ORDER BY `Nachname`, `Vorname`;',
$GLOBALS['dbprefix']
);
$dbr = mysqli_query($conn, $sql);
sqlerror();
while($row = mysqli_fetch_array($dbr)) {
    $M = new User;
    $M->load_by_id($row['Index']);
    $M->printUserTableLine();
}
?>
</div>
<script src="js/filterLog.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>

<?php
include "common/footer.php";
?>
