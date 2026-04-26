<?php
session_start();
$_SESSION['page']='myinventories';
$_SESSION['adminpage']=false;
include "common/header.php";
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2>Mein Inventar</h2>
</div>

<div class="w3-row w3-padding w3-border-bottom w3-border-black w3-hide-small w3-hide-medium">
  <div class="w3-col l1 m1 s1 w3-center w3-border-right"><b>Inventarnummer</b></div>
  <div class="w3-col l2 m2 s2 w3-center w3-border-right"><b>Typ</b></div>
<?php if(requirePermission("perm_showInventories")) { ?>
  <div class="w3-col l3 m4 s4 w3-center w3-border-right"><b>Beschreibung</b></div>
  <div class="w3-col l1 m1 s1 w3-center w3-border-right"><b>Kaufdatum</b></div>
  <div class="w3-col l1 m1 s1 w3-center w3-border-right"><b>Kaufpreis</b></div>
<?php } else { ?>
  <div class="w3-col l5 m4 s4 w3-center w3-border-right"><b>Beschreibung</b></div>    
<?php } ?>
  <div class="w3-col l3 m2 s2 w3-center w3-border-right"><b>ausgeliehen an</b></div>
</div>
<?php
$u = new User;
$u->load_by_id($_SESSION['userid']);
$loans = $u->getInventoriesLoans();
if(count($loans)) {
    for($i=0; $i < count($loans); $i++) {
        $loan = new InventoriesLoan;
        $loan->load_by_id($loans[$i]);
        $inventory = new Inventories;
        $inventory->load_by_id($loan->Inventory);
        echo $inventory->printTableLine();
    }
}

include "common/footer.php";
?>
