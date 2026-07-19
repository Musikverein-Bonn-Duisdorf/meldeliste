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
    if(isset($_GET['token'])) {
        return (string)$_GET['token'];
    }
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

    return $events;
}
?>
