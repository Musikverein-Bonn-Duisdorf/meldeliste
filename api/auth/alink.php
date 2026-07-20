<?php
/**
 * POST /api/auth/alink.php (MELD-123)
 * Body (JSON or form): alink (hash or full login.php?alink=… URL), device (optional)
 * Returns app token and establishes PHP session cookies for WebView.
 */
require_once dirname(__DIR__).'/_bootstrap.php';
apiRequireMethod('POST');

$json = readJsonRequestBody();
$alinkRaw = isset($json['alink']) ? $json['alink'] : (isset($_POST['alink']) ? $_POST['alink'] : '');
$device = isset($json['device']) ? $json['device'] : (isset($_POST['device']) ? $_POST['device'] : 'Android');

$alink = normalizeAlinkInput($alinkRaw);
if($alink === '') {
    apiJsonExit(array('error' => 'invalid_alink'), 401);
}

$row = findUserByActiveLink($alink);
if(!$row) {
    $logentry = new Log;
    $logentry->error("App login not successful. Invalid alink <b>".htmlspecialchars($alink)."</b>.");
    apiJsonExit(array('error' => 'invalid_alink'), 401);
}

establishSessionFromUserRow($row, 'AppLink');
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
