<?php
session_start();
$_SESSION['page']='log';
include "common/header.php";
?>
<div id="header" class="w3-container <?php echo $GLOBALS['commonColors']['titlebar']; ?>">
<h2>Log</h2>
</div>
<?php
$sql = sprintf('SELECT `Index` FROM `%sLog` ORDER BY `Timestamp` DESC;',
$GLOBALS['dbprefix']
);
$dbr = mysqli_query($conn, $sql);
sqlerror();
while($row = mysqli_fetch_array($dbr)) {
    $M = new Log;
    $M->load_by_id($row['Index']);
    $M->repair();
    $M->load_by_id($row['Index']);
    echo $M->printTableLine();
}
?>
<?php
include "common/footer.php";
?>