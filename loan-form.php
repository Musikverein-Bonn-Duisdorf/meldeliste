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

if(!LoanForm::userMayView(isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0)) {
    denyAccess();
}

$loanId = isset($_GET['loan']) ? (int)$_GET['loan'] : 0;
$kind = LoanForm::normalizeKind(isset($_GET['kind']) ? $_GET['kind'] : LoanForm::KIND_LOAN);

$loan = new InventoriesLoan;
if($loanId > 0) {
    $loan->load_by_id($loanId);
}
$ctx = $loan->Index ? LoanForm::buildContext($loan, $kind) : null;
if(!$ctx) {
    denyAccess('Leihe nicht gefunden.');
}

$canEdit = LoanForm::userMayEdit(isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0);
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
$partyLabel = 'Entleiher';

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

  <article class="loan-form-doc">
    <header class="loan-form-header" style="border-color: <?php echo $h($brandBar); ?>;">
      <div class="loan-form-brand">
<?php if($logoPath !== '') { ?>
        <img class="loan-form-logo" src="<?php echo $h($logoPath); ?>" alt="">
<?php } ?>
        <div>
          <p class="loan-form-org"><?php echo $h($ctx['orgName']); ?></p>
          <h1><?php echo $h($ctx['title']); ?></h1>
        </div>
      </div>
      <p class="loan-form-meta">Leihe Nr. <?php echo (int)$ctx['loanId']; ?></p>
    </header>

    <section class="loan-form-section">
      <h2>Parteien</h2>
      <dl class="loan-form-dl loan-form-dl--parties">
        <div>
          <dt>Verleiher</dt>
          <dd><strong class="loan-form-em"><?php echo $h($ctx['orgName']); ?></strong></dd>
        </div>
        <div>
          <dt><?php echo $h($partyLabel); ?></dt>
          <dd>
            <strong class="loan-form-em"><?php echo $h($ctx['borrowerName']); ?></strong>
<?php if($ctx['isMember']) { ?>
            <div class="loan-form-sub">
              Mitgliedsnummer:
<?php   if($ctx['mitgliedsnummer'] !== '') { ?>
              <strong class="loan-form-em"><?php echo $h($ctx['mitgliedsnummer']); ?></strong>
<?php   } else { ?>
              <span class="loan-form-blank loan-form-blank--short"></span>
<?php   } ?>
            </div>
<?php } ?>
<?php if($ctx['borrowerEmail'] !== '') { ?>
            <div class="loan-form-muted"><?php echo $h($ctx['borrowerEmail']); ?></div>
<?php } ?>
<?php if(!empty($ctx['needAddressField'])) { ?>
            <div class="loan-form-address">
              <span class="loan-form-address-label">Adresse</span>
              <span class="loan-form-blank loan-form-blank--address"></span>
            </div>
<?php } ?>
          </dd>
        </div>
      </dl>
    </section>

    <section class="loan-form-section loan-form-leihgut">
      <h2>Leihgut</h2>
      <p class="loan-form-item-title">
        Leihgegenstand:
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
      <section class="loan-form-section loan-form-terms">
        <h2><?php echo $kind === LoanForm::KIND_RETURN ? 'Protokoll' : 'Vertragsbedingungen'; ?></h2>
        <ol class="loan-form-clauses">
<?php foreach($ctx['clauses'] as $clause) { ?>
          <li><?php echo $h($clause); ?></li>
<?php } ?>
        </ol>
      </section>

<?php if($kind === LoanForm::KIND_RETURN) { ?>
      <section class="loan-form-section">
        <h2>Checkliste</h2>
        <ul class="loan-form-checks">
          <li><span class="loan-form-box"></span> Leihgut zurückgegeben</li>
<?php   if($ctx['hasKaution']) { ?>
          <li><span class="loan-form-box"></span> <strong class="loan-form-em">Kaution</strong> zurückgezahlt (<strong class="loan-form-em"><?php echo $h($ctx['kautionFormatted']); ?></strong>)</li>
          <li><span class="loan-form-box"></span> Abzüge (Betrag / Grund): _______________________________</li>
<?php   } ?>
          <li><span class="loan-form-box"></span> Mängel / Bemerkungen: _________________________________</li>
        </ul>
      </section>
<?php } ?>

      <section class="loan-form-section loan-form-signatures">
        <h2>Unterschriften</h2>
        <div class="loan-form-sign-grid">
          <div class="loan-form-sign">
            <p class="loan-form-sign-line"></p>
            <p class="loan-form-sign-label">Ort, Datum</p>
            <p class="loan-form-sign-line"></p>
            <p class="loan-form-sign-label"><strong class="loan-form-em"><?php echo $h($ctx['orgName']); ?></strong> (Verleiher)</p>
          </div>
          <div class="loan-form-sign">
            <p class="loan-form-sign-line"></p>
            <p class="loan-form-sign-label">Ort, Datum</p>
            <p class="loan-form-sign-line"></p>
            <p class="loan-form-sign-label"><strong class="loan-form-em"><?php echo $h($ctx['borrowerName']); ?></strong> (Entleiher)</p>
          </div>
        </div>
      </section>
    </div>
  </article>
</body>
</html>
