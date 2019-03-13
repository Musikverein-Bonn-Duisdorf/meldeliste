<?php
session_start();
$_SESSION['page']='musiker';
include "header.php";

if(isset($_POST['insert'])) {
    $n = new User;
    $n->fill_from_array($_POST);
    $n->save();
}
?>
<div class="w3-container w3-dark-gray">
<h2>Liste aller Musiker</h2>
</div>
<table class="w3-table-all w3-hoverable">
<thead>
<tr>
<th>Vorname</th><th>Nachname</th><th>Stimme</th><th>Instrument</th><th>Email</th><th>Mitglied</th><th>getMail</th>
</tr>
</thead>
<tbody>

<?php
$sql = 'SELECT `Index` FROM `MVD`.`User` ORDER BY `Nachname`, `Vorname`;';
$dbr = mysqli_query($conn, $sql);
while($row = mysqli_fetch_array($dbr)) {
    $M = new User;
    $M = $M->load_by_id($row['Index']);
    $M->printTableLine();
}
?>
</tbody>
</table>
<form action="new-musiker.php">
    <button class="button" type="submit">neuen Musiker anlegen</button>
</form>
<?php
include "footer.php";
?>