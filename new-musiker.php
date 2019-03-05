<?php
include "header.php";
?>

<h1>neuen Musiker anlegen</h1>
<form action="musiker.php" method="POST">
  <table>
    <tr><td><input name="Vorname" type="text" placeholder="Vorname"></td></tr>
    <tr><td><input name="Nachname" type="text" placeholder="Nachname"></td></tr>
    <tr><td><input name="Email" type="email" placeholder="Email"></td></tr>
    <tr><td>Stimme <input type="number" name="Stimme" min="1" max="15" value="1"></td></tr>
    <tr><td>
	Instrument <select name="Instrument">
	  <?php instrumentOption(); ?>
	  </select>
    </td></tr>
    <tr><td><input type="checkbox" name="Mitglied" value="1"> Mitglied</td></tr>
    <tr><td><input type="checkbox" name="getMail" value="1"> Mailverteiler</td></tr>
    <tr><td><input type="submit" name="insert" value="speichern"></td></tr>
  </table>
</form>

<?php
include "footer.php";
?>
