<?php
session_start();
$_SESSION['page']='termine';
$_SESSION['adminpage']=false;
include "common/header.php";
if(isset($_POST['proxy'])) {
    $user = $_POST['proxy'];
    $proxy = new User;
    $proxy->load_by_id($user);
}
else {
    $user = $_SESSION['userid'];
}

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

    $m->load_by_user_event($user, $_POST['Index']);
    if($m->User < 1) {
        $m = new Meldung;
        $m->User = $user;
        $m->Termin = $_POST['Index'];
    }
    $m->Wert = $_POST['meldung'];
    $m->save();
}
?>
<script src="js/getStatus.js"></script>
<script src="js/melde.js"></script>
<script src="js/meldeshift.js"></script>
<script src="js/changeInstrument.js"></script>

<?php
if(isset($_POST['proxy'])) {
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorLogWarning']; ?>">
    <h2>Termin&uuml;bersicht <?php echo $proxy->getName();?></h2>
</div>
<?php
} else {
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
<h2>Termin&uuml;bersicht</h2>
</div>
<?php
}
$now = date("Y-m-d");
if($_SESSION['admin']) {
    $sql = sprintf('SELECT `Index` FROM `%sTermine` WHERE `Datum` >= "%s" ORDER BY `Datum`, `Uhrzeit`;',
    $GLOBALS['dbprefix'],
    $now
    );
}
else {
    $sql = sprintf('SELECT `Index` FROM `%sTermine` WHERE `published` = 1 AND `Datum` >= "%s" ORDER BY `Datum`, `Uhrzeit`;',
    $GLOBALS['dbprefix'],
    $now
    );
}
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
