<?php
session_start();
$_SESSION['page']='insurance';
$_SESSION['adminpage']=true;
include "common/header.php";

    $sql = sprintf('SELECT COUNT(`Index`) AS `Count` FROM `%sInstruments` WHERE `Insurance` = "1";',
    $GLOBALS['dbprefix']
    );
    $dbr = mysqli_query($conn, $sql);
    sqlerror();
    $row = mysqli_fetch_array($dbr);
    $nInstruments = $row['Count'];
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2>Versicherte Instrumente (<?php echo $nInstruments; ?>)</h2>
</div>

<div class="w3-row">
  <input class="w3-input w3-border w3-padding w3-col l6 s6 m6" type="text" placeholder="Nach Instrument suchen..." id="filterString" onkeyup="filterMusiker()">
</div>
  <div class="w3-row w3-padding w3-border-bottom w3-border-black w3-hide-small w3-hide-medium">
  <div class="w3-col l1 m1 s1 w3-center w3-border-right"><b>Inventarnummer</b></div>
  <div class="w3-col l2 m2 s2 w3-center w3-border-right"><b>Instrument</b></div>
  <div class="w3-col l2 m2 s2 w3-center w3-border-right"><b>Hersteller</b></div>
  <div class="w3-col l2 m2 s2 w3-center w3-border-right"><b>Modell</b></div>
  <div class="w3-col l2 m1 s1 w3-center w3-border-right"><b>Seriennummer</b></div>
  <div class="w3-col l1 m1 s1 w3-center w3-border-right"><b>Zeitwert</b></div>
  <div class="w3-col l2 m1 s1 w3-center w3-border-right"><b>Besitzer</b></div>
</div>
<div id="Liste">
<?php
    $sql = sprintf('SELECT `Index` FROM `%sInstruments` INNER JOIN (SELECT `Index` AS `iIndex`, `Register`, `Name` AS `iName`, `Sortierung` AS `iSort` FROM `%sInstrument`) `%sInstrument` ON `Instrument` = `iIndex` INNER JOIN (SELECT `Index` AS `rIndex`, `Name` AS `rName`, `Sortierung` AS `rSort` FROM `%sRegister`) `%sRegister` ON `Register` = `rIndex` WHERE `Insurance` = "1" AND `rName` != "keins" ORDER BY `rSort`, `iSort`;',
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix']
    );
    $dbr = mysqli_query($conn, $sql);
    sqlerror();
    while($row = mysqli_fetch_array($dbr)) {
        $M = new Instruments;
        $M->load_by_id($row['Index']);
        echo $M->printInsuranceLine();
    }
?>
</div>
<script src="js/filterInstruments.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>

<?php
 include "common/footer.php";
?>
