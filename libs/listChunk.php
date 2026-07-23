<?php
/**
 * Shared chunk loaders for infinite-scroll lists (MELD-55).
 * Returns array: html, nextCursor (string), hasMore (bool), lastIndex (int|null for log).
 */

function listChunkLimit($requested = 50) {
    $n = (int)$requested;
    if($n < 1) $n = 50;
    if($n > 100) $n = 100;
    return $n;
}

function listChunkLog($beforeIndex, $limit) {
    $limit = listChunkLimit($limit);
    $beforeIndex = (int)$beforeIndex;
    if($beforeIndex > 0) {
        $sql = sprintf(
            'SELECT `Index` FROM `%sLog` WHERE `Index` < %d ORDER BY `Index` DESC LIMIT %d;',
            $GLOBALS['dbprefix'],
            $beforeIndex,
            $limit + 1
        );
    }
    else {
        $sql = sprintf(
            'SELECT `Index` FROM `%sLog` ORDER BY `Index` DESC LIMIT %d;',
            $GLOBALS['dbprefix'],
            $limit + 1
        );
    }
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    $ids = array();
    if($dbr) {
        while($row = mysqli_fetch_array($dbr)) {
            $ids[] = (int)$row['Index'];
        }
    }
    $hasMore = count($ids) > $limit;
    if($hasMore) {
        $ids = array_slice($ids, 0, $limit);
    }
    $html = '';
    $next = $beforeIndex;
    // Capture all echoes (printTableLine uses echo; must not leak before page chrome)
    ob_start();
    foreach($ids as $id) {
        $M = new Log;
        $M->load_by_id($id);
        $M->printTableLine();
        $next = $id;
    }
    $html = ob_get_clean();
    if($html === false) $html = '';
    return array(
        'html' => $html,
        'nextCursor' => (string)$next,
        'hasMore' => $hasMore,
    );
}

/**
 * @param string $mode 'future'|'past'
 * @param string $render 'basic'|'response'
 * @param string $cursor "datum|id" or empty for start
 */
