<?php
session_start();
$_SESSION['page']='musiker';
include "common/header.php";

if(isset($_POST['insert'])) {
    $n = new User;
    $n->fill_from_array($_POST);
    $n->save();
}
if(isset($_POST['delete'])) {
    $n = new User;
    $n->fill_from_array($_POST);
    $n->delete();
}
$sql = 'SELECT COUNT(`Index`) AS `Count` FROM `MVD`.`User`;';
$dbr = mysqli_query($conn, $sql);
$row = mysqli_fetch_array($dbr);
$nMusiker = $row['Count'];
?>
<div class="w3-container w3-dark-gray">
    <h2>Liste aller Musiker (<?php echo $nMusiker; ?>)</h2>
</div>
<?php
$sql = 'SELECT `Index` FROM `MVD`.`User` ORDER BY `Nachname`, `Vorname`;';
$dbr = mysqli_query($conn, $sql);
while($row = mysqli_fetch_array($dbr)) {
    $M = new User;
    $M = $M->load_by_id($row['Index']);
    $M->printTableLine();
}
?>
<?php
include "common/footer.php";
?>
