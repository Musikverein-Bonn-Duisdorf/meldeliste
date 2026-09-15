<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

function collectionGrantJsonOut($payload, $code = 200) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

if(!loggedIn()) {
    collectionGrantJsonOut(array('ok' => false, 'error' => 'not_logged_in'), 403);
}
if(!empty($_SESSION['singleUsePW'])) {
    collectionGrantJsonOut(array('ok' => false, 'error' => 'password_change_required'), 403);
}
if(!requirePermission('perm_editAppmnts') || !archivFeatureEnabled()) {
    collectionGrantJsonOut(array('ok' => false, 'error' => 'forbidden'), 403);
}
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    collectionGrantJsonOut(array('ok' => false, 'error' => 'method'), 405);
}

CollectionGrant::ensureSchema();

$collectionId = isset($_POST['collection']) ? (int)$_POST['collection'] : 0;
if($collectionId < 1 || archivCollectionName($collectionId) === '') {
    collectionGrantJsonOut(array('ok' => false, 'error' => 'invalid_collection'), 400);
}

$specRaw = isset($_POST['accessSpec']) ? $_POST['accessSpec'] : '{}';
$decoded = json_decode($specRaw, true);
if(!is_array($decoded)) {
    $decoded = AudienceSpec::emptySpec();
}

$grant = CollectionGrant::ensureStub($collectionId);
$grant->setAccessSpecArray($decoded);
if(!$grant->save()) {
    collectionGrantJsonOut(array('ok' => false, 'error' => 'save_failed'), 500);
}

collectionGrantJsonOut(array(
    'ok' => true,
    'collection' => $collectionId,
    'hasAccess' => $grant->hasAccess() ? 1 : 0,
));
?>