function listChunkTermine($mode, $render, $cursor, $limit, $userId = 0) {
    $limit = listChunkLimit($limit);
    $now = date('Y-m-d');
    $userId = (int)$userId;
    if($userId < 1 && isset($_SESSION['userid'])) {
        $userId = (int)$_SESSION['userid'];
    }

    $cursorDatum = '';
    $cursorId = 0;
    if($cursor !== '' && strpos($cursor, '|') !== false) {
        $parts = explode('|', $cursor, 2);
        $cursorDatum = $parts[0];
        $cursorId = (int)$parts[1];
    }

    $html = '';
    $emitted = 0;
    $nextCursor = $cursor;
    $hasMore = false;
    $batch = max($limit * 3, 50);
    $safety = 0;

    while($emitted < $limit && $safety < 20) {
        $safety++;
        if($mode === 'past') {
            if($cursorDatum !== '') {
                $sql = sprintf(
                    'SELECT `Index`, `Datum`, `Uhrzeit` FROM `%sTermine` WHERE `Datum` <= "%s" AND ((`Datum` < "%s") OR (`Datum` = "%s" AND `Index` < %d)) ORDER BY `Datum` DESC, `Index` DESC LIMIT %d;',
                    $GLOBALS['dbprefix'],
                    mysqli_real_escape_string($GLOBALS['conn'], $now),
                    mysqli_real_escape_string($GLOBALS['conn'], $cursorDatum),
                    mysqli_real_escape_string($GLOBALS['conn'], $cursorDatum),
                    $cursorId,
                    $batch
                );
            }
            else {
                $sql = sprintf(
                    'SELECT `Index`, `Datum`, `Uhrzeit` FROM `%sTermine` WHERE `Datum` <= "%s" ORDER BY `Datum` DESC, `Index` DESC LIMIT %d;',
                    $GLOBALS['dbprefix'],
                    mysqli_real_escape_string($GLOBALS['conn'], $now),
                    $batch
                );
            }
        }
        else {
            if($cursorDatum !== '') {
                $sql = sprintf(
                    'SELECT `Index`, `Datum`, `Uhrzeit` FROM `%sTermine` WHERE `Datum` >= "%s" AND ((`Datum` > "%s") OR (`Datum` = "%s" AND `Index` > %d)) ORDER BY `Datum` ASC, `Index` ASC LIMIT %d;',
                    $GLOBALS['dbprefix'],
                    mysqli_real_escape_string($GLOBALS['conn'], $now),
                    mysqli_real_escape_string($GLOBALS['conn'], $cursorDatum),
                    mysqli_real_escape_string($GLOBALS['conn'], $cursorDatum),
                    $cursorId,
                    $batch
                );
            }
            else {
                $sql = sprintf(
                    'SELECT `Index`, `Datum`, `Uhrzeit` FROM `%sTermine` WHERE `Datum` >= "%s" ORDER BY `Datum` ASC, `Index` ASC LIMIT %d;',
                    $GLOBALS['dbprefix'],
                    mysqli_real_escape_string($GLOBALS['conn'], $now),
                    $batch
                );
            }
        }

        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $rows = array();
        if($dbr) {
            while($row = mysqli_fetch_array($dbr)) {
                $rows[] = $row;
            }
        }
        if(!$rows) {
            $hasMore = false;
            break;
        }

        $batchHadVisible = false;
        foreach($rows as $row) {
            $cursorDatum = $row['Datum'];
            $cursorId = (int)$row['Index'];
            $nextCursor = $cursorDatum.'|'.$cursorId;

            $M = new Termin;
            $M->load_by_id($cursorId);

            $visible = true;
            if($mode === 'future' && $render === 'basic') {
                $visible = $M->isVisibleToUser((int)$userId);
            }

            if(!$visible) {
                continue;
            }
            $batchHadVisible = true;
            if($render === 'response') {
                $html .= $M->printResponseLine();
            }
            else {
                $html .= $M->printBasicTableLine();
            }
            $emitted++;
            if($emitted >= $limit) {
                break;
            }
        }

        if(count($rows) < $batch) {
            $hasMore = false;
            break;
        }
        $hasMore = true;
        if(!$batchHadVisible && $emitted < $limit) {
            continue;
        }
        if($emitted >= $limit) {
            // Check if anything remains after cursor
            if($mode === 'past') {
                $check = sprintf(
                    'SELECT `Index` FROM `%sTermine` WHERE `Datum` <= "%s" AND ((`Datum` < "%s") OR (`Datum` = "%s" AND `Index` < %d)) LIMIT 1;',
                    $GLOBALS['dbprefix'],
                    mysqli_real_escape_string($GLOBALS['conn'], $now),
                    mysqli_real_escape_string($GLOBALS['conn'], $cursorDatum),
                    mysqli_real_escape_string($GLOBALS['conn'], $cursorDatum),
                    $cursorId
                );
            }
            else {
                $check = sprintf(
                    'SELECT `Index` FROM `%sTermine` WHERE `Datum` >= "%s" AND ((`Datum` > "%s") OR (`Datum` = "%s" AND `Index` > %d)) LIMIT 1;',
                    $GLOBALS['dbprefix'],
                    mysqli_real_escape_string($GLOBALS['conn'], $now),
                    mysqli_real_escape_string($GLOBALS['conn'], $cursorDatum),
                    mysqli_real_escape_string($GLOBALS['conn'], $cursorDatum),
                    $cursorId
                );
            }
            $cdb = mysqli_query($GLOBALS['conn'], $check);
            $hasMore = ($cdb && mysqli_fetch_array($cdb)) ? true : false;
            break;
        }
    }

    return array(
        'html' => $html,
        'nextCursor' => (string)$nextCursor,
        'hasMore' => $hasMore,
    );
}

