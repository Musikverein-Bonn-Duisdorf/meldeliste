<?php
session_start();
$_SESSION['page']='musiker';
include "common/header.php";

if(isset($_POST['insert'])) {
    $n = new User;
    $n->load_by_id($_POST['Index']);
    $n->fill_from_array($_POST);
    $n->save();
}
if(isset($_POST['delete'])) {
    $n = new User;
    $n->load_by_id($_POST['Index']);
    $n->delete();
}
if(isset($_POST['passwd'])) {
    $n = new User;
    $n->load_by_id($_POST['Index']);
    $n->fill_from_array($_POST);
    $n->passwd();
}
if($_SESSION['admin']) {
$sql = 'SELECT COUNT(`Index`) AS `Count` FROM `User`;';
$dbr = mysqli_query($conn, $sql);
$row = mysqli_fetch_array($dbr);
$nMusiker = $row['Count'];
?>
<div class="w3-container w3-dark-gray">
    <h2>Liste aller Musiker (<?php echo $nMusiker; ?>)</h2>
</div>
<?php
$sql = 'SELECT `Index` FROM `User` ORDER BY `Nachname`, `Vorname`;';
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
