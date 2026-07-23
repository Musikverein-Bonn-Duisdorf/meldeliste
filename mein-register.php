<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page']='meinregister';
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
adminListPageBegin('Register', 'Mein Register');
adminListSearchField('Termine suchen (Titel, Ort, Datum, Beschreibung)…', array(
    'onkeyup' => 'filterTermine()',
));
?>
<div id="Liste">
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
    if(!$M->isVisibleToUser((int)$user)) {
        continue;
    }
    echo $M->printMyResponseLine();
}
?>
</div>
<?php adminListPageEnd(); ?>
<script src="js/filterTermine.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<?php
include "common/footer.php";
?>
