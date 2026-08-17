<?php
/**
 * Serve / upload / delete inventory photos (MELD-191).
 * GET id — image stream
 * POST inventory, action=upload, photo — store
 * POST inventory, action=delete, id — remove one photo
 */
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
requireLoggedInOrRedirect();

$userId = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
$isAjax = !empty($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

$respondAjax = function ($ok, $inventoryId, $error = '') use ($isAjax) {
    if(!$isAjax) {
        if(!$ok) {
            denyAccess($error !== '' ? $error : 'Aktion fehlgeschlagen.');
        }
        header('Location: inventories.php');
        exit;
    }
    while(ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=UTF-8');
    $html = '';
    $invId = (int)$inventoryId;
    if($ok && $invId > 0) {
        $inv = new Inventories();
        $inv->load_by_id($invId);
        if((int)$inv->Index) {
            $html = $inv->getModalHtml(true);
        }
    }
    if(!$ok) {
        http_response_code(400);
    }
    echo json_encode(array(
        'ok' => $ok,
        'inventoryId' => $invId,
        'html' => $html,
        'error' => $error,
    ));
    exit;
};

$mayViewItem = function (Inventories $inv) use ($userId) {
    if(!(int)$inv->Index) {
        return false;
    }
    if(requirePermission('perm_showInventories')) {
        return true;
    }
    return $inv->userMayView($userId);
};

$isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
if($isPost) {
    if(!requirePermission('perm_editInventories')) {
        $respondAjax(false, 0, 'Keine Berechtigung.');
    }
    $inventoryId = isset($_POST['inventory']) ? (int)$_POST['inventory'] : 0;
    $inv = new Inventories();
    if($inventoryId > 0) {
        $inv->load_by_id($inventoryId);
    }
    if(!(int)$inv->Index) {
        $respondAjax(false, 0, 'Inventar nicht gefunden.');
    }
    $action = isset($_POST['action']) ? (string)$_POST['action'] : '';
    if($action === 'delete') {
        $photo = new InventoriesPhoto();
        $photo->load_by_id(isset($_POST['id']) ? (int)$_POST['id'] : 0);
        if(!(int)$photo->Index || (int)$photo->Inventory !== (int)$inv->Index) {
            $respondAjax(false, (int)$inv->Index, 'Foto nicht gefunden.');
        }
        $photo->delete();
        $log = new Log();
        $log->DBdelete('Inventar-Foto: Inventory '.(int)$inv->Index);
        $respondAjax(true, (int)$inv->Index);
    }
    if($action !== 'upload' || !isset($_FILES['photo'])) {
        $respondAjax(false, (int)$inv->Index, 'Upload fehlgeschlagen.');
    }
    $stored = InventoriesPhoto::storeUpload((int)$inv->Index, $_FILES['photo']);
    if($stored === false) {
        $respondAjax(false, (int)$inv->Index, 'Datei konnte nicht gespeichert werden.');
    }
    $log = new Log();
    $log->DBinsert('Inventar-Foto: Inventory '.(int)$inv->Index);
    $respondAjax(true, (int)$inv->Index);
}

$photoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$photo = new InventoriesPhoto();
if($photoId > 0) {
    $photo->load_by_id($photoId);
}
if(!(int)$photo->Index) {
    denyAccess('Foto nicht gefunden.');
}
$inv = new Inventories();
$inv->load_by_id((int)$photo->Inventory);
if(!$mayViewItem($inv)) {
    denyAccess();
}
$path = $photo->absolutePath();
if($path === null) {
    denyAccess('Foto nicht gefunden.');
}

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$map = array(
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
);
$mime = isset($map[$ext]) ? $map[$ext] : 'application/octet-stream';
header('Content-Type: '.$mime);
header('Content-Length: '.(string)filesize($path));
header('Content-Disposition: inline; filename="'.basename($path).'"');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
