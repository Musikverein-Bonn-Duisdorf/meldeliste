<?php
session_start();
$_SESSION['page']='myinstruments';
include "common/header.php";
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2>Meine Instrumente</h2>
</div>

<div class="w3-row w3-padding w3-border-bottom w3-border-black w3-hide-small w3-hide-medium">
  <div class="w3-col l1 m1 s1 w3-center w3-border-right"><b>Inventarnummer</b></div>
  <div class="w3-col l1 m2 s2 w3-center w3-border-right"><b>Instrument</b></div>
  <div class="w3-col l1 m2 s2 w3-center w3-border-right"><b>Hersteller</b></div>
  <div class="w3-col l1 m2 s2 w3-center w3-border-right"><b>Modell</b></div>
  <div class="w3-col l1 m1 s1 w3-center w3-border-right"><b>Seriennummer</b></div>
  <div class="w3-col l1 m1 s1 w3-center w3-border-right"><b>Kaufdatum</b></div>
  <div class="w3-col l1 m1 s1 w3-center w3-border-right"><b>Kaufpreis</b></div>
  <div class="w3-col l1 m1 s1 w3-center w3-border-right"><b>Zeitwert</b></div>
  <div class="w3-col l2 m1 s1 w3-center w3-border-right"><b>Besitzer</b></div>
  <div class="w3-col l2 m2 s2 w3-center w3-border-right"><b>ausgeliehen an</b></div>
</div>
<?php
      $sql = sprintf('SELECT `Index` FROM `%sInstruments` INNER JOIN (SELECT `Index` AS `iIndex`, `Register`, `Name` AS `iName`, `Sortierung` AS `iSort` FROM `%sInstrument`) `%sInstrument` ON `Instrument` = `iIndex` WHERE `Owner` = %d ORDER BY `iSort`;',
      $GLOBALS['dbprefix'],
      $GLOBALS['dbprefix'],
      $GLOBALS['dbprefix'],
      $_SESSION['userid']
      );
$dbr = mysqli_query($conn, $sql);
sqlerror();
while($row = mysqli_fetch_array($dbr)) {
    $M = new Instruments;
    $M->load_by_id($row['Index']);
    echo $M->printTableLine();
}

$u = new User;
$u->load_by_id($_SESSION['userid']);
$loans = $u->getLoans();
for($i=0; $i < count($loans); $i++) {
    $loan = new Loan;
    $loan->load_by_id($loans[$i]);
    $instrument = new Instruments;
    $instrument->load_by_id($loan->Instrument);
    echo $instrument->printTableLine();
}

include "common/footer.php";
?>
