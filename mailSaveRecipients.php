<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

if(!loggedIn() || !requirePermission('perm_sendEmail')) {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'error' => 'forbidden'));
    exit;
}

MailJob::ensureSchema();

$jobId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if($jobId <= 0) {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'error' => 'missing_id'));
    exit;
}

$job = new MailJob;
$job->load_by_id($jobId);
if(!$job->Index || $job->Status !== 'draft') {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'error' => 'not_draft'));
    exit;
}

$specRaw = isset($_POST['recipientSpec']) ? (string)$_POST['recipientSpec'] : '';
$decoded = json_decode($specRaw, true);
if(!is_array($decoded)) {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'error' => 'invalid_spec'));
    exit;
}

$job->setRecipientSpecArray($decoded);
if(!$job->save()) {
    http_response_code(500);
    echo json_encode(array('ok' => false, 'error' => 'save_failed'));
    exit;
}

echo json_encode(array(
    'ok' => true,
    'id' => (int)$job->Index,
    'spec' => $job->getRecipientSpecArray(),
));
?>
