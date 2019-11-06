<?php
session_start();
include 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
if(!isset($_GET['id'])) {
    die('no ID specified');
}
if($_GET['id'] != $GLOBALS['cronID']) {
    die('invalid ID');
}
switch($_GET['cmd']) {
case "save":
    $m = new Shiftmeldung;
    $m->load_by_user_event($_GET['user'], $_GET['shift']);
    if($m->User < 1) {
        $m->User = $_GET['user'];
        $m->Shift = $_GET['shift'];
    }
    $m->Wert = $_GET['wert'];
    $m->save();
    $t = new Termin;
    $t->load_by_id($_GET['termin']);
    echo $t->printBasicTableLine();
    break;
default:
    break;
}
?>