<?php
echo "\n";
echo date("Y-m-d H:i:s")."\n";

session_start();
include 'common/include.php';
if(!isset($_GET['id'])) die("no ID specified\n");
if(!$_GET['id']==$GLOBALS['cronID']) die("ID invalid\n");
if(!isset($_GET['cmd'])) die("no command specified\n");
mkAdmin();
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($conn));

switch($_GET['cmd']) {
case "newAppmnts":
    $datetime = new DateTime('today');
    $today = $datetime->format('Y-m-d');
    $sql = sprintf("SELECT * FROM `Termine` WHERE `new` = 1 AND `Datum` >= '%s';", $today);
    $dbr = mysqli_query($conn, $sql);
    $text = "bitte ";
    $Appmnts = '';
    $i=0;
    while($row = mysqli_fetch_array($dbr)) {
        $n = new Termin;
        $n->load_by_id($row['Index']);
        $Appmnts = $Appmnts.$n->printMailLine()."\n";
        $n->setOld();
        $i++;
    }
    if($i>1)
        $text = $text."folgende Termine vormerken.\n";
    else {
        $text = $text."folgenden Termin vormerken.\n";
    }
    if($i) {
        $mail = new Usermail;
        $mail->subject("neue Termine");
        $mail->send($text.$Appmnts."\n".$GLOBALS['commonStrings']['EmailGreeting']);
    }
    break;
case "tomorrow":
    $datetime = new DateTime('tomorrow');
    $tomorrow = $datetime->format('Y-m-d');

    $sql = sprintf("SELECT * FROM `Termine` WHERE `published` = 1 AND `Datum` = '%s';", $tomorrow);
    $dbr = mysqli_query($conn, $sql);
    while($row = mysqli_fetch_array($dbr)) {
        $n = new Termin;
        $n->load_by_id($row['Index']);
        echo $n->printBasicTableLine()."\n";
    }
    break;
default:
    die("command invalid\n");
    break;
}
?>