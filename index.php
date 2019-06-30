<?php
session_start();
$_SESSION['page']='home';
include "common/header.php";
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
<div class="w3-container <?php echo $GLOBALS['commonColors']['titlebar'] ;?>">
<h2>Home</h2>
</div>
<div class="w3-container <?php echo $GLOBALS['commonColors']['titlebar'] ;?>">
<h3>Bevorstehende Termine</h3>
</div>
<?php
$now = date("Y-m-d");
$sql = sprintf('SELECT `Index` FROM `%sTermine` WHERE `Datum` >= "%s" AND `published` > 0 ORDER BY `Datum`, `Uhrzeit` LIMIT 5;',
$GLOBALS['dbprefix'],
$now
);
$dbr = mysqli_query($conn, $sql);
sqlerror();
while($row = mysqli_fetch_array($dbr)) {
    $M = new Termin;
    $M->load_by_id($row['Index']);
    echo $M->printBasicTableLine();
}
?>
<?php
include "common/footer.php";
?>
