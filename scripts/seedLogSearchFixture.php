<?php
/**
 * Lokale Log-Fixture für MELD-189/179-Suche (~12k Einträge + Adventskonzert 2019).
 *
 *   php scripts/seedLogSearchFixture.php
 *   php scripts/seedLogSearchFixture.php --delete
 *
 * Markierung: Message beginnt mit [seed-log-search]
 * Reihenfolge: zuerst Adventskonzert (niedriger Index), danach Filler → ohne Suche
 * weit unten; mit Suche „Adventskonzert 2019“ sofort treffbar.
 */
declare(strict_types=1);

if(PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Nur CLI.\n");
    exit(1);
}

require dirname(__DIR__).'/common/config.php';
mysqli_set_charset($GLOBALS['conn'], 'utf8mb4');
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));

$prefix = $GLOBALS['dbprefix'];
$table = '`'.$prefix.'Log`';
$marker = '[seed-log-search]';
$delete = in_array('--delete', $argv, true);
$fillerCount = 12000;

if($delete) {
    $sqlDel = sprintf(
        'DELETE FROM %s WHERE `Message` LIKE \'%s%%\'',
        $table,
        mysqli_real_escape_string($GLOBALS['conn'], $marker)
    );
    if(!mysqli_query($GLOBALS['conn'], $sqlDel)) {
        fwrite(STDERR, mysqli_error($GLOBALS['conn'])."\n");
        exit(1);
    }
    printf("Gelöscht: %d Zeilen mit %s\n", mysqli_affected_rows($GLOBALS['conn']), $marker);
    exit(0);
}

$existing = mysqli_query($GLOBALS['conn'], "SELECT COUNT(*) AS c FROM {$table} WHERE `Message` LIKE '".mysqli_real_escape_string($GLOBALS['conn'], $marker)."%'");
$row = $existing ? mysqli_fetch_assoc($existing) : null;
if($row && (int)$row['c'] > 0) {
    fwrite(STDERR, "Bereits ".(int)$row['c']." Seed-Zeilen vorhanden. Zuerst: php scripts/seedLogSearchFixture.php --delete\n");
    exit(1);
}

$userId = 0;
$ur = mysqli_query($GLOBALS['conn'], "SELECT `Index` FROM `{$prefix}User` WHERE `Deleted` = 0 ORDER BY `Index` ASC LIMIT 1");
if($ur && ($u = mysqli_fetch_assoc($ur))) {
    $userId = (int)$u['Index'];
}

$advent = array(
    array('2019-12-01 10:15:00', 7, 'Adventskonzert 2019: Termin angelegt (Kirche Duisdorf)'),
    array('2019-12-08 18:30:00', 7, 'Adventskonzert 2019: Probe im Proberaum'),
    array('2019-12-14 19:00:00', 7, 'Adventskonzert 2019: Generalprobe'),
    array('2019-12-15 16:45:00', 6, 'Adventskonzert 2019: Einladung an Mitglieder versendet'),
    array('2019-12-15 17:20:00', 4, 'Adventskonzert 2019: Meldung Ralf Cimiotti = ja'),
    array('2019-12-15 20:05:00', 7, 'Adventskonzert 2019: Konzert erfolgreich, 87 Zuschauer'),
    array('2019-12-16 09:10:00', 5, 'Adventskonzert 2019: Termin-Status auf archiviert gesetzt'),
);

mysqli_begin_transaction($GLOBALS['conn']);

foreach($advent as $entry) {
    list($ts, $type, $msg) = $entry;
    $full = $marker.' '.$msg;
    $sqlIns = sprintf(
        'INSERT INTO %s (`Timestamp`, `User`, `Type`, `Message`) VALUES (\'%s\', %d, %d, \'%s\')',
        $table,
        mysqli_real_escape_string($GLOBALS['conn'], $ts),
        $userId,
        (int)$type,
        mysqli_real_escape_string($GLOBALS['conn'], $full)
    );
    if(!mysqli_query($GLOBALS['conn'], $sqlIns)) {
        mysqli_rollback($GLOBALS['conn']);
        fwrite(STDERR, mysqli_error($GLOBALS['conn'])."\n");
        exit(1);
    }
}

$batchSize = 500;
$types = array(1, 2, 4, 5, 6, 7, 7, 7, 7);
// Nur Tages-Offsets + feste Tageszeiten (10–17 Uhr), vermeidet ungültige DST-Stunden.
$day0 = new DateTimeImmutable('2019-01-01', new DateTimeZone('Europe/Berlin'));
$daySpan = (int)$day0->diff(new DateTimeImmutable('2026-08-14', new DateTimeZone('Europe/Berlin')))->days;

$inserted = 0;
for($offset = 0; $offset < $fillerCount; $offset += $batchSize) {
    $n = min($batchSize, $fillerCount - $offset);
    $values = array();
    for($i = 0; $i < $n; $i++) {
        $idx = $offset + $i;
        $frac = ($idx + 1) / ($fillerCount + 1);
        $dayOffset = (int)round($frac * $daySpan);
        $hour = 10 + ($idx % 8);
        $minute = ($idx * 7) % 60;
        $second = ($idx * 13) % 60;
        $when = $day0->modify('+'.$dayOffset.' days')->setTime($hour, $minute, $second);
        $type = $types[$idx % count($types)];
        $msg = sprintf(
            '%s Noise #%05d Login/Sync/Mail filler (kein Advent)',
            $marker,
            $idx + 1
        );
        $values[] = sprintf(
            '(\'%s\', %d, %d, \'%s\')',
            $when->format('Y-m-d H:i:s'),
            $userId,
            $type,
            mysqli_real_escape_string($GLOBALS['conn'], $msg)
        );
    }
    $sqlBatch = 'INSERT INTO '.$table.' (`Timestamp`, `User`, `Type`, `Message`) VALUES '.implode(',', $values);
    if(!mysqli_query($GLOBALS['conn'], $sqlBatch)) {
        mysqli_rollback($GLOBALS['conn']);
        fwrite(STDERR, mysqli_error($GLOBALS['conn'])."\n");
        exit(1);
    }
    $inserted += $n;
    fwrite(STDOUT, "Filler: {$inserted}/{$fillerCount}\r");
}

mysqli_commit($GLOBALS['conn']);
fwrite(STDOUT, "\n");

$check = mysqli_query(
    $GLOBALS['conn'],
    "SELECT COUNT(*) AS c FROM {$table} WHERE `Message` LIKE '%Adventskonzert 2019%'"
);
$adventCount = $check ? (int)mysqli_fetch_assoc($check)['c'] : 0;
$total = mysqli_query($GLOBALS['conn'], "SELECT COUNT(*) AS c FROM {$table}");
$totalC = $total ? (int)mysqli_fetch_assoc($total)['c'] : 0;

printf(
    "OK: %d Adventskonzert-Zeilen, %d Filler, Log gesamt jetzt %d.\nSuche in der UI: Adventskonzert 2019\nLöschen: php scripts/seedLogSearchFixture.php --delete\n",
    $adventCount,
    $inserted,
    $totalC
);
