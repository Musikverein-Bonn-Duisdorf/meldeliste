<?php
/**
 * POST /api/auth/revoke.php
 * Revoke the current app token (device logout).
 */
require_once dirname(__DIR__).'/_bootstrap.php';
apiRequireMethod('POST');

$token = readAppTokenFromRequest();
if($token === '') {
    apiJsonExit(array('error' => 'missing_token'), 400);
}

$ok = revokeAppToken($token);
if($ok && loggedIn()) {
    session_destroy();
}

apiJsonExit(array('ok' => $ok));
?>
