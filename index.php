<?php
session_start();
$_SESSION['page']='home';
include "header.php";
?>
<div class="w3-container w3-dark-gray">
<h2>Home</h2>
</div>
<div class="w3-panel w3-dark-gray">
<h2>Bevorstehende Termine</h2>
</div>
<table class="w3-table-all w3-hoverable">
<thead>
<tr>
<th>Datum</th><th>Beginn</th><th>Ende</th><th>Veranstaltung</th><th>Ort</th>
</tr>
</thead>
<tbody>

<?php
$now = date("Y-m-d");
$sql = sprintf('SELECT `Index` FROM `MVD`.`Termine` WHERE `Datum` > "%s" AND `published` > 0 ORDER BY `Datum`, `Uhrzeit` LIMIT 5;',
$now
);
$dbr = mysqli_query($conn, $sql);
while($row = mysqli_fetch_array($dbr)) {
    $M = new Termin;
    $M = $M->load_by_id($row['Index']);
    $M->printBasicTableLine();
}
?>
</tbody>
</table>

<div class="w3-panel w3-dark-gray">
<h2>Meine Meldungen</h2>
</div>

<?php
include "footer.php";
?>