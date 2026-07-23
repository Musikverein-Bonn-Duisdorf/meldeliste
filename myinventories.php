<?php
ob_start();
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page']='myinventories';
$_SESSION['adminpage']=false;

include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
requireLoggedInOrRedirect();

if(handleInventoriesMutations()) {
    redirectAfterPost('myinventories.php');
}

include "common/header.php";
adminListPageBegin('Inventar', 'Mein Inventar');
?>
<div class="w3-row w3-padding w3-border-bottom w3-border-black w3-hide-small w3-hide-medium">
  <div class="w3-col l1 m1 s1 w3-center w3-border-right"><b>Inventarnummer</b></div>
  <div class="w3-col l2 m2 s2 w3-center w3-border-right"><b>Typ</b></div>
<?php if(requirePermission("perm_showInventories")) { ?>
  <div class="w3-col l2 m4 s4 w3-center w3-border-right"><b>Beschreibung</b></div>
  <div class="w3-col l2 m4 s4 w3-center w3-border-right"><b>Kommentar</b></div>    
  <div class="w3-col l1 m1 s1 w3-center w3-border-right"><b>Kaufdatum</b></div>
  <div class="w3-col l1 m1 s1 w3-center w3-border-right"><b>Kaufpreis</b></div>
<?php } else { ?>
  <div class="w3-col l4 m4 s4 w3-center w3-border-right"><b>Beschreibung</b></div>    
  <div class="w3-col l2 m4 s4 w3-center w3-border-right"><b>Kommentar</b></div>    
<?php } ?>
  <div class="w3-col l3 m2 s2 w3-center w3-border-right"><b>ausgeliehen an</b></div>
</div>
<?php
$u = new User;
$u->load_by_id($_SESSION['userid']);
$shown = array();

$owned = $u->getInventories();
for($i=0; $i < count($owned); $i++) {
    $id = (int)$owned[$i];
    if(isset($shown[$id])) continue;
    $shown[$id] = true;
    $inventory = new Inventories;
    $inventory->load_by_id($id);
    echo $inventory->printTableLine(false);
}

$loans = $u->getInventoriesLoans();
for($i=0; $i < count($loans); $i++) {
    $loan = new InventoriesLoan;
    $loan->load_by_id($loans[$i]);
    $id = (int)$loan->Inventory;
    if(isset($shown[$id])) continue;
    $shown[$id] = true;
    $inventory = new Inventories;
    $inventory->load_by_id($id);
    echo $inventory->printTableLine(false);
}

adminListPageEnd();
include "common/footer.php";
?>
