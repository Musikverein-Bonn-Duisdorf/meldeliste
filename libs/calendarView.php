<?php
/**
 * In-app month calendar data helpers (MELD-126).
 * Pure data API — no HTML. Reusable for later print/sync tickets.
 */

/**
 * Parse YYYY-MM; invalid → current month.
 *
 * @param string|null $ym
 * @return string YYYY-MM
 */
function calendarParseYearMonth($ym) {
    $ym = trim((string)$ym);
    if(preg_match('/^(\d{4})-(\d{2})$/', $ym, $m)) {
        $y = (int)$m[1];
        $mo = (int)$m[2];
        if($y >= 1970 && $y <= 2100 && $mo >= 1 && $mo <= 12) {
            return sprintf('%04d-%02d', $y, $mo);
        }
    }
    return date('Y-m');
}

/**
 * German month names (1–12).
 *
 * @return array<int,string>
 */
function calendarMonthNames() {
    return array(
        1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
        5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember',
    );
}

/**
 * First Monday on/before the 1st of the month through last Sunday on/after the last day.
 *
 * @param string $ym YYYY-MM
 * @return array{ym:string,year:int,month:int,monthStart:string,monthEnd:string,gridStart:string,gridEnd:string,prevYm:string,nextYm:string,prevYearYm:string,nextYearYm:string,label:string}
 */
function calendarMonthBounds($ym) {
    $ym = calendarParseYearMonth($ym);
    $monthStart = DateTimeImmutable::createFromFormat('Y-m-d', $ym.'-01');
    if($monthStart === false) {
        $ym = date('Y-m');
        $monthStart = DateTimeImmutable::createFromFormat('Y-m-d', $ym.'-01');
    }
    $monthEnd = $monthStart->modify('last day of this month');

    // Monday = 1 … Sunday = 7 (ISO)
    $dow = (int)$monthStart->format('N');
    $gridStart = $monthStart->modify('-'.($dow - 1).' days');
    $dowEnd = (int)$monthEnd->format('N');
    $gridEnd = $monthEnd->modify('+'.(7 - $dowEnd).' days');

    $prev = $monthStart->modify('-1 month');
    $next = $monthStart->modify('+1 month');
    $prevYear = $monthStart->modify('-1 year');
    $nextYear = $monthStart->modify('+1 year');

    $monthsDe = calendarMonthNames();
    $mi = (int)$monthStart->format('n');
    $yi = (int)$monthStart->format('Y');
    $label = $monthsDe[$mi].' '.$yi;

    return array(
        'ym' => $ym,
        'year' => $yi,
        'month' => $mi,
        'monthStart' => $monthStart->format('Y-m-d'),
        'monthEnd' => $monthEnd->format('Y-m-d'),
        'gridStart' => $gridStart->format('Y-m-d'),
        'gridEnd' => $gridEnd->format('Y-m-d'),
        'prevYm' => $prev->format('Y-m'),
        'nextYm' => $next->format('Y-m'),
        'prevYearYm' => $prevYear->format('Y-m'),
        'nextYearYm' => $nextYear->format('Y-m'),
        'label' => $label,
    );
}

/**
 * CSS class for own melde status (optionsDB colorBtn*).
 *
 * @param int|null $wert 1=ja, 2=nein, 3=vielleicht, null/0=ohne
 * @return string
 */
function calendarColorClassForWert($wert) {
    $opts = isset($GLOBALS['optionsDB']) ? $GLOBALS['optionsDB'] : array();
    $w = ($wert === null || $wert === '') ? 0 : (int)$wert;
    if($w === 1) {
        return isset($opts['colorBtnYes']) ? (string)$opts['colorBtnYes'] : 'w3-green';
    }
    if($w === 2) {
        return isset($opts['colorBtnNo']) ? (string)$opts['colorBtnNo'] : 'w3-red';
    }
    if($w === 3) {
        return isset($opts['colorBtnMaybe']) ? (string)$opts['colorBtnMaybe'] : 'w3-blue';
    }
    return isset($opts['colorBtnEdit']) ? (string)$opts['colorBtnEdit'] : 'w3-teal';
}

/**
 * Load visible appointments overlapping [fromDate, toDate] for a user.
 *
 * @param int $userId
 * @param string $fromDate Y-m-d
 * @param string $toDate Y-m-d
 * @return list<array{id:int,name:string,date:string,endDate:string,startTime:string,endTime:string,wert:int|null,colorClass:string}>
 */
