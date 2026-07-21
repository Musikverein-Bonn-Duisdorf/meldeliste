<?php
/**
 * Print table partial (MELD-9). Expects: $bounds, $events, $orgName, $printDate, $nEvents
 */
?>
  <header class="meld-cal-print-header">
<?php if($orgName !== '') { ?>
    <p class="meld-cal-print-org"><?php echo htmlspecialchars($orgName, ENT_QUOTES, 'UTF-8'); ?></p>
<?php } ?>
    <h1>Terminkalender</h1>
    <p class="meld-cal-print-meta">
      <strong><?php echo htmlspecialchars($bounds['label'], ENT_QUOTES, 'UTF-8'); ?></strong>
      &middot;
      gedruckt am <?php echo htmlspecialchars((string)$printDate, ENT_QUOTES, 'UTF-8'); ?>
      &middot;
      <?php echo (int)$nEvents; ?> Termin<?php echo $nEvents === 1 ? '' : 'e'; ?>
    </p>
  </header>

<?php if($nEvents === 0) { ?>
  <p class="meld-cal-print-empty">Keine Termine in diesem Monat.</p>
<?php } else { ?>
  <table class="meld-cal-print-table">
    <thead>
      <tr>
        <th>Datum</th>
        <th>Zeit</th>
        <th>Termin</th>
        <th>Ort</th>
        <th>R&uuml;ckmeldung</th>
      </tr>
    </thead>
    <tbody>
<?php foreach($events as $ev) {
    $dateLabel = calendarPrintDateLabel($ev);
    $timeLabel = calendarPrintTimeLabel($ev);
    $name = isset($ev['name']) ? (string)$ev['name'] : '';
    $location = isset($ev['location']) ? (string)$ev['location'] : '';
    $melde = calendarMeldeLabel(isset($ev['wert']) ? $ev['wert'] : null);
?>
      <tr>
        <td><?php echo htmlspecialchars($dateLabel, ENT_QUOTES, 'UTF-8'); ?></td>
        <td class="meld-cal-print-time"><?php echo htmlspecialchars($timeLabel, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($location, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($melde, ENT_QUOTES, 'UTF-8'); ?></td>
      </tr>
<?php } ?>
    </tbody>
  </table>
<?php } ?>
