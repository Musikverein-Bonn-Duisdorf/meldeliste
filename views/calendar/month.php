<?php
/**
 * Month grid partial. Expects: $bounds, $byDay
 */
$weekdays = array('Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So');
$today = date('Y-m-d');
$monthStart = $bounds['monthStart'];
$monthEnd = $bounds['monthEnd'];
$maxChips = 3;

$cursor = DateTimeImmutable::createFromFormat('Y-m-d', $bounds['gridStart']);
$gridEnd = DateTimeImmutable::createFromFormat('Y-m-d', $bounds['gridEnd']);
?>
<style>
.meld-cal-grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 2px; }
.meld-cal-head { font-weight: bold; text-align: center; padding: 6px 2px; font-size: 0.85em; }
.meld-cal-cell {
  min-height: 5.5rem; border: 1px solid #ccc; padding: 4px; vertical-align: top;
  background: #fff; overflow: hidden;
}
.meld-cal-cell--out { opacity: 0.45; background: #f5f5f5; }
.meld-cal-cell--weekend { background: #f0f3f7; }
.meld-cal-cell--out.meld-cal-cell--weekend { background: #e8ecf1; }
.meld-cal-cell--today { outline: 2px solid #345A95; outline-offset: -2px; }
.meld-cal-daynum { font-size: 0.8em; font-weight: bold; margin-bottom: 2px; }
.meld-cal-chip {
  display: block; width: 100%; box-sizing: border-box; margin: 0 0 2px 0;
  padding: 2px 4px; font-size: 0.7em; line-height: 1.2; text-align: left;
  border: 1px solid rgba(0,0,0,0.25); cursor: pointer; overflow: hidden;
  text-overflow: ellipsis; white-space: nowrap; border-radius: 2px;
}
.meld-cal-more {
  display: block; width: 100%; box-sizing: border-box; margin: 0;
  padding: 2px 4px; font-size: 0.7em; line-height: 1.2; text-align: left;
  border: 1px dashed rgba(0,0,0,0.3); border-radius: 2px;
  background: transparent; color: #345A95; cursor: pointer;
  font-family: inherit;
}
.meld-cal-more:hover { background: rgba(52, 90, 149, 0.08); }
.meld-cal-events { max-height: 6.5rem; overflow-y: auto; }
.meld-cal-day-pick-list { display: flex; flex-direction: column; gap: 6px; }
.meld-cal-day-pick-item {
  display: block; width: 100%; box-sizing: border-box; text-align: left;
  padding: 8px 10px; border: 1px solid rgba(0,0,0,0.2); border-radius: 4px;
  cursor: pointer; font-size: 0.95em;
}
.meld-cal-cell--create { cursor: pointer; }
.meld-cal-cell--create:hover { border-color: #345A95; }
@media (max-width: 900px) {
  .meld-cal-cell { min-height: 4.2rem; padding: 2px; }
  .meld-cal-chip, .meld-cal-more { font-size: 0.6em; padding: 1px 2px; }
  .meld-cal-head { font-size: 0.75em; }
}
</style>

<div class="w3-container w3-padding-small meld-cal-wrap"
     data-color-yes="<?php echo htmlspecialchars($GLOBALS['optionsDB']['colorBtnYes'], ENT_QUOTES, 'UTF-8'); ?>"
     data-color-no="<?php echo htmlspecialchars($GLOBALS['optionsDB']['colorBtnNo'], ENT_QUOTES, 'UTF-8'); ?>"
     data-color-maybe="<?php echo htmlspecialchars($GLOBALS['optionsDB']['colorBtnMaybe'], ENT_QUOTES, 'UTF-8'); ?>"
     data-color-none="<?php echo htmlspecialchars($GLOBALS['optionsDB']['colorBtnEdit'], ENT_QUOTES, 'UTF-8'); ?>"
     data-can-create="<?php echo (isAdmin() && requirePermission('perm_editAppmnts')) ? '1' : '0'; ?>">
  <div class="meld-cal-grid" role="grid" aria-label="Monatskalender">
<?php foreach($weekdays as $wd) { ?>
    <div class="meld-cal-head" role="columnheader"><?php echo htmlspecialchars($wd, ENT_QUOTES, 'UTF-8'); ?></div>
<?php } ?>
<?php
$canCreate = isAdmin() && requirePermission('perm_editAppmnts');
while($cursor && $gridEnd && $cursor <= $gridEnd) {
    $key = $cursor->format('Y-m-d');
    $inMonth = ($key >= $monthStart && $key <= $monthEnd);
    $isToday = ($key === $today);
    $dow = (int)$cursor->format('N'); // 6=Sa, 7=So
    $classes = 'meld-cal-cell';
    if(!$inMonth) {
        $classes .= ' meld-cal-cell--out';
    }
    if($dow >= 6) {
        $classes .= ' meld-cal-cell--weekend';
    }
    if($isToday) {
        $classes .= ' meld-cal-cell--today';
    }
    if($canCreate) {
        $classes .= ' meld-cal-cell--create';
    }
    $dayEvents = isset($byDay[$key]) ? $byDay[$key] : array();
    $total = count($dayEvents);
    $shown = array_slice($dayEvents, 0, $maxChips);
    $extra = $total - count($shown);
?>
    <div class="<?php echo $classes; ?>" role="gridcell" data-date="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"<?php
      if($canCreate) {
          echo ' title="Klick: neuen Termin anlegen"';
      }
    ?>>
      <div class="meld-cal-daynum"><?php echo (int)$cursor->format('j'); ?></div>
      <div class="meld-cal-events">
<?php
    $pickerEvents = array();
    foreach($dayEvents as $ev) {
        $timeLabel = calendarFormatTimeShort($ev['startTime']);
        $label = ($timeLabel !== '' ? $timeLabel.' ' : '').$ev['name'];
        $pickerEvents[] = array(
            'id' => (int)$ev['id'],
            'label' => $label,
            'color' => (string)$ev['colorClass'],
            'wert' => $ev['wert'] === null ? '' : (int)$ev['wert'],
        );
    }
    foreach($shown as $ev) {
        $timeLabel = calendarFormatTimeShort($ev['startTime']);
        $label = ($timeLabel !== '' ? $timeLabel.' ' : '').$ev['name'];
        $title = $ev['name'].($timeLabel !== '' ? ' ('.$timeLabel.')' : '');
        $color = htmlspecialchars($ev['colorClass'], ENT_QUOTES, 'UTF-8');
?>
        <button type="button"
          class="meld-cal-chip <?php echo $color; ?>"
          data-termin-id="<?php echo (int)$ev['id']; ?>"
          data-melde-wert="<?php echo $ev['wert'] === null ? '' : (int)$ev['wert']; ?>"
          title="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"
          onclick="openModal('calendarMelde', <?php echo (int)$ev['id']; ?>)">
          <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
        </button>
<?php
    }
    if($extra > 0) {
        $pickerJson = htmlspecialchars(
            json_encode($pickerEvents, JSON_UNESCAPED_UNICODE),
            ENT_QUOTES,
            'UTF-8'
        );
?>
        <button type="button"
          class="meld-cal-more"
          data-date="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"
          data-cal-day-events="<?php echo $pickerJson; ?>"
          title="Alle Termine an diesem Tag"
          aria-label="+<?php echo (int)$extra; ?> weitere Termine anzeigen">+<?php echo (int)$extra; ?></button>
<?php } ?>
      </div>
    </div>
<?php
    $cursor = $cursor->modify('+1 day');
}
?>
  </div>
</div>
