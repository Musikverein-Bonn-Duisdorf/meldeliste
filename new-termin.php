<?php
include "header.php";
?>

<h1>neuen Termin anlegen</h1>
<form action="termine.php" method="POST">
  <table>
    <tr><td><input name="Datum" type="date"></td></tr>
    <tr><td><input name="Uhrzeit" type="time"></td></tr>
    <tr><td><input name="Uhrzeit2" type="time"></td></tr>
    <tr><td><input name="Name" type="text" placeholder="Name"></td></tr>
    <tr><td><input name="Beschreibung" type="text" placeholder="Beschreibung"></td></tr>
    <tr><td><input name="Ort1" type="text" placeholder="Ort"></td></tr>
    <tr><td><input name="Ort2" type="text" placeholder="Ort"></td></tr>
    <tr><td><input name="Ort3" type="text" placeholder="Ort"></td></tr>
    <tr><td><input name="Ort4" type="text" placeholder="Ort"></td></tr>
    <tr><td><input type="checkbox" name="Auftritt" value="1" checked> Auftritt</td></tr>
    <tr><td><input type="checkbox" name="published" value="1" checked> published</td></tr>
    <tr><td><input type="submit" name="insert" value="speichern"></td></tr>
  </table>
</form>

<?php
include "footer.php";
?>
