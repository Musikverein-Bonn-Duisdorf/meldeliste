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

$calUser = new User();
$calUser->load_by_id($userId);
$showCalendarSubscribe = ((int)$calUser->Index > 0 && $calUser->activeLink);

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
.meld-cal-nav {
  display: flex; flex-wrap: wrap; justify-content: center; align-items: flex-start;
  gap: 1rem 1.5rem; position: relative;
}
.meld-cal-nav-actions {
  display: inline-flex; align-items: center; gap: 0.35rem;
  align-self: center;
}
@media (min-width: 901px) {
  .meld-cal-nav-actions {
    position: absolute; left: 0; top: 50%; transform: translateY(-50%);
  }
}
.meld-cal-nav-icon {
  margin: 0; padding: 8px 12px; line-height: 1; min-width: 2.5rem; text-align: center;
}
.meld-cal-spinner { display: inline-flex; flex-direction: column; align-items: center; min-width: 9rem; }
.meld-cal-spinner select {
  text-align: center; text-align-last: center; font-weight: bold;
  padding: 8px 6px; margin: 0; border-radius: 0; width: 100%;
}
.meld-cal-spinner .meld-cal-step {
  margin: 0; padding: 4px 10px; width: auto; min-width: 2.25rem;
  line-height: 1;
}
.meld-cal-nav-today { align-self: center; }
#calSubscribePanel[hidden] { display: none !important; }
</style>

<div class="w3-container w3-padding-16 meld-cal-nav">
  <div class="meld-cal-nav-actions">
<?php if($showCalendarSubscribe) { ?>
    <button type="button" id="calSubscribeToggle" class="w3-button w3-border meld-cal-nav-icon"
            title="Kalender abonnieren" aria-label="Kalender abonnieren" aria-expanded="false" aria-controls="calSubscribePanel">
      <i class="fas fa-info-circle" aria-hidden="true"></i>
    </button>
<?php } ?>
    <a class="w3-button w3-border meld-cal-nav-icon"
       href="calendar-print.php?ym=<?php echo htmlspecialchars($bounds['ym'], ENT_QUOTES, 'UTF-8'); ?>"
       title="Terminkalender drucken" aria-label="Terminkalender drucken" target="_blank" rel="noopener">
      <i class="fas fa-print" aria-hidden="true"></i>
    </a>
  </div>
  <div class="meld-cal-spinner" role="group" aria-label="Monat">
    <a class="w3-button w3-border meld-cal-step" href="calendar.php?ym=<?php echo htmlspecialchars($bounds['prevYm'], ENT_QUOTES, 'UTF-8'); ?>" title="Vorheriger Monat" aria-label="Früherer Monat"><i class="fas fa-chevron-up"></i></a>
    <select id="calMonthSelect" class="w3-select w3-border" aria-label="Monat wählen">
<?php foreach($monthNames as $num => $name) { ?>
      <option value="<?php echo (int)$num; ?>"<?php echo $num === $calMonth ? ' selected' : ''; ?>><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></option>
<?php } ?>
    </select>
    <a class="w3-button w3-border meld-cal-step" href="calendar.php?ym=<?php echo htmlspecialchars($bounds['nextYm'], ENT_QUOTES, 'UTF-8'); ?>" title="Nächster Monat" aria-label="Späterer Monat"><i class="fas fa-chevron-down"></i></a>
  </div>
  <div class="meld-cal-spinner" role="group" aria-label="Jahr">
    <a class="w3-button w3-border meld-cal-step" href="calendar.php?ym=<?php echo htmlspecialchars($bounds['prevYearYm'], ENT_QUOTES, 'UTF-8'); ?>" title="Vorheriges Jahr" aria-label="Früheres Jahr"><i class="fas fa-chevron-up"></i></a>
    <select id="calYearSelect" class="w3-select w3-border" aria-label="Jahr wählen">
<?php for($y = $yearFrom; $y <= $yearTo; $y++) { ?>
      <option value="<?php echo $y; ?>"<?php echo $y === $calYear ? ' selected' : ''; ?>><?php echo $y; ?></option>
<?php } ?>
    </select>
    <a class="w3-button w3-border meld-cal-step" href="calendar.php?ym=<?php echo htmlspecialchars($bounds['nextYearYm'], ENT_QUOTES, 'UTF-8'); ?>" title="Nächstes Jahr" aria-label="Späteres Jahr"><i class="fas fa-chevron-down"></i></a>
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

    var toggle = document.getElementById('calSubscribeToggle');
    var panel = document.getElementById('calSubscribePanel');
    if(toggle && panel) {
        toggle.addEventListener('click', function () {
            var open = panel.hasAttribute('hidden');
            if(open) {
                panel.removeAttribute('hidden');
                toggle.setAttribute('aria-expanded', 'true');
            } else {
                panel.setAttribute('hidden', '');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }
})();
</script>

<?php
include __DIR__.'/views/calendar/month.php';

if($showCalendarSubscribe) {
    $n = $calUser;
    $calendarSubscribeUid = 'page-cal';
?>
<div id="calSubscribePanel" class="w3-container w3-padding-16" style="max-width:40rem;margin:0 auto;" hidden>
<?php include __DIR__.'/views/calendar/subscribe.php'; ?>
</div>
<?php
}

include 'common/footer.php';
?>
