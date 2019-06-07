<?php
session_start();
$_SESSION['page']='meldungen';
include "common/header.php";

?>
<div class="w3-container w3-dark-gray">
<h2>Rückmeldungen</h2>
</div>
<?php
$now = date("Y-m-d");
$sql = sprintf('SELECT `Index` FROM `Termine` WHERE `Datum` > "%s" ORDER BY `Datum`, `Uhrzeit`;',
$now
);
$dbr = mysqli_query($conn, $sql);
while($row = mysqli_fetch_array($dbr)) {
    $M = new Termin;
    $M->load_by_id($row['Index']);
    echo $M->printResponseLine();
}
?>
<?php
include "common/footer.php";
?>