<?php
session_start();
$_SESSION['page']='inventories';
$_SESSION['adminpage']=true;
include "common/header.php";

$mutating = isset($_POST['newLoan']) || isset($_POST['endLoan']) || isset($_POST['insert'])
    || isset($_POST['update']) || isset($_POST['delete']);
if($mutating && !requirePermission('perm_editInventories')) {
    http_response_code(403);
    die('Keine Berechtigung.');
}

if(isset($_POST['newLoan'])) {
    $n = new InventoriesLoan;
    $n->fill_from_array($_POST);
    $n->save();
}
if(isset($_POST['endLoan'])) {
    $n = new InventoriesLoan;
    $loanId = isset($_POST['LoanIndex']) ? $_POST['LoanIndex'] : $_POST['Index'];
    $n->load_by_id($loanId);
    $n->EndDate = $_POST['EndDate'];
    $n->save();
}
if(isset($_POST['insert'])) {
    $n = new Inventories;
    $n->fill_from_array($_POST);
    if(empty($_POST['Insurance'])) $n->Insurance = 0;
    $type = RegNumber::loadType((int)$n->Inventory);
    if(!$type || RegNumber::normalizePrefix($type->Prefix) !== RegNumber::DEFAULT_INSTR_PREFIX) {
        $n->Instrument = 0;
    }
    $n->save();
}
if(isset($_POST['update'])) {
    $id = isset($_POST['InventoriesIndex']) ? (int)$_POST['InventoriesIndex'] : (int)$_POST['Index'];
    $n = new Inventories;
    $n->load_by_id($id);
    if($n->Index) {
        // Keep type FK; do not let loan-form field "Inventory" overwrite it
        $typeId = (int)$n->Inventory;
        $n->RegNumber = isset($_POST['RegNumber']) ? $_POST['RegNumber'] : $n->RegNumber;
        $n->Description = isset($_POST['Description']) ? $_POST['Description'] : $n->Description;
        $n->Vendor = isset($_POST['Vendor']) ? $_POST['Vendor'] : $n->Vendor;
        $n->Model = isset($_POST['Model']) ? $_POST['Model'] : $n->Model;
        $n->SerialNr = isset($_POST['SerialNr']) ? $_POST['SerialNr'] : $n->SerialNr;
        $n->PurchaseDate = isset($_POST['PurchaseDate']) ? $_POST['PurchaseDate'] : $n->PurchaseDate;
        $n->PurchasePrize = isset($_POST['PurchasePrize']) ? $_POST['PurchasePrize'] : $n->PurchasePrize;
        $n->Owner = isset($_POST['Owner']) ? $_POST['Owner'] : $n->Owner;
        $n->Insurance = isset($_POST['Insurance']) ? (int)$_POST['Insurance'] : 0;
        $n->Comment = isset($_POST['Comment']) ? $_POST['Comment'] : $n->Comment;
        $n->Inventory = $typeId;
        $type = RegNumber::loadType($typeId);
        if($type && RegNumber::normalizePrefix($type->Prefix) === RegNumber::DEFAULT_INSTR_PREFIX) {
            $n->Instrument = isset($_POST['Instrument']) ? (int)$_POST['Instrument'] : (int)$n->Instrument;
        }
        else {
            $n->Instrument = 0;
        }
        $n->save();
    }
}
if(isset($_POST['delete'])) {
    $id = isset($_POST['InventoriesIndex']) ? (int)$_POST['InventoriesIndex'] : (int)$_POST['Index'];
    $n = new Inventories;
    $n->load_by_id($id);
    $n->delete();
}
if(requirePermission("perm_showInventories")) {
    $sql = sprintf('SELECT COUNT(`Index`) AS `Count` FROM `%sInventories`;',
    $GLOBALS['dbprefix']
    );
    $dbr = mysqli_query($conn, $sql);
    sqlerror();
    $row = mysqli_fetch_array($dbr);
    $nInventories = $row['Count'];
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2>Inventarliste (<?php echo $nInventories; ?>)</h2>
</div>

<div class="w3-row">
  <input class="w3-input w3-border w3-padding w3-col l6 m10 s10" type="text" placeholder="Nach Inventar suchen..." id="filterString" onkeyup="filterMusiker()">
  <div onclick="document.getElementById('inputModal').style.display='block'" class="w3-col l1 m2 s2 w3-center w3-padding <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>"><i class="fas fa-plus"></i></div>
</div>
<div id="inputModal" class="w3-modal">
  <form class="w3-modal-content" action="" method="POST">
    <header class="w3-container w3-row <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
      <span onclick="document.getElementById('inputModal').style.display='none'" class="w3-button w3-display-topright">&times;</span>
      <h2>neues Inventar anlegen</h2>
    </header>
    <div class="w3-row w3-padding">
      <div class="w3-col l4 m6 s12"><b>Inventar</b></div>
      <select id="newInventoryType" class="w3-col l4 m6 s12 w3-input <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="Inventory">
	<?php echo inventoryOptionAll(0); ?>
      </select>
    </div>
    <div class="w3-row w3-padding">
      <div class="w3-col l4 m6 s12"><b>Inventarnummer</b> <span class="w3-small" id="regPreview"></span></div>
      <input id="newRegNumber" name="RegNumber" type="number" class="w3-input w3-col l4 m6 s12 <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" value="<?php echo getNextRegInventoryNumber(); ?>" />
    </div>
    <div id="newInstrumentFamilyRow" class="w3-row w3-padding" style="display:none;">
      <div class="w3-col l4 m6 s12"><b>Instrument</b></div>
      <select id="newInstrumentFamily" class="w3-col l4 m6 s12 w3-input <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="Instrument">
	<?php echo instrumentOptionAll(0); ?>
      </select>
    </div>
    <div class="w3-row w3-padding">
      <div class="w3-col l4 m6 s12"><b>Beschreibung</b></div>
      <input name="Description" type="text" class="w3-input w3-col l4 m6 s12 <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>"/>
    </div>
    <div class="w3-row w3-padding">
      <div class="w3-col l4 m6 s12"><b>Hersteller</b></div>
      <input name="Vendor" type="text" class="w3-input w3-col l4 m6 s12 <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>"/>
    </div>
    <div class="w3-row w3-padding">
      <div class="w3-col l4 m6 s12"><b>Modell</b></div>
      <input name="Model" type="text" class="w3-input w3-col l4 m6 s12 <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>"/>
    </div>
    <div class="w3-row w3-padding">
      <div class="w3-col l4 m6 s12"><b>Seriennummer</b></div>
      <input name="SerialNr" type="text" class="w3-input w3-col l4 m6 s12 <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>"/>
    </div>
    <div class="w3-row w3-padding">
      <div class="w3-col l4 m6 s12"><b>Kaufdatum</b></div>
      <input name="PurchaseDate" type="date" class="w3-input w3-col l4 m6 s12 <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>"/>
    </div>
    <div class="w3-row w3-padding">
      <div class="w3-col l4 m6 s12"><b>Kaufpreis</b></div>
      <input name="PurchasePrize" step="0.01" value="0.00" min="0" type="number" class="w3-input w3-col l4 m6 s12 <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>"/>
    </div>
    <div class="w3-row w3-padding">
      <div class="w3-col l4 m6 s12"><b>Besitzer</b></div>
      <select class="w3-col l4 m6 s12 w3-input <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>" name="Owner">
	<?php echo UserOptionAll(0); ?>
      </select>
    </div>
    <div class="w3-row w3-padding">
      <div class="w3-col l4 m6 s12"><b>Kommentar</b></div>
      <input name="Comment" type="text" class="w3-input w3-col l4 m6 s12 <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>"/>
    </div>
    <div class="w3-row w3-padding">
      <div class="w3-col l4 m6 s12"><b>Versichert</b></div>
      <input type="hidden" name="Insurance" value="0" />
      <input name="Insurance" value="1" type="checkbox" class="w3-check w3-col l4 m6 s12"/>
    </div>
    <div class="w3-row w3-padding">
      <input type="submit" name="insert" value="speichern" class="w3-input w3-button w3-col l8 m12 s12 <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?>"/>
    </div>
  </form>
</div>
<div class="w3-row w3-padding w3-border-bottom w3-border-black w3-hide-small w3-hide-medium">
  <div class="w3-col l1 m1 s1 w3-center w3-border-right"><b>Inventarnummer</b></div>
  <div class="w3-col l2 m2 s2 w3-center w3-border-right"><b>Typ</b></div>
  <div class="w3-col l2 m4 s4 w3-center w3-border-right"><b>Beschreibung</b></div>
  <div class="w3-col l2 m4 s4 w3-center w3-border-right"><b>Kommentar</b></div>
  <div class="w3-col l1 m1 s1 w3-center w3-border-right"><b>Kaufdatum</b></div>
  <div class="w3-col l1 m1 s1 w3-center w3-border-right"><b>Kaufpreis</b></div>
  <div class="w3-col l3 m2 s2 w3-center w3-border-right"><b>ausgeliehen an</b></div>
</div>
</div>
<div id="Liste">
<?php
    $sql = sprintf(
        'SELECT `Index` FROM `%sInventories` INNER JOIN (SELECT `Index` AS `iIndex`, `Typ` AS `iTyp`, `Sortierung` AS `iSort` FROM `%sInventory`) `%sInventory` ON `Inventory` = `iIndex` ORDER BY `iSort`;',
        $GLOBALS['dbprefix'],
        $GLOBALS['dbprefix'],
        $GLOBALS['dbprefix']
    );
    $dbr = mysqli_query($conn, $sql);
    sqlerror();
    while($row = mysqli_fetch_array($dbr)) {
        $M = new Inventories;
        $M->load_by_id($row['Index']);
        echo $M->printTableLine();
    }
?>
</div>
<script src="js/filterInstruments.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script>
var nextRegByType = <?php echo json_encode(RegNumber::nextMapForInventoryTypes()); ?>;
var prefixByType = <?php
$map = array();
$sql = sprintf('SELECT `Index`, `Prefix` FROM `%sInventory`;', $GLOBALS['dbprefix']);
$dbr = mysqli_query($GLOBALS['conn'], $sql);
while($dbr && ($r = mysqli_fetch_array($dbr))) {
    $map[(int)$r['Index']] = $r['Prefix'];
}
echo json_encode($map);
?>;
var INSTR_PREFIX = <?php echo json_encode(RegNumber::DEFAULT_INSTR_PREFIX); ?>;
function updateNewRegNumber() {
  var sel = document.getElementById('newInventoryType');
  var inp = document.getElementById('newRegNumber');
  var prev = document.getElementById('regPreview');
  var famRow = document.getElementById('newInstrumentFamilyRow');
  if(!sel || !inp) return;
  var id = sel.value;
  if(nextRegByType[id]) inp.value = nextRegByType[id];
  var p = prefixByType[id] || 'X';
  if(prev) {
    if(String(p).toUpperCase() === INSTR_PREFIX) {
      prev.textContent = '(' + p + '-' + inp.value + ')';
    } else {
      prev.textContent = '(' + p + '-' + String(inp.value).padStart(3,'0') + ')';
    }
  }
  var famSel = document.getElementById('newInstrumentFamily');
  var isInstr = (String(p).toUpperCase() === INSTR_PREFIX);
  if(famRow) famRow.style.display = isInstr ? '' : 'none';
  if(famSel) famSel.disabled = !isInstr;
}
var sel = document.getElementById('newInventoryType');
if(sel) {
  sel.addEventListener('change', updateNewRegNumber);
  updateNewRegNumber();
}
</script>

<?php }
    else {
 ?>
<meta http-equiv="refresh" content="0; URL=index.php" />
<?php
    }

 include "common/footer.php";
?>
