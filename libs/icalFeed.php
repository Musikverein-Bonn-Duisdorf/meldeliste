<?php
/**
 * On-demand personal ICS feed (MELD-127, Konzertmeister-style).
 * No static per-user files — build on request.
 */

/**
 * Date window for subscription feeds.
 *
 * @return array{from:string,to:string}
 */
function icalFeedDateWindow() {
    $past = 0;
    if(isset($GLOBALS['optionsDB']['calendarPastDays'])) {
        $past = max(0, (int)$GLOBALS['optionsDB']['calendarPastDays']);
    }
    $future = 365;
    if(isset($GLOBALS['optionsDB']['calendarFutureDays'])) {
        $future = max(1, (int)$GLOBALS['optionsDB']['calendarFutureDays']);
    }
    $today = new DateTimeImmutable('today');
    return array(
        'from' => $today->modify('-'.$past.' days')->format('Y-m-d'),
        'to' => $today->modify('+'.$future.' days')->format('Y-m-d'),
    );
}

/**
 * Drop "Nein" (wert=2); keep ja / vielleicht / ohne.
 *
 * @param list<array{id:int,name:string,date:string,endDate:string,startTime:string,endTime:string,wert:int|null,description?:string,location?:string}> $events
 * @return list<array>
 */
function icalFeedFilterEvents(array $events) {
    $out = array();
    foreach($events as $ev) {
        $wert = isset($ev['wert']) ? $ev['wert'] : null;
        if($wert !== null && $wert !== '' && (int)$wert === 2) {
            continue;
        }
        $out[] = $ev;
    }
    return $out;
}

/**
 * Host part for UID (no scheme/path).
 *
 * @param string|null $websiteUrl
 * @return string
 */
function icalFeedUidHost($websiteUrl = null) {
    if($websiteUrl === null && isset($GLOBALS['optionsDB']['WebSiteURL'])) {
        $websiteUrl = (string)$GLOBALS['optionsDB']['WebSiteURL'];
    }
    $host = parse_url((string)$websiteUrl, PHP_URL_HOST);
    if(is_string($host) && $host !== '') {
        return $host;
    }
    return 'meldeliste.local';
}

/**
 * Stable VEVENT UID.
 *
 * @param int $terminId
 * @param string|null $host
 * @return string
 */
function icalFeedUid($terminId, $host = null) {
    if($host === null || $host === '') {
        $host = icalFeedUidHost();
    }
    return 'meld-'.(int)$terminId.'@'.$host;
}

/**
 * Escape text for ICS CONTENTLINE.
 *
 * @param string $text
 * @return string
 */
function icalFeedEscapeText($text) {
    $text = str_replace(array("\\", ";", ",", "\r\n", "\n", "\r"), array("\\\\", "\\;", "\\,", "\\n", "\\n", "\\n"), (string)$text);
    return $text;
}

/**
 * Fold long ICS lines (max 75 octets, CRLF + space).
 *
 * @param string $line
 * @return string
 */
function icalFeedFoldLine($line) {
    $line = (string)$line;
    if(strlen($line) <= 75) {
        return $line;
    }
    $out = '';
    $chunk = substr($line, 0, 75);
    $rest = substr($line, 75);
    $out .= $chunk;
    while($rest !== '' && $rest !== false) {
        $out .= "\r\n ".substr($rest, 0, 74);
        $rest = substr($rest, 74);
    }
    return $out;
}

/**
 * Melde status label for DESCRIPTION.
 *
 * @param int|null $wert
 * @return string
 */
function icalFeedMeldeLabel($wert) {
    if($wert === null || $wert === '') {
        return 'ohne Rückmeldung';
    }
    $w = (int)$wert;
    if($w === 1) {
        return 'zugesagt';
    }
    if($w === 3) {
        return 'vielleicht';
    }
    if($w === 2) {
        return 'abgesagt';
    }
    return 'ohne Rückmeldung';
}

/**
 * Local DTSTART/DTEND as YmdThis (Europe/Berlin wall clock), matching legacy UserCalendar.
 *
 * @param array{date:string,endDate:string,startTime:string,endTime:string} $ev
 * @return array{begin:string,end:string}
 */
