<?php
/**
 * App token helpers for WebView auto-login (MELD-49).
 * Raw tokens are returned once; only SHA-256 hashes are stored.
 */

function hashAppToken($rawToken) {
    return hash('sha256', (string)$rawToken);
}

/**
 * Establish the same PHP session keys as password / link login.
 */
function establishSessionFromUserRow($row, $via = 'Password') {
    if((int)$row['Deleted'] === 1) {
        $logentry = new Log;
        $logentry->error("Login via ".$via." verweigert: gelöschter Benutzer.");
        return false;
    }
    $active = array_key_exists('Active', $row) ? (int)$row['Active'] : 1;
    if($active === 0 && trim((string)$row['Passhash']) === '') {
        $logentry = new Log;
        $logentry->error("Login via ".$via." verweigert: Gastmusiker ohne Passwort.");
        return false;
    }
    $_SESSION['userid'] = (int)$row['Index'];
    $_SESSION['Vorname'] = $row['Vorname'];
    $_SESSION['Nachname'] = $row['Nachname'];
    $_SESSION['username'] = $row['Vorname']." ".$row['Nachname'];
    $_SESSION['singleUsePW'] = (bool)$row['singleUsePW'];
    $logentry = new Log;
    $logentry->info("Login via ".$via.".");
    recordLogin();
    $_SESSION['permissions'] = loadPermissions($row['Index']);
    $_SESSION['admin'] = isAdmin() ? 1 : 0;
    return true;
}

/**
 * Normalize an alink value: accept raw hash or full login.php?alink=… URL.
 * Returns the hash or empty string if invalid.
 */
function normalizeAlinkInput($value) {
    $value = trim((string)$value);
    if($value === '') {
        return '';
    }
    if(preg_match('/^[a-zA-Z0-9]+$/', $value)) {
        return $value;
    }
    // Full URL or query fragment containing alink=
    if(preg_match('/[?&]alink=([a-zA-Z0-9]+)/', $value, $m)) {
        return $m[1];
    }
    $parts = @parse_url($value);
    if(is_array($parts) && !empty($parts['query'])) {
        parse_str($parts['query'], $query);
        if(isset($query['alink']) && preg_match('/^[a-zA-Z0-9]+$/', (string)$query['alink'])) {
            return (string)$query['alink'];
        }
    }
    return '';
}

/**
 * Look up a non-deleted user by activeLink hash. Returns user row or null.
 */
