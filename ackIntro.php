<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

if(!loggedIn()) {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'error' => 'forbidden'));
    exit;
}
if(!empty($_SESSION['singleUsePW'])) {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'error' => 'password_change_required'));
    exit;
}

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('ok' => false, 'error' => 'method'));
    exit;
}

$token = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
if(!csrf_verify($token)) {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'error' => 'csrf'));
    exit;
}

$kind = isset($_POST['kind']) ? (string)$_POST['kind'] : '';
if($kind !== 'intro' && $kind !== 'changelog') {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'error' => 'kind'));
    exit;
}

$userId = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
if($userId < 1) {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'error' => 'forbidden'));
    exit;
}

$action = isset($_POST['action']) ? (string)$_POST['action'] : 'ack';
if($action === 'dismiss') {
    Intro::dismissForSession($kind);
    echo json_encode(array('ok' => true, 'kind' => $kind, 'dismissed' => true));
    exit;
}

$pending = Intro::pendingKindForUser($userId);
if($pending !== $kind) {
    // Already acked, dismissed this session, or nothing pending — treat as ok.
    echo json_encode(array('ok' => true, 'kind' => $kind, 'already' => true));
    exit;
}

if(!Intro::acknowledge($userId, $kind)) {
    http_response_code(500);
    echo json_encode(array('ok' => false, 'error' => 'save_failed'));
    exit;
}

$next = Intro::pendingKindForUser($userId);
echo json_encode(array(
    'ok' => true,
    'kind' => $kind,
    'next' => $next,
));
?>
