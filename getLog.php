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

$sql = sprintf('SELECT `Index` FROM `%sLog` WHERE `Index` > %d ORDER BY `Timestamp` ASC LIMIT 1;',
    $GLOBALS['dbprefix'],
    (int)$maxIndex
);
$dbr = mysqli_query($conn, $sql);
sqlerror();
while($row = mysqli_fetch_array($dbr)) {
    $M = new Log;
    $M->load_by_id($row['Index']);
    if($M->Index > 0) {
        echo $M->printTableLine();
    }
}
?>
