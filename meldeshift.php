<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
include 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));

if(!loggedIn()) {
    http_response_code(403);
    die('forbidden');
}

$cmd = (string)meldeRequest('cmd', '');
switch($cmd) {
case "save":
    if(meldeRequest('ajax') === '1' || meldeRequest('ajax') === 1) {
        requireMeldeChipEditAuth();
    }
    else {
        requireEditResponseAuth(meldeRequest('user', 0));
    }
    $shiftId = (int)meldeRequest('shift');
    if($shiftId < 1) {
        http_response_code(400);
        die('invalid shift');
    }
    $m = new Shiftmeldung;
    $m->load_by_user_event(meldeRequest('user'), $shiftId);
    if($m->User < 1) {
        $m->User = meldeRequest('user');
        $m->Shift = $shiftId;
    }
    $m->Wert = meldeRequest('wert');
    $m->save();

    $uid = (int)meldeRequest('user');
    $s = new Shift;
    $s->load_by_id($shiftId);
    $t = new Termin;
    $t->load_by_id($s->Termin);
    if((int)$t->Index > 0) {
        $t->ensureUserVisibleForMeldedResponse($uid, (int)$m->Wert);
    }

    if(meldeRequest('ajax') === '1' || meldeRequest('ajax') === 1) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('ok' => true, 'shift' => $shiftId, 'termin' => (int)$t->Index, 'user' => $uid));
        break;
    }
    echo $t->printBasicTableLine($uid);
    break;
case "delete":
    if(meldeRequest('ajax') === '1' || meldeRequest('ajax') === 1) {
        requireMeldeChipEditAuth();
    }
    else {
        requireEditResponseAuth(meldeRequest('user', 0));
    }
    $shiftId = (int)meldeRequest('shift');
    $uid = (int)meldeRequest('user');
    if($shiftId < 1 || $uid < 1) {
        http_response_code(400);
        die('invalid shift or user');
    }
    $m = new Shiftmeldung;
    $m->load_by_user_event($uid, $shiftId);
    if((int)$m->Index > 0) {
        $m->delete();
    }
    if(meldeRequest('ajax') === '1' || meldeRequest('ajax') === 1) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('ok' => true, 'shift' => $shiftId, 'user' => $uid));
        break;
    }
    $s = new Shift;
    $s->load_by_id($shiftId);
    $t = new Termin;
    $t->load_by_id($s->Termin);
    echo $t->printBasicTableLine($uid);
    break;
default:
    http_response_code(400);
    die('invalid command');
}
?>
