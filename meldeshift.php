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
    requireEditResponseAuth(meldeRequest('user', 0));
    $m = new Shiftmeldung;
    $m->load_by_user_event(meldeRequest('user'), meldeRequest('shift'));
    if($m->User < 1) {
        $m->User = meldeRequest('user');
        $m->Shift = meldeRequest('shift');
    }
    $m->Wert = meldeRequest('wert');
    $m->save();
    $uid = (int)meldeRequest('user');
    $t = new Termin;
    $t->load_by_id(meldeRequest('termin'));
    echo $t->printBasicTableLine($uid);
    break;
default:
    http_response_code(400);
    die('invalid command');
}
?>
