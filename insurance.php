<?php
ob_start();
session_start();
$_SESSION['page']='insurance';
$_SESSION['adminpage']=true;

include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
requireLoggedInOrRedirect();

if(handleInventoriesMutations()) {
    redirectAfterPost('insurance.php');
}

include "common/header.php";

if(!requirePermission("perm_showInventories") && !requirePermission("perm_showInstruments")) {
    denyAccess();
}

    $instrType = RegNumber::loadInstrType();
    $instrTypeId = $instrType ? (int)$instrType->Index : 0;
    $sql = sprintf('SELECT COUNT(`Index`) AS `Count` FROM `%sInventories` WHERE `Inventory` = %d AND `Insurance` = "1";',
    $GLOBALS['dbprefix'],
    $instrTypeId
    );
    $dbr = mysqli_query($conn, $sql);
    sqlerror();
    $row = mysqli_fetch_array($dbr);
    $nInstruments = $row['Count'];
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2>Versicherte Instrumente (<?php echo $nInstruments; ?>)</h2>
</div>

<div class="w3-row w3-padding-small" style="display:flex; flex-wrap:wrap; gap:0.5rem; align-items:center;">
  <input class="w3-input w3-border w3-padding w3-col l6 m12 s12" type="text" placeholder="Nach Instrument suchen..." id="filterString" onkeyup="filterMusiker()" style="flex:1 1 16rem;">
  <a class="w3-button w3-border <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?>" href="insuranceExport.php" target="_blank" rel="noopener">Übersicht für Versicherung</a>
</div>
<div id="listHeader" class="list-header w3-row w3-hide-small w3-hide-medium">
  <div class="w3-col l1 m1 s1 w3-center w3-border-right list-sort" data-sort="regnumber" data-type="number">Inventarnummer</div>
  <div class="w3-col l2 m2 s2 w3-center w3-border-right list-sort" data-sort="instrument" data-type="string">Instrument</div>
  <div class="w3-col l2 m2 s2 w3-center w3-border-right list-sort" data-sort="vendor" data-type="string">Hersteller</div>
  <div class="w3-col l2 m2 s2 w3-center w3-border-right list-sort" data-sort="model" data-type="string">Modell</div>
  <div class="w3-col l2 m1 s1 w3-center w3-border-right list-sort" data-sort="serial" data-type="string">Seriennummer</div>
  <div class="w3-col l1 m1 s1 w3-center w3-border-right list-sort" data-sort="zeitwert" data-type="number">Zeitwert</div>
  <div class="w3-col l2 m1 s1 w3-center w3-border-right list-sort" data-sort="owner" data-type="string">Besitzer</div>
</div>
<div id="Liste">
<?php
    $sql = sprintf('SELECT `Index` FROM `%sInventories` INNER JOIN (SELECT `Index` AS `iIndex`, `Register`, `Name` AS `iName`, `Sortierung` AS `iSort` FROM `%sInstrument`) `%sInstrument` ON `Instrument` = `iIndex` INNER JOIN (SELECT `Index` AS `rIndex`, `Name` AS `rName`, `Sortierung` AS `rSort` FROM `%sRegister`) `%sRegister` ON `Register` = `rIndex` WHERE `Inventory` = %d AND `Insurance` = "1" AND `rName` != "keins" ORDER BY `rSort`, `iSort`;',
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $instrTypeId
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
<script src="js/sortList.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script>bindListSort({ headerId: 'listHeader', listId: 'Liste', mode: 'client' });</script>

<?php
 include "common/footer.php";
?>
