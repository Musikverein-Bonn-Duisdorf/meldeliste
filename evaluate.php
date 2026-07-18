<?php
session_start();
$_SESSION['page'] = 'evaluate';
$_SESSION['adminpage'] = true;
include "common/header.php";
if(!requirePermission("perm_showLog")) {
    denyAccess();
}

require_once __DIR__.'/libs/evaluateStats.php';

$days = evaluateNormalizeDays(isset($_GET['days']) ? (int)$_GET['days'] : 90);
$besetzungOnly = !empty($_GET['besetzung']);
$inactiveDays = isset($GLOBALS['optionsDB']['inactiveUsersDays'])
    ? max(1, (int)$GLOBALS['optionsDB']['inactiveUsersDays'])
    : 90;

$attendance = evaluateAttendanceSeries($days, $besetzungOnly);
$logSeries = evaluateLogSeries($days);
$ranking = evaluateAttendanceRanking($days, $besetzungOnly);
$inactive = evaluateInactiveUsers($inactiveDays);

$assetV = isset($GLOBALS['version']['Hash']) ? $GLOBALS['version']['Hash'] : '0';
$jsMtime = @filemtime(__DIR__.'/js/evaluate.js');
$evaluateJs = htmlspecialchars('js/evaluate.js?'.$assetV.'-'.$jsMtime, ENT_QUOTES, 'UTF-8');

$payload = array(
    'attendance' => $attendance,
    'log' => $logSeries,
    'ranking' => $ranking,
    'inactive' => $inactive,
    'logLabels' => array('FATAL', 'ERROR', 'WARNING', 'DBDELETE', 'DBINSERT', 'DBUPDATE', 'EMAIL', 'INFO'),
);
?>
<div id="header" class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2>Datenauswertung</h2>
</div>

<div class="w3-container w3-margin-top w3-margin-bottom">
  <form method="get" action="evaluate.php" class="w3-bar w3-mobile" style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:center;">
    <label for="eval-days"><b>Zeitraum</b></label>
    <select id="eval-days" name="days" class="w3-select w3-border w3-mobile" style="max-width:10rem;" onchange="this.form.submit()">
      <?php foreach(evaluateAllowedDayOptions() as $opt): ?>
      <option value="<?php echo (int)$opt; ?>"<?php echo $days === $opt ? ' selected' : ''; ?>><?php echo (int)$opt; ?> Tage</option>
      <?php endforeach; ?>
    </select>
    <label class="w3-mobile">
      <input type="checkbox" name="besetzung" value="1"<?php echo $besetzungOnly ? ' checked' : ''; ?> onchange="this.form.submit()">
      nur Besetzung
    </label>
  </form>
</div>

<div class="w3-container w3-margin-bottom">
  <h3>Teilnahme über Zeit</h3>
  <p class="w3-text-gray">Veröffentlichte Termine ohne Schichten<?php echo $besetzungOnly ? ' (nur Besetzung)' : ''; ?> der letzten <?php echo (int)$days; ?> Tage.</p>
  <div class="w3-responsive" style="position:relative;height:320px;">
    <canvas id="chartAttendance" aria-label="Teilnahme-Diagramm"></canvas>
  </div>
</div>

<div class="w3-container w3-margin-bottom">
  <h3>System-Log</h3>
  <div class="w3-responsive" style="position:relative;height:320px;">
    <canvas id="chartLog" aria-label="Log-Diagramm"></canvas>
  </div>
</div>

<div class="w3-container w3-margin-bottom">
  <h3>Ranking nach Teilnahme</h3>
  <p class="w3-text-gray">Quote = Ja-Meldungen / Termine im Zeitraum. Spaltenüberschriften zum Sortieren anklicken.</p>
  <div class="w3-responsive">
    <table id="evalRanking" class="w3-table w3-striped w3-bordered w3-hoverable">
      <thead>
        <tr class="<?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
          <th class="eval-sort" data-sort="name" data-type="string" style="cursor:pointer;">Name</th>
          <th class="eval-sort" data-sort="yes" data-type="number" style="cursor:pointer;">Ja</th>
          <th class="eval-sort" data-sort="no" data-type="number" style="cursor:pointer;">Nein</th>
          <th class="eval-sort" data-sort="maybe" data-type="number" style="cursor:pointer;">Vielleicht</th>
          <th class="eval-sort" data-sort="termine" data-type="number" style="cursor:pointer;">Termine</th>
          <th class="eval-sort" data-sort="quote" data-type="number" style="cursor:pointer;">Quote</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<div class="w3-container w3-margin-bottom">
  <h3>Inaktive Nutzer</h3>
  <p class="w3-text-gray">Musiker ohne Aktivität (Login und Teilnahme) in den letzten <?php echo (int)$inactiveDays; ?> Tagen. Schwellwert: Konfiguration <code>inactiveUsersDays</code>.</p>
  <div class="w3-responsive">
    <table id="evalInactive" class="w3-table w3-striped w3-bordered w3-hoverable">
      <thead>
        <tr class="<?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
          <th class="eval-sort" data-sort="name" data-type="string" style="cursor:pointer;">Name</th>
          <th class="eval-sort" data-sort="lastLogin" data-type="string" style="cursor:pointer;">Letzter Login</th>
          <th class="eval-sort" data-sort="lastAttend" data-type="string" style="cursor:pointer;">Letzte Teilnahme</th>
          <th class="eval-sort" data-sort="quote" data-type="number" style="cursor:pointer;">Meldequote</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<script type="application/json" id="evaluate-data"><?php echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS); ?></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script>
<script src="<?php echo $evaluateJs; ?>"></script>

<?php
include "common/footer.php";
?>
