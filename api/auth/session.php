<?php
/**
 * POST /api/auth/session.php
 * Exchange app token → PHP session (Set-Cookie) for WebView auto-login.
 * Auth: Authorization: Bearer <token> or JSON/form `token`.
 */
require_once dirname(__DIR__).'/_bootstrap.php';
apiRequireMethod('POST');

$token = readAppTokenFromRequest();
if($token === '') {
    apiJsonExit(array('error' => 'missing_token'), 400);
}

if(!validateAppToken($token)) {
    apiJsonExit(array('error' => 'invalid_token'), 401);
}

apiJsonExit(array(
    'ok' => true,
    'user' => array(
        'id' => (int)$_SESSION['userid'],
        'name' => (string)$_SESSION['username'],
        'singleUsePW' => !empty($_SESSION['singleUsePW']),
    ),
));
?>
