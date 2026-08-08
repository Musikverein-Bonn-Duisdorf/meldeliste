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

<?php
adminListPageBegin('Termine', 'Kalender');
$calYear = (int)$bounds['year'];
$calMonth = (int)$bounds['month'];
$monthNames = calendarMonthNames();
$yearFrom = max(1970, min($calYear, (int)date('Y')) - 10);
$yearTo = min(2100, max($calYear, (int)date('Y')) + 10);
?>
<style>
.meld-cal-page {
  max-width: 72rem;
  margin: 0 auto;
  padding: 0 0 1rem;
  box-sizing: border-box;
  width: 100%;
}
/* MELD-174: toolbar is a 3-zone grid so Info/Druck never share a wrap line with month/year */
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
.meld-cal-actions {
  grid-area: actions;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-shrink: 0;
}
.meld-cal-pickers {
  grid-area: pickers;
  display: flex;
  flex-wrap: nowrap;
  justify-content: flex-end;
  align-items: center;
  gap: 0.45rem;
  min-width: 0;
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}
.meld-cal-nav-today {
  grid-area: today;
  align-self: center;
  white-space: nowrap;
}
.meld-cal-nav-icon,
a.w3-button.meld-cal-nav-icon,
button.w3-button.meld-cal-nav-icon {
  margin: 0;
  padding: 0;
  line-height: 1;
  width: 2.5rem;
  height: 2.5rem;
  min-width: 2.5rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 50% !important;
  box-sizing: border-box;
}
.meld-cal-spinner {
  display: inline-flex;
  flex-direction: row;
  flex-wrap: nowrap;
  align-items: stretch;
  min-width: 0;
  flex: 0 1 auto;
}
.meld-cal-spinner select {
  text-align: center;
  text-align-last: center;
  font-weight: bold;
  padding: 6px 4px;
  margin: 0;
  border-radius: 0;
  width: auto;
  min-width: 0;
  max-width: 9.5rem;
  box-sizing: border-box;
}
#calYearSelect {
  max-width: 5.5rem;
}
.meld-cal-spinner .meld-cal-step,
a.w3-button.meld-cal-step {
  margin: 0;
  padding: 0;
  width: 2.15rem;
  min-width: 2.15rem;
  height: auto;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  box-sizing: border-box;
}
.meld-cal-page .meld-cal-wrap {
  padding-left: 0;
  padding-right: 0;
}
/* Smartphone / narrow: Info+Druck | Heute on row 1; month+year full width on row 2 */
@media (max-width: 720px) {
  .meld-cal-toolbar {
    grid-template-columns: auto 1fr;
    grid-template-areas:
      "actions today"
      "pickers pickers";
  }
  .meld-cal-pickers {
    justify-content: stretch;
    width: 100%;
    gap: 0.4rem;
  }
  .meld-cal-spinner {
    flex: 1 1 0;
    min-width: 0;
  }
  .meld-cal-spinner select {
    flex: 1 1 auto;
    width: 100%;
    max-width: none;
    padding: 5px 2px;
    font-size: 0.9rem;
  }
  #calYearSelect {
    max-width: none;
  }
  .meld-cal-nav-icon,
  a.w3-button.meld-cal-nav-icon,
  button.w3-button.meld-cal-nav-icon {
    width: 2.25rem;
    height: 2.25rem;
    min-width: 2.25rem;
  }
  .meld-cal-spinner .meld-cal-step,
  a.w3-button.meld-cal-step {
    width: 2rem;
    min-width: 2rem;
  }
  .meld-cal-nav-today {
    justify-self: end;
    padding: 0.35rem 0.6rem;
    font-size: 0.9rem;
  }
}
</style>

<div class="meld-cal-page">
<div class="meld-cal-toolbar" role="toolbar" aria-label="Kalender-Navigation">
  <div class="meld-cal-actions">
