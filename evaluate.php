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

$titleBar = $GLOBALS['optionsDB']['colorTitleBar'];
$btnSubmit = $GLOBALS['optionsDB']['colorBtnSubmit'];
?>
<div id="header" class="w3-container <?php echo $titleBar; ?>">
  <h2>Datenauswertung</h2>
</div>

<div class="w3-container w3-margin-top w3-margin-bottom">
  <form method="get" action="evaluate.php" class="eval-filter" id="eval-filter">
    <label for="eval-days"><b>Zeitraum</b></label>
    <input id="eval-days" name="days" type="number" min="1" max="3650" step="1" value="<?php echo (int)$days; ?>" class="w3-input w3-border" required>
    <span>Tage</span>
    <button type="submit" class="w3-button w3-border <?php echo $btnSubmit; ?>">Anzeigen</button>
    <label>
      <input type="checkbox" name="besetzung" value="1"<?php echo $besetzungOnly ? ' checked' : ''; ?> onchange="this.form.submit()">
      nur Besetzung
    </label>
  </form>
</div>

<nav class="eval-toc w3-container w3-margin-bottom" aria-label="Seitenabschnitte">
  <a class="w3-button w3-border w3-round" href="#eval-attendance">Teilnahme</a>
  <a class="w3-button w3-border w3-round" href="#eval-log">System-Log</a>
  <a class="w3-button w3-border w3-round" href="#eval-ranking">Ranking</a>
  <a class="w3-button w3-border w3-round" href="#eval-inactive">Inaktive</a>
</nav>

<div class="eval-layout w3-container">
  <div class="eval-charts">
    <section class="eval-panel" id="eval-attendance">
      <h3>Teilnahme über Zeit</h3>
      <p class="w3-text-gray">Veröffentlichte Termine ohne Schichten<?php echo $besetzungOnly ? ' (nur Besetzung)' : ''; ?> der letzten <?php echo (int)$days; ?> Tage.</p>
      <div class="eval-chart-wrap">
        <canvas id="chartAttendance" aria-label="Teilnahme-Diagramm"></canvas>
      </div>
    </section>

    <section class="eval-panel" id="eval-log">
      <h3>System-Log</h3>
      <div class="eval-chart-wrap">
        <canvas id="chartLog" aria-label="Log-Diagramm"></canvas>
      </div>
    </section>
  </div>

  <div class="eval-tables">
    <section class="eval-panel" id="eval-ranking">
      <h3>Ranking nach Teilnahme</h3>
      <p class="w3-text-gray">Quote = Ja-Meldungen / Termine im Zeitraum. Spaltenüberschriften zum Sortieren anklicken.</p>
      <div class="eval-table-scroll w3-responsive">
        <table id="evalRanking" class="w3-table w3-striped w3-bordered w3-hoverable">
          <thead>
            <tr class="<?php echo $titleBar; ?>">
              <th class="eval-sort" data-sort="name" data-type="string">Name</th>
              <th class="eval-sort" data-sort="yes" data-type="number">Ja</th>
              <th class="eval-sort" data-sort="no" data-type="number">Nein</th>
              <th class="eval-sort" data-sort="maybe" data-type="number">Vielleicht</th>
              <th class="eval-sort" data-sort="termine" data-type="number">Termine</th>
              <th class="eval-sort" data-sort="quote" data-type="number">Quote</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </section>

    <section class="eval-panel" id="eval-inactive">
      <h3>Inaktive Nutzer</h3>
      <p class="w3-text-gray">Musiker ohne Aktivität (Login und Teilnahme) in den letzten <?php echo (int)$inactiveDays; ?> Tagen. Schwellwert: Konfiguration <code>inactiveUsersDays</code>.</p>
      <div class="eval-table-scroll w3-responsive">
        <table id="evalInactive" class="w3-table w3-striped w3-bordered w3-hoverable">
          <thead>
            <tr class="<?php echo $titleBar; ?>">
              <th class="eval-sort" data-sort="name" data-type="string">Name</th>
              <th class="eval-sort" data-sort="lastLogin" data-type="string">Letzter Login</th>
              <th class="eval-sort" data-sort="lastAttend" data-type="string">Letzte Teilnahme</th>
              <th class="eval-sort" data-sort="quote" data-type="number">Meldequote</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </section>
  </div>
</div>

<script type="application/json" id="evaluate-data"><?php echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS); ?></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script>
<script src="<?php echo $evaluateJs; ?>"></script>

<?php
include "common/footer.php";
?>