function calendarLoadEventsForUser($userId, $fromDate, $toDate) {
    $userId = (int)$userId;
    $fromDate = (string)$fromDate;
    $toDate = (string)$toDate;
    if($userId <= 0 || $fromDate === '' || $toDate === '') {
        return array();
    }

    $conn = $GLOBALS['conn'];
    $prefix = $GLOBALS['dbprefix'];
    $fromEsc = mysqli_real_escape_string($conn, $fromDate);
    $toEsc = mysqli_real_escape_string($conn, $toDate);

    // Overlap: start <= to AND (end OR start) >= from
    // CAST/NULLIF avoids "Incorrect DATE value: ''" when EndDatum is empty string
    $sql = sprintf(
        'SELECT `t`.*, `m`.`Wert` AS `meldeWert` FROM `%sTermine` `t`
        LEFT JOIN `%sMeldungen` `m` ON `m`.`Termin` = `t`.`Index` AND `m`.`User` = %d
        WHERE %s
          AND `t`.`Datum` <= \'%s\'
          AND COALESCE(NULLIF(CAST(`t`.`EndDatum` AS CHAR), \'\'), `t`.`Datum`) >= \'%s\'
        ORDER BY `t`.`Datum`, `t`.`Uhrzeit`, `t`.`Name`;',
        $prefix,
        $prefix,
        $userId,
        Termin::sqlIsListed('`t`.'),
        $toEsc,
        $fromEsc
    );
    $dbr = mysqli_query($conn, $sql);
    sqlerror();
    if(!$dbr) {
        return array();
    }

    $out = array();
    while($row = mysqli_fetch_assoc($dbr)) {
        $t = new Termin();
        $t->fill_from_array($row);
        if(!$t->isVisibleToUser($userId)) {
            continue;
        }
        $wert = null;
        if(isset($row['meldeWert']) && $row['meldeWert'] !== null && $row['meldeWert'] !== '') {
            $wert = (int)$row['meldeWert'];
        }
        $endDate = trim((string)$t->EndDatum);
        if($endDate === '') {
            $endDate = (string)$t->Datum;
        }
        $startTime = trim((string)$t->Uhrzeit);
        $endTime = trim((string)$t->Uhrzeit2);
        $out[] = array(
            'id' => (int)$t->Index,
            'name' => (string)$t->Name,
            'date' => (string)$t->Datum,
            'endDate' => $endDate,
            'startTime' => $startTime,
            'endTime' => $endTime,
            'wert' => $wert,
            'colorClass' => calendarColorClassForWert($wert),
        );
    }
    return $out;
}

/**
 * Expand events onto each day they span within [from, to].
 *
 * @param list<array{id:int,name:string,date:string,endDate:string,startTime:string,endTime:string,wert:int|null,colorClass:string}> $events
 * @param string $fromDate
 * @param string $toDate
 * @return array<string, list<array>> keyed by Y-m-d
 */
function calendarEventsByDay($events, $fromDate, $toDate) {
    $map = array();
    $from = DateTimeImmutable::createFromFormat('Y-m-d', $fromDate);
    $to = DateTimeImmutable::createFromFormat('Y-m-d', $toDate);
    if($from === false || $to === false) {
        return $map;
    }

    foreach($events as $ev) {
        $start = DateTimeImmutable::createFromFormat('Y-m-d', $ev['date']);
        $end = DateTimeImmutable::createFromFormat('Y-m-d', $ev['endDate']);
        if($start === false) {
            continue;
        }
        if($end === false || $end < $start) {
            $end = $start;
        }
        if($end < $from || $start > $to) {
            continue;
        }
        $day = ($start < $from) ? $from : $start;
        $last = ($end > $to) ? $to : $end;
        while($day <= $last) {
            $key = $day->format('Y-m-d');
            if(!isset($map[$key])) {
                $map[$key] = array();
            }
            $map[$key][] = $ev;
            $day = $day->modify('+1 day');
        }
    }
    return $map;
}

/**
 * Short time label for chips (HH:MM or empty).
 *
 * @param string $time
 * @return string
 */
function calendarFormatTimeShort($time) {
    $time = trim((string)$time);
    if($time === '') {
        return '';
    }
    if(preg_match('/^(\d{1,2}):(\d{2})/', $time, $m)) {
        return sprintf('%02d:%02d', (int)$m[1], (int)$m[2]);
    }
    return $time;
}
