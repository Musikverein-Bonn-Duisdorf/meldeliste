<?php
session_start();
include 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
if(!isset($_GET['id'])) {
    die('invalid');
}
if($_GET['id'] == $GLOBALS['cronID']) {
    $m = new Meldung;
    $m->load_by_user_event($_GET['user'], $_GET['termin']);
    if($m->User < 1) {
        $m = new Meldung;
        $m->User = $_GET['user'];
        $m->Termin = $_GET['termin'];
    }
    $m->Wert = $_GET['wert'];
    $m->save();
    $t = new Termin;
    $t->load_by_id($_GET['termin']);
    echo $t->printBasicTableLine();
}
?>