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
    LoanForm::saveContractFields($loan, $_POST);
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

$assetV = isset($GLOBALS['version']['Hash']) ? $GLOBALS['version']['Hash'] : '0';
$cssMtime = @filemtime(__DIR__.'/styles/custom.css');
$cssUrl = 'styles/custom.css?'.$assetV.'-'.$cssMtime;
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
$showMemberNr = !empty($ctx['isMember']);
$editMemberNr = $canEdit && !empty($ctx['needMitgliedsnummerField']);
$showAddress = !empty($ctx['needAddressField']);
$editAddress = $canEdit && $showAddress;
$hasEditableFields = $editMemberNr || $editAddress;

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $h($ctx['title']); ?> – <?php echo $h($ctx['orgName']); ?></title>
  <link rel="stylesheet" href="<?php echo $h($cssUrl); ?>">
</head>
<body class="loan-form-print">
  <div class="loan-form-toolbar no-print">
    <a class="loan-form-btn" href="<?php echo $h($backHref); ?>">Zurück</a>
    <button type="button" class="loan-form-btn" onclick="window.print()">Drucken / als PDF speichern</button>
<?php if($hasEditableFields) { ?>
    <button type="submit" form="loan-form-fields" class="loan-form-btn loan-form-btn--primary">Speichern</button>
<?php } ?>
<?php if($canEdit) { ?>
    <form class="loan-form-upload" method="POST" action="loan-contract.php" enctype="multipart/form-data">
      <input type="hidden" name="loan" value="<?php echo (int)$ctx['loanId']; ?>">
      <input type="hidden" name="kind" value="<?php echo $h($kind); ?>">
      <input type="hidden" name="action" value="upload">
      <label class="loan-form-btn loan-form-btn--file">
        Scan hochladen
        <input type="file" name="scan" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,application/pdf,image/*" required>
      </label>
      <button type="submit" class="loan-form-btn">Ablegen</button>
    </form>
<?php } ?>
<?php if($hasScan) { ?>
    <a class="loan-form-btn" href="loan-contract.php?loan=<?php echo (int)$ctx['loanId']; ?>&amp;kind=<?php echo $h($kind); ?>">Scan öffnen</a>
<?php } ?>
  </div>

<?php if($hasEditableFields) { ?>
  <form id="loan-form-fields" method="POST" action="loan-form.php?loan=<?php echo (int)$ctx['loanId']; ?>&amp;kind=<?php echo $h($kind); ?>">
    <input type="hidden" name="loan" value="<?php echo (int)$ctx['loanId']; ?>">
    <input type="hidden" name="kind" value="<?php echo $h($kind); ?>">
    <input type="hidden" name="action" value="saveFields">
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
<?php if($showMemberNr) { ?>
          <div class="loan-form-field-row">
            <label class="loan-form-field-label" for="loan-mitgliedsnummer">Mitgliedsnummer</label>
<?php   if($editMemberNr) { ?>
            <input id="loan-mitgliedsnummer" class="loan-form-input loan-form-input--short" type="text" name="Mitgliedsnummer" inputmode="numeric" value="" autocomplete="off">
<?php   } elseif($ctx['mitgliedsnummer'] !== '') { ?>
            <strong class="loan-form-em"><?php echo $h($ctx['mitgliedsnummer']); ?></strong>
<?php   } else { ?>
            <span class="loan-form-blank loan-form-blank--short"></span>
<?php   } ?>
          </div>
<?php } ?>
<?php if($ctx['borrowerEmail'] !== '') { ?>
          <p class="loan-form-muted"><?php echo $h($ctx['borrowerEmail']); ?></p>
<?php } ?>
<?php if($showAddress) { ?>
          <div class="loan-form-field-row loan-form-field-row--stack">
            <label class="loan-form-field-label" for="loan-adresse">Adresse</label>
<?php   if($editAddress) { ?>
            <textarea id="loan-adresse" class="loan-form-input loan-form-input--address" name="BorrowerAddress" rows="2"><?php echo $h($ctx['borrowerAddress']); ?></textarea>
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
        Leihgegenstand
        <strong class="loan-form-em"><?php echo $h($ctx['itemLabel']); ?></strong>
      </p>
      <dl class="loan-form-dl loan-form-dl--2col">
<?php foreach($ctx['itemDetails'] as $row) { ?>
        <div><dt><?php echo $h($row['label']); ?></dt><dd><?php echo $h($row['value']); ?></dd></div>
<?php } ?>
        <div>
          <dt>Leihbeginn</dt>
          <dd><strong class="loan-form-em"><?php echo $h($ctx['startDateDe']); ?></strong></dd>
        </div>
<?php if($ctx['hasFixedEnd']) { ?>
        <div>
          <dt>Leihende</dt>
          <dd><strong class="loan-form-em"><?php echo $h($ctx['endDateDe']); ?></strong></dd>
        </div>
<?php } else { ?>
        <div><dt>Dauer</dt><dd>unbefristet</dd></div>
<?php } ?>
<?php if($ctx['hasKaution']) { ?>
        <div>
          <dt><strong class="loan-form-em">Kaution</strong></dt>
          <dd><strong class="loan-form-em"><?php echo $h($ctx['kautionFormatted']); ?></strong></dd>
        </div>
<?php } ?>
<?php if(!empty($ctx['hasLeihgebuehr'])) { ?>
        <div>
          <dt><strong class="loan-form-em">Leihgebühr</strong></dt>
          <dd><strong class="loan-form-em"><?php echo $h($ctx['leihgebuehrFormatted']); ?></strong></dd>
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

<?php if($kind === LoanForm::KIND_RETURN) { ?>
      <section class="loan-form-section loan-form-panel">
        <h2>Checkliste</h2>
        <ul class="loan-form-checks">
          <li><span class="loan-form-box" aria-hidden="true"></span> Leihgut zurückgegeben</li>
<?php   if($ctx['hasKaution']) { ?>
          <li><span class="loan-form-box" aria-hidden="true"></span> <strong class="loan-form-em">Kaution</strong> zurückgezahlt (<strong class="loan-form-em"><?php echo $h($ctx['kautionFormatted']); ?></strong>)</li>
          <li><span class="loan-form-box" aria-hidden="true"></span> Abzüge (Betrag / Grund): <span class="loan-form-blank loan-form-blank--line"></span></li>
<?php   } ?>
          <li><span class="loan-form-box" aria-hidden="true"></span> Mängel / Bemerkungen: <span class="loan-form-blank loan-form-blank--line"></span></li>
        </ul>
      </section>
<?php } ?>

      <section class="loan-form-section loan-form-signatures">
        <h2>Unterschriften</h2>
        <div class="loan-form-sign-grid">
          <div class="loan-form-sign">
            <div class="loan-form-sign-space"></div>
            <p class="loan-form-sign-caption">Ort, Datum</p>
            <div class="loan-form-sign-space"></div>
            <p class="loan-form-sign-caption"><strong class="loan-form-em"><?php echo $h($ctx['orgName']); ?></strong><span class="loan-form-sign-role">Verleiher</span></p>
          </div>
          <div class="loan-form-sign">
            <div class="loan-form-sign-space"></div>
            <p class="loan-form-sign-caption">Ort, Datum</p>
            <div class="loan-form-sign-space"></div>
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
