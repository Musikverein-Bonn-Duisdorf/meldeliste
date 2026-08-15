<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
include 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));

if(!loggedIn()) {
    http_response_code(403);
    die('forbidden');
}

$appId = 0;
if(isset($_GET['appID'])) {
    $appId = (int)$_GET['appID'];
} elseif(isset($_POST['appID'])) {
    // Legacy POST forms (pre-MELD-180)
    $appId = (int)$_POST['appID'];
}

if($appId <= 0) {
    http_response_code(400);
    die('Error: Appointment not found.');
}

$n = new Termin;
$n->load_by_id($appId);
if(!(int)$n->Index) {
    http_response_code(404);
    die('Error: Appointment not found.');
}

$shiftId = 0;
if(isset($_GET['shiftID'])) {
    $shiftId = (int)$_GET['shiftID'];
} elseif(isset($_POST['shiftID'])) {
    $shiftId = (int)$_POST['shiftID'];
}

$shift = null;
if((int)$n->Shifts) {
    if($shiftId <= 0) {
        http_response_code(400);
        die('Error: Shift required.');
    }
    $shift = new Shift;
    $shift->load_by_id($shiftId);
    if(!(int)$shift->Index || (int)$shift->Termin !== (int)$n->Index) {
        http_response_code(404);
        die('Error: Shift not found.');
    }
}

$summary = (string)$n->Name;
$startTime = trim((string)$n->Uhrzeit);
$endTime = trim((string)$n->Uhrzeit2);
$fileLabel = (string)$n->Name;
if($shift) {
    $shiftName = trim((string)$shift->Name);
    $fileLabel = $shiftName !== '' ? $shiftName : $fileLabel;
    if($summary !== '' && $shiftName !== '') {
        $summary = $summary.': '.$shiftName;
    }
    elseif($shiftName !== '') {
        $summary = $shiftName;
    }
    $startNorm = Shift::normalizedTime($shift->Start);
    $endNorm = Shift::normalizedTime($shift->End);
    if($startNorm !== null) {
        $startTime = $startNorm;
    }
    if($endNorm !== null) {
        $endTime = $endNorm;
    }
}

$rawName = preg_replace('/[^\w.\-]+/u', '_', $fileLabel);
if($rawName === null || $rawName === '') {
    $rawName = 'termin';
}
$filename = preg_replace('/[^\w.\-]+/u', '_', (string)$n->Datum).'_'.$rawName.'.ics';

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('X-Content-Type-Options: nosniff');

date_default_timezone_set('Europe/Berlin');
if($n->EndDatum) {
    $end = gmdate('Y-m-d H:i:s', strtotime($n->EndDatum.' 23:59:00'));
    if($endTime !== '') {
        $end = gmdate('Y-m-d H:i:s', strtotime($n->EndDatum.' '.$endTime));
    }
    $begin = gmdate('Y-m-d H:i:s', strtotime($n->Datum.' '.$startTime));
    if($startTime === '') {
        $begin = gmdate('Y-m-d H:i:s', strtotime($n->Datum.' 00:00:00'));
        $end = gmdate('Y-m-d H:i:s', strtotime($n->EndDatum.' 23:59:00'));
    }
}
else {
    $end = gmdate('Y-m-d H:i:s', strtotime('+120 minutes', strtotime($n->Datum.' '.$startTime)));

    if($endTime !== '') {
        $end = gmdate('Y-m-d H:i:s', strtotime($n->Datum.' '.$endTime));
    }

    $begin = gmdate('Y-m-d H:i:s', strtotime($n->Datum.' '.$startTime));
    if($startTime === '') {
        $begin = gmdate('Y-m-d H:i:s', strtotime($n->Datum.' 00:00:00'));
        $end = gmdate('Y-m-d H:i:s', strtotime($n->Datum.' 23:59:00'));
    }
}

$ics = new ICS(array(
    'timezone' => 'Europe/Berlin',
    'location' => $n->getOrt(),
    'description' => $n->Beschreibung,
    'dtstart' => $begin,
    'dtend' => $end,
    'summary' => $summary,
));

echo $ics->to_string();
