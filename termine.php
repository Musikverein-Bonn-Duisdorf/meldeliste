<?php
session_start();
$_SESSION['page']='termine';
include "common/header.php";

if(isset($_POST['insert'])) {
    $n = new Termin;
    $n->fill_from_array($_POST);
    $n->save();
}
if(isset($_POST['delete'])) {
    $n = new Termin;
    $n->fill_from_array($_POST);
    $n->delete();
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
<div class="w3-container <?php echo $GLOBALS['commonColors']['titlebar']; ?>">
<h2>Termin&uuml;bersicht</h2>
</div>
<?php
$now = date("Y-m-d");
if($_SESSION['admin']) {
    $sql = sprintf('SELECT `Index` FROM `Termine` WHERE `Datum` >= "%s" ORDER BY `Datum`, `Uhrzeit`;',
    $now
    );
}
else {
    $sql = sprintf('SELECT `Index` FROM `Termine` WHERE `Datum` >= "%s" ORDER BY `Datum`, `Uhrzeit` WHERE `published` = 1;',
    $now
    );
}
$dbr = mysqli_query($conn, $sql);
while($row = mysqli_fetch_array($dbr)) {
    $M = new Termin;
    $M->load_by_id($row['Index']);
    echo $M->printBasicTableLine();
}
?>
<?php
include "common/footer.php";
?>
