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
    $t->load_by_id($id);
    if(!(int)$t->Index) {
        http_response_code(404);
        echo '<div class="w3-container w3-padding"><p>Termin nicht gefunden.</p></div>';
        exit;
    }
    echo $t->getDetailModalHtml();
    break;

case 'terminResponse':
    // All logged-in users may view register responses (MELD-68); edit stays gated in modal HTML
    $t = new Termin;
    $t->load_by_id($id);
    if(!(int)$t->Index) {
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
    // Same as terminResponse: view without perm_showResponse (MELD-68)
    $s = new Shift;
    $s->load_by_id($id);
    if(!(int)$s->Index) {
        http_response_code(404);
        echo '<div class="w3-container w3-padding"><p>Schicht nicht gefunden.</p></div>';
        exit;
    }
    $t = new Termin;
    $t->load_by_id($s->Termin);
    if(!(int)$t->Index) {
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
    $u->load_by_id($id);
    if(!(int)$u->Index) {
        http_response_code(404);
        echo '<div class="w3-container w3-padding"><p>Benutzer nicht gefunden.</p></div>';
        exit;
    }
    echo $u->getModalHtml();
    break;

case 'inventar':
case 'inventory': // alias
    // View: perm_showInventories OR owner/active loan; edit UI gated by perm_editInventories
    try {
        $inv = new Inventories;
        $inv->load_by_id($id);
        if(!(int)$inv->Index) {
            http_response_code(404);
            echo '<div class="w3-container w3-padding"><header class="w3-container"><span onclick="closeModal()" class="w3-button w3-display-topright">&times;</span><h2>Fehler</h2></header><p>Inventar nicht gefunden.</p></div>';
            exit;
        }
        if(!requirePermission('perm_showInventories') && !$inv->userMayView((int)$_SESSION['userid'])) {
            http_response_code(403);
            echo '<div class="w3-container w3-padding"><header class="w3-container"><span onclick="closeModal()" class="w3-button w3-display-topright">&times;</span><h2>Fehler</h2></header><p>Keine Berechtigung.</p></div>';
            exit;
        }
        $editable = requirePermission('perm_editInventories');
        echo $inv->getModalHtml($editable);
    }
    catch(Throwable $e) {
        http_response_code(500);
        echo '<div class="w3-container w3-padding"><header class="w3-container"><span onclick="closeModal()" class="w3-button w3-display-topright">&times;</span><h2>Fehler</h2></header><p>Inventar-Modal: '.htmlspecialchars($e->getMessage()).'</p></div>';
    }
    break;

default:
    http_response_code(400);
    echo '<div class="w3-container w3-padding"><p>Unbekannter Modal-Typ.</p></div>';
    break;
}
?>
