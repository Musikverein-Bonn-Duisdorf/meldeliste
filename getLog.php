<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
include 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));

if(!loggedIn()) {
    http_response_code(403);
    die('forbidden');
}
if(!requirePermission('perm_showLog') && !isAdmin()) {
    http_response_code(403);
    die('forbidden');
}

$maxIndex = meldeRequest('maxIndex');
if($maxIndex === null || !is_numeric($maxIndex)) {
    http_response_code(400);
    die('invalid maxIndex');
}

$topTimestamp = meldeRequest('topTimestamp');
if($topTimestamp === null) {
    $topTimestamp = '';
}

echo logPollNextHtml((int)$maxIndex, (string)$topTimestamp);
?>
