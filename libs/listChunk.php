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
                $visible = false;
                if($M->published > 0) {
                    $visible = true;
                }
                elseif(requirePermission('perm_showHiddenAppmnts')) {
                    $visible = true;
                }
                elseif($userId > 0 && $M->getMeldungenByUser($userId)) {
                    $visible = true;
                }
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
 * User lists by offset (stable ORDER BY Nachname, Vorname, Index).
 * @param string $kind musiker|users|mitglied
 */
function listChunkUsers($kind, $offset, $limit) {
    $limit = listChunkLimit($limit);
    $offset = max(0, (int)$offset);

    switch($kind) {
    case 'musiker':
        $sql = sprintf(
            'SELECT `%sUser`.`Index` FROM `%sUser` INNER JOIN (SELECT `Index` AS `iIndex`, `Register` FROM `%sInstrument`) `%sInstrument` ON `Instrument` = `iIndex` INNER JOIN (SELECT `Index` AS `rIndex`, `Name` AS `rName` FROM `%sRegister`) `%sRegister` ON `Register` = `rIndex` WHERE `rName` != "keins" AND `Deleted` != 1 ORDER BY `Nachname`, `Vorname`, `%sUser`.`Index` LIMIT %d OFFSET %d;',
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $limit + 1,
            $offset
        );
        $lineMethod = 'printTableLine';
        break;
    case 'mitglied':
        $sql = sprintf(
            'SELECT `Index` FROM `%sUser` WHERE `Mitglied` = 1 AND `Instrument` > 0 AND `Deleted` != 1 ORDER BY `Nachname`, `Vorname`, `Index` LIMIT %d OFFSET %d;',
            $GLOBALS['dbprefix'],
            $limit + 1,
            $offset
        );
        $lineMethod = 'printTableLine';
        break;
    case 'users':
    default:
        $sql = sprintf(
            'SELECT `Index` FROM `%sUser` WHERE `Deleted` != 1 ORDER BY `Nachname`, `Vorname`, `Index` LIMIT %d OFFSET %d;',
            $GLOBALS['dbprefix'],
            $limit + 1,
            $offset
        );
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