function icalFeedEventTimes(array $ev) {
    $date = (string)$ev['date'];
    $endDate = trim((string)$ev['endDate']);
    if($endDate === '') {
        $endDate = $date;
    }
    $startTime = trim((string)$ev['startTime']);
    $endTime = trim((string)$ev['endTime']);

    $multiDay = ($endDate !== $date);

    if($multiDay) {
        $end = date('Ymd\THis', strtotime($endDate.' 23:59:00'));
        if($endTime !== '') {
            $end = date('Ymd\THis', strtotime($endDate.' '.$endTime));
        }
        if($startTime === '') {
            $begin = date('Ymd\THis', strtotime($date.' 00:00:00'));
            $end = date('Ymd\THis', strtotime($endDate.' 23:59:00'));
        }
        else {
            $begin = date('Ymd\THis', strtotime($date.' '.$startTime));
        }
    }
    else {
        if($startTime === '') {
            $begin = date('Ymd\THis', strtotime($date.' 00:00:00'));
            $end = date('Ymd\THis', strtotime($date.' 23:59:00'));
        }
        else {
            $begin = date('Ymd\THis', strtotime($date.' '.$startTime));
            if($endTime !== '') {
                $end = date('Ymd\THis', strtotime($date.' '.$endTime));
            }
            else {
                $end = date('Ymd\THis', strtotime('+120 minutes', strtotime($date.' '.$startTime)));
            }
        }
    }

    return array('begin' => $begin, 'end' => $end);
}

/**
 * Build full VCALENDAR body (CRLF).
 *
 * @param list<array{id:int,name:string,date:string,endDate:string,startTime:string,endTime:string,wert:int|null,description?:string,location?:string}> $events
 * @param string|null $websiteUrl
 * @return string
 */
function icalFeedBuild(array $events, $websiteUrl = null) {
    if($websiteUrl === null && isset($GLOBALS['optionsDB']['WebSiteURL'])) {
        $websiteUrl = (string)$GLOBALS['optionsDB']['WebSiteURL'];
    }
    $websiteUrl = rtrim((string)$websiteUrl, '/');
    $host = icalFeedUidHost($websiteUrl);
    $dtstamp = gmdate('Ymd\THis\Z');

    $lines = array(
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//Meldeliste//Kalenderabo//DE',
        'CALSCALE:GREGORIAN',
        'METHOD:PUBLISH',
        'X-WR-CALNAME:Meldeliste',
    );

    foreach($events as $ev) {
        $id = (int)$ev['id'];
        $times = icalFeedEventTimes($ev);
        $wert = isset($ev['wert']) ? $ev['wert'] : null;
        $status = ($wert === null || $wert === '' || (int)$wert === 3) ? 'TENTATIVE' : 'CONFIRMED';

        $descParts = array();
        $descParts[] = 'Rückmeldung: '.icalFeedMeldeLabel($wert);
        $besch = isset($ev['description']) ? trim((string)$ev['description']) : '';
        if($besch !== '') {
            $descParts[] = $besch;
        }
        if($websiteUrl !== '') {
            $descParts[] = $websiteUrl.'/';
        }
        $description = implode("\n\n", $descParts);
        $location = isset($ev['location']) ? trim((string)$ev['location']) : '';

        $vevent = array(
            'BEGIN:VEVENT',
            'UID:'.icalFeedUid($id, $host),
            'DTSTAMP:'.$dtstamp,
            'SUMMARY:'.icalFeedEscapeText((string)$ev['name']),
            'DESCRIPTION:'.icalFeedEscapeText($description),
            'STATUS:'.$status,
            'DTSTART;TZID=Europe/Berlin:'.$times['begin'],
            'DTEND;TZID=Europe/Berlin:'.$times['end'],
        );
        if($location !== '') {
            $vevent[] = 'LOCATION:'.icalFeedEscapeText($location);
        }
        $vevent[] = 'END:VEVENT';

        foreach($vevent as $line) {
            $lines[] = icalFeedFoldLine($line);
        }
    }

    $lines[] = 'END:VCALENDAR';
    return implode("\r\n", $lines)."\r\n";
}

/**
 * Weak ETag for conditional GET.
 *
 * @param int $userId
 * @param list<array{id:int,wert:int|null}> $events
 * @param string $from
 * @param string $to
 * @return string quoted ETag
 */
function icalFeedEtag($userId, array $events, $from, $to) {
    $parts = array((int)$userId, (string)$from, (string)$to);
    foreach($events as $ev) {
        $w = isset($ev['wert']) && $ev['wert'] !== null && $ev['wert'] !== '' ? (int)$ev['wert'] : 0;
        $parts[] = (int)$ev['id'].':'.$w.':'.(string)$ev['date'].':'.(string)$ev['endDate'].':'.(string)$ev['startTime'].':'.(string)$ev['endTime'];
    }
    return '"'.hash('sha256', implode('|', $parts)).'"';
}

/**
 * Load filtered feed events for a user (visibility + Nein dropped).
 *
 * @param int $userId
 * @return array{from:string,to:string,events:list<array>}
 */
function icalFeedLoadForUser($userId) {
    $window = icalFeedDateWindow();
    $events = calendarLoadEventsForUser((int)$userId, $window['from'], $window['to']);
    $events = icalFeedFilterEvents($events);
    return array(
        'from' => $window['from'],
        'to' => $window['to'],
        'events' => $events,
    );
}
