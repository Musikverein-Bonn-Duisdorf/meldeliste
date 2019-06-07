<?php
session_start();
$_SESSION['page']='mitglied';
include "common/header.php";

if($_SESSION['admin']) {
$sql = 'SELECT COUNT(`Index`) AS `Count` FROM `User` WHERE `Mitglied` = 1;';
$dbr = mysqli_query($conn, $sql);
$row = mysqli_fetch_array($dbr);
$nMusiker = $row['Count'];
?>
<div class="w3-container w3-dark-gray">
    <h2>Liste aller aktiven Mitglieder (<?php echo $nMusiker; ?>)</h2>
</div>
<?php
$sql = 'SELECT `Index` FROM `User` WHERE `Mitglied` = 1 ORDER BY `Nachname`, `Vorname`;';
$dbr = mysqli_query($conn, $sql);
while($row = mysqli_fetch_array($dbr)) {
    $M = new User;
    $M->load_by_id($row['Index']);
    $M->printTableLine();
}
?>
<?php }
else {
?>
    <meta http-equiv="refresh" content="0; URL=index.php" />
<?php
}
include "common/footer.php";
?>
