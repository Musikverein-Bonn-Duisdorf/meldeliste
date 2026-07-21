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

<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2>Kalender</h2>
</div>

<div class="w3-container w3-padding-16 w3-center meld-cal-nav">
  <a class="w3-button w3-border" href="calendar.php?ym=<?php echo htmlspecialchars($bounds['prevYm'], ENT_QUOTES, 'UTF-8'); ?>" title="Vorheriger Monat" aria-label="Vorheriger Monat"><i class="fas fa-chevron-left"></i></a>
  <span class="w3-padding meld-cal-label"><b><?php echo htmlspecialchars($bounds['label'], ENT_QUOTES, 'UTF-8'); ?></b></span>
  <a class="w3-button w3-border" href="calendar.php?ym=<?php echo htmlspecialchars($bounds['nextYm'], ENT_QUOTES, 'UTF-8'); ?>" title="Nächster Monat" aria-label="Nächster Monat"><i class="fas fa-chevron-right"></i></a>
  <a class="w3-button w3-border w3-margin-left" href="calendar.php" title="Aktueller Monat">Heute</a>
</div>

<div class="w3-container w3-padding-small meld-cal-legend">
  <span class="w3-tag w3-tiny <?php echo htmlspecialchars($GLOBALS['optionsDB']['colorBtnYes'], ENT_QUOTES, 'UTF-8'); ?>">Ja</span>
  <span class="w3-tag w3-tiny <?php echo htmlspecialchars($GLOBALS['optionsDB']['colorBtnNo'], ENT_QUOTES, 'UTF-8'); ?>">Nein</span>
  <span class="w3-tag w3-tiny <?php echo htmlspecialchars($GLOBALS['optionsDB']['colorBtnMaybe'], ENT_QUOTES, 'UTF-8'); ?>">Vielleicht</span>
  <span class="w3-tag w3-tiny <?php echo htmlspecialchars($GLOBALS['optionsDB']['colorBtnEdit'], ENT_QUOTES, 'UTF-8'); ?>">Ohne Meldung</span>
</div>

<?php
include __DIR__.'/views/calendar/month.php';
include 'common/footer.php';
?>
