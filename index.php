<?php
session_start();
$_SESSION['page']='home';
$_SESSION['adminpage']=false;
include "common/header.php";
?>
<script src="js/getStatus.js"></script>
<script src="js/melde.js"></script>
<script src="js/meldeshift.js"></script>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar'] ;?>">
<h2>Home</h2>
</div>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar'] ;?>">
<h3>Bevorstehende Termine</h3>
</div>
<?php
$now = date("Y-m-d");
if($GLOBALS['optionsDB']['entriesMainPage'] > 0) {
    $sql = sprintf('SELECT `Index` FROM `%sTermine` WHERE `Datum` >= "%s" AND `published` > 0 ORDER BY `Datum`, `Uhrzeit` LIMIT %s;',
    $GLOBALS['dbprefix'],
    $now,
    $GLOBALS['optionsDB']['entriesMainPage']
    );
}
else {
    $sql = sprintf('SELECT `Index` FROM `%sTermine` WHERE `Datum` >= "%s" AND `published` > 0 ORDER BY `Datum`, `Uhrzeit`;',
    $GLOBALS['dbprefix'],
    $now);
}
$dbr = mysqli_query($conn, $sql);
sqlerror();
while($row = mysqli_fetch_array($dbr)) {
    $M = new Termin;
    $M->load_by_id($row['Index']);
    echo $M->printBasicTableLine();
}

$more = new div;
$more->tag="a";
$more->class="w3-btn w3-padding w3-margin w3-mobile w3-border w3-border-black";
$more->class=$GLOBALS['optionsDB']['colorBtnSubmit'];
$more->href="termine.php";
$more->body="mehr Termine";
echo $more->print();
?>
<?php
include "common/footer.php";
?>
