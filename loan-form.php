<?php
/**
 * Printable loan / return form for any inventory loan (MELD-181).
 * Query: loan=<id>&kind=loan|return
 */
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page'] = 'inventories';
$_SESSION['adminpage'] = true;
include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
requireLoggedInOrRedirect();

$userId = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;

$loanId = isset($_GET['loan']) ? (int)$_GET['loan'] : (isset($_POST['loan']) ? (int)$_POST['loan'] : 0);
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

if(!LoanForm::userMayView($userId, $loan)) {
    denyAccess();
}

if($kind === LoanForm::KIND_RETURN && !LoanForm::userMayViewReturnForm($userId, $loan)) {
    denyAccess('Rückgabe nur durch Inventar-Verwaltung.');
}

$canEdit = LoanForm::userMayEdit($userId);
$canDeleteScan = LoanForm::userMayDeleteScan($userId);
$locked = LoanForm::isDigitallyComplete($loan, $kind);
$isBorrowerOnly = !$canEdit && (int)$loan->User === $userId;

if($canEdit && !$locked && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && isset($_POST['action']) && (string)$_POST['action'] === 'saveFields') {
    LoanForm::saveContractFields($loan, $_POST, $kind);
    $loan->load_by_id($loanId);
    header('Location: loan-form.php?loan='.$loanId.'&kind='.rawurlencode($kind).'&saved=1');
    exit;
}