/**
 * User lists by offset.
 * @param string $kind musiker|users|mitglied|gastmusiker
 * @param string $sort Whitelisted column key (nachname|vorname|instrument|email|lastlogin|lastvisit|index)
 * @param string $dir asc|desc
 */
function listChunkUsers($kind, $offset, $limit, $sort = '', $dir = 'asc') {
    $limit = listChunkLimit($limit);
    $offset = max(0, (int)$offset);
    $dirSql = (strtolower((string)$dir) === 'desc') ? 'DESC' : 'ASC';
    $sort = strtolower(trim((string)$sort));
    $p = $GLOBALS['dbprefix'];

    $lastVisitJoin = sprintf(
        'LEFT JOIN (SELECT `m`.`User` AS `lvUser`, MAX(`t`.`Datum`) AS `lastVisit` FROM `%sMeldungen` `m` INNER JOIN `%sTermine` `t` ON `m`.`Termin` = `t`.`Index` WHERE `m`.`Wert` = 1 AND `t`.`Datum` <= CURRENT_DATE() GROUP BY `m`.`User`) `lv` ON `lv`.`lvUser` = `%sUser`.`Index`',
        $p,
        $p,
        $p
    );
    $lastVisitOrder = '(lastVisit IS NULL) ASC, lastVisit '.$dirSql;

    $orderMusiker = function($sort, $dirSql) use ($p, $lastVisitOrder) {
        switch($sort) {
        case 'vorname':
            return '`Vorname` '.$dirSql.', `Nachname` ASC, `'.$p.'User`.`Index` ASC';
        case 'instrument':
            return '`iName` '.$dirSql.', `Nachname` ASC, `Vorname` ASC, `'.$p.'User`.`Index` ASC';
        case 'email':
            return '`Email` '.$dirSql.', `Nachname` ASC, `Vorname` ASC, `'.$p.'User`.`Index` ASC';
        case 'lastlogin':
            return '`LastLogin` '.$dirSql.', `Nachname` ASC, `Vorname` ASC, `'.$p.'User`.`Index` ASC';
        case 'lastvisit':
            return $lastVisitOrder.', `Nachname` ASC, `Vorname` ASC, `'.$p.'User`.`Index` ASC';
        case 'nachname':
            return '`Nachname` '.$dirSql.', `Vorname` ASC, `'.$p.'User`.`Index` ASC';
        default:
            return '`Nachname` ASC, `Vorname` ASC, `'.$p.'User`.`Index` ASC';
        }
    };

    $orderPlain = function($sort, $dirSql) use ($lastVisitOrder) {
        switch($sort) {
        case 'vorname':
            return '`Vorname` '.$dirSql.', `Nachname` ASC, `Index` ASC';
        case 'email':
            return '`Email` '.$dirSql.', `Nachname` ASC, `Vorname` ASC, `Index` ASC';
        case 'lastlogin':
            return '`LastLogin` '.$dirSql.', `Nachname` ASC, `Vorname` ASC, `Index` ASC';
        case 'lastvisit':
            return $lastVisitOrder.', `Nachname` ASC, `Vorname` ASC, `Index` ASC';
        case 'index':
            return '`Index` '.$dirSql;
        case 'instrument':
            return '`iName` '.$dirSql.', `Nachname` ASC, `Vorname` ASC, `Index` ASC';
        case 'nachname':
            return '`Nachname` '.$dirSql.', `Vorname` ASC, `Index` ASC';
        default:
            return '`Nachname` ASC, `Vorname` ASC, `Index` ASC';
        }
    };

    $needLastVisit = ($sort === 'lastvisit');
    $needInstrument = ($sort === 'instrument');

    switch($kind) {
    case 'musiker':
        $orderBy = $orderMusiker($sort, $dirSql);
        $sql = sprintf(
            'SELECT `%sUser`.`Index` FROM `%sUser` INNER JOIN (SELECT `Index` AS `iIndex`, `Register`, `Name` AS `iName` FROM `%sInstrument`) `%sInstrument` ON `Instrument` = `iIndex` INNER JOIN (SELECT `Index` AS `rIndex`, `Name` AS `rName` FROM `%sRegister`) `%sRegister` ON `Register` = `rIndex` %s WHERE `rName` != "keins" AND `Deleted` != 1 AND `Active` = 1 ORDER BY %s LIMIT %d OFFSET %d;',
            $p, $p, $p, $p, $p, $p,
            $needLastVisit ? $lastVisitJoin : '',
            $orderBy,
            $limit + 1,
            $offset
        );
        $lineMethod = 'printTableLine';
        break;
    case 'mitglied':
        $orderBy = $orderPlain($sort, $dirSql);
        if($needInstrument || $needLastVisit) {
            $sql = sprintf(
                'SELECT `%sUser`.`Index` FROM `%sUser` LEFT JOIN (SELECT `Index` AS `iIndex`, `Name` AS `iName` FROM `%sInstrument`) `%sInstrument` ON `Instrument` = `iIndex` %s WHERE `Mitglied` = 1 AND `Instrument` > 0 AND `Deleted` != 1 ORDER BY %s LIMIT %d OFFSET %d;',
                $p, $p, $p, $p,
                $needLastVisit ? $lastVisitJoin : '',
                $orderBy,
                $limit + 1,
                $offset
            );
        }
        else {
            $sql = sprintf(
                'SELECT `Index` FROM `%sUser` WHERE `Mitglied` = 1 AND `Instrument` > 0 AND `Deleted` != 1 ORDER BY %s LIMIT %d OFFSET %d;',
                $p,
                $orderBy,
                $limit + 1,
                $offset
            );
        }
        $lineMethod = 'printTableLine';
        break;
    case 'gastmusiker':
        $orderBy = $orderPlain($sort, $dirSql);
        if($needInstrument || $needLastVisit) {
            $sql = sprintf(
                'SELECT `%sUser`.`Index` FROM `%sUser` LEFT JOIN (SELECT `Index` AS `iIndex`, `Name` AS `iName` FROM `%sInstrument`) `%sInstrument` ON `Instrument` = `iIndex` %s WHERE `Active` = 0 AND `Deleted` != 1 ORDER BY %s LIMIT %d OFFSET %d;',
                $p, $p, $p, $p,
                $needLastVisit ? $lastVisitJoin : '',
                $orderBy,
                $limit + 1,
                $offset
            );
        }
        else {
            $sql = sprintf(
                'SELECT `Index` FROM `%sUser` WHERE `Active` = 0 AND `Deleted` != 1 ORDER BY %s LIMIT %d OFFSET %d;',
                $p,
                $orderBy,
                $limit + 1,
                $offset
            );
        }
        $lineMethod = 'printTableLine';
        break;
    case 'users':
    default:
        $orderBy = $orderPlain($sort === 'instrument' ? 'nachname' : $sort, $dirSql);
        if($needLastVisit) {
            $sql = sprintf(
                'SELECT `%sUser`.`Index` FROM `%sUser` %s WHERE `Deleted` != 1 ORDER BY %s LIMIT %d OFFSET %d;',
                $p, $p,
                $lastVisitJoin,
                $orderBy,
                $limit + 1,
                $offset
            );
        }
        else {
            $sql = sprintf(
                'SELECT `Index` FROM `%sUser` WHERE `Deleted` != 1 ORDER BY %s LIMIT %d OFFSET %d;',
                $p,
                $orderBy,
                $limit + 1,
                $offset
            );
        }
        $lineMethod = 'printUserTableLine';
        break;
    }

    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    $ids = array();
    if($dbr) {
        while($row = mysqli_fetch_array($dbr)) {
            $ids[] = (int)$row['Index'];
        }
    }
    $hasMore = count($ids) > $limit;
    if($hasMore) {
        $ids = array_slice($ids, 0, $limit);
    }
    $html = '';
    foreach($ids as $id) {
        $M = new User;
        $M->load_by_id($id);
        ob_start();
        $M->$lineMethod();
        $html .= ob_get_clean();
    }
    $nextOffset = $offset + count($ids);
    return array(
        'html' => $html,
        'nextCursor' => (string)$nextOffset,
        'hasMore' => $hasMore,
    );
}

