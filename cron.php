<?php
echo date("Y-m-d H:i:s")."<br />\n";

session_start();
include 'common/include.php';
if(!isset($_GET['id'])) die("no ID specified\n");
if(!$_GET['id']==$GLOBALS['cronID']) die("ID invalid\n");
if(!isset($_GET['cmd'])) die("no command specified\n");
mkAdmin();

switch($_GET['cmd']) {
case "newAppmnts":
    $datetime = new DateTime('today');
    $today = $datetime->format('Y-m-d');
    $sql = sprintf("SELECT * FROM `%sTermine` WHERE `new` = 1 AND `Datum` >= '%s';",
    $GLOBALS['dbprefix'],
    $today);
    $dbr = mysqli_query($conn, $sql);
    sqlerror();
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
        $mail->send($text.$Appmnts."\n".$GLOBALS['commonStrings']['MailGreetings']);
    }
    break;
case "tomorrow":
    $datetime = new DateTime('tomorrow');
    $tomorrow = $datetime->format('Y-m-d');

    $sql = sprintf("SELECT * FROM `%sTermine` WHERE `published` = 1 AND `Datum` = '%s';",
    $GLOBALS['dbprefix'],
    $tomorrow);
    $dbr = mysqli_query($conn, $sql);
    sqlerror();
    while($row = mysqli_fetch_array($dbr)) {
        $n = new Termin;
        $n->load_by_id($row['Index']);
        echo $n->printBasicTableLine()."\n";
    }
    break;
case "reminder":
    echo "reminder...\n";
    if($GLOBALS['optionsDB']['cronSendReminder'] == 0) {#
        echo "Reminder not activated.\n";
        break;
    }
    $v = $GLOBALS['optionsDB']['cronSendReminderDays'];
    $c=array(false, false, false, false, false, false, false);
    for($i=7; $i>=1; $i--) {
        if($v/2**($i-1)>=1) {
            $c[$i-1]=true;
            $v=$v-2**($i-1);
        }
    }
    $dow = intval(date("N"));
    if($c[$dow-1] == false) { 
        echo "No reminder today ".$dow.".\n";
        break;
    }
    $time = date("H:i");
    if($time != $GLOBALS['optionsDB']['cronSendReminderTime']) {
        echo "No reminder at ".$time.".\n";
        break;
    }
    $datetime = new DateTime('today');
    $today = $datetime->format('Y-m-d');
    $sql = sprintf("SELECT COUNT(`Index`) AS `cnt` FROM `%sTermine` WHERE `published` = 1 AND `Datum` >= '%s';",
    $GLOBALS['dbprefix'],
    $today
    );
    $dbr = mysqli_query($conn, $sql);
    sqlerror();
    $row = mysqli_fetch_array($dbr);
    $Nappmnts = $row['cnt'];

    
    $sql = sprintf("SELECT * FROM `%sUser` WHERE `getMail` = 1;",
    $GLOBALS['dbprefix']
    );
    $dbr = mysqli_query($conn, $sql);
    sqlerror();
    while($user = mysqli_fetch_array($dbr)) {
        $u = new User;
        $u->load_by_id($user['Index']);
        if($u->getRegisterName() == 'keins') continue;
        $sql = sprintf("SELECT COUNT(`Index`) AS `cnt` FROM `%sMeldungen` INNER JOIN (SELECT `Index` AS `tIndex`, `Datum`, `published` FROM `%sTermine`) `Termine` ON `Termin` = `tIndex` WHERE `published` = 1 AND `Datum` >= '%s' AND `User` = '%d';",
        $GLOBALS['dbprefix'],
        $GLOBALS['dbprefix'],
        $today,
        $user['Index']
        );
        $dbr2 = mysqli_query($conn, $sql);
        sqlerror();
        $row2 = mysqli_fetch_array($dbr2);
        $missing = $Nappmnts - $row2['cnt'];
        echo $u->getName()." (".$u->getRegisterName().") ".$row2['cnt']."/".$Nappmnts.", missing: ".$missing."<br />\n";
        if($missing > 0) {
            $mail = new Usermail;
            if($missing == 1) {
                $body = "Es fehlt noch eine R&uuml;ckmeldung von dir.\n\n";
            }
            else {
                $body = "Es fehlen noch ".$missing." R&uuml;ckmeldungen von dir.\n\n";
            }
            $mail->singleUser($u->Index, $GLOBALS['commonStrings']['MailReminderSubject'], $GLOBALS['commonStrings']['MailReminderBody']."\n".$body.$GLOBALS['commonStrings']['MailGreetings']);
        }
    }
    break;
default:
    die("command invalid\n");
    break;
}
?>