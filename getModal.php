<?php
session_start();
include 'common/include.php';

header('Content-Type: text/html; charset=utf-8');

if(!isset($_SESSION['userid']) || !(int)$_SESSION['userid']) {
    http_response_code(401);
    echo '<div class="w3-container w3-padding"><p>Nicht angemeldet.</p><button class="w3-button" onclick="closeModal()">&times; Schließen</button></div>';
    exit;
}

$type = isset($_GET['type']) ? (string)$_GET['type'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($id <= 0 || $type === '') {
    http_response_code(400);
    echo '<div class="w3-container w3-padding"><p>Ungültige Anfrage.</p><button class="w3-button" onclick="closeModal()">Schließen</button></div>';
    exit;
}

switch($type) {
case 'termin':
    $t = new Termin;
    if(!$t->load_by_id($id)) {
        http_response_code(404);
        echo '<div class="w3-container w3-padding"><p>Termin nicht gefunden.</p></div>';
        exit;
    }
    echo $t->getDetailModalHtml();
    break;

case 'terminResponse':
    if(!requirePermission('perm_showResponse')) {
        http_response_code(403);
        echo '<div class="w3-container w3-padding"><p>Keine Berechtigung.</p></div>';
        exit;
    }
    $t = new Termin;
    if(!$t->load_by_id($id)) {
        http_response_code(404);
        echo '<div class="w3-container w3-padding"><p>Termin nicht gefunden.</p></div>';
        exit;
    }
    $filter = 0;
    if(isset($_GET['register'])) {
        $filter = (int)$_GET['register'];
    }
    echo $t->getResponseModalHtml($filter);
    break;

case 'shiftResponse':
    if(!requirePermission('perm_showResponse')) {
        http_response_code(403);
        echo '<div class="w3-container w3-padding"><p>Keine Berechtigung.</p></div>';
        exit;
    }
    $s = new Shift;
    if(!$s->load_by_id($id)) {
        http_response_code(404);
        echo '<div class="w3-container w3-padding"><p>Schicht nicht gefunden.</p></div>';
        exit;
    }
    $t = new Termin;
    if(!$t->load_by_id($s->Termin)) {
        http_response_code(404);
        echo '<div class="w3-container w3-padding"><p>Termin nicht gefunden.</p></div>';
        exit;
    }
    echo $t->getShiftResponseModalHtml($s);
    break;

case 'user':
    if(!requirePermission('perm_showUsers') && (int)$_SESSION['userid'] !== $id) {
        http_response_code(403);
        echo '<div class="w3-container w3-padding"><p>Keine Berechtigung.</p></div>';
        exit;
    }
    $u = new User;
    if(!$u->load_by_id($id)) {
        http_response_code(404);
        echo '<div class="w3-container w3-padding"><p>Benutzer nicht gefunden.</p></div>';
        exit;
    }
    echo $u->getModalHtml();
    break;

case 'inventory':
    // Any logged-in user may open (e.g. myinventories); edit UI gated by $editable
    $inv = new Inventories;
    if(!$inv->load_by_id($id)) {
        http_response_code(404);
        echo '<div class="w3-container w3-padding"><p>Inventar nicht gefunden.</p></div>';
        exit;
    }
    $editable = requirePermission('perm_editInventories');
    echo $inv->getModalHtml($editable);
    break;

case 'instrument':
    // Insurance / instrument lists: session auth is enough; edit gated in modal
    $ins = new Instruments;
    if(!$ins->load_by_id($id)) {
        http_response_code(404);
        echo '<div class="w3-container w3-padding"><p>Instrument nicht gefunden.</p></div>';
        exit;
    }
    echo $ins->getModalHtml();
    break;

default:
    http_response_code(400);
    echo '<div class="w3-container w3-padding"><p>Unbekannter Modal-Typ.</p></div>';
    break;
}
?>
