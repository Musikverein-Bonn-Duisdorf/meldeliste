<?php
session_start();
$_SESSION['page']='tracking';
$_SESSION['adminpage']=true;
include "common/header.php";
$termin = new Termin;
$termin->load_by_id($_POST['termin']);
?>
<script src="js/getStatus.js"></script>
<script src="js/melde.js"></script>
<script src="js/meldeshift.js"></script>
<?php
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
         <h2>Anwesenheitsliste - <?php echo $termin->Name." ".($termin->Datum); ?></h2>
</div>
<?php
$now = date("Y-m-d");
$sql = sprintf('SELECT `Index` FROM `%sTermine` WHERE `Datum` >= "%s" ORDER BY `Datum`, `Uhrzeit`;',
$GLOBALS['dbprefix'],
$now
);

$dbr = mysqli_query($conn, $sql);
sqlerror();
while($row = mysqli_fetch_array($dbr)) {
    $M = new Termin;
    $M->load_by_id($row['Index']);
    $meldung = $M->getMeldungenByUser($user);
    if($M->published > 0) {        
        echo $M->printBasicTableLine();
    }
    elseif($_SESSION['admin']) {
        echo $M->printBasicTableLine();
    }
    elseif($meldung) {
        echo $M->printBasicTableLine();        
    }
}
?>
<?php
include "common/footer.php";
?>
