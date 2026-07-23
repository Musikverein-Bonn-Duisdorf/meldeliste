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

$u = new User;
$u->load_by_id($_SESSION['userid']);
if(!$u->hasInventories()) {
    header('Location: '.$GLOBALS['optionsDB']['WebSiteURL']);
    exit;
}

include "common/header.php";
adminListPageBegin('Inventar', 'Mein Inventar');

$shown = array();
$html = '';

// Zuerst aktive Leihen (typisch Vereinsinventar), dann Eigentum
$loans = $u->getInventoriesLoans();
for($i=0; $i < count($loans); $i++) {
    $loan = new InventoriesLoan;
    $loan->load_by_id($loans[$i]);
    $id = (int)$loan->Inventory;
    if($id < 1 || isset($shown[$id])) continue;
    $shown[$id] = true;
    $inventory = new Inventories;
    $inventory->load_by_id($id);
    $html .= $inventory->printTableLine(false);
}

$owned = $u->getInventories();
for($i=0; $i < count($owned); $i++) {
    $id = (int)$owned[$i];
    if(isset($shown[$id])) continue;
    $shown[$id] = true;
    $inventory = new Inventories;
    $inventory->load_by_id($id);
    $html .= $inventory->printTableLine(false);
}
?>
<div class="inv-list" id="Liste">
<?php
if($html === '') {
    echo '<p class="inv-list-empty">Kein Inventar zugeordnet oder ausgeliehen.</p>';
}
else {
    echo $html;
}
?>
</div>
<?php
adminListPageEnd();
include "common/footer.php";
?>
