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
	echo "newAppmnts...\n";
	if($GLOBALS['optionsDB']['cronSendnewAppmnts'] == 0) {
            echo "new Appmnts not activated.\n";
            break;
	}
	$datetime = new DateTime('today');
	$today = $datetime->format('Y-m-d');
	$sql = sprintf("SELECT * FROM `%sTermine` WHERE `new` = 1 AND `published` > 0 AND `Datum` >= '%s';",
		       $GLOBALS['dbprefix'],
		       $today);
	$dbr = mysqli_query($conn, $sql);
	sqlerror();
    if(!$dbr) break;
	$Appmnts = '';
	$i=0;
	while($row = mysqli_fetch_array($dbr)) {
            $n = new Termin;
            $n->load_by_id($row['Index']);
	    if($GLOBALS['optionsDB']['cronSendnewAppmntsInMail']) {
		$Appmnts = $Appmnts.$n->printMailLine()."\n";
	    }
            $n->setOld();
            $i++;
	}
	if($i) {
            $mail = new Usermail;
            $mail->subject("neue Termine");
            $mail->send($GLOBALS['optionsDB']['cronSendnewAppmntsText'].$Appmnts."\n\n".$GLOBALS['optionsDB']['MailGreetings']);
	}
	break;
    case "tomorrow":
	echo "tomorrow...\n";
	if($GLOBALS['optionsDB']['cronSendTomorrow'] == 0) {
            echo "Tomorrow not activated.\n";
            break;
	}
	$datetime = new DateTime('tomorrow');
	$tomorrow = $datetime->format('Y-m-d');

	$sql = sprintf("SELECT * FROM `%sTermine` WHERE `published` = 1 AND `Datum` = '%s';",
		       $GLOBALS['dbprefix'],
		       $tomorrow);
	$dbr = mysqli_query($conn, $sql);
	sqlerror();
    if(!$dbr) break;
	while($row = mysqli_fetch_array($dbr)) {
            $n = new Termin;
            $n->load_by_id($row['Index']);
            echo $n->printBasicTableLine()."\n";
	}
	break;
    case "reminder":
	echo "reminder...\n";
	if($GLOBALS['optionsDB']['cronSendReminder'] == 0) {
            echo "Reminder not activated.\n";
            break;
	}
	if(!checkCronDate($GLOBALS['optionsDB']['cronSendReminderDays'])) { 
            break;
	}
	$time = date("H:i");
	if($time != $GLOBALS['optionsDB']['cronSendReminderTime']) {
            echo "No reminder at ".$time.".\n";
            break;
	}
	$datetime = new DateTime('today');
	$today = $datetime->format('Y-m-d');
	$sql = sprintf("SELECT COUNT(`Index`) AS `cnt` FROM `%sTermine` WHERE `published` = 1 AND `Datum` >= '%s' AND `Shifts` = 0;",
		       $GLOBALS['dbprefix'],
		       $today
	);
	$dbr = mysqli_query($conn, $sql);
	sqlerror();
    if(!$dbr) break;
	$row = mysqli_fetch_array($dbr);
	$Nappmnts = $row['cnt'];

	
	$sql = sprintf("SELECT * FROM `%sUser` WHERE `getMail` = 1;",
		       $GLOBALS['dbprefix']
	);
	$dbr = mysqli_query($conn, $sql);
	sqlerror();
    if(!$dbr) break;
	while($user = mysqli_fetch_array($dbr)) {
        $u = new User;
        $u->load_by_id($user['Index']);
        if($u->getRegisterName() == 'keins') continue;
        $sql = sprintf("SELECT COUNT(`Index`) AS `cnt` FROM `%sMeldungen` INNER JOIN (SELECT `Index` AS `tIndex`, `Datum`, `published`, `Shifts` FROM `%sTermine`) `Termine` ON `Termin` = `tIndex` WHERE `published` = 1 AND `Datum` >= '%s' AND `User` = '%d' AND `Shifts` = 0;",
        $GLOBALS['dbprefix'],
        $GLOBALS['dbprefix'],
        $today,
        $user['Index']
        );
        $dbr2 = mysqli_query($conn, $sql);
        if(!$dbr2) continue;
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
            $mail->singleUser($u->Index, $GLOBALS['optionsDB']['SubjectReminder'], $body.$GLOBALS['optionsDB']['MailGreetings']);
        }
	}
	break;
    default:
	die("command invalid\n");
	break;
}
?>
