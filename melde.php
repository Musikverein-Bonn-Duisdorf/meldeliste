<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
include 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));

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
    if(meldeRequest('Children') !== null) {
        $m->Children = meldeRequest('Children');
    }
    if(meldeRequest('Guests') !== null) {
        $m->Guests = meldeRequest('Guests');
    }
    $m->save();

    $t = new Termin;
    $t->load_by_id(meldeRequest('termin'));
    echo $t->printBasicTableLine();
    break;
case "reload":
    if(!loggedIn()) {
        http_response_code(403);
        die('forbidden');
    }
    $targetUser = meldeRequest('user') !== null ? (int)meldeRequest('user') : 0;
    $sessionUser = (int)$_SESSION['userid'];
    $proxyUser = (isset($_SESSION['proxy']) && (int)$_SESSION['proxy'] > 0) ? (int)$_SESSION['proxy'] : 0;
    if($targetUser < 1) {
        http_response_code(400);
        die('invalid user');
    }
    if($targetUser !== $sessionUser && $targetUser !== $proxyUser && !requirePermission('perm_editResponse')) {
        http_response_code(403);
        die('forbidden');
    }
    if(!meldeRequest('termin') || (int)meldeRequest('termin') < 1) {
        http_response_code(400);
        die('invalid termin');
    }
    $t = new Termin;
    $t->load_by_id(meldeRequest('termin'));
    if(!(int)$t->Index) {
        http_response_code(404);
        die('not found');
    }
    echo $t->printBasicTableLine();
    break;
case "responseLine":
    if(!loggedIn()) {
        http_response_code(403);
        die('forbidden');
    }
    if(!meldeRequest('termin') || (int)meldeRequest('termin') < 1) {
        http_response_code(400);
        die('invalid termin');
    }
    $t = new Termin;
    $t->load_by_id(meldeRequest('termin'));
    if(!(int)$t->Index) {
        http_response_code(404);
        die('not found');
    }
    $filter = meldeRequest('register') !== null ? (int)meldeRequest('register') : 0;
    echo $t->getResponseLine($filter);
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
    if(!loggedIn()) {
        http_response_code(403);
        die('forbidden');
    }
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
case "freetext":
    requireEditResponseAuth(meldeRequest('user', 0));
    $m = new AppmntFreeTextResponse;
    $m->load_by_user_event(meldeRequest('user'), meldeRequest('termin'));
    $m->User = meldeRequest('user');
    $m->Termin = meldeRequest('termin');
    $m->Text = meldeRequest('freeText');
    $m->save();

    $t = new Termin;
    $t->load_by_id(meldeRequest('termin'));
    echo $t->printBasicTableLine();
    break;
default:
    http_response_code(400);
    die('invalid command');
}
?>
