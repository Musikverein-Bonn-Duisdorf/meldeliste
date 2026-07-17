<?php
session_start();
include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

function permJsonOut($payload, $code = 200) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

if(!loggedIn()) {
    permJsonOut(array('ok' => false, 'error' => 'not_logged_in'), 403);
}
if(!empty($_SESSION['singleUsePW'])) {
    permJsonOut(array('ok' => false, 'error' => 'password_change_required'), 403);
}
if(!requirePermission('perm_editPermissions')) {
    permJsonOut(array('ok' => false, 'error' => 'forbidden'), 403);
}

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    permJsonOut(array('ok' => false, 'error' => 'method'), 405);
}

$userId = isset($_POST['user']) ? (int)$_POST['user'] : 0;
$perm = isset($_POST['perm']) ? (string)$_POST['perm'] : '';
$value = isset($_POST['value']) ? (int)$_POST['value'] : 0;
$value = $value ? 1 : 0;

if($userId <= 0) {
    permJsonOut(array('ok' => false, 'error' => 'invalid_user'), 400);
}
if(!in_array($perm, Permissions::permissionKeys(), true)) {
    permJsonOut(array('ok' => false, 'error' => 'invalid_perm'), 400);
}

$target = new User;
$target->load_by_id($userId);
if(!$target->Index || (int)$target->Deleted === 1) {
    permJsonOut(array('ok' => false, 'error' => 'user_not_found'), 404);
}

$sessionUserId = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
if($sessionUserId === $userId && $perm === 'perm_editPermissions' && $value === 0) {
    permJsonOut(array('ok' => false, 'error' => 'cannot_remove_own_edit'), 400);
}

$p = new Permissions;
$p->load_by_user($userId);
$p->$perm = $value;
$p->save();

if($sessionUserId === $userId) {
    $_SESSION['permissions'] = loadPermissions($userId);
    $_SESSION['admin'] = isAdmin() ? 1 : 0;
}

permJsonOut(array(
    'ok' => true,
    'user' => $userId,
    'perm' => $perm,
    'value' => $value,
));
?>
