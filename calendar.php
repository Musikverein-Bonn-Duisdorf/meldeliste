<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page'] = 'calendar';
$_SESSION['adminpage'] = false;

include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
requireLoggedInOrRedirect();

$userId = (int)$_SESSION['userid'];
$ym = calendarParseYearMonth(isset($_GET['ym']) ? $_GET['ym'] : null);
$bounds = calendarMonthBounds($ym);
$events = calendarLoadEventsForUser($userId, $bounds['gridStart'], $bounds['gridEnd']);
$byDay = calendarEventsByDay($events, $bounds['gridStart'], $bounds['gridEnd']);

include 'common/header.php';
?>
<script src="<?php echo assetUrl('js/melde.js'); ?>"></script>
<script src="<?php echo assetUrl('js/meldeFT.js'); ?>"></script>
<script src="<?php echo assetUrl('js/meldeshift.js'); ?>"></script>
<script src="<?php echo assetUrl('js/getStatus.js'); ?>"></script>
<script src="<?php echo assetUrl('js/changeInstrument.js'); ?>"></script>
<script src="<?php echo assetUrl('js/calendarMelde.js'); ?>"></script>

<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2>Kalender</h2>
</div>

<?php
$calYear = (int)$bounds['year'];
$calMonth = (int)$bounds['month'];
$monthNames = calendarMonthNames();
$yearFrom = max(1970, min($calYear, (int)date('Y')) - 10);
$yearTo = min(2100, max($calYear, (int)date('Y')) + 10);
?>
<style>
.meld-cal-nav { display: flex; flex-wrap: wrap; justify-content: center; align-items: flex-start; gap: 1rem 1.5rem; }
.meld-cal-spinner { display: inline-flex; flex-direction: column; align-items: stretch; min-width: 9rem; }
.meld-cal-spinner select {
  text-align: center; text-align-last: center; font-weight: bold;
  padding: 8px 6px; margin: 0; border-radius: 0;
}
.meld-cal-spinner .w3-button { margin: 0; padding: 6px 8px; }
.meld-cal-nav-today { align-self: center; }
</style>

<div class="w3-container w3-padding-16 meld-cal-nav">
  <div class="meld-cal-spinner" role="group" aria-label="Monat">
    <a class="w3-button w3-border" href="calendar.php?ym=<?php echo htmlspecialchars($bounds['nextYm'], ENT_QUOTES, 'UTF-8'); ?>" title="Nächster Monat" aria-label="Monat erhöhen"><i class="fas fa-chevron-up"></i></a>
    <select id="calMonthSelect" class="w3-select w3-border" aria-label="Monat wählen">
<?php foreach($monthNames as $num => $name) { ?>
      <option value="<?php echo (int)$num; ?>"<?php echo $num === $calMonth ? ' selected' : ''; ?>><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></option>
<?php } ?>
    </select>
    <a class="w3-button w3-border" href="calendar.php?ym=<?php echo htmlspecialchars($bounds['prevYm'], ENT_QUOTES, 'UTF-8'); ?>" title="Vorheriger Monat" aria-label="Monat verringern"><i class="fas fa-chevron-down"></i></a>
  </div>
  <div class="meld-cal-spinner" role="group" aria-label="Jahr">
    <a class="w3-button w3-border" href="calendar.php?ym=<?php echo htmlspecialchars($bounds['nextYearYm'], ENT_QUOTES, 'UTF-8'); ?>" title="Nächstes Jahr" aria-label="Jahr erhöhen"><i class="fas fa-chevron-up"></i></a>
    <select id="calYearSelect" class="w3-select w3-border" aria-label="Jahr wählen">
<?php for($y = $yearFrom; $y <= $yearTo; $y++) { ?>
      <option value="<?php echo $y; ?>"<?php echo $y === $calYear ? ' selected' : ''; ?>><?php echo $y; ?></option>
<?php } ?>
    </select>
    <a class="w3-button w3-border" href="calendar.php?ym=<?php echo htmlspecialchars($bounds['prevYearYm'], ENT_QUOTES, 'UTF-8'); ?>" title="Vorheriges Jahr" aria-label="Jahr verringern"><i class="fas fa-chevron-down"></i></a>
  </div>
  <a class="w3-button w3-border meld-cal-nav-today" href="calendar.php" title="Aktueller Monat">Heute</a>
</div>
<script>
(function() {
    var monthSel = document.getElementById('calMonthSelect');
    var yearSel = document.getElementById('calYearSelect');
    function goYm() {
        if(!monthSel || !yearSel) return;
        var m = parseInt(monthSel.value, 10);
        var y = parseInt(yearSel.value, 10);
        if(isNaN(m) || isNaN(y)) return;
        var mm = (m < 10 ? '0' : '') + m;
        window.location.href = 'calendar.php?ym=' + encodeURIComponent(y + '-' + mm);
    }
    if(monthSel) monthSel.addEventListener('change', goYm);
    if(yearSel) yearSel.addEventListener('change', goYm);
})();
</script>

<?php
include __DIR__.'/views/calendar/month.php';
include 'common/footer.php';
?>
