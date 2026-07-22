<?php
/**
 * POST /api/auth/login.php
 * Body (JSON or form): login, password, device (optional)
 * Returns app token and establishes PHP session cookies for WebView.
 */
require_once dirname(__DIR__).'/_bootstrap.php';
apiRequireMethod('POST');

$json = readJsonRequestBody();
$login = isset($json['login']) ? $json['login'] : (isset($_POST['login']) ? $_POST['login'] : '');
$password = isset($json['password']) ? $json['password'] : (isset($_POST['password']) ? $_POST['password'] : '');
$device = isset($json['device']) ? $json['device'] : (isset($_POST['device']) ? $_POST['device'] : 'Android');

$login = trim((string)$login);
if($login === '' || $password === null || $password === '') {
    apiJsonExit(array('error' => 'invalid_credentials'), 401);
}

$sql = sprintf(
    "SELECT * FROM `%sUser` WHERE `login` = '%s' AND `Deleted` != 1 LIMIT 1;",
    $GLOBALS['dbprefix'],
    mysqli_real_escape_string($GLOBALS['conn'], $login)
);
$dbr = mysqli_query($GLOBALS['conn'], $sql);
sqlerror();
$row = mysqli_fetch_assoc($dbr);
if(!$row) {
    apiJsonExit(array('error' => 'invalid_credentials'), 401);
}
$hash = (string)$row['Passhash'];
if($hash === '' || !password_verify($password, $hash)) {
    $logentry = new Log;
    $logentry->error("App login not successful. Invalid password for username <b>".htmlspecialchars($login)."</b>.");
    apiJsonExit(array('error' => 'invalid_credentials'), 401);
}

if(!establishSessionFromUserRow($row, 'AppPassword')) {
    apiJsonExit(array('error' => 'invalid_credentials'), 401);
}
$token = createAppToken((int)$row['Index'], $device);
if(!$token) {
    apiJsonExit(array('error' => 'token_create_failed'), 500);
}

apiJsonExit(array(
    'token' => $token,
    'user' => array(
        'id' => (int)$row['Index'],
        'name' => $row['Vorname'].' '.$row['Nachname'],
        'singleUsePW' => (bool)$row['singleUsePW'],
    ),
    'expires' => null,
));
?>
