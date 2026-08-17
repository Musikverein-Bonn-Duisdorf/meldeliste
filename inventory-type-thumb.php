<?php
/**
 * Serve inventory type default thumbnails (MELD-206).
 * GET id — type Index
 */
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
requireLoggedInOrRedirect();

$typeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$type = new Inventory();
if($typeId > 0) {
    $type->load_by_id($typeId);
}
$path = ((int)$type->Index) ? $type->thumbAbsolutePath() : null;
if($path === null) {
    denyAccess('Vorschau nicht gefunden.');
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