/**
 * Compact Created/list timestamps for mail list meta (short weekday + date).
 */
function mailListFormatDate($raw) {
    $raw = (string)$raw;
    if($raw === '') return '';
    $out = (string)germanDates($raw, true, true);
    if(strlen($raw) >= 16) {
        $out .= ' '.sql2timeRaw(substr($raw, 11, 8));
    }
    return $out;
}

/**
 * Resolve user display name with a shared cache map.
 */
function mailListResolveUserName($userId, &$userNameCache) {
    $userId = (int)$userId;
    if($userId <= 0) return 'System';
    if(!isset($userNameCache[$userId])) {
        $u = new User;
        $u->load_by_id($userId);
        $userNameCache[$userId] = $u->Index ? $u->getName() : ('User '.$userId);
    }
    return $userNameCache[$userId];
}

/**
 * One row for Meine Nachrichten overview.
 * @param array $row MailOutbox fields + optional SenderId
 */
function mailOutboxRenderListItemHtml(array $row, &$userNameCache = null) {
    if(!is_array($userNameCache)) {
        $userNameCache = array();
    }
    $id = (int)$row['Index'];
    $unread = empty($row['ReadAt']);
    $when = htmlspecialchars(mailListFormatDate(isset($row['Created']) ? $row['Created'] : ''), ENT_QUOTES, 'UTF-8');
    $senderName = mailListResolveUserName(isset($row['SenderId']) ? $row['SenderId'] : 0, $userNameCache);
    $sender = htmlspecialchars($senderName, ENT_QUOTES, 'UTF-8');
    $subject = (isset($row['Subject']) && $row['Subject'] !== '' && $row['Subject'] !== null)
        ? htmlspecialchars((string)$row['Subject'], ENT_QUOTES, 'UTF-8')
        : '<em>(ohne Betreff)</em>';
    $rowCls = $unread ? ' mail-unread' : '';
    $neu = $unread
        ? '<span class="w3-tag '.$GLOBALS['optionsDB']['colorLogEmail'].'">neu</span>'
        : '';
    $mailFail = (isset($row['Status']) && $row['Status'] === 'failed')
        ? ' <span class="w3-tag '.(isset($GLOBALS['optionsDB']['colorLogError']) ? $GLOBALS['optionsDB']['colorLogError'] : 'w3-red').'">E-Mail fehlgeschlagen</span>'
        : '';
    if($unread) {
        $subject = '<strong>'.$subject.'</strong>';
    }
    $searchBits = array(
        isset($row['Subject']) ? (string)$row['Subject'] : '',
        $senderName,
        isset($row['Created']) ? (string)$row['Created'] : '',
        $unread ? 'neu' : '',
        (isset($row['Status']) && $row['Status'] === 'failed') ? 'fehlgeschlagen' : '',
    );
    $html = '<div class="mail-list-item'.$rowCls.'" data-search="'.htmlspecialchars(implode(' ', $searchBits), ENT_QUOTES, 'UTF-8').'">';
    $html .= '<div class="mail-list-primary"><a href="meine-mails.php?id='.$id.'">'.$subject.'</a></div>';
    $html .= '<div class="mail-list-meta">'.$when.' · '.$sender.'</div>';
    $html .= '<div class="mail-list-status">'.$neu.$mailFail.'</div>';
    $html .= '<div class="mail-list-actions">';
    $html .= '<a class="w3-button w3-small '.$GLOBALS['optionsDB']['colorBtnEdit'].'" href="meine-mails.php?id='.$id.'">Anzeigen</a>';
    $html .= '<form method="post" action="meine-mails.php" onsubmit="return confirm(\'Nachricht ausblenden?\');">';
    $html .= '<input type="hidden" name="id" value="'.$id.'" />';
    $html .= '<button type="submit" name="delete" value="1" class="w3-button w3-small '.$GLOBALS['optionsDB']['colorBtnNo'].'">Ausblenden</button>';
    $html .= '</form>';
    $html .= '</div>';
    $html .= '</div>';
    return $html;
}

