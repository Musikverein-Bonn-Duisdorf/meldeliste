<?php
ob_start();
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page']='inventories';
$_SESSION['adminpage']=true;

include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
requireLoggedInOrRedirect();

if(handleInventoriesMutations()) {
    redirectAfterPost('inventories.php');
}

include "common/header.php";

if(requirePermission("perm_showInventories")) {
    $sql = sprintf('SELECT COUNT(`Index`) AS `Count` FROM `%sInventories`;',
    $GLOBALS['dbprefix']
    );
    $dbr = mysqli_query($conn, $sql);
    sqlerror();
    $row = mysqli_fetch_array($dbr);
    $nInventories = $row['Count'];
?>
<?php
$filterInsured = isset($_GET['versichert']) && (string)$_GET['versichert'] === '1';
$exportBtn = '<a class="w3-button w3-border '.$GLOBALS['optionsDB']['colorBtnSubmit'].'" href="insuranceExport.php" target="_blank" rel="noopener">Übersicht für Versicherung</a>';
$plusBtn = requirePermission('perm_editInventories')
    ? '<a class="w3-button w3-border '.$GLOBALS['optionsDB']['colorInputBackground'].'" href="new-inventory.php" title="neues Inventar"><i class="fas fa-plus"></i></a>'
    : '';
$extraBtns = trim($exportBtn.' '.$plusBtn);
adminListPageBegin('Inventar', 'Inventar ('.$nInventories.')');
adminListSearchField('Nach Inventar suchen…', array(
    'onkeyup' => 'filterMusiker()',
    'extraHtml' => $extraBtns,
));
?>
<div id="listHeader" class="inv-sort-bar">
  <div class="inv-sort-bar-filters">
    <button type="button" id="filterInsured" class="inv-sort-chip inv-filter-chip<?php echo $filterInsured ? ' is-active' : ''; ?>" aria-pressed="<?php echo $filterInsured ? 'true' : 'false'; ?>">Versichert</button>
  </div>
  <div class="inv-sort-bar-sorts" role="toolbar" aria-label="Sortierung">
    <button type="button" class="inv-sort-chip list-sort" data-sort="regnumber" data-type="number">Nr.</button>
    <button type="button" class="inv-sort-chip list-sort" data-sort="typ" data-type="string">Typ</button>
    <button type="button" class="inv-sort-chip list-sort" data-sort="vendor" data-type="string">Hersteller</button>
    <button type="button" class="inv-sort-chip list-sort" data-sort="model" data-type="string">Modell</button>
    <button type="button" class="inv-sort-chip list-sort" data-sort="description" data-type="string">Beschreibung</button>
    <button type="button" class="inv-sort-chip list-sort" data-sort="comment" data-type="string">Kommentar</button>
    <button type="button" class="inv-sort-chip list-sort" data-sort="purchasedate" data-type="date">Kaufdatum</button>
    <button type="button" class="inv-sort-chip list-sort" data-sort="purchaseprize" data-type="number">Kaufpreis</button>
    <button type="button" class="inv-sort-chip list-sort" data-sort="loan" data-type="string">Ausleihe</button>
    <button type="button" class="inv-sort-chip list-sort" data-sort="owner" data-type="string">Eigentümer</button>
  </div>
</div>
<div id="Liste" class="inv-list">
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
<?php adminListPageEnd(); ?>
<script src="<?php echo assetUrl('js/filterInstruments.js'); ?>"></script>
<script src="<?php echo assetUrl('js/sortList.js'); ?>"></script>
<script>
bindListSort({ headerId: 'listHeader', listId: 'Liste', mode: 'client' });
(function () {
  var chip = document.getElementById('filterInsured');
  if (chip) {
    chip.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      toggleInsuredFilter();
    });
  }
  initInsuredFilterFromQuery();
})();
</script>

<?php }
    else {
        denyAccess();
    }

 include "common/footer.php";
?>
