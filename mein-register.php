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
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
<h2>Mein Register</h2>
</div>
      <div class="w3-row">
               <div class="w3-panel w3-col l3 w3-left"></div>
                        <div class="w3-col l6 s12 m12">
<div class="w3-container w3-padding-16" style="clear:both;">
<input class="w3-input w3-border w3-padding" type="text" placeholder="Termine suchen (Titel, Ort, Datum, Beschreibung)…" id="filterString" onkeyup="filterTermine()">
</div>
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
</div>
<div class="w3-col l3"></div>
</div>
<script src="js/filterTermine.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<?php
include "common/footer.php";
?>
