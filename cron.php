<?php
// CLI: php cron.php <cronId> <cmd>
if(PHP_SAPI === 'cli') {
    global $argv;
    if(!isset($argv[1], $argv[2])) {
        fwrite(STDERR, "Usage: php cron.php <cronId> <cmd>\n");
        exit(1);
    }
    $_GET['id'] = (string)$argv[1];
    $_GET['cmd'] = (string)$argv[2];
}

require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
include 'common/include.php';

if(!isset($_GET['id'])) {
    die("no ID specified\n");
}
if($_GET['id'] != $GLOBALS['cronID']) {
    die("ID invalid\n");
}
if(!isset($_GET['cmd'])) {
    die("no command specified\n");
}

$cmd = (string)$_GET['cmd'];

// Binary download: CLI only (HTTP backup via backup.php)
if($cmd === 'backup') {
    if(PHP_SAPI !== 'cli') {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        die("Backup via HTTP disabled; use backup.php or CLI: php cron.php <cronId> backup\n");
    }
    require_once __DIR__.'/libs/backup.php';
    mkAdmin();
    try {
        sendBackupDownload();
    }
    catch(Throwable $e) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Backup failed: ".$e->getMessage()."\n";
        exit(1);
    }
}

if(PHP_SAPI === 'cli') {
    echo date("Y-m-d H:i:s")."\n";
}
else {
    echo date("Y-m-d H:i:s")."<br />\n";
}
mkAdmin();

switch($cmd) {
case "newAppmnts":
    echo "newAppmnts...\n";
    if($GLOBALS['optionsDB']['cronSendnewAppmnts'] == 0) {
        echo "new Appmnts not activated.\n";
        break;
    }
    if(!checkCronDate($GLOBALS['optionsDB']['cronSendnewAppmntsDays'])) {
        echo "No sendnewAppmnts today\n";
        break;
    }
    $time = date("H:i");
    if($time != $GLOBALS['optionsDB']['cronSendnewAppmntsTime']) {
        echo "No sendnewAppmnts at ".$time.".\n";
        break;
    }
    $datetime = new DateTime('today');
    $today = $datetime->format('Y-m-d');
    $sql = sprintf("SELECT * FROM `%sTermine` WHERE `new` = 1 AND %s AND `Datum` >= '%s';",
    $GLOBALS['dbprefix'],
    Termin::sqlIsListed(),
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
    echo $i." new Appointments identified\n";
	if($i) {
        $mail = new Usermail;
        $mail->source = 'cron_new';
        $mail->quiet = true;
        $mail->subject("neue Termine");
        $mail->send($GLOBALS['optionsDB']['cronSendnewAppmntsText'].$Appmnts."\n\n".$GLOBALS['optionsDB']['MailGreetings']);
        echo $i." appointments → mail queue enqueued\n";
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

	$sql = sprintf("SELECT * FROM `%sTermine` WHERE %s AND `Datum` = '%s';",
    $GLOBALS['dbprefix'],
    Termin::sqlIsListed(),
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
case "calendar":
	echo "calendar...\n";
    rebuildCalendars();
	echo "done...\n";
    break;
case "processMailQueue":
	echo "processMailQueue...\n";
	MailJob::ensureSchema();
	$result = Usermail::processQueue();
	if(!empty($result['skipped'])) {
		echo "skipped=1 (another worker active)\n";
		break;
	}
	echo "batchSize=".$result['batchSize']
		." processed=".$result['processed']
		." sent=".$result['sent']
		." failed=".$result['failed']
		." reclaimed=".$result['reclaimed']
		."\n";
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
	$sql = sprintf("SELECT * FROM `%sTermine` WHERE %s AND `Datum` >= '%s' AND `Shifts` = 0;",
    $GLOBALS['dbprefix'],
    Termin::sqlIsListed(),
    $today
    );
	$dbr = mysqli_query($conn, $sql);
	sqlerror();
    if(!$dbr) break;
	$publishedTermines = array();
	while($row = mysqli_fetch_array($dbr)) {
        $t = new Termin;
        $t->load_by_id($row['Index']);
        $publishedTermines[] = $t;
	}

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
        $visibleIds = array();
        foreach($publishedTermines as $t) {
            if($t->isVisibleToUser((int)$u->Index, array('asViewer' => false))) {
                $visibleIds[] = (int)$t->Index;
            }
        }
        $Nappmnts = count($visibleIds);
        if($Nappmnts <= 0) {
            continue;
        }
        $meldungenCount = 0;
        if(count($visibleIds)) {
            $sql = sprintf(
                "SELECT COUNT(`Index`) AS `cnt` FROM `%sMeldungen` WHERE `User` = %d AND `Termin` IN (%s);",
                $GLOBALS['dbprefix'],
                (int)$u->Index,
                implode(',', $visibleIds)
            );
            $dbr2 = mysqli_query($conn, $sql);
            if($dbr2) {
                sqlerror();
                $row2 = mysqli_fetch_array($dbr2);
                $meldungenCount = (int)$row2['cnt'];
            }
        }
        $missing = $Nappmnts - $meldungenCount;
        echo $u->getName()." (".$u->getRegisterName().") ".$meldungenCount."/".$Nappmnts.", missing: ".$missing."<br />\n";
        if($missing > 0) {
            $mail = new Usermail;
            $mail->source = 'cron_reminder';
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
