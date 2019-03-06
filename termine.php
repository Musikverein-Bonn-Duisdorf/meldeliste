<?php
include "header.php";

if(isset($_POST['insert'])) {
    $n = new Termin;
    $n->fill_from_array($_POST);
    $n->save();
}
?>

<h1>Termin&uuml;bersicht</h1>

<form action="/MVD">
    <button class="button" type="submit">Home</button>
</form>
<form action="new-termin.php">
    <button class="button" type="submit">neuen Termin anlegen</button>
</form>
    <br />
<table class="list">
<thead>
<tr>
<th>Datum</th><th>Beginn</th><th>Ende</th><th>Name</th><th>Beschreibung</th><th>Ort1</th><th>Ort2</th><th>Ort3</th><th>Ort4</th><th>Auftritt</th><th>published</th>
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


<?php
include "footer.php";
?>