<?php if($showCalendarSubscribe) { ?>
    <button type="button" id="calSubscribeToggle" class="w3-button w3-border meld-cal-nav-icon"
            title="Persönlichen Kalender abonnieren" aria-label="Persönlichen Kalender abonnieren" aria-haspopup="dialog" aria-controls="calSubscribeModal">
      <i class="fas fa-info-circle" aria-hidden="true"></i>
    </button>
<?php } ?>
    <a class="w3-button w3-border meld-cal-nav-icon"
       href="calendar-print.php?ym=<?php echo htmlspecialchars($bounds['ym'], ENT_QUOTES, 'UTF-8'); ?>"
       title="Terminkalender drucken" aria-label="Terminkalender drucken" target="_blank" rel="noopener">
      <i class="fas fa-print" aria-hidden="true"></i>
    </a>
  </div>
  <div class="meld-cal-pickers">
    <div class="meld-cal-spinner" role="group" aria-label="Monat">
      <a class="w3-button w3-border meld-cal-step" href="calendar.php?ym=<?php echo htmlspecialchars($bounds['prevYm'], ENT_QUOTES, 'UTF-8'); ?>" title="Vorheriger Monat" aria-label="Früherer Monat"><i class="fas fa-chevron-left" aria-hidden="true"></i></a>
      <select id="calMonthSelect" class="w3-select w3-border" aria-label="Monat wählen">
<?php foreach($monthNames as $num => $name) { ?>
        <option value="<?php echo (int)$num; ?>"<?php echo $num === $calMonth ? ' selected' : ''; ?>><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></option>
<?php } ?>
      </select>
      <a class="w3-button w3-border meld-cal-step" href="calendar.php?ym=<?php echo htmlspecialchars($bounds['nextYm'], ENT_QUOTES, 'UTF-8'); ?>" title="Nächster Monat" aria-label="Späterer Monat"><i class="fas fa-chevron-right" aria-hidden="true"></i></a>
    </div>
    <div class="meld-cal-spinner" role="group" aria-label="Jahr">
      <a class="w3-button w3-border meld-cal-step" href="calendar.php?ym=<?php echo htmlspecialchars($bounds['prevYearYm'], ENT_QUOTES, 'UTF-8'); ?>" title="Vorheriges Jahr" aria-label="Früheres Jahr"><i class="fas fa-chevron-left" aria-hidden="true"></i></a>
      <select id="calYearSelect" class="w3-select w3-border" aria-label="Jahr wählen">
<?php for($y = $yearFrom; $y <= $yearTo; $y++) { ?>
        <option value="<?php echo $y; ?>"<?php echo $y === $calYear ? ' selected' : ''; ?>><?php echo $y; ?></option>
<?php } ?>
      </select>
      <a class="w3-button w3-border meld-cal-step" href="calendar.php?ym=<?php echo htmlspecialchars($bounds['nextYearYm'], ENT_QUOTES, 'UTF-8'); ?>" title="Nächstes Jahr" aria-label="Späteres Jahr"><i class="fas fa-chevron-right" aria-hidden="true"></i></a>
    </div>
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
?>
</div>
<?php adminListPageEnd(); ?>
<?php
if($showCalendarSubscribe) {
    $n = $calUser;
    $calendarSubscribeUid = 'page-cal';
    $calendarSubscribeInModal = true;
    ob_start();
?>
<div id="calSubscribeModal" class="w3-modal" role="dialog" aria-modal="true" aria-labelledby="calSubscribeModalTitle" style="display:none;"
     onclick="if(event.target===this){ this.style.display='none'; }">
  <div class="w3-modal-content">
    <div class="profile-shell modal-shell calendar-subscribe-modal">
      <header class="profile-hero">
        <div class="profile-hero-text">
          <p class="profile-kicker">Kalender</p>
          <h2 class="profile-title" id="calSubscribeModalTitle">Persönlichen Kalender abonnieren</h2>
        </div>
        <div class="profile-hero-actions">
          <button type="button" class="modal-close w3-button" id="calSubscribeClose" aria-label="Schließen">&times;</button>
        </div>
      </header>
      <div class="calendar-subscribe-body">
        <h3 class="profile-col-title">Abo-Link</h3>
<?php include __DIR__.'/views/calendar/subscribe.php'; ?>
      </div>
    </div>
  </div>
</div>
<script>
(function() {
    var toggle = document.getElementById('calSubscribeToggle');
    var modal = document.getElementById('calSubscribeModal');
    var closeBtn = document.getElementById('calSubscribeClose');
    if(!toggle || !modal) return;
    function openSub() { modal.style.display = 'block'; }
    function closeSub() { modal.style.display = 'none'; }
    toggle.addEventListener('click', openSub);
    if(closeBtn) closeBtn.addEventListener('click', closeSub);
    document.addEventListener('keydown', function (e) {
        if(e.key === 'Escape' && modal.style.display === 'block') closeSub();
    });
})();
</script>
<?php
    deferPageModalHtml(ob_get_clean());
}

include 'common/footer.php';
?>
