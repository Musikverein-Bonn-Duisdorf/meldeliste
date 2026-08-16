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
if(!LoanForm::userMayView($userId)) {
    denyAccess();
}

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

$canEdit = LoanForm::userMayEdit($userId);

if($canEdit && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && isset($_POST['action']) && (string)$_POST['action'] === 'saveFields') {
    LoanForm::saveContractFields($loan, $_POST, $kind);
    $loan->load_by_id($loanId);
    header('Location: loan-form.php?loan='.$loanId.'&kind='.rawurlencode($kind));
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

$backHref = 'inventories.php';
$showAddress = !empty($ctx['needAddressField']);
$editAddress = $canEdit && !empty($ctx['needAddressEditField']);
$editNotes = $canEdit && !empty($ctx['needContractNotesField']);
$editLoanParams = $canEdit && !empty($ctx['needLoanParamsField']);
$editChecklist = $canEdit && $kind === LoanForm::KIND_RETURN;
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

$scanLabel = $kind === LoanForm::KIND_RETURN ? 'Scan Rückgabe' : 'Scan Vertrag';

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $h(isset($ctx['printFileBase']) ? $ctx['printFileBase'] : $ctx['title']); ?></title>
  <link rel="stylesheet" href="<?php echo $h($cssUrl); ?>">
</head>
<body class="loan-form-print">
  <div class="loan-form-toolbar no-print">
    <div class="loan-form-toolbar-group">
      <a class="loan-form-btn" href="<?php echo $h($backHref); ?>">Zurück</a>
<?php if($hasEditableFields) { ?>
      <button type="submit" form="loan-form-fields" class="loan-form-btn loan-form-btn--primary">Speichern</button>
<?php } ?>
      <button type="button" class="loan-form-btn" onclick="window.print()">Drucken</button>
    </div>
<?php if($canEdit || $hasScan) { ?>
    <div class="loan-form-toolbar-group loan-form-toolbar-group--scan">
<?php   if($hasScan) { ?>
      <div class="loan-form-scan-pair">
      <a class="loan-form-btn loan-form-btn--scan" target="_blank" rel="noopener" href="loan-contract.php?loan=<?php echo (int)$ctx['loanId']; ?>&amp;kind=<?php echo $h($kind); ?>"><?php echo $h($scanLabel); ?></a>
<?php     if($canEdit) { ?>
      <form class="loan-form-upload" method="POST" action="loan-contract.php" onsubmit="return confirm('Scan löschen? Die Leihe bleibt erhalten.');">
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
  <form id="loan-form-fields" method="POST" action="loan-form.php?loan=<?php echo (int)$ctx['loanId']; ?>&amp;kind=<?php echo $h($kind); ?>">
    <input type="hidden" name="loan" value="<?php echo (int)$ctx['loanId']; ?>">
    <input type="hidden" name="kind" value="<?php echo $h($kind); ?>">
    <input type="hidden" name="action" value="saveFields">
<?php   if($editChecklist) { ?>
    <input type="hidden" name="checklist_save" value="1">
<?php   } ?>
<?php } ?>

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
            <span class="loan-form-print-only"><?php echo $h($ctx['startDateDe']); ?></span>
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
            <span class="loan-form-print-only"><?php echo $ctx['hasFixedEnd'] ? $h($ctx['endDateDe']) : 'unbefristet'; ?></span>
<?php } elseif($ctx['hasFixedEnd']) { ?>
            <?php echo $h($ctx['endDateDe']); ?>
<?php } else { ?>
            unbefristet
<?php } ?>
          </dd>
        </div>
<?php if($editLoanParams || $ctx['hasKaution']) { ?>
        <div<?php echo ($editLoanParams && !$ctx['hasKaution']) ? ' class="no-print"' : ''; ?>>
          <dt>Kaution</dt>
          <dd>
<?php   if($editLoanParams) { ?>
            <input id="loan-kaution-form" class="loan-form-input loan-form-input--short no-print" type="text" name="Kaution" inputmode="decimal" placeholder="0,00" value="<?php echo $h($kautionInput); ?>" aria-label="Kaution">
            <span class="loan-form-muted no-print">€</span>
<?php     if($ctx['hasKaution']) { ?>
            <span class="loan-form-print-only"><?php echo $h($ctx['kautionFormatted']); ?></span>
<?php     } ?>
<?php   } else { ?>
            <?php echo $h($ctx['kautionFormatted']); ?>
<?php   } ?>
          </dd>
        </div>
<?php } ?>
<?php if($editLoanParams || !empty($ctx['hasLeihgebuehr'])) { ?>
        <div<?php echo ($editLoanParams && empty($ctx['hasLeihgebuehr'])) ? ' class="no-print"' : ''; ?>>
          <dt>Leihgebühr</dt>
          <dd>
<?php   if($editLoanParams) { ?>
            <input id="loan-leihgebuehr-form" class="loan-form-input loan-form-input--short no-print" type="text" name="Leihgebuehr" inputmode="decimal" placeholder="0,00" value="<?php echo $h($leihgebuehrInput); ?>" aria-label="Leihgebühr">
            <span class="loan-form-muted no-print">€</span>
<?php     if(!empty($ctx['hasLeihgebuehr'])) { ?>
            <span class="loan-form-print-only"><?php echo $h($ctx['leihgebuehrFormatted']); ?></span>
<?php     } ?>
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
<?php foreach($ctx['clauses'] as $clause) { ?>
          <li><?php echo $clause; ?></li>
<?php } ?>
        </ol>
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
              <span><strong class="loan-form-em">Kaution</strong> zurückgezahlt (<strong class="loan-form-em"><?php echo $h($ctx['kautionFormatted']); ?></strong>)</span>
            </label>
<?php     } else { ?>
            <span class="loan-form-box<?php echo $checkDeposit ? ' loan-form-box--on' : ''; ?>" aria-hidden="true"></span>
            <span><strong class="loan-form-em">Kaution</strong> zurückgezahlt (<strong class="loan-form-em"><?php echo $h($ctx['kautionFormatted']); ?></strong>)</span>
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

      <section class="loan-form-section loan-form-signatures">
        <h2>Unterschriften</h2>
        <div class="loan-form-sign-grid">
          <div class="loan-form-sign">
            <div class="loan-form-sign-space loan-form-sign-space--date"></div>
            <p class="loan-form-sign-caption">Ort, Datum</p>
            <div class="loan-form-sign-space loan-form-sign-space--sig"></div>
            <p class="loan-form-sign-caption"><strong class="loan-form-em"><?php echo $h($ctx['orgName']); ?></strong><span class="loan-form-sign-role">Verleiher</span></p>
          </div>
          <div class="loan-form-sign">
            <div class="loan-form-sign-space loan-form-sign-space--date"></div>
            <p class="loan-form-sign-caption">Ort, Datum</p>
            <div class="loan-form-sign-space loan-form-sign-space--sig"></div>
            <p class="loan-form-sign-caption"><strong class="loan-form-em"><?php echo $h($ctx['borrowerName']); ?></strong><span class="loan-form-sign-role">Entleiher</span></p>
          </div>
        </div>
      </section>
    </div>
  </article>

<?php if($hasEditableFields) { ?>
  </form>
<?php } ?>
</body>
</html>
