<?php
session_start();
include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

if(!loggedIn() || !requirePermission('perm_sendEmail')) {
    http_response_code(403);
    echo json_encode(array('error' => 'forbidden', 'count' => 0));
    exit;
}

$termin = 0;
if(isset($_REQUEST['termin'])) {
    $termin = (int)$_REQUEST['termin'];
}

$specRaw = '';
if(isset($_POST['recipientSpec'])) {
    $specRaw = (string)$_POST['recipientSpec'];
}
elseif(isset($_GET['recipientSpec'])) {
    $specRaw = (string)$_GET['recipientSpec'];
}

$spec = null;
if($specRaw !== '') {
    $decoded = json_decode($specRaw, true);
    if(is_array($decoded)) {
        $spec = $decoded;
    }
}

$count = Usermail::countFromRecipientSpec($spec, $termin);
echo json_encode(array('count' => (int)$count));
?>
