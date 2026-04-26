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
  <div class="w3-col l1 m2 s2 w3-center w3-border-right"><b>Typ</b></div>
  <div class="w3-col l1 m1 s1 w3-center w3-border-right"><b>Beschreibung</b></div>
  <div class="w3-col l2 m2 s2 w3-center w3-border-right"><b>ausgeliehen seit</b></div>
</div>
<?php
      $sql = sprintf('SELECT `Index` FROM `%sInventories` INNER JOIN (SELECT `Index` AS `iIndex`, `Typ` AS `iTyp`, `Sortierung` AS `iSort` FROM `%sInventory`) `%sInventory` ON `Inventory` = `iIndex` WHERE `Owner` = %d ORDER BY `iSort`;',
      $GLOBALS['dbprefix'],
      $GLOBALS['dbprefix'],
      $GLOBALS['dbprefix'],
      $_SESSION['userid']
      );
$dbr = mysqli_query($conn, $sql);
sqlerror();
while($row = mysqli_fetch_array($dbr)) {
    $M = new Instruments;
    $M->load_by_id($row['Index']);
    echo $M->printTableLine();
}

$u = new User;
$u->load_by_id($_SESSION['userid']);
$loans = $u->getInventoriesLoans();
for($i=0; $i < count($loans); $i++) {
    $loan = new Loan;
    $loan->load_by_id($loans[$i]);
    $inventory = new Inventories;
    $inventory->load_by_id($loan->Inventory);
    echo $inventory->printTableLine();
}

include "common/footer.php";
?>