if(($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && isset($_POST['action']) && (string)$_POST['action'] === 'sign') {
    $role = LoanForm::normalizeRole(isset($_POST['role']) ? $_POST['role'] : '');
    if(!LoanForm::userMaySign($userId, $loan, $role)) {
        denyAccess();
    }
    $png = isset($_POST['signature']) ? (string)$_POST['signature'] : '';
    $place = isset($_POST['place']) ? (string)$_POST['place'] : '';
    $result = LoanForm::storeSignature($loan, $kind, $role, $png, $userId, $place);
    $qs = 'loan='.$loanId.'&kind='.rawurlencode($kind);
    if(!empty($result['ok'])) {
        if(!empty($result['complete'])) {
            $qs .= '&complete=1';
        }
        if(!empty($result['mailed'])) {
            $qs .= '&mailed=1';
        }
    }
    else {
        $qs .= '&signerr=1';
    }
    header('Location: loan-form.php?'.$qs);
    exit;
}

if(($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && isset($_POST['action']) && (string)$_POST['action'] === 'notifyBorrower') {
    if(!LoanForm::userMayEdit($userId)) {
        denyAccess();
    }
    $queued = LoanForm::queueBorrowerSignReminder($loan, $kind);
    header('Location: loan-form.php?loan='.$loanId.'&kind='.rawurlencode($kind)
        .($queued ? '&notified=1' : '&notifyerr=1'));
    exit;
}

if(($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && isset($_POST['action']) && (string)$_POST['action'] === 'clearSign') {
    $role = LoanForm::normalizeRole(isset($_POST['role']) ? $_POST['role'] : '');
    if(!LoanForm::userMayClearSignature($userId, $loan, $role, $kind)) {
        denyAccess();
    }
    $ok = LoanForm::clearSignature($loan, $kind, $role);
    header('Location: loan-form.php?loan='.$loanId.'&kind='.rawurlencode($kind).($ok ? '&signcleared=1' : '&signerr=1'));
    exit;
}

if(($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && isset($_POST['action']) && (string)$_POST['action'] === 'restartWorkflow') {
    if(!LoanForm::userMayRestartWorkflow($userId, $loan, $kind)) {
        denyAccess();
    }
    $ok = LoanForm::restartWorkflow($loan, $kind);
    header('Location: loan-form.php?loan='.$loanId.'&kind='.rawurlencode($kind)
        .($ok ? '&restarted=1' : '&restarterr=1'));
    exit;
}

$ctx = LoanForm::buildContext($loan, $kind);
if(!$ctx) {
    denyAccess('Leihe nicht gefunden.');
}

$h = function ($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};

$stored = $kind === LoanForm::KIND_RETURN
    ? $ctx['returnContractFile']
    : $ctx['contractFile'];
$hasScan = $stored !== '';

$cssUrl = assetUrl('styles/custom.css');
$jsUrl = assetUrl('js/loanSign.js');
$toastJsUrl = assetUrl('js/toast.js');
$appDialogJsUrl = assetUrl('js/appDialog.js');
$logoPath = is_file(__DIR__.'/imgs/Logo.png') ? 'imgs/Logo.png' : '';

$brandBar = '#345A95';
if(isset($GLOBALS['optionsDB']['colorTitleBar'])) {
    $raw = (string)$GLOBALS['optionsDB']['colorTitleBar'];
    if(function_exists('normalizeHexColor')) {
        $hex = normalizeHexColor($raw);
        if($hex !== '') {
            $brandBar = $hex;
        }
        elseif(function_exists('getBrandPalette')) {
            $palette = getBrandPalette();
            $key = strtolower(trim($raw));
            if(isset($palette[$key])) {
                $hex = normalizeHexColor($palette[$key]);
                if($hex !== '') {
                    $brandBar = $hex;
                }
            }
        }
    }
}

$backHref = $isBorrowerOnly ? 'myinventories.php' : 'inventories.php';
$showAddress = !empty($ctx['needAddressField']);
$editAddress = $canEdit && !$locked && !empty($ctx['needAddressEditField']);
$editNotes = $canEdit && !$locked && !empty($ctx['needContractNotesField']);
$editLoanParams = $canEdit && !$locked && !empty($ctx['needLoanParamsField']);
if($editLoanParams) {
    $ctx['keepOptionalMoney'] = true;
    $ctx['clauses'] = LoanForm::buildClauses($ctx);
}
$editChecklist = $canEdit && !$locked && $kind === LoanForm::KIND_RETURN;
$hasEditableFields = $editAddress || $editNotes || $editChecklist || $editLoanParams;
$checklist = isset($ctx['checklist']) && is_array($ctx['checklist'])
    ? $ctx['checklist']
    : LoanForm::defaultChecklist();
$checkReturned = !empty($checklist['returned']);
$checkDeposit = !empty($checklist['depositReturned']);
$checkDeductions = isset($checklist['deductions']) ? (string)$checklist['deductions'] : '';
$checkNotes = isset($checklist['notes']) ? (string)$checklist['notes'] : '';
$contractNotes = isset($ctx['contractNotes']) ? (string)$ctx['contractNotes'] : '';
$startIso = LoanForm::normalizeDateYmd(isset($ctx['startDate']) ? $ctx['startDate'] : '');
if($startIso === '' || $startIso === null) {
    $startIso = date('Y-m-d');
}
$endIso = LoanForm::normalizeDateYmd(isset($ctx['endDate']) ? $ctx['endDate'] : '');
if($endIso === null) {
    $endIso = '';
}
$kautionInput = LoanForm::formatAmountInput(isset($ctx['kaution']) ? $ctx['kaution'] : 0);
$leihgebuehrInput = LoanForm::formatAmountInput(isset($ctx['leihgebuehr']) ? $ctx['leihgebuehr'] : 0);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $h(isset($ctx['printFileBase']) ? $ctx['printFileBase'] : $ctx['title']); ?></title>
  <link rel="stylesheet" href="<?php echo $h($cssUrl); ?>">
  <script src="<?php echo $h($toastJsUrl); ?>" defer></script>
  <script src="<?php echo $h($appDialogJsUrl); ?>" defer></script>
  <script src="<?php echo $h($jsUrl); ?>" defer></script>
</head>
<body class="loan-form-print">
<?php
$completeNotice = null;
if(!empty($_GET['complete'])) {
    $completeNotice = array(
        'title' => $kind === LoanForm::KIND_RETURN ? 'Protokoll abgeschlossen.' : 'Vertrag abgeschlossen.',
        'sub' => !empty($_GET['mailed']) ? 'Kopie wird versendet.' : 'Kopie konnte nicht versendet werden.',
        'error' => empty($_GET['mailed']),
    );
}
elseif(!empty($_GET['saved'])) {
    $completeNotice = array('title' => 'Gespeichert.', 'sub' => '', 'error' => false);
}
elseif(!empty($_GET['signcleared'])) {
    $completeNotice = array('title' => 'Unterschrift gelöscht.', 'sub' => '', 'error' => false);
}
elseif(!empty($_GET['restarted'])) {
    $completeNotice = array('title' => 'Workflow neu gestartet.', 'sub' => '', 'error' => false);
}
elseif(!empty($_GET['restarterr'])) {
    $completeNotice = array('title' => 'Workflow konnte nicht neu gestartet werden.', 'sub' => '', 'error' => true);
}
elseif(!empty($_GET['signerr'])) {
    $completeNotice = array('title' => 'Unterschrift konnte nicht gespeichert werden.', 'sub' => '', 'error' => true);
}
elseif(!empty($_GET['notified'])) {
    $completeNotice = array('title' => 'Zur Unterschrift gesendet.', 'sub' => '', 'error' => false);
}
elseif(!empty($_GET['notifyerr'])) {
    $completeNotice = array('title' => 'Senden nicht möglich.', 'sub' => '', 'error' => true);
}
$digitallyComplete = LoanForm::isDigitallyComplete($loan, $kind);
$lenderSig = LoanForm::getSignature($loan, $kind, LoanForm::ROLE_LENDER);
$borrowerSig = LoanForm::getSignature($loan, $kind, LoanForm::ROLE_BORROWER);
$canSignLender = !$digitallyComplete && LoanForm::userMaySign($userId, $loan, LoanForm::ROLE_LENDER) && $lenderSig === null;
$canSignBorrower = !$digitallyComplete && LoanForm::userMaySign($userId, $loan, LoanForm::ROLE_BORROWER) && $borrowerSig === null;
$canClearLender = LoanForm::userMayClearSignature($userId, $loan, LoanForm::ROLE_LENDER, $kind);
$canClearBorrower = LoanForm::userMayClearSignature($userId, $loan, LoanForm::ROLE_BORROWER, $kind);
$defaultSignPlace = LoanForm::defaultSignPlace();
$lenderRep = $lenderSig
    ? LoanForm::lenderRepresentativeFromSig($lenderSig)
    : ($canSignLender ? LoanForm::lenderRepresentativeLabel($userId) : '');
$scanLabel = LoanForm::storedContractLinkLabel($loan, $kind);
if($scanLabel === '') {
    $scanLabel = $kind === LoanForm::KIND_RETURN ? 'Scan Rückgabe' : 'Scan Vertrag';
}
$frozenSnapshotArticle = LoanForm::readFrozenSnapshotArticle($loan, $kind);
$canSendBorrower = $canEdit && !$digitallyComplete && $lenderSig !== null && $borrowerSig === null;
$canRestartWorkflow = LoanForm::userMayRestartWorkflow($userId, $loan, $kind);
?>
<?php if($completeNotice !== null) { ?>
<div class="app-toast-host app-toast-host--complete no-print" data-loan-complete-notice>
  <div class="app-toast<?php echo $completeNotice['error'] ? ' app-toast--error' : ' app-toast--success'; ?>" role="<?php echo $completeNotice['error'] ? 'alert' : 'status'; ?>"<?php
    echo $completeNotice['error'] ? '' : ' data-autodismiss="3500"';
?>>
    <div class="app-toast-body">
      <p class="loan-form-complete-title"><?php echo $h($completeNotice['title']); ?></p>
<?php   if($completeNotice['sub'] !== '') { ?>
      <p class="loan-form-complete-sub"><?php echo $h($completeNotice['sub']); ?></p>
<?php   } ?>
    </div>
<?php   if($completeNotice['error']) { ?>
    <button type="button" class="app-toast-close" aria-label="Hinweis schließen">&times;</button>
<?php   } ?>
  </div>
</div>
<?php } ?>
  <div class="loan-form-toolbar no-print">
    <div class="loan-form-toolbar-group">
      <a class="loan-form-btn" href="<?php echo $h($backHref); ?>">Zurück</a>
<?php if($hasEditableFields) { ?>
      <button type="submit" form="loan-form-fields" class="loan-form-btn loan-form-btn--primary">Speichern</button>
<?php } ?>
      <button type="button" class="loan-form-btn" onclick="window.print()">Drucken</button>
<?php if($canRestartWorkflow) { ?>
      <form class="loan-form-upload" method="POST" action="loan-form.php?loan=<?php echo (int)$ctx['loanId']; ?>&amp;kind=<?php echo $h($kind); ?>" data-confirm="<?php echo $h(LoanForm::restartWorkflowConfirmMessage()); ?>" data-confirm-title="Workflow neustarten" data-confirm-ok="Neustarten">
        <input type="hidden" name="loan" value="<?php echo (int)$ctx['loanId']; ?>">
        <input type="hidden" name="kind" value="<?php echo $h($kind); ?>">
        <input type="hidden" name="action" value="restartWorkflow">
        <button type="submit" class="loan-form-btn">Workflow neustarten</button>
      </form>
<?php } ?>
    </div>
<?php if($canEdit || $hasScan) { ?>
    <div class="loan-form-toolbar-group loan-form-toolbar-group--scan">
<?php   if($hasScan) { ?>
      <div class="loan-form-scan-pair">
      <a class="loan-form-btn loan-form-btn--scan" target="_blank" rel="noopener" href="loan-contract.php?loan=<?php echo (int)$ctx['loanId']; ?>&amp;kind=<?php echo $h($kind); ?>"><?php echo $h($scanLabel); ?></a>
<?php     if($canDeleteScan) { ?>
      <form class="loan-form-upload" method="POST" action="loan-contract.php" data-confirm="<?php echo $h(LoanForm::deleteStoredFileConfirmMessage($loan, $kind)); ?>" data-confirm-ok="Löschen">
        <input type="hidden" name="loan" value="<?php echo (int)$ctx['loanId']; ?>">
        <input type="hidden" name="kind" value="<?php echo $h($kind); ?>">
        <input type="hidden" name="action" value="deleteScan">
        <button type="submit" class="loan-form-btn" title="Scan löschen" aria-label="Scan löschen">Löschen</button>
      </form>
<?php     } ?>
      </div>
<?php   } ?>
<?php   if($canEdit) { ?>
      <form class="loan-form-upload" method="POST" action="loan-contract.php" enctype="multipart/form-data">
        <input type="hidden" name="loan" value="<?php echo (int)$ctx['loanId']; ?>">
        <input type="hidden" name="kind" value="<?php echo $h($kind); ?>">
        <input type="hidden" name="action" value="upload">
        <label class="loan-form-btn loan-form-btn--file" title="PDF, JPEG oder PNG">
          Datei
          <input type="file" name="scan" accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp,application/pdf,.pdf" required>
        </label>
        <button type="submit" class="loan-form-btn loan-form-btn--primary">Hochladen</button>
      </form>
<?php   } ?>
    </div>
<?php } ?>
  </div>

<?php if($hasEditableFields) { ?>
  <form id="loan-form-fields" method="POST" action="loan-form.php?loan=<?php echo (int)$ctx['loanId']; ?>&amp;kind=<?php echo $h($kind); ?>"<?php
    echo LoanForm::hasAnySignature($loan, $kind)
        ? ' data-confirm="Angaben ändern verwirft vorhandene Unterschriften." data-confirm-ok="Speichern"'
        : '';
?>>
    <input type="hidden" name="loan" value="<?php echo (int)$ctx['loanId']; ?>">
    <input type="hidden" name="kind" value="<?php echo $h($kind); ?>">
    <input type="hidden" name="action" value="saveFields">
<?php   if($editChecklist) { ?>
    <input type="hidden" name="checklist_save" value="1">
<?php   } ?>
<?php } ?>

<?php if($frozenSnapshotArticle !== null) { ?>
  <?php echo $frozenSnapshotArticle; ?>
<?php } else { ?>
  <article class="loan-form-doc" style="--loan-brand: <?php echo $h($brandBar); ?>;">
    <header class="loan-form-header">
      <div class="loan-form-brand">
<?php if($logoPath !== '') { ?>
        <img class="loan-form-logo" src="<?php echo $h($logoPath); ?>" alt="">
<?php } ?>
        <div class="loan-form-brand-text">
          <p class="loan-form-org"><?php echo $h($ctx['orgName']); ?></p>
          <h1><?php echo $h($ctx['title']); ?></h1>
        </div>
      </div>
      <p class="loan-form-meta">Leihe Nr. <?php echo (int)$ctx['loanId']; ?></p>
    </header>

    <section class="loan-form-section loan-form-panel">
      <h2>Parteien</h2>
      <div class="loan-form-parties">
        <div class="loan-form-party">
          <p class="loan-form-party-role">Verleiher</p>
          <p class="loan-form-party-name"><strong class="loan-form-em"><?php echo $h($ctx['orgName']); ?></strong></p>
          <div class="loan-form-field-row loan-form-field-row--stack">
            <span class="loan-form-field-label">Adresse</span>
<?php if($ctx['orgAddress'] !== '') { ?>
            <p class="loan-form-address-value"><?php echo $h($ctx['orgAddress']); ?></p>
<?php } else { ?>
            <span class="loan-form-blank loan-form-blank--address"></span>
<?php } ?>
          </div>
        </div>
        <div class="loan-form-party">
          <p class="loan-form-party-role">Entleiher</p>
          <p class="loan-form-party-name"><strong class="loan-form-em"><?php echo $h($ctx['borrowerName']); ?></strong></p>
<?php if($ctx['borrowerEmail'] !== '') { ?>
          <p class="loan-form-muted"><?php echo $h($ctx['borrowerEmail']); ?></p>
<?php } ?>
<?php if($showAddress) { ?>
          <div class="loan-form-field-row loan-form-field-row--stack">
            <span class="loan-form-field-label">Adresse</span>
<?php   if($editAddress) { ?>
            <textarea id="loan-adresse" class="loan-form-input loan-form-input--address no-print" name="BorrowerAddress" rows="2" aria-label="Adresse"><?php echo $h($ctx['borrowerAddress']); ?></textarea>
<?php     if($ctx['borrowerAddress'] !== '') { ?>
            <p class="loan-form-address-value loan-form-print-only"><?php echo nl2br($h($ctx['borrowerAddress'])); ?></p>
<?php     } else { ?>
            <span class="loan-form-blank loan-form-blank--address loan-form-print-only" aria-hidden="true"></span>
<?php     } ?>
<?php   } elseif($ctx['borrowerAddress'] !== '') { ?>
            <p class="loan-form-address-value"><?php echo nl2br($h($ctx['borrowerAddress'])); ?></p>
<?php   } else { ?>
            <span class="loan-form-blank loan-form-blank--address"></span>
<?php   } ?>
          </div>
<?php } ?>
        </div>
      </div>
    </section>

    <section class="loan-form-section loan-form-panel loan-form-leihgut">
      <h2>Leihgut</h2>
      <p class="loan-form-item-title">
        <span class="loan-form-item-label">Leihgegenstand</span>
        <span class="loan-form-item-value"><?php echo $h($ctx['itemLabel']); ?></span>
      </p>
      <dl class="loan-form-dl loan-form-dl--2col">
<?php foreach($ctx['itemDetails'] as $row) { ?>
        <div><dt><?php echo $h($row['label']); ?></dt><dd><?php echo $h($row['value']); ?></dd></div>
<?php } ?>
        <div>
          <dt>Leihbeginn</dt>
          <dd>
<?php if($editLoanParams) { ?>
            <input id="loan-start-date" class="loan-form-input loan-form-input--short no-print" type="date" name="StartDate" value="<?php echo $h($startIso); ?>" required aria-label="Leihbeginn">
            <span class="loan-form-print-only" data-loan-start-print><?php echo $h($ctx['startDateDe']); ?></span>
<?php } else { ?>
            <?php echo $h($ctx['startDateDe']); ?>
<?php } ?>
          </dd>
        </div>
        <div>
          <dt><?php echo $ctx['hasFixedEnd'] || $editLoanParams ? 'Leihende' : 'Dauer'; ?></dt>
          <dd>
<?php if($editLoanParams) { ?>
            <input id="loan-end-date" class="loan-form-input loan-form-input--short no-print" type="date" name="EndDate" value="<?php echo $h($endIso); ?>" aria-label="Leihende">
            <span class="loan-form-print-only" data-loan-end-print><?php echo $ctx['hasFixedEnd'] ? $h($ctx['endDateDe']) : 'unbefristet'; ?></span>
<?php } elseif($ctx['hasFixedEnd']) { ?>
            <?php echo $h($ctx['endDateDe']); ?>
<?php } else { ?>
            unbefristet
<?php } ?>
          </dd>
        </div>
<?php if($editLoanParams || $ctx['hasKaution']) { ?>
        <div id="loan-kaution-row"<?php echo ($editLoanParams && !$ctx['hasKaution']) ? ' class="no-print"' : ''; ?>>
          <dt>Kaution</dt>
          <dd>
<?php   if($editLoanParams) { ?>
            <input id="loan-kaution-form" class="loan-form-input loan-form-input--short no-print" type="text" name="Kaution" inputmode="decimal" placeholder="0,00" value="<?php echo $h($kautionInput); ?>" aria-label="Kaution">
            <span class="loan-form-muted no-print">€</span>
            <span class="loan-form-print-only" data-loan-kaution-print><?php echo $ctx['hasKaution'] ? $h($ctx['kautionFormatted']) : ''; ?></span>
<?php   } else { ?>
            <?php echo $h($ctx['kautionFormatted']); ?>
<?php   } ?>
          </dd>
        </div>
<?php } ?>
<?php if($editLoanParams || !empty($ctx['hasLeihgebuehr'])) { ?>
        <div id="loan-leihgebuehr-row"<?php echo ($editLoanParams && empty($ctx['hasLeihgebuehr'])) ? ' class="no-print"' : ''; ?>>
          <dt>Leihgebühr</dt>
          <dd>
<?php   if($editLoanParams) { ?>
            <input id="loan-leihgebuehr-form" class="loan-form-input loan-form-input--short no-print" type="text" name="Leihgebuehr" inputmode="decimal" placeholder="0,00" value="<?php echo $h($leihgebuehrInput); ?>" aria-label="Leihgebühr">
            <span class="loan-form-muted no-print">€</span>
            <span class="loan-form-print-only" data-loan-leihgebuehr-print><?php echo !empty($ctx['hasLeihgebuehr']) ? $h($ctx['leihgebuehrFormatted']) : ''; ?></span>
<?php   } else { ?>
            <?php echo $h($ctx['leihgebuehrFormatted']); ?>
<?php   } ?>
          </dd>
        </div>
<?php } ?>
      </dl>
    </section>

    <div class="loan-form-body">
      <section class="loan-form-section loan-form-panel loan-form-terms">
        <h2><?php echo $kind === LoanForm::KIND_RETURN ? 'Protokoll' : 'Vertragsbedingungen'; ?></h2>
        <ol class="loan-form-clauses">
<?php
foreach($ctx['clauses'] as $clause) {
    $liId = '';
    $liHidden = false;
    if($kind === LoanForm::KIND_LOAN && strpos($clause, 'data-loan-fill="start"') !== false) {
        $liId = 'loan-duration-clause';
    }
    if(strpos($clause, 'data-loan-fill="fee"') !== false) {
        $liId = 'loan-fee-clause';
        $liHidden = empty($ctx['hasLeihgebuehr']);
    }
    if(strpos($clause, 'data-loan-fill="kaution"') !== false) {
        $liId = ($kind === LoanForm::KIND_RETURN) ? 'loan-return-deposit-clause' : 'loan-deposit-clause';
        $liHidden = empty($ctx['hasKaution']);
    }
    $attr = '';
    if($liId !== '') {
        $attr .= ' id="'.$liId.'"';
    }
    if($liHidden) {
        $attr .= ' hidden';
    }
?>
          <li<?php echo $attr; ?>><?php echo $clause; ?></li>
<?php } ?>
        </ol>
<?php if($kind === LoanForm::KIND_LOAN && $editLoanParams) { ?>
        <template id="loan-duration-tpl-open"><?php echo LoanForm::durationPhraseHtml('', false); ?></template>
        <template id="loan-duration-tpl-fixed"><?php echo LoanForm::durationPhraseHtml('__END__', true); ?></template>
        <template id="loan-returndue-tpl-open"><?php echo LoanForm::returnDuePhraseHtml('', false); ?></template>
        <template id="loan-returndue-tpl-fixed"><?php echo LoanForm::returnDuePhraseHtml('__END__', true); ?></template>
<?php } ?>
      </section>

      <section class="loan-form-section loan-form-panel">
        <h2>Zusätzliche Vereinbarungen</h2>
        <div class="loan-form-field-row loan-form-field-row--stack">
          <span class="loan-form-field-label">Bemerkungen</span>
<?php if($editNotes) { ?>
          <textarea id="loan-contract-notes" class="loan-form-input loan-form-input--address no-print" name="ContractNotes" rows="4" aria-label="Bemerkungen"><?php echo $h($contractNotes); ?></textarea>
<?php   if($contractNotes !== '') { ?>
          <p class="loan-form-address-value loan-form-print-only"><?php echo nl2br($h($contractNotes)); ?></p>
<?php   } else { ?>
          <span class="loan-form-blank loan-form-blank--address loan-form-print-only" aria-hidden="true"></span>
<?php   } ?>
<?php } elseif($contractNotes !== '') { ?>
          <p class="loan-form-address-value"><?php echo nl2br($h($contractNotes)); ?></p>
<?php } else { ?>
          <span class="loan-form-blank loan-form-blank--address"></span>
<?php } ?>
        </div>
      </section>

<?php if($kind === LoanForm::KIND_RETURN) { ?>
      <section class="loan-form-section loan-form-panel">
        <h2>Checkliste</h2>
        <ul class="loan-form-checks">
          <li>
<?php   if($editChecklist) { ?>
            <label class="loan-form-check">
              <input type="checkbox" name="checklist_returned" value="1"<?php echo $checkReturned ? ' checked' : ''; ?>>
              <span>Leihgut zurückgegeben</span>
            </label>
<?php   } else { ?>
            <span class="loan-form-box<?php echo $checkReturned ? ' loan-form-box--on' : ''; ?>" aria-hidden="true"></span>
            <span>Leihgut zurückgegeben</span>
<?php   } ?>
          </li>
<?php   if($ctx['hasKaution']) { ?>
          <li>
<?php     if($editChecklist) { ?>
            <label class="loan-form-check">
              <input type="checkbox" name="checklist_depositReturned" value="1"<?php echo $checkDeposit ? ' checked' : ''; ?>>
              <span><strong class="loan-form-em">Kaution</strong> ausgezahlt (<strong class="loan-form-em"><?php echo $h($ctx['kautionFormatted']); ?></strong>)</span>
            </label>
<?php     } else { ?>
            <span class="loan-form-box<?php echo $checkDeposit ? ' loan-form-box--on' : ''; ?>" aria-hidden="true"></span>
            <span><strong class="loan-form-em">Kaution</strong> ausgezahlt (<strong class="loan-form-em"><?php echo $h($ctx['kautionFormatted']); ?></strong>)</span>
<?php     } ?>
          </li>
          <li class="loan-form-check-note-row">
            <span class="loan-form-field-label">Abzüge</span>
<?php     if($editChecklist) { ?>
            <input class="loan-form-input loan-form-input--inline" type="text" name="checklist_deductions" value="<?php echo $h($checkDeductions); ?>" autocomplete="off">
<?php     } elseif($checkDeductions !== '') { ?>
            <span class="loan-form-field-value"><?php echo $h($checkDeductions); ?></span>
<?php     } else { ?>
            <span class="loan-form-blank loan-form-blank--line"></span>
<?php     } ?>
          </li>
<?php   } ?>
          <li class="loan-form-check-note-row">
            <span class="loan-form-field-label">Bemerkungen</span>
<?php   if($editChecklist) { ?>
            <input class="loan-form-input loan-form-input--inline" type="text" name="checklist_notes" value="<?php echo $h($checkNotes); ?>" autocomplete="off">
<?php   } elseif($checkNotes !== '') { ?>
            <span class="loan-form-field-value"><?php echo $h($checkNotes); ?></span>
<?php   } else { ?>
            <span class="loan-form-blank loan-form-blank--line"></span>
<?php   } ?>
          </li>
        </ul>
      </section>
<?php } ?>

<?php if($hasEditableFields) { ?>
    </form>
<?php } ?>

      <section class="loan-form-section loan-form-signatures">
        <h2>Unterschriften</h2>
        <div class="loan-form-sign-grid">
<?php
$signSlots = array(
    array(
        'role' => LoanForm::ROLE_LENDER,
        'label' => $ctx['orgName'],
        'rep' => $lenderRep,
        'roleLabel' => 'Verleiher',
        'sig' => $lenderSig,
        'can' => $canSignLender,
        'canClear' => $canClearLender,
    ),
    array(
        'role' => LoanForm::ROLE_BORROWER,
        'label' => $ctx['borrowerName'],
        'rep' => '',
        'roleLabel' => 'Entleiher',
        'sig' => $borrowerSig,
        'can' => $canSignBorrower,
        'canSend' => $canSendBorrower,
        'canClear' => $canClearBorrower,
    ),
);
foreach($signSlots as $slot) {
    $when = $slot['sig'] ? LoanForm::formatSignPlaceDate($slot['sig']) : '';
?>
          <div class="loan-form-sign">
<?php   if($slot['sig']) { ?>
            <div class="loan-form-sign-digital">
              <p class="loan-form-sign-caption"><?php echo $h($when); ?></p>
              <img class="loan-form-sign-img" src="<?php echo $h(LoanForm::signatureUrl($loan, $kind, $slot['role'])); ?>" alt="Unterschrift <?php echo $h($slot['roleLabel']); ?>">
<?php     if(!empty($slot['canClear'])) { ?>
              <form class="loan-form-sign-clear no-print" method="POST" action="loan-form.php?loan=<?php echo (int)$ctx['loanId']; ?>&amp;kind=<?php echo $h($kind); ?>" data-confirm="Unterschrift löschen?" data-confirm-ok="Löschen">
                <input type="hidden" name="loan" value="<?php echo (int)$ctx['loanId']; ?>">
                <input type="hidden" name="kind" value="<?php echo $h($kind); ?>">
                <input type="hidden" name="action" value="clearSign">
                <input type="hidden" name="role" value="<?php echo $h($slot['role']); ?>">
                <button type="submit" class="loan-form-btn">Löschen</button>
              </form>
<?php     } ?>
            </div>
<?php   } else {
        $showSign = !empty($slot['can']);
        $showSend = !empty($slot['canSend']);
        if($showSign || $showSend) {
?>
            <div class="loan-form-sign-actions no-print">
<?php       if($showSign) { ?>
              <button type="button" class="loan-form-btn loan-form-btn--primary" data-loan-sign-open
                data-loan="<?php echo (int)$ctx['loanId']; ?>"
                data-kind="<?php echo $h($kind); ?>"
                data-role="<?php echo $h($slot['role']); ?>"
                data-role-label="<?php echo $h($slot['roleLabel']); ?>"
                data-place="<?php echo $h($defaultSignPlace); ?>">Unterschreiben</button>
<?php       } ?>
<?php       if($showSend) { ?>
              <form class="loan-form-sign-send" method="POST" action="loan-form.php?loan=<?php echo (int)$ctx['loanId']; ?>&amp;kind=<?php echo $h($kind); ?>">
                <input type="hidden" name="loan" value="<?php echo (int)$ctx['loanId']; ?>">
                <input type="hidden" name="kind" value="<?php echo $h($kind); ?>">
                <input type="hidden" name="action" value="notifyBorrower">
                <button type="submit" class="loan-form-btn">Zur Unterschrift senden</button>
              </form>
<?php       } ?>
            </div>
<?php   } ?>
<?php   } ?>
<?php   if(!$slot['sig']) { ?>
            <div class="loan-form-sign-manual<?php echo $slot['can'] ? ' loan-form-print-only' : ''; ?>">
              <div class="loan-form-sign-space loan-form-sign-space--date"></div>
              <p class="loan-form-sign-caption">Ort, Datum</p>
              <div class="loan-form-sign-space loan-form-sign-space--sig"></div>
            </div>
<?php   } ?>
            <p class="loan-form-sign-caption"><strong class="loan-form-em"><?php echo $h($slot['label']); ?></strong><?php
    if(!empty($slot['rep'])) {
        echo '<span class="loan-form-sign-iv">'.$h($slot['rep']).'</span>';
    }
?><span class="loan-form-sign-role"><?php echo $h($slot['roleLabel']); ?></span></p>
          </div>
<?php } ?>
        </div>
      </section>
    </div>
  </article>
<?php } ?>

<?php if($canSignLender || $canSignBorrower) { ?>
<div id="loanSignModal" class="w3-modal loan-form-sign-modal no-print" hidden role="dialog" aria-modal="true" aria-labelledby="loanSignTitle">
  <div class="w3-modal-content loan-form-sign-modal-panel">
    <form class="profile-shell modal-shell" method="POST" action="loan-form.php" data-loan-sign>
      <header class="profile-hero">
        <div class="profile-hero-text">
          <p class="profile-kicker">Unterschrift</p>
          <h2 class="profile-title" id="loanSignTitle">Verleiher</h2>
        </div>
        <div class="profile-hero-actions">
          <button type="button" class="modal-close" data-loan-sign-close aria-label="Schließen">&times;</button>
        </div>
      </header>
      <div class="loan-form-sign-modal-body">
        <input type="hidden" name="loan" value="">
        <input type="hidden" name="kind" value="">
        <input type="hidden" name="action" value="sign">
        <input type="hidden" name="role" value="">
        <input type="hidden" name="signature" value="">
        <div class="loan-form-sign-meta">
          <label class="loan-form-sign-meta-field">
            <span class="loan-form-field-label">Ort</span>
            <input class="loan-form-input" type="text" name="place" value="" maxlength="80" autocomplete="off">
          </label>
        </div>
        <div class="loan-form-sign-canvas-wrap">
          <canvas class="loan-form-canvas" width="720" height="280" aria-label="Unterschrift"></canvas>
        </div>
        <div class="loan-form-sign-pad-actions">
          <button type="button" class="loan-form-btn" data-loan-sign-clear>Löschen</button>
          <button type="submit" class="loan-form-btn loan-form-btn--primary">Unterschreiben</button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php } ?>
<div id="appConfirmModal" class="w3-modal" role="dialog" aria-modal="true" aria-labelledby="appConfirmTitle" style="display:none;">
  <div class="w3-modal-content">
    <div class="profile-shell modal-shell confirm-delete-modal">
      <header class="profile-hero">
        <div class="profile-hero-text">
          <p class="profile-kicker" id="appConfirmKicker" style="display:none;"></p>
          <h2 class="profile-title" id="appConfirmTitle">Bestätigen</h2>
        </div>
        <div class="profile-hero-actions">
          <button type="button" class="modal-close w3-button" id="appConfirmClose" aria-label="Schließen">&times;</button>
        </div>
      </header>
      <div class="confirm-delete-body">
        <p class="profile-value" id="appConfirmMessage"></p>
        <div class="profile-actions profile-actions--confirm">
          <div class="profile-actions-primary">
            <button type="button" class="w3-btn profile-btn-primary w3-border w3-mobile" id="appConfirmOk">OK</button>
          </div>
          <button type="button" class="w3-btn w3-border w3-mobile" id="appConfirmCancel">Abbrechen</button>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
