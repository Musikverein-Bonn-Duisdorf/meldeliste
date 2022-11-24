<?php
session_start();
$_SESSION['page']='instruments';
$_SESSION['adminpage']=true;
include "common/header.php";

/* if(isset($_POST['insert'])) { */
/*     $n = new User; */
/*     $n->load_by_id($_POST['Index']); */
/*     $n->fill_from_array($_POST); */
/*     $n->save(); */
/*     if(isset($_POST['pw1']) && isset($_POST['pw2'])) { */
/*         if($_POST['pw1'] == $_POST['pw2'] && $_POST['pw1'] != '') { */
/*             $n->passwd($_POST['pw1']); */
/*         } */
/*     } */
/* } */
/* if(isset($_POST['delete'])) { */
/*     $n = new User; */
/*     $n->load_by_id($_POST['Index']); */
/*     $n->delete(); */
/* } */
/* if(isset($_POST['passwd'])) { */
/*     $n = new User; */
/*     $n->load_by_id($_POST['Index']); */
/*     $n->fill_from_array($_POST); */
/*     if($_POST['Index'] > 0) { */
/*         $n->passwd(""); */
/*     } */
/* } */
/* if(isset($_POST['newmail'])) { */
/*     $n = new User; */
/*     $n->load_by_id($_POST['Index']); */
/*     $n->fill_from_array($_POST); */
/*     if($_POST['Index'] > 0) { */
/*         $n->newmail(""); */
/*     } */
/* } */
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

<div>
<input class="w3-input w3-border w3-padding" type="text" placeholder="Nach Instrument suchen..." id="filterString" onkeyup="filterMusiker()">
</div>
<div id="Liste">
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
</div>
<script src="js/filterInstruments.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>

<?php }
else {
?>
    <meta http-equiv="refresh" content="0; URL=index.php" />
<?php
}

include "common/footer.php";
?>
