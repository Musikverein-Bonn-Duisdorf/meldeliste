<?php
ob_start();
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page']='inventories';
$_SESSION['adminpage']=true;

include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
requireLoggedInOrRedirect();

if(($invMut = handleInventoriesMutations()) !== false) {
    if(isInventoriesAjaxRequest()) {
        respondInventoriesAjax($invMut);
    }
    if(isset($invMut['action']) && $invMut['action'] === 'newLoan' && !empty($invMut['loanId'])) {
        redirectAfterPost('loan-form.php?loan='.(int)$invMut['loanId'].'&kind=loan');
    }
    if(isset($invMut['action']) && $invMut['action'] === 'endLoan' && !empty($invMut['loanId'])) {
        redirectAfterPost('loan-form.php?loan='.(int)$invMut['loanId'].'&kind=return');
    }
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

    $chunk = listChunkInventories(0, 50);
?>
<?php
$filterInsured = isset($_GET['versichert']) && (string)$_GET['versichert'] === '1';
$exportBtn = '<a class="w3-button w3-border '.$GLOBALS['optionsDB']['colorBtnSubmit'].'" href="insuranceExport.php" target="_blank" rel="noopener">Übersicht für Versicherung</a>';
$plusBtn = requirePermission('perm_editInventories')
    ? '<a class="w3-button w3-border '.$GLOBALS['optionsDB']['colorInputBackground'].'" href="new-inventory.php" title="neues Inventar"><i class="fas fa-plus"></i></a>'
    : '';
$extraBtns = trim($exportBtn.' '.$plusBtn);
adminListPageBegin('Inventar', 'Inventar', array('listCount' => (int)$nInventories));
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
<?php echo $chunk['html']; ?>
<?php echo listChunkRenderSentinel('inventories', $chunk['nextCursor'], $chunk['hasMore'], 'filterMusiker'); ?>
</div>
<?php adminListPageEnd(); ?>
<script src="<?php echo assetUrl('js/filterInstruments.js'); ?>"></script>
<script src="<?php echo assetUrl('js/sortList.js'); ?>"></script>
<script src="<?php echo assetUrl('js/infiniteScroll.js'); ?>"></script>
<script>
bindListSort({ headerId: 'listHeader', listId: 'Liste', mode: 'server' });
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
