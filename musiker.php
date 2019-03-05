<?php
include "header.php";
?>

<h1>Liste aller Musiker</h1>

<table class="list">
<thead>
<tr>
<th>Vorname</th><th>Nachname</th><th>Stimme</th><th>Instrument</th><th>Mitglied</th><th>getMail</th>
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

<?php
include "footer.php";
?>