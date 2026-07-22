<?php
/**
 * Printable appointment table — all upcoming visible events (MELD-9).
 */
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

$window = icalFeedDateWindow();
$fromDate = date('Y-m-d');
$toDate = $window['to'];
if($toDate < $fromDate) {
    $toDate = $fromDate;
}

$events = calendarLoadEventsForUser($userId, $fromDate, $toDate);
$events = calendarEventsOverlappingRange($events, $fromDate, $toDate);

$orgName = isset($GLOBALS['optionsDB']['orgName']) ? (string)$GLOBALS['optionsDB']['orgName'] : '';
$printDate = germanDate(date('Y-m-d'), 1);
$rangeLabel = 'ab '.germanDate($fromDate, 1).' bis '.germanDate($toDate, 1);
$nEvents = count($events);
$assetV = isset($GLOBALS['version']['Hash']) ? $GLOBALS['version']['Hash'] : '0';
$cssMtime = @filemtime(__DIR__.'/styles/custom.css');
$cssUrl = 'styles/custom.css?'.$assetV.'-'.$cssMtime;
$backYm = htmlspecialchars($bounds['ym'], ENT_QUOTES, 'UTF-8');

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Terminkalender – kommende Termine</title>
  <link rel="stylesheet" href="<?php echo htmlspecialchars($cssUrl, ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="meld-cal-print">
  <div class="meld-cal-print-toolbar no-print">
    <a class="meld-cal-print-btn" href="calendar.php?ym=<?php echo $backYm; ?>">Zur&uuml;ck zum Kalender</a>
    <button type="button" class="meld-cal-print-btn" onclick="window.print()">Drucken / als PDF speichern</button>
  </div>

<?php include __DIR__.'/views/calendar/print.php'; ?>
</body>
</html>
