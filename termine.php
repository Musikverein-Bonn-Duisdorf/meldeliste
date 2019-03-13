<?php
session_start();
$_SESSION['page']='termine';
include "header.php";

if(isset($_POST['insert'])) {
    $n = new Termin;
    $n->fill_from_array($_POST);
    $n->save();
}
?>
<div class="w3-container w3-dark-gray">
<h2>Termin&uuml;bersicht</h2>
</div>
<table class="w3-table-all w3-hoverable">
<thead>
<tr>
<th>Datum</th><th>Beginn</th><th>Ende</th><th>Name</th><th>Beschreibung</th><th>Ort1</th><th>Ort2</th><th>Ort3</th><th>Ort4</th><th>published</th>
</tr>
</thead>
<tbody>

<?php
$now = date("Y-m-d");
$sql = sprintf('SELECT `Index` FROM `MVD`.`Termine` WHERE `Datum` > "%s" ORDER BY `Datum`, `Uhrzeit`;',
$now
);
$dbr = mysqli_query($conn, $sql);
while($row = mysqli_fetch_array($dbr)) {
    $M = new Termin;
    $M = $M->load_by_id($row['Index']);
    $M->printTableLine();
}
?>
</tbody>
</table>
<form action="new-termin.php">
    <button class="button" type="submit">neuen Termin anlegen</button>
</form>
<?php
include "footer.php";
?>