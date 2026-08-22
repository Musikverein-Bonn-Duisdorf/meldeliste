<?php
/**
 * In-app document viewer with Meldeliste chrome (MELD-219).
 * Query:
 *   type=loan-scan&loan=<id>&kind=loan|return[&file=snapshot][&sig=lender|borrower]
 *   type=inventory-doc&id=<documentId>
 */
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page'] = 'document-view';
$_SESSION['adminpage'] = false;
include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
requireLoggedInOrRedirect();

$userId = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
$type = isset($_GET['type']) ? trim((string)$_GET['type']) : '';
$title = 'Dokument';
$kicker = 'Ansicht';
$streamSrc = '';
$backHref = 'javascript:history.back()';

if($type === 'loan-scan') {
    $loanId = isset($_GET['loan']) ? (int)$_GET['loan'] : 0;
    $kind = LoanForm::normalizeKind(isset($_GET['kind']) ? $_GET['kind'] : LoanForm::KIND_LOAN);
    $loan = new InventoriesLoan;
    if($loanId > 0) {
        $loan->load_by_id($loanId);
    }
    if(!(int)$loan->Index) {
        denyAccess('Leihe nicht gefunden.');
    }
    if(!LoanForm::userMayView($userId, $loan)) {
        denyAccess();
    }
    $streamSrc = 'loan-contract.php?loan='.$loanId.'&kind='.rawurlencode($kind);
    $file = isset($_GET['file']) ? trim((string)$_GET['file']) : '';
    if($file === 'snapshot') {
        $streamSrc .= '&file=snapshot';
        $title = $kind === LoanForm::KIND_RETURN ? 'Rückgabeprotokoll' : 'Leihvertrag';
    }
    else {
        $sig = isset($_GET['sig']) ? strtolower(trim((string)$_GET['sig'])) : '';
        if($sig === LoanForm::ROLE_LENDER || $sig === LoanForm::ROLE_BORROWER) {
            $streamSrc .= '&sig='.rawurlencode($sig);
            $title = 'Unterschrift';
        }
        else {
            $title = LoanForm::storedContractLinkLabel($loan, $kind);
        }
    }
    $kicker = 'Leihe';
    $backHref = 'loan-form.php?loan='.$loanId.'&kind='.rawurlencode($kind);
}
elseif($type === 'inventory-doc') {
    $docId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $doc = new InventoriesDocument();
    if($docId < 1 || !$doc->load_by_id($docId)) {
        denyAccess('Dokument nicht gefunden.');
    }
    $inv = new Inventories();
    $inv->load_by_id((int)$doc->Inventory);
    if(!(int)$inv->Index || !InventoriesDocument::userMayAccess($inv)) {
        denyAccess();
    }
    $streamSrc = 'get-inventory-document.php?id='.$docId;
    $title = $doc->displayName();
    $kicker = 'Inventar';
    if((int)$inv->Index > 0) {
        $backHref = 'inventories.php';
    }
}
else {
    denyAccess('Unbekannter Dokumenttyp.');
}

$h = function ($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};

include 'common/header.php';
$actions = '<a class="w3-button profile-btn-secondary w3-mobile" href="'
    .$h($backHref).'" onclick="if(window.history.length&gt;1){history.back();return false;}">Zurück</a>';
adminListPageBegin($kicker, $title, array('actionsHtml' => $actions, 'shellClass' => 'doc-view-shell'));
?>
<div class="doc-view-frame-wrap">
  <iframe class="doc-view-frame" src="<?php echo $h($streamSrc); ?>" title="<?php echo $h($title); ?>"></iframe>
</div>
<?php
adminListPageEnd();
include 'common/footer.php';
