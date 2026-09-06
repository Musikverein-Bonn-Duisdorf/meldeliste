<?php
/**
 * Serve clothing/uniform thumbnails (MELD-234 / MELD-235).
 * GET id — Uniform Index
 * GET g  — optional m|w (fallback to other gender / first available)
 */
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
requireLoggedInOrRedirect();

$typeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$gender = isset($_GET['g']) ? $_GET['g'] : null;
$type = new Uniform();
if($typeId > 0) {
    $type->load_by_id($typeId);
}
$path = ((int)$type->Index) ? $type->thumbAbsolutePath($gender) : null;
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
