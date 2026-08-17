<?php
/**
 * Serve an inventory document inline (MELD-205).
 * GET id=<documentId>
 */
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
requireLoggedInOrRedirect();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$doc = new InventoriesDocument();
if($id < 1 || !$doc->load_by_id($id)) {
    http_response_code(404);
    echo 'Dokument nicht gefunden.';
    exit;
}

$inv = new Inventories();
$inv->load_by_id((int)$doc->Inventory);
if(!(int)$inv->Index || !InventoriesDocument::userMayAccess($inv)) {
    denyAccess();
}

$path = $doc->absolutePath();
if($path === null) {
    http_response_code(404);
    echo 'Datei nicht gefunden.';
    exit;
}

header('Content-Type: '.$doc->mimeType());
header('Content-Length: '.(string)filesize($path));
header('Content-Disposition: inline; filename="'.$doc->downloadName().'"');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