/**
 * Admin mail job list chunks (ORDER BY queue/created DESC, Index DESC).
 * Cursor: "timestamp|id" of last emitted row, or empty for start.
 * @return array{html:string,nextCursor:string,hasMore:bool,sendingIds:int[]}
 */
function listChunkMailJobs($cursor, $limit) {
    $limit = listChunkLimit($limit);
    MailJob::ensureSchema();
    $prefix = $GLOBALS['dbprefix'];
    $cursorTs = '';
    $cursorId = 0;
    if($cursor !== '' && strpos($cursor, '|') !== false) {
        $parts = explode('|', $cursor, 2);
        $cursorTs = $parts[0];
        $cursorId = (int)$parts[1];
    }
    $sortExpr = sprintf(
        'COALESCE((SELECT MIN(o2.`Created`) FROM `%sMailOutbox` o2 WHERE o2.`Job` = j.`Index`), j.`Created`)',
        $prefix
    );
    $queueCreated = sprintf(
        '(SELECT MIN(o.`Created`) FROM `%sMailOutbox` o WHERE o.`Job` = j.`Index`) AS `QueueCreated`',
        $prefix
    );
    if($cursorTs !== '') {
        $esc = mysqli_real_escape_string($GLOBALS['conn'], $cursorTs);
        $sql = sprintf(
            'SELECT j.*, %s FROM `%sMailJob` j
             WHERE (%s < "%s") OR (%s = "%s" AND j.`Index` < %d)
             ORDER BY %s DESC, j.`Index` DESC LIMIT %d;',
            $queueCreated,
            $prefix,
            $sortExpr,
            $esc,
            $sortExpr,
            $esc,
            $cursorId,
            $sortExpr,
            $limit + 1
        );
    }
    else {
        $sql = sprintf(
            'SELECT j.*, %s FROM `%sMailJob` j ORDER BY %s DESC, j.`Index` DESC LIMIT %d;',
            $queueCreated,
            $prefix,
            $sortExpr,
            $limit + 1
        );
    }
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    $jobs = array();
    if($dbr) {
        while($row = mysqli_fetch_array($dbr)) {
            $j = new MailJob;
            $j->fill_from_array($row);
            $jobs[] = $j;
        }
    }
    $hasMore = count($jobs) > $limit;
    if($hasMore) {
        $jobs = array_slice($jobs, 0, $limit);
    }
    $html = '';
    $userNameCache = array();
    $sendingIds = array();
    $nextCursor = $cursor;
    foreach($jobs as $job) {
        $html .= $job->renderAdminListItemHtml($userNameCache, $sendingIds);
        $nextCursor = $job->listTimestamp().'|'.(int)$job->Index;
    }
    return array(
        'html' => $html,
        'nextCursor' => (string)$nextCursor,
        'hasMore' => $hasMore,
        'sendingIds' => $sendingIds,
    );
}