function findUserByActiveLink($alinkHash) {
    $alinkHash = trim((string)$alinkHash);
    if($alinkHash === '' || !preg_match('/^[a-zA-Z0-9]+$/', $alinkHash)) {
        return null;
    }
    $sql = sprintf(
        "SELECT * FROM `%sUser` WHERE `activeLink` = '%s' AND `Deleted` != 1 LIMIT 1;",
        $GLOBALS['dbprefix'],
        mysqli_real_escape_string($GLOBALS['conn'], $alinkHash)
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    $row = mysqli_fetch_assoc($dbr);
    return $row ? $row : null;
}

/**
 * Create a new app token for a user. Returns the raw token (store client-side only).
 */
function createAppToken($userId, $deviceLabel = '') {
    $userId = (int)$userId;
    if($userId <= 0) {
        return null;
    }
    $raw = bin2hex(random_bytes(32));
    $hash = hashAppToken($raw);
    $device = mysqli_real_escape_string($GLOBALS['conn'], substr((string)$deviceLabel, 0, 200));
    $sql = sprintf(
        "INSERT INTO `%sAppTokens` (`User`, `TokenHash`, `DeviceLabel`, `Revoked`) VALUES (%d, '%s', '%s', 0);",
        $GLOBALS['dbprefix'],
        $userId,
        mysqli_real_escape_string($GLOBALS['conn'], $hash),
        $device
    );
    mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    if(!mysqli_insert_id($GLOBALS['conn'])) {
        return null;
    }
    return $raw;
}

/**
 * Look up a valid (non-revoked, non-expired) app token. Returns user row or null.
 */
function findUserByAppToken($rawToken) {
    $rawToken = trim((string)$rawToken);
    if($rawToken === '' || strlen($rawToken) < 32) {
        return null;
    }
    $hash = hashAppToken($rawToken);
    $sql = sprintf(
        "SELECT u.*, t.`Index` AS `TokenIndex`
         FROM `%sAppTokens` t
         INNER JOIN `%sUser` u ON u.`Index` = t.`User`
         WHERE t.`TokenHash` = '%s'
           AND t.`Revoked` = 0
           AND (t.`Expires` IS NULL OR t.`Expires` > CURRENT_TIMESTAMP())
           AND u.`Deleted` != 1
         LIMIT 1;",
        $GLOBALS['dbprefix'],
        $GLOBALS['dbprefix'],
        mysqli_real_escape_string($GLOBALS['conn'], $hash)
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    $row = mysqli_fetch_assoc($dbr);
    if(!$row) {
        return null;
    }
    $upd = sprintf(
        "UPDATE `%sAppTokens` SET `LastUsed` = CURRENT_TIMESTAMP() WHERE `Index` = %d;",
        $GLOBALS['dbprefix'],
        (int)$row['TokenIndex']
    );
    mysqli_query($GLOBALS['conn'], $upd);
    return $row;
}

/**
 * Exchange app token for a PHP session. Returns true on success.
 */
function validateAppToken($rawToken) {
    $_SESSION['userid'] = 0;
    $row = findUserByAppToken($rawToken);
    if(!$row) {
        $logentry = new Log;
        $logentry->error("Login not successful. Invalid or revoked app token.");
        return false;
    }
    return establishSessionFromUserRow($row, 'AppToken');
}

/**
 * Revoke a single app token (logout from this device).
 */
function revokeAppToken($rawToken) {
    $rawToken = trim((string)$rawToken);
    if($rawToken === '') {
        return false;
    }
    $hash = hashAppToken($rawToken);
    $sql = sprintf(
        "UPDATE `%sAppTokens` SET `Revoked` = 1 WHERE `TokenHash` = '%s' AND `Revoked` = 0;",
        $GLOBALS['dbprefix'],
        mysqli_real_escape_string($GLOBALS['conn'], $hash)
    );
    mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    return mysqli_affected_rows($GLOBALS['conn']) > 0;
}

/**
 * Read Bearer token from Authorization header or JSON/form `token` field.
 */
function readAppTokenFromRequest() {
    $header = '';
    if(isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $header = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif(isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif(function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        foreach($headers as $k => $v) {
            if(strtolower($k) === 'authorization') {
                $header = $v;
                break;
            }
        }
    }
    if(preg_match('/^\s*Bearer\s+(\S+)\s*$/i', $header, $m)) {
        return $m[1];
    }
    $json = readJsonRequestBody();
    if(isset($json['token']) && is_string($json['token'])) {
        return $json['token'];
    }
    if(isset($_POST['token'])) {
        return (string)$_POST['token'];
    }
    // Do not accept tokens via GET (logs, Referer leakage).
    return '';
}

/**
 * Parse JSON request body once (cached).
 */
function readJsonRequestBody() {
    static $cached = null;
    if($cached !== null) {
        return $cached;
    }
    $cached = array();
    $raw = file_get_contents('php://input');
    if($raw === false || $raw === '') {
        return $cached;
    }
    $decoded = json_decode($raw, true);
    if(is_array($decoded)) {
        $cached = $decoded;
    }
    return $cached;
}

/**
 * Poll notification events for a user since a timestamp (ISO or MySQL datetime).
 */
function pollNotifyEvents($userId, $since) {
    $userId = (int)$userId;
    $events = array();
    $sinceSql = null;
    $since = trim((string)$since);
    if($since !== '') {
        $ts = strtotime($since);
        if($ts !== false) {
            $sinceSql = date('Y-m-d H:i:s', $ts);
        }
    }

    $user = new User;
    $user->load_by_id($userId);
    if((int)$user->Index !== $userId) {
        return $events;
    }

    $notifyAppMail = (int)$user->notifyAppMail === 1;
    $notifyAppTerminNew = (int)$user->notifyAppTerminNew === 1;
    $notifyAppTerminChange = (int)$user->notifyAppTerminChange === 1;
    $notifyAppTerminSoon = (int)$user->notifyAppTerminSoon === 1;

    if($notifyAppMail) {
        $whereSince = '';
        if($sinceSql !== null) {
            $whereSince = sprintf(
                " AND o.`Created` > '%s'",
                mysqli_real_escape_string($GLOBALS['conn'], $sinceSql)
            );
        }

        $sql = sprintf(
            "SELECT o.`Index`, o.`Subject`, o.`Created`, o.`Status`, o.`ReadAt`
             FROM `%sMailOutbox` o
             WHERE o.`User` = %d
               AND o.`DeletedByUser` = 0
               AND o.`Status` IN ('pending','sending','sent')
               %s
             ORDER BY o.`Created` ASC
             LIMIT 50;",
            $GLOBALS['dbprefix'],
            $userId,
            $whereSince
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        while($row = mysqli_fetch_assoc($dbr)) {
            $events[] = array(
                'id' => 'mail-'.(int)$row['Index'],
                'type' => 'mail',
                'title' => (string)$row['Subject'],
                'body' => 'Neue Nachricht in der Meldeliste',
                'created' => date('c', strtotime($row['Created'])),
                'unread' => empty($row['ReadAt']),
                'url' => 'meine-mails.php',
            );
        }
    }

    $needTermine = $notifyAppTerminNew || $notifyAppTerminChange || $notifyAppTerminSoon;
    if($needTermine) {
        $soonUntil = date('Y-m-d', strtotime('+3 days'));
        $today = date('Y-m-d');
        // Without since-cursor: only termin_soon (avoid flood on first poll / after schema backfill)
        $terminSince = $sinceSql;
        $sqlParts = array();
        if($terminSince !== null && ($notifyAppTerminNew || $notifyAppTerminChange)) {
            $sqlParts[] = sprintf(
                "(`Created` > '%s') OR (`Updated` > '%s' AND `Updated` > `Created`)",
                mysqli_real_escape_string($GLOBALS['conn'], $terminSince),
                mysqli_real_escape_string($GLOBALS['conn'], $terminSince)
            );
        }
        if($notifyAppTerminSoon) {
            $sqlParts[] = sprintf(
                "(`Datum` >= '%s' AND `Datum` <= '%s')",
                mysqli_real_escape_string($GLOBALS['conn'], $today),
                mysqli_real_escape_string($GLOBALS['conn'], $soonUntil)
            );
        }
        if(!count($sqlParts)) {
            // nothing to query
        }
        else {
        $sql = sprintf(
            "SELECT `Index` FROM `%sTermine`
             WHERE (%s)
             ORDER BY `Created` DESC, `Updated` DESC, `Datum` ASC
             LIMIT 200;",
            $GLOBALS['dbprefix'],
            implode(' OR ', $sqlParts)
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $terminCount = 0;
        $emitDayStart = date('Y-m-d 00:00:00');
        while($row = mysqli_fetch_assoc($dbr)) {
            if($terminCount >= 50) {
                break;
            }
            $termin = new Termin;
            $termin->load_by_id((int)$row['Index']);
            if(!(int)$termin->Index || !$termin->isVisibleToUser($userId, array('asViewer' => true))) {
                continue;
            }
            $name = (string)$termin->Name;
            $datumDe = function_exists('germanDate') ? germanDate($termin->Datum, true) : (string)$termin->Datum;
            $url = 'index.php';
            $created = (string)$termin->Created;
            $updated = (string)$termin->Updated;

            if($notifyAppTerminNew && $terminSince !== null && $created !== '' && $created > $terminSince) {
                $createdTs = strtotime($created);
                if($createdTs !== false) {
                    $events[] = array(
                        'id' => 'termin-new-'.(int)$termin->Index,
                        'type' => 'termin_new',
                        'title' => 'Neuer Termin: '.$name,
                        'body' => $datumDe,
                        'created' => date('c', $createdTs),
                        'unread' => true,
                        'url' => $url,
                    );
                    $terminCount++;
                }
            }

            if($notifyAppTerminChange && $terminSince !== null && $updated !== '' && $created !== ''
                && $updated > $created
                && $updated > $terminSince) {
                $updatedTs = strtotime($updated);
                if($updatedTs !== false) {
                    $events[] = array(
                        'id' => 'termin-change-'.(int)$termin->Index.'-'.date('YmdHis', $updatedTs),
                        'type' => 'termin_change',
                        'title' => 'Termin geändert: '.$name,
                        'body' => $datumDe,
                        'created' => date('c', $updatedTs),
                        'unread' => true,
                        'url' => $url,
                    );
                    $terminCount++;
                }
            }

            if($notifyAppTerminSoon
                && $termin->Datum >= $today
                && $termin->Datum <= $soonUntil
                && ($sinceSql === null || $sinceSql < $emitDayStart)) {
                $events[] = array(
                    'id' => 'termin-soon-'.(int)$termin->Index.'-'.date('Ymd', strtotime($termin->Datum)),
                    'type' => 'termin_soon',
                    'title' => 'Termin bald: '.$name,
                    'body' => $datumDe,
                    'created' => date('c', strtotime($emitDayStart)),
                    'unread' => true,
                    'url' => $url,
                );
                $terminCount++;
            }
        }
        }
    }

    usort($events, function ($a, $b) {
        return strcmp((string)$a['created'], (string)$b['created']);
    });

    return array_slice($events, 0, 50);
}
?>
