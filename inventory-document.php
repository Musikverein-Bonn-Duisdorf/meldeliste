<?php
/**
 * Upload / delete inventory documents (MELD-205).
 * POST inventory_id, action=document_upload|document_delete
 * AJAX: JSON {ok, html, inventoryId, action}
 */
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
requireLoggedInOrRedirect();

$isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
if(!$isPost) {
    http_response_code(405);
    echo 'Method not allowed.';
    exit;
}

$ajax = isInventoriesAjaxRequest();
$inventoryId = isset($_POST['inventory_id']) ? (int)$_POST['inventory_id'] : 0;
$action = isset($_POST['action']) ? (string)$_POST['action'] : '';

$fail = function ($message, $code = 400) use ($ajax) {
    if($ajax) {
        while(ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code($code);
        echo json_encode(array('ok' => false, 'error' => $message));
        exit;
    }
    denyAccess($message);
};

if(!requirePermission('perm_editInventories')) {
    $fail('Keine Berechtigung zum Ändern von Inventar.', 403);
}

$inv = new Inventories();
if($inventoryId > 0) {
    $inv->load_by_id($inventoryId);
}
if(!(int)$inv->Index) {
    $fail('Inventar nicht gefunden.', 404);
}

if($action === 'document_delete') {
    $docId = isset($_POST['document_id']) ? (int)$_POST['document_id'] : 0;
    $doc = new InventoriesDocument();
    if($docId < 1 || !$doc->load_by_id($docId) || (int)$doc->Inventory !== (int)$inv->Index) {
        $fail('Dokument nicht gefunden.', 404);
    }
    if(!$doc->delete()) {
        $fail('Dokument konnte nicht gelöscht werden.');
    }
}
elseif($action === 'document_upload') {
    if(!isset($_FILES['document'])) {
        $fail('Datei fehlt.');
    }
    $note = isset($_POST['doc_note']) ? $_POST['doc_note'] : null;
    $type = isset($_POST['doc_type']) ? $_POST['doc_type'] : InventoriesDocument::TYPE_RECHNUNG;
    $doc = InventoriesDocument::createFromUpload((int)$inv->Index, $type, $_FILES['document'], $note);
    if($doc === null) {
        $fail('Datei konnte nicht gespeichert werden (PDF, JPEG oder PNG).');
    }
}
else {
    $fail('Unbekannte Aktion.');
}

if($ajax) {
    respondInventoriesAjax(array(
        'ok' => true,
        'inventoryId' => (int)$inv->Index,
        'action' => $action,
    ));
}

header('Location: inventories.php');
exit;
