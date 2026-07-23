<?php
ob_start();
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page'] = 'newinventory';
$_SESSION['adminpage'] = true;

include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
requireLoggedInOrRedirect();

if(handleInventoriesMutations()) {
    redirectAfterPost('inventories.php');
}

include 'common/header.php';

if(!requirePermission('perm_editInventories')) {
    denyAccess();
}

$inputBg = $GLOBALS['optionsDB']['colorInputBackground'];
$btnSubmit = $GLOBALS['optionsDB']['colorBtnSubmit'];
$backLink = '<a class="w3-button w3-border '.htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8').'" href="inventories.php">Zur Liste</a>';
?>
<div class="w3-container w3-margin-bottom termin-page">
<div class="profile-shell termin-shell">
<form class="profile-form" action="new-inventory.php" method="POST">

  <header class="<?php echo htmlspecialchars(adminHeroClass(array('kicker' => 'Inventar', 'permKey' => 'perm_editInventories')), ENT_QUOTES, 'UTF-8'); ?>">
    <div class="profile-hero-text">
      <p class="profile-kicker">Inventar</p>
      <h2 class="profile-title">Neues Inventar</h2>
    </div>
    <div class="profile-hero-actions">
      <div class="profile-actions">
        <div class="profile-actions-primary">
          <input class="w3-btn profile-btn-primary <?php echo htmlspecialchars($btnSubmit, ENT_QUOTES, 'UTF-8'); ?> w3-border w3-mobile" type="submit" name="insert" value="Speichern">
          <?php echo $backLink; ?>
        </div>
      </div>
    </div>
  </header>

  <div class="termin-grid">
    <section class="profile-col" aria-labelledby="inv-col-typ">
      <h3 id="inv-col-typ" class="profile-col-title">Typ &amp; Nummer</h3>
      <div class="profile-field">
        <label class="profile-label" for="newInventoryType">Inventar</label>
        <select id="newInventoryType" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" name="Inventory">
          <?php echo inventoryOptionAll(0); ?>
        </select>
      </div>
      <div class="profile-field">
        <label class="profile-label" for="newRegNumber">Inventarnummer <span class="w3-small" id="regPreview"></span></label>
        <input id="newRegNumber" name="RegNumber" type="number" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo (int)getNextRegInventoryNumber(); ?>">
      </div>
      <div id="newInstrumentFamilyRow" class="profile-field" style="display:none;">
        <label class="profile-label" for="newInstrumentFamily">Instrument</label>
        <select id="newInstrumentFamily" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" name="Instrument" disabled>
          <?php echo instrumentOptionAll(0); ?>
        </select>
      </div>
      <div class="profile-field">
        <label class="profile-label" for="newDescription">Beschreibung</label>
        <input id="newDescription" name="Description" type="text" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>">
      </div>
    </section>

    <section class="profile-col" aria-labelledby="inv-col-produkt">
      <h3 id="inv-col-produkt" class="profile-col-title">Produkt</h3>
      <div class="profile-field">
        <label class="profile-label" for="newVendor">Hersteller</label>
        <input id="newVendor" name="Vendor" type="text" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>">
      </div>
      <div class="profile-field">
        <label class="profile-label" for="newModel">Modell</label>
        <input id="newModel" name="Model" type="text" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>">
      </div>
      <div class="profile-field">
        <label class="profile-label" for="newSerialNr">Seriennummer</label>
        <input id="newSerialNr" name="SerialNr" type="text" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>">
      </div>
    </section>

    <section class="profile-col" aria-labelledby="inv-col-meta">
      <h3 id="inv-col-meta" class="profile-col-title">Kauf &amp; Besitz</h3>
      <div class="profile-field">
        <label class="profile-label" for="newPurchaseDate">Kaufdatum</label>
        <input id="newPurchaseDate" name="PurchaseDate" type="date" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>">
      </div>
      <div class="profile-field">
        <label class="profile-label" for="newPurchasePrize">Kaufpreis</label>
        <input id="newPurchasePrize" name="PurchasePrize" step="0.01" value="0.00" min="0" type="number" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>">
      </div>
      <div class="profile-field">
        <label class="profile-label" for="newOwner">Eigentümer</label>
        <select id="newOwner" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" name="Owner">
          <?php echo UserOptionAll(0); ?>
        </select>
      </div>
      <div class="profile-field">
        <label class="profile-label" for="newComment">Kommentar</label>
        <input id="newComment" name="Comment" type="text" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>">
      </div>
      <div class="profile-field">
        <span class="profile-label">Versichert</span>
        <label class="profile-pref">
          <input type="hidden" name="Insurance" value="0">
          <input id="newInsurance" name="Insurance" value="1" type="checkbox" class="w3-check">
          <span>ja</span>
        </label>
      </div>
    </section>
  </div>
</form>
</div>
</div>

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
      prev.textContent = '(' + p + '-' + String(inp.value).padStart(3, '0') + ')';
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
<?php
include 'common/footer.php';
?>
