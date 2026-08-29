<?php
ob_start();
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page'] = 'admincalendar';
$_SESSION['adminpage'] = true;

include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
requireLoggedInOrRedirect();

if(!requirePermission('perm_showHiddenAppmnts')) {
    denyAccess('Keine Berechtigung für den Admin-Kalender.');
}

$userId = (int)$_SESSION['userid'];
$ym = calendarParseYearMonth(isset($_GET['ym']) ? $_GET['ym'] : null);
$bounds = calendarMonthBounds($ym);
$events = calendarLoadEventsForUser($userId, $bounds['gridStart'], $bounds['gridEnd'], array('admin' => true));
$byDay = calendarEventsByDay($events, $bounds['gridStart'], $bounds['gridEnd']);

include 'common/header.php';
?>
<script src="<?php echo assetUrl('js/melde.js'); ?>"></script>
<script src="<?php echo assetUrl('js/meldeFT.js'); ?>"></script>
<script src="<?php echo assetUrl('js/meldeshift.js'); ?>"></script>
<script src="<?php echo assetUrl('js/getStatus.js'); ?>"></script>
<script src="<?php echo assetUrl('js/changeInstrument.js'); ?>"></script>
<script src="<?php echo assetUrl('js/calendarMelde.js'); ?>"></script>

<?php
adminListPageBegin('Termine', 'Admin-Kalender');
$calYear = (int)$bounds['year'];
$calMonth = (int)$bounds['month'];
$monthNames = calendarMonthNames();
$yearFrom = max(1970, min($calYear, (int)date('Y')) - 10);
$yearTo = min(2100, max($calYear, (int)date('Y')) + 10);
$self = 'admin-calendar.php';
?>
<style>
.meld-cal-page {
  max-width: 72rem;
  margin: 0 auto;
  padding: 0 0 1rem;
  box-sizing: border-box;
  width: 100%;
}
.meld-cal-toolbar {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  grid-template-areas: "actions pickers today";
  align-items: center;
  column-gap: 0.75rem;
  row-gap: 0.55rem;
  padding: 0.75rem 0;
  margin: 0;
  width: 100%;
  box-sizing: border-box;
}
.meld-cal-pickers {
  grid-area: pickers;
  display: flex;
  flex-wrap: nowrap;
  justify-content: flex-end;
  align-items: center;
  gap: 0.45rem;
  min-width: 0;
}
.meld-cal-nav-today { grid-area: today; white-space: nowrap; }
.meld-cal-spinner { display: inline-flex; align-items: stretch; }
.meld-cal-spinner select { font-weight: bold; padding: 6px 4px; }
@media (max-width: 900px) {
  .meld-cal-toolbar {
    grid-template-columns: 1fr auto;
    grid-template-areas: "pickers today";
  }
  .meld-cal-pickers { justify-content: stretch; width: 100%; }
}
</style>

<div class="meld-cal-page">
<div class="meld-cal-toolbar" role="toolbar" aria-label="Kalender-Navigation">
  <div class="meld-cal-pickers">
    <div class="meld-cal-spinner" role="group" aria-label="Monat">
      <a class="w3-button w3-border meld-cal-step" href="<?php echo htmlspecialchars($self, ENT_QUOTES, 'UTF-8'); ?>?ym=<?php echo htmlspecialchars($bounds['prevYm'], ENT_QUOTES, 'UTF-8'); ?>" title="Vorheriger Monat" aria-label="Früherer Monat"><i class="fas fa-chevron-left" aria-hidden="true"></i></a>
      <select id="calMonthSelect" class="w3-select w3-border" aria-label="Monat wählen">
<?php foreach($monthNames as $num => $name) { ?>
        <option value="<?php echo (int)$num; ?>"<?php echo $num === $calMonth ? ' selected' : ''; ?>><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></option>
<?php } ?>
      </select>
      <a class="w3-button w3-border meld-cal-step" href="<?php echo htmlspecialchars($self, ENT_QUOTES, 'UTF-8'); ?>?ym=<?php echo htmlspecialchars($bounds['nextYm'], ENT_QUOTES, 'UTF-8'); ?>" title="Nächster Monat" aria-label="Späterer Monat"><i class="fas fa-chevron-right" aria-hidden="true"></i></a>
    </div>
    <div class="meld-cal-spinner" role="group" aria-label="Jahr">
      <a class="w3-button w3-border meld-cal-step" href="<?php echo htmlspecialchars($self, ENT_QUOTES, 'UTF-8'); ?>?ym=<?php echo htmlspecialchars($bounds['prevYearYm'], ENT_QUOTES, 'UTF-8'); ?>" title="Vorheriges Jahr" aria-label="Früheres Jahr"><i class="fas fa-chevron-left" aria-hidden="true"></i></a>
      <select id="calYearSelect" class="w3-select w3-border" aria-label="Jahr wählen">
<?php for($y = $yearFrom; $y <= $yearTo; $y++) { ?>
        <option value="<?php echo $y; ?>"<?php echo $y === $calYear ? ' selected' : ''; ?>><?php echo $y; ?></option>
<?php } ?>
      </select>
      <a class="w3-button w3-border meld-cal-step" href="<?php echo htmlspecialchars($self, ENT_QUOTES, 'UTF-8'); ?>?ym=<?php echo htmlspecialchars($bounds['nextYearYm'], ENT_QUOTES, 'UTF-8'); ?>" title="Nächstes Jahr" aria-label="Späteres Jahr"><i class="fas fa-chevron-right" aria-hidden="true"></i></a>
    </div>
  </div>
  <a class="w3-button w3-border meld-cal-nav-today" href="<?php echo htmlspecialchars($self, ENT_QUOTES, 'UTF-8'); ?>" title="Aktueller Monat">Heute</a>
</div>
<script>
(function() {
    var monthSel = document.getElementById('calMonthSelect');
    var yearSel = document.getElementById('calYearSelect');
    var base = <?php echo json_encode($self, JSON_UNESCAPED_UNICODE); ?>;
    function goYm() {
        if(!monthSel || !yearSel) return;
        var m = parseInt(monthSel.value, 10);
        var y = parseInt(yearSel.value, 10);
        if(isNaN(m) || isNaN(y)) return;
        var mm = (m < 10 ? '0' : '') + m;
        window.location.href = base + '?ym=' + encodeURIComponent(y + '-' + mm);
    }
    if(monthSel) monthSel.addEventListener('change', goYm);
    if(yearSel) yearSel.addEventListener('change', goYm);
})();
</script>

<?php include __DIR__.'/views/calendar/month.php'; ?>
</div>
<?php
adminListPageEnd();
include 'common/footer.php';
