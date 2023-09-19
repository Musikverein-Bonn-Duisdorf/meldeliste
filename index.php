<?php
session_start();
$_SESSION['page']='home';
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
if(isset($_POST['insertAushilfe'])) {
        $aushilfe = new Aushilfe;
        $aushilfe->fill_from_array($_POST);
        $aushilfe->save();
}
?>
<script src="js/getStatus.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script src="js/melde.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script src="js/changeInstrument.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script src="js/meldeshift.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar'] ;?>">
    <h2>Bevorstehende Termine</h2>
</div>
<?php
$now = date("Y-m-d");
if($GLOBALS['optionsDB']['entriesMainPage'] > 0) {
    $sql = sprintf('SELECT `Index` FROM `%sTermine` WHERE `Datum` >= "%s" ORDER BY `Datum`, `Uhrzeit` LIMIT %s;',
		   $GLOBALS['dbprefix'],
		   $now,
		   $GLOBALS['optionsDB']['entriesMainPage']
    );
}
else {
    $sql = sprintf('SELECT `Index` FROM `%sTermine` WHERE `Datum` >= "%s" ORDER BY `Datum`, `Uhrzeit`;',
		   $GLOBALS['dbprefix'],
		   $now,
    );
}
$dbr = mysqli_query($conn, $sql);
sqlerror();
while($row = mysqli_fetch_array($dbr)) {
    $M = new Termin;
    $M->load_by_id($row['Index']);
    $meldung = $M->getMeldungenByUser($user);
    if($M->published > 0) {        
        echo $M->printBasicTableLine();
    }
    elseif(requirePermission("perm_showHiddenAppmnts")) {
        echo $M->printBasicTableLine();
    }
    elseif($meldung) {
        echo $M->printBasicTableLine();        
    }
}
if($GLOBALS['optionsDB']['showAppmntPage']) {
    $more = new div;
    $more->tag="a";
    $more->class="w3-btn w3-hide-large w3-mobile w3-border w3-border-black w3-margin-bottom";
    $more->class=$GLOBALS['optionsDB']['colorBtnSubmit'];
    $more->href="termine.php";
    $more->body="mehr Termine";
    echo $more->print();
    $moreL = new div;
    $moreL->tag="a";
    $moreL->class="w3-btn w3-hide-small w3-hide-medium w3-margin-left w3-margin-bottom w3-padding w3-border w3-border-black";
    $moreL->class=$GLOBALS['optionsDB']['colorBtnSubmit'];
    $moreL->href="termine.php";
    $moreL->body="mehr Termine";
    echo $moreL->print();
}
?>
<?php
include "common/footer.php";
?>