/**
 * User inbox (Meine Nachrichten) chunks.
 * Cursor: "created|id" of last emitted row, or empty for start.
 */
function listChunkUserMails($userId, $cursor, $limit) {
    $limit = listChunkLimit($limit);
    $userId = (int)$userId;
    MailJob::ensureSchema();
    $prefix = $GLOBALS['dbprefix'];
    $cursorTs = '';
    $cursorId = 0;
    if($cursor !== '' && strpos($cursor, '|') !== false) {
        $parts = explode('|', $cursor, 2);
        $cursorTs = $parts[0];
        $cursorId = (int)$parts[1];
    }
    $baseWhere = sprintf(
        'o.`User` = %d AND o.`DeletedByUser` = 0 AND o.`Status` IN ("pending", "sending", "sent", "failed")',
        $userId
    );
    if($cursorTs !== '') {
        $esc = mysqli_real_escape_string($GLOBALS['conn'], $cursorTs);
        $sql = sprintf(
            'SELECT o.*, j.`CreatedBy` AS `SenderId`
             FROM `%sMailOutbox` o
             LEFT JOIN `%sMailJob` j ON j.`Index` = o.`Job`
             WHERE %s AND ((o.`Created` < "%s") OR (o.`Created` = "%s" AND o.`Index` < %d))
             ORDER BY o.`Created` DESC, o.`Index` DESC LIMIT %d;',
            $prefix,
            $prefix,
            $baseWhere,
            $esc,
            $esc,
            $cursorId,
            $limit + 1
        );
    }
    else {
        $sql = sprintf(
            'SELECT o.*, j.`CreatedBy` AS `SenderId`
             FROM `%sMailOutbox` o
             LEFT JOIN `%sMailJob` j ON j.`Index` = o.`Job`
             WHERE %s
             ORDER BY o.`Created` DESC, o.`Index` DESC LIMIT %d;',
            $prefix,
            $prefix,
            $baseWhere,
            $limit + 1
        );
    }
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    $rows = array();
    if($dbr) {
        while($row = mysqli_fetch_assoc($dbr)) {
            $rows[] = $row;
        }
    }
    $hasMore = count($rows) > $limit;
    if($hasMore) {
        $rows = array_slice($rows, 0, $limit);
    }
    $html = '';
    $userNameCache = array();
    $nextCursor = $cursor;
    foreach($rows as $row) {
        $html .= mailOutboxRenderListItemHtml($row, $userNameCache);
        $nextCursor = (string)$row['Created'].'|'.(int)$row['Index'];
    }
    return array(
        'html' => $html,
        'nextCursor' => (string)$nextCursor,
        'hasMore' => $hasMore,
    );
}

