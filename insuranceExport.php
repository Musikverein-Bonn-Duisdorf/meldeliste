<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page'] = 'insurance';
$_SESSION['adminpage'] = true;
include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
requireLoggedInOrRedirect();

if(!requirePermission('perm_showInventories') && !requirePermission('perm_showInstruments')) {
    denyAccess();
}

$columns = array(
    'reg' => 'Inventarnummer',
    'instrument' => 'Instrument',
    'vendor' => 'Hersteller',
    'model' => 'Modell',
    'serial' => 'Seriennummer',
    'purchaseDate' => 'Kaufdatum',
    'purchasePrize' => 'Anschaffungswert',
    'zeitwert' => 'Zeitwert',
    'owner' => 'Eigentümer',
);
$numCols = array('purchasePrize' => true, 'zeitwert' => true);

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
$colCount = count($columns);

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

  <fieldset class="insurance-print-columns no-print">
    <legend>Spalten</legend>
    <div class="insurance-print-columns-list">
<?php foreach($columns as $key => $label): ?>
      <label class="insurance-print-col-toggle">
        <input type="checkbox" name="col" value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" checked>
        <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
      </label>
<?php endforeach; ?>
    </div>
  </fieldset>

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

  <table class="insurance-print-table" id="insurancePrintTable">
    <thead>
      <tr>
<?php foreach($columns as $key => $label): ?>
        <th data-col="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"<?php echo isset($numCols[$key]) ? ' class="num"' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></th>
<?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
<?php if($nInstruments === 0): ?>
      <tr>
        <td colspan="<?php echo (int)$colCount; ?>" id="insuranceEmptyCell">Keine versicherten Instrumente.</td>
      </tr>
<?php else: ?>
<?php foreach($rows as $r): ?>
      <tr>
<?php foreach($columns as $key => $label): ?>
        <td data-col="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"<?php echo isset($numCols[$key]) ? ' class="num"' : ''; ?>><?php echo htmlspecialchars((string)$r[$key], ENT_QUOTES, 'UTF-8'); ?></td>
<?php endforeach; ?>
      </tr>
<?php endforeach; ?>
<?php endif; ?>
    </tbody>
<?php if($nInstruments > 0): ?>
    <tfoot>
      <tr>
<?php
$firstLabel = true;
foreach($columns as $key => $label) {
    $cls = isset($numCols[$key]) ? ' class="num"' : '';
    $content = '';
    if($key === 'purchasePrize') {
        $content = '<strong>'.htmlspecialchars(sprintf('%.2f €', $sumAnschaffung), ENT_QUOTES, 'UTF-8').'</strong>';
    }
    elseif($key === 'zeitwert') {
        $content = '<strong>'.htmlspecialchars(sprintf('%.2f €', $sumZeitwert), ENT_QUOTES, 'UTF-8').'</strong>';
    }
    elseif($firstLabel) {
        $content = '<strong>Summe</strong>';
        $firstLabel = false;
    }
    echo '        <td data-col="'.htmlspecialchars($key, ENT_QUOTES, 'UTF-8').'"'.$cls.'>'.$content."</td>\n";
}
?>
      </tr>
    </tfoot>
<?php endif; ?>
  </table>

  <p class="insurance-print-footnote">
    Zeitwert berechnet aus Anschaffungswert und Kaufdatum (Stichtag <?php echo htmlspecialchars($stichtag, ENT_QUOTES, 'UTF-8'); ?>).
  </p>

  <script>
(function() {
  var STORAGE_KEY = 'insurancePrintColumns';
  var table = document.getElementById('insurancePrintTable');
  var checks = document.querySelectorAll('.insurance-print-columns input[name="col"]');
  var emptyCell = document.getElementById('insuranceEmptyCell');
  if(!table || !checks.length) return;

  function loadPrefs() {
    try {
      var raw = localStorage.getItem(STORAGE_KEY);
      if(!raw) return null;
      var parsed = JSON.parse(raw);
      return parsed && typeof parsed === 'object' ? parsed : null;
    } catch (e) {
      return null;
    }
  }

  function savePrefs(visible) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(visible));
    } catch (e) {}
  }

  function applyColumns() {
    var visible = {};
    var nVisible = 0;
    for(var i = 0; i < checks.length; i++) {
      var key = checks[i].value;
      var on = !!checks[i].checked;
      visible[key] = on;
      if(on) nVisible++;
      if(on) {
        table.classList.remove('hide-col-' + key);
      } else {
        table.classList.add('hide-col-' + key);
      }
    }
    if(emptyCell) {
      emptyCell.colSpan = Math.max(nVisible, 1);
    }
    // Move "Summe" label into the first visible non-sum column
    var foot = table.querySelector('tfoot tr');
    if(foot) {
      var cells = foot.querySelectorAll('td[data-col]');
      var labelPlaced = false;
      for(var j = 0; j < cells.length; j++) {
        var col = cells[j].getAttribute('data-col');
        if(col === 'purchasePrize' || col === 'zeitwert') continue;
        if(visible[col] && !labelPlaced) {
          cells[j].innerHTML = '<strong>Summe</strong>';
          labelPlaced = true;
        } else if(col !== 'purchasePrize' && col !== 'zeitwert') {
          cells[j].innerHTML = '';
        }
      }
    }
    savePrefs(visible);
  }

  var prefs = loadPrefs();
  if(prefs) {
    for(var i = 0; i < checks.length; i++) {
      if(Object.prototype.hasOwnProperty.call(prefs, checks[i].value)) {
        checks[i].checked = !!prefs[checks[i].value];
      }
    }
  }

  for(var i = 0; i < checks.length; i++) {
    checks[i].addEventListener('change', function() {
      // Keep at least one column visible
      var any = false;
      for(var j = 0; j < checks.length; j++) {
        if(checks[j].checked) { any = true; break; }
      }
      if(!any) {
        this.checked = true;
        return;
      }
      applyColumns();
    });
  }

  applyColumns();
})();
  </script>
</body>
</html>
