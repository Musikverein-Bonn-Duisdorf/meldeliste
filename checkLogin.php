<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

if(!loggedIn() || !requirePermission('perm_editUsers')) {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'error' => 'forbidden'));
    exit;
}

$action = isset($_REQUEST['action']) ? (string)$_REQUEST['action'] : 'check';
$excludeUserId = isset($_REQUEST['excludeUserId']) ? (int)$_REQUEST['excludeUserId'] : 0;

if($action === 'suggest') {
    $vorname = isset($_REQUEST['vorname']) ? (string)$_REQUEST['vorname'] : '';
    $nachname = isset($_REQUEST['nachname']) ? (string)$_REQUEST['nachname'] : '';
    $suggest = UserLogin::suggestLogin($vorname, $nachname, $excludeUserId);
    echo json_encode(array(
        'ok' => true,
        'suggest' => $suggest,
        'valid' => UserLogin::isLoginFormatValid($suggest),
        'available' => $suggest !== '' && !UserLogin::loginExists($suggest, $excludeUserId),
    ));
    exit;
}

$loginRaw = isset($_REQUEST['login']) ? (string)$_REQUEST['login'] : '';
$normalized = UserLogin::normalizeLoginCandidate($loginRaw);
$valid = UserLogin::isLoginFormatValid($normalized);
$available = $normalized !== '' && $valid && !UserLogin::loginExists($normalized, $excludeUserId);

$message = '';
if($loginRaw === '' || $normalized === '') {
    $message = 'leer';
}
elseif(!$valid) {
    $message = 'Login mindestens 5 Zeichen (a–z, 0–9).';
}
elseif(!$available) {
    $message = 'Login bereits vergeben.';
}

echo json_encode(array(
    'ok' => true,
    'login' => $normalized,
    'valid' => $valid,
    'available' => $available,
    'message' => $message,
));
?>
