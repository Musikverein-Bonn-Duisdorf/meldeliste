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
    $m = new Meldung;
    $m->load_by_user_event($_GET['user'], $_GET['termin']);
    if($m->User < 1) {
        $m = new Meldung;
        $m->User = $_GET['user'];
        $m->Termin = $_GET['termin'];
    }
    $m->Wert = $_GET['wert'];
    $m->Children = $_GET['Children'];
    $m->Guests = $_GET['Guests'];
    $m->save();
    $t = new Termin;
    $t->load_by_id($_GET['termin']);
    echo $t->printBasicTableLine();
    break;
case "instrument":
    $m = new Meldung;
    $m->load_by_user_event($_GET['user'], $_GET['termin']);
    if($m->User < 1) {
        break;
    }
    $m->Instrument = $_GET['instrument'];
    $m->save();
    break;
case "status":
    setlocale (LC_ALL, 'de_DE.UTF8');
    $today = strftime('%A, %d. %B %Y um %H:%M Uhr');

    $t = new Termin;
    if($_GET['termin'] < 1) break;
    $t->load_by_id($_GET["termin"]);
    $body = "dies ist der Meldestand f&uuml;r ".$today.".\n";
    $body = $body.$t->printMailResponse();
    $mail = new Usermail;
    $mail->singleUser($_GET['user'], $t->Name, $body);
    break;
case "freetext":
    $m = new AppmntFreeTextResponse;
    $m->load_by_user_event($_GET['user'], $_GET['termin']);
    $m->Text = $_GET['freeText'];
    $m->save();
    break;
default:
    break;
}
?>