function listChunkRenderSentinelAttrs($type, $cursor, $hasMore, $filterFn = '') {
    $attrs = ' id="listSentinel" data-list-type="'.htmlspecialchars($type, ENT_QUOTES, 'UTF-8').'"'
        .' data-cursor="'.htmlspecialchars((string)$cursor, ENT_QUOTES, 'UTF-8').'"'
        .' data-has-more="'.($hasMore ? '1' : '0').'"';
    if($filterFn !== '') {
        $attrs .= ' data-filter-fn="'.htmlspecialchars($filterFn, ENT_QUOTES, 'UTF-8').'"';
    }
    return $attrs;
}

/**
 * Full status/sentinel bar for infinite scroll (loading + end-of-list).
 * @param string $extraHtmlAttrs e.g. ' data-extra="user=1"'
 */
function listChunkRenderSentinel($type, $cursor, $hasMore, $filterFn = '', $extraHtmlAttrs = '') {
    $attrs = listChunkRenderSentinelAttrs($type, $cursor, $hasMore, $filterFn).$extraHtmlAttrs;
    if($hasMore) {
        // Invisible sentinel for IntersectionObserver; status text is set by JS while loading / at end
        return '<div'.$attrs.' style="clear:both;height:1px;padding:0;margin:0;"></div>';
    }
    return '<div class="w3-panel w3-padding w3-center w3-margin-top w3-light-grey"'
        .$attrs
        .' style="clear:both;">Keine weiteren Einträge</div>';
}
?>
