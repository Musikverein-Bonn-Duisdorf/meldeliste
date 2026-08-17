<?php
/**
 * Upload / download / delete scanned loan or return contracts (MELD-181 / MELD-188).
 * GET: loan, kind — download stored scan
 * POST: loan, kind, action=upload, scan — store scan
 * POST: loan, kind, action=deleteScan — clear scan
 */
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
requireLoggedInOrRedirect();

$userId = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
$loanId = 0;
if(isset($_POST['loan'])) {
    $loanId = (int)$_POST['loan'];
}
elseif(isset($_GET['loan'])) {
    $loanId = (int)$_GET['loan'];
}
$kind = LoanForm::normalizeKind(
    isset($_POST['kind']) ? $_POST['kind'] : (isset($_GET['kind']) ? $_GET['kind'] : LoanForm::KIND_LOAN)
);

$loan = new InventoriesLoan;
if($loanId > 0) {
    $loan->load_by_id($loanId);
}
if(!(int)$loan->Index) {
    denyAccess('Leihe nicht gefunden.');
}

$isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
if($isPost) {
    if(!LoanForm::userMayEdit($userId)) {
        denyAccess();
    }
    $action = isset($_POST['action']) ? (string)$_POST['action'] : '';
    if($action === 'deleteScan') {
        if(!LoanForm::deleteScan($loan, $kind)) {
            denyAccess('Scan konnte nicht gelöscht werden.');
        }
        header('Location: loan-form.php?loan='.$loanId.'&kind='.rawurlencode($kind));
        exit;
    }
    if($action !== 'upload' || !isset($_FILES['scan'])) {
        denyAccess('Upload fehlgeschlagen.');
    }
    $name = LoanForm::storeUpload($loanId, $_FILES['scan'], $kind);
    if($name === false) {
        denyAccess('Datei konnte nicht gespeichert werden (PDF, JPEG oder PNG).');
    }
    if($kind === LoanForm::KIND_RETURN) {
        $loan->ReturnContractFile = $name;
    }
    else {
        $loan->ContractFile = $name;
    }
    $loan->save();
    header('Location: loan-form.php?loan='.$loanId.'&kind='.rawurlencode($kind));
    exit;
}

if(!LoanForm::userMayView($userId, $loan)) {
    denyAccess();
}

$rawSig = isset($_GET['sig']) ? strtolower(trim((string)$_GET['sig'])) : '';
$wantSnapshot = isset($_GET['file']) && (string)$_GET['file'] === 'snapshot';

if($rawSig === LoanForm::ROLE_LENDER || $rawSig === LoanForm::ROLE_BORROWER) {
    $sig = LoanForm::getSignature($loan, $kind, $rawSig);
    if($sig === null) {
        denyAccess('Unterschrift nicht gefunden.');
    }
    $path = LoanForm::resolveStoredFile($loanId, $sig['File']);
    if($path === null) {
        denyAccess('Unterschrift nicht gefunden.');
    }
    header('Content-Type: image/png');
    header('Content-Length: '.(string)filesize($path));
    header('Content-Disposition: inline; filename="'.basename($path).'"');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

if($wantSnapshot) {
    $lender = LoanForm::getSignature($loan, $kind, LoanForm::ROLE_LENDER);
    $borrower = LoanForm::getSignature($loan, $kind, LoanForm::ROLE_BORROWER);
    $storedSnap = '';
    if($lender && $lender['SnapshotFile'] !== '') {
        $storedSnap = $lender['SnapshotFile'];
    }
    elseif($borrower && $borrower['SnapshotFile'] !== '') {
        $storedSnap = $borrower['SnapshotFile'];
    }
    $path = $storedSnap !== '' ? LoanForm::resolveStoredFile($loanId, $storedSnap) : null;
    if($path === null) {
        denyAccess('Dokument nicht gefunden.');
    }
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Length: '.(string)filesize($path));
    header('Content-Disposition: inline; filename="'.basename($path).'"');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

$stored = $kind === LoanForm::KIND_RETURN
    ? (string)$loan->ReturnContractFile
    : (string)$loan->ContractFile;
$path = LoanForm::resolveStoredFile($loanId, $stored);
if($path === null) {
    denyAccess('Scan nicht gefunden.');
}

$mime = 'application/octet-stream';
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$map = array(
    'pdf' => 'application/pdf',
    'html' => 'text/html',
    'htm' => 'text/html',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
);
if(isset($map[$ext])) {
    $mime = $map[$ext];
}

header('Content-Type: '.$mime);
header('Content-Length: '.(string)filesize($path));
header('Content-Disposition: inline; filename="'.basename($path).'"');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
?>
