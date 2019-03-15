<?php
session_start();
$_SESSION['page']='termine';
include "common/header.php";

if(isset($_POST['insert'])) {
    $n = new Termin;
    $n->fill_from_array($_POST);
    $n->save();
}
if(isset($_POST['meldung'])) {
    $m = new Meldung;
    $m->load_by_user_event($_SESSION['userid'], $_POST['Index']);
    if($m->User < 1) {
        $m = new Meldung;
        $m->User = $_SESSION['userid'];
        $m->Termin = $_POST['Index'];
    }
    $m->Wert = $_POST['meldung'];
    $m->save();
}
?>
<div class="w3-container w3-dark-gray">
<h2>Termin&uuml;bersicht</h2>
</div>
<?php
$now = date("Y-m-d");
$sql = sprintf('SELECT `Index` FROM `MVD`.`Termine` WHERE `Datum` > "%s" ORDER BY `Datum`, `Uhrzeit`;',
$now
);
$dbr = mysqli_query($conn, $sql);
while($row = mysqli_fetch_array($dbr)) {
    $M = new Termin;
    $M->load_by_id($row['Index']);
    $M->printBasicTableLine();
}
?>
<?php
include "common/footer.php";
?>