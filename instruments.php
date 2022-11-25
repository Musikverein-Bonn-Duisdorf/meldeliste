<?php
session_start();
$_SESSION['page']='instruments';
$_SESSION['adminpage']=true;
include "common/header.php";

if(isset($_POST['insert'])) {
    $n = new Instruments;
    $n->fill_from_array($_POST);
    $n->save();
}
if(isset($_POST['delete'])) {
    $n = new Instruments;
    $n->load_by_id($_POST['Index']);
    $n->delete();
}
if($_SESSION['admin']) {
    $sql = sprintf('SELECT COUNT(`Index`) AS `Count` FROM `%sInstruments`;',
    $GLOBALS['dbprefix']
    );
    $dbr = mysqli_query($conn, $sql);
    sqlerror();
    $row = mysqli_fetch_array($dbr);
    $nInstruments = $row['Count'];
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2>Instrumentenliste (<?php echo $nInstruments; ?>)</h2>
</div>

<div class="w3-row">
  <input class="w3-input w3-border w3-padding w3-col l6 s6 m6" type="text" placeholder="Nach Instrument suchen..." id="filterString" onkeyup="filterMusiker()">
  <div onclick="document.getElementById('inputModal').style.display='block'" class="w3-col l1 m6 s6 w3-center w3-padding <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>"><i class="fas fa-plus"></i></div>
</div>
<div id="inputModal" class="w3-modal">
  <form class="w3-modal-content" action="" method="POST">
    <header class="w3-container w3-row <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
      <span onclick="document.getElementById('inputModal').style.display='none'" class="w3-button w3-display-topright">&times;</span>
      <h2>neues Instrument anlegen</h2>
    </header>
    <div class="w3-row w3-padding">
      <div class="w3-col l4 m6 s6"><b>Inventarnummer</b></div>
      <input name="RegNumber" type="number" class="w3-input w3-col l4 m6 s6 <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" value="<?php echo getNextRegNumber(); ?>" />
    </div>
    <div class="w3-row w3-padding">
      <div class="w3-col l4 m6 s6"><b>Instrument</b></div>
      <select class="w3-col l4 m6 s6 w3-input <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="Instrument">
	<?php echo instrumentOptionAll(0); ?>
      </select>
    </div>
    <div class="w3-row w3-padding">
      <div class="w3-col l4 m6 s6"><b>Hersteller</b></div>
      <input name="Vendor" type="text" class="w3-input w3-col l4 m6 s6 <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>"/>
    </div>
    <div class="w3-row w3-padding">
      <div class="w3-col l4 m6 s6"><b>Seriennummer</b></div>
      <input name="SerialNr" type="text" class="w3-input w3-col l4 m6 s6 <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>"/>
    </div>
    <div class="w3-row w3-padding">
      <div class="w3-col l4 m6 s6"><b>Kaufdatum</b></div>
      <input name="PurchaseDate" type="date" class="w3-input w3-col l4 m6 s6 <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>"/>
    </div>
    <div class="w3-row w3-padding">
      <div class="w3-col l4 m6 s6"><b>Kaufpreis</b></div>
      <input name="PurchasePrize" step="0.01" value="0.00" min="0" type="number" class="w3-input w3-col l4 m6 s6 <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>"/>
    </div>
    <div class="w3-row w3-padding">
      <div class="w3-col l4 m6 s6"><b>Besitzer</b></div>
      <select class="w3-col l4 m6 s6 w3-input <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="Owner">
	<?php echo UserOptionAll(0); ?>
      </select>
    </div>
    <div class="w3-row w3-padding">
      <div class="w3-col l4 m6 s6"><b>Kommentar</b></div>
      <input name="Comment" type="text" class="w3-input w3-col l4 m6 s6 <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>"/>
    </div>
    <div class="w3-row w3-padding">
      <div class="w3-col l4 m6 s6"><b>Versichert</b></div>
      <input name="Insurance" value="1" type="checkbox" class="w3-input w3-col l4 m6 s6 <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>"/>
    </div>
    <div class="w3-row w3-padding">
      <input type="submit" name="insert" value="speichern" class="w3-input w3-button w3-col l8 m12 s12 <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?>"/>
    </div>
  </form>
</div>
<div class="w3-row w3-padding w3-border-bottom w3-border-black w3-hide-small w3-hide-medium">
  <div class="w3-col l1 m1 s1 w3-center w3-border-right"><b>Inventarnummer</b></div>
  <div class="w3-col l2 m2 s2 w3-center w3-border-right"><b>Instrument</b></div>
  <div class="w3-col l2 m2 s2 w3-center w3-border-right"><b>Hersteller</b></div>
  <div class="w3-col l1 m1 s1 w3-center w3-border-right"><b>Seriennummer</b></div>
  <div class="w3-col l1 m1 s1 w3-center w3-border-right"><b>Kaufdatum</b></div>
  <div class="w3-col l1 m1 s1 w3-center w3-border-right"><b>Kaufpreis</b></div>
  <div class="w3-col l1 m1 s1 w3-center w3-border-right"><b>Zeitwert</b></div>
  <div class="w3-col l1 m1 s1 w3-center w3-border-right"><b>Besitzer</b></div>
  <div class="w3-col l2 m2 s2 w3-center w3-border-right"><b>ausgeliehen an</b></div>
</div>
<?php
    $sql = sprintf('SELECT `Index` FROM `%sInstruments` INNER JOIN (SELECT `Index` AS `iIndex`, `Register`, `Name` AS `iName`, `Sortierung` AS `iSort` FROM `%sInstrument`) `%sInstrument` ON `Instrument` = `iIndex` INNER JOIN (SELECT `Index` AS `rIndex`, `Name` AS `rName`, `Sortierung` AS `rSort` FROM `%sRegister`) `%sRegister` ON `Register` = `rIndex` WHERE `rName` != "keins" ORDER BY `rSort`, `iSort`;',
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
        echo $M->printTableLine();
    }
?>
<script src="js/filterInstruments.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>

<?php }
    else {
 ?>
<meta http-equiv="refresh" content="0; URL=index.php" />
<?php
    }

 include "common/footer.php";
?>
