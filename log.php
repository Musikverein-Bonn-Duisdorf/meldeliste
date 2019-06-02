<?php
session_start();
$_SESSION['page']='Log';
include "common/header.php";

?>
<div class="w3-container w3-dark-gray">
<h2>Log</h2>
</div>
<?php
$now = date("Y-m-d");
$sql = sprintf('SELECT `Index` FROM `MVD`.`Log` ORDER BY `Timestamp` DESC;');
$dbr = mysqli_query($conn, $sql);
while($row = mysqli_fetch_array($dbr)) {
    $M = new Log;
    $M->load_by_id($row['Index']);
    echo $M->printTableLine();
}
?>
<?php
include "common/footer.php";
?>