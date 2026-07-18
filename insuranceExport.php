<?php
session_start();
$_SESSION['page'] = 'insurance';
$_SESSION['adminpage'] = true;
include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
requireLoggedInOrRedirect();

if(!requirePermission('perm_showInventories') && !requirePermission('perm_showInstruments')) {
    denyAccess();
}

$instrType = RegNumber::loadInstrType();
$instrTypeId = $instrType ? (int)$instrType->Index : 0;
$sql = sprintf(
    'SELECT `%sInventories`.*, `iName` FROM `%sInventories`
     INNER JOIN (SELECT `Index` AS `iIndex`, `Register`, `Name` AS `iName`, `Sortierung` AS `iSort` FROM `%sInstrument`) `%sInstrument`
       ON `Instrument` = `iIndex`
     INNER JOIN (SELECT `Index` AS `rIndex`, `Name` AS `rName`, `Sortierung` AS `rSort` FROM `%sRegister`) `%sRegister`
       ON `Register` = `rIndex`
     WHERE `Inventory` = %d AND `Insurance` = "1" AND `rName` != "keins"
     ORDER BY `rSort`, `iSort`;',
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $instrTypeId
);
$dbr = mysqli_query($GLOBALS['conn'], $sql);
sqlerror();

$rows = array();
$sumZeitwert = 0.0;
$sumAnschaffung = 0.0;
while($row = mysqli_fetch_array($dbr)) {
    $M = new Instruments;
    $M->load_by_id($row['Index']);

    $zeitwert = $M->getCurrentValue();
    $zeitwertNum = is_numeric($zeitwert) ? (float)$zeitwert : 0.0;
    $anschaffungNum = is_numeric($M->PurchasePrize) ? (float)$M->PurchasePrize : 0.0;
    $sumZeitwert += $zeitwertNum;
    $sumAnschaffung += $anschaffungNum;

    $rows[] = array(
        'reg' => RegNumber::displayInstrument($row['RegNumber']),
        'instrument' => (string)$row['iName'],
        'vendor' => (string)$row['Vendor'],
        'model' => (string)$row['Model'],
        'serial' => (string)$row['SerialNr'],
        'purchaseDate' => germanDate($row['PurchaseDate'], 0),
        'purchasePrize' => $anschaffungNum > 0 ? sprintf('%.2f €', $anschaffungNum) : '',
        'zeitwert' => $zeitwertNum > 0 ? sprintf('%.2f €', $zeitwertNum) : '',
        'owner' => getOwner($row['Owner']),
    );
}

$nInstruments = count($rows);
$stichtag = germanDate(date('Y-m-d'), 0);
$orgName = isset($GLOBALS['optionsDB']['orgName']) ? (string)$GLOBALS['optionsDB']['orgName'] : '';
$assetV = isset($GLOBALS['version']['Hash']) ? $GLOBALS['version']['Hash'] : '0';
$cssMtime = @filemtime(__DIR__ . '/styles/custom.css');
$cssUrl = 'styles/custom.css?' . $assetV . '-' . $cssMtime;

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Instrumentenversicherung – <?php echo htmlspecialchars($orgName !== '' ? $orgName : 'Meldeliste', ENT_QUOTES, 'UTF-8'); ?></title>
  <link rel="stylesheet" href="<?php echo htmlspecialchars($cssUrl, ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="insurance-print">
  <div class="insurance-print-toolbar no-print">
    <a class="insurance-print-btn" href="insurance.php">Zur&uuml;ck zur Liste</a>
    <button type="button" class="insurance-print-btn" onclick="window.print()">Drucken / als PDF speichern</button>
  </div>

  <header class="insurance-print-header">
    <?php if($orgName !== ''): ?>
      <p class="insurance-print-org"><?php echo htmlspecialchars($orgName, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
    <h1>Instrumentenversicherung</h1>
    <p class="insurance-print-meta">
      Stichtag: <strong><?php echo htmlspecialchars($stichtag, ENT_QUOTES, 'UTF-8'); ?></strong>
      &middot;
      <?php echo (int)$nInstruments; ?> Instrument<?php echo $nInstruments === 1 ? '' : 'e'; ?>
    </p>
  </header>

  <table class="insurance-print-table">
    <thead>
      <tr>
        <th>Inventarnummer</th>
        <th>Instrument</th>
        <th>Hersteller</th>
        <th>Modell</th>
        <th>Seriennummer</th>
        <th>Kaufdatum</th>
        <th class="num">Anschaffungswert</th>
        <th class="num">Zeitwert</th>
        <th>Besitzer</th>
      </tr>
    </thead>
    <tbody>
<?php if($nInstruments === 0): ?>
      <tr>
        <td colspan="9">Keine versicherten Instrumente.</td>
      </tr>
<?php else: ?>
<?php foreach($rows as $r): ?>
      <tr>
        <td><?php echo htmlspecialchars($r['reg'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($r['instrument'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($r['vendor'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($r['model'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($r['serial'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string)$r['purchaseDate'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td class="num"><?php echo htmlspecialchars($r['purchasePrize'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td class="num"><?php echo htmlspecialchars($r['zeitwert'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string)$r['owner'], ENT_QUOTES, 'UTF-8'); ?></td>
      </tr>
<?php endforeach; ?>
<?php endif; ?>
    </tbody>
<?php if($nInstruments > 0): ?>
    <tfoot>
      <tr>
        <td colspan="6"><strong>Summe</strong></td>
        <td class="num"><strong><?php echo htmlspecialchars(sprintf('%.2f €', $sumAnschaffung), ENT_QUOTES, 'UTF-8'); ?></strong></td>
        <td class="num"><strong><?php echo htmlspecialchars(sprintf('%.2f €', $sumZeitwert), ENT_QUOTES, 'UTF-8'); ?></strong></td>
        <td></td>
      </tr>
    </tfoot>
<?php endif; ?>
  </table>

  <p class="insurance-print-footnote">
    Zeitwert berechnet aus Anschaffungswert und Kaufdatum (Stichtag <?php echo htmlspecialchars($stichtag, ENT_QUOTES, 'UTF-8'); ?>).
  </p>
</body>
</html>
