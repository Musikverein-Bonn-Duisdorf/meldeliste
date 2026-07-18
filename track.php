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
    $m = new Meldung;
    $m->load_by_user_event(meldeRequest('user'), meldeRequest('termin'));
    if($m->User < 1) {
        $m = new Meldung;
        $m->User = meldeRequest('user');
        $m->Termin = meldeRequest('termin');
    }
    $m->Wert = meldeRequest('wert');
    $m->Children = meldeRequest('Children');
    $m->Guests = meldeRequest('Guests');
    $m->save();
    $t = new Termin;
    $t->load_by_id(meldeRequest('termin'));
    echo $t->TrackingUser(meldeRequest('user'));
    break;
case "instrument":
    requireEditResponseAuth(meldeRequest('user', 0));
    $m = new Meldung;
    $m->load_by_user_event(meldeRequest('user'), meldeRequest('termin'));
    if($m->User < 1) {
        break;
    }
    $m->Instrument = meldeRequest('instrument');
    $m->save();
    break;
case "status":
    if(!requirePermission('perm_editResponse')) {
        http_response_code(403);
        die('forbidden');
    }
    setlocale (LC_ALL, 'de_DE.UTF8');
    $today = strftime('%A, %d. %B %Y um %H:%M Uhr');

    $t = new Termin;
    if((int)meldeRequest('termin') < 1) break;
    $t->load_by_id(meldeRequest("termin"));
    $body = "dies ist der Meldestand f&uuml;r ".$today.".\n";
    $body = $body.$t->printMailResponse();
    $mail = new Usermail;
    $mail->source = 'status';
    $mail->singleUser(meldeRequest('user'), $t->Name, $body);
    break;
default:
    http_response_code(400);
    die('invalid command');
}
?>
