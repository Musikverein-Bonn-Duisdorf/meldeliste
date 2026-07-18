<?php
/**
 * One-time SSO tickets for sibling modules (MELD-111).
 * Table lives in Meldeliste DB prefix; sibling apps read via identityPrefix().
 */
class SsoTicket
{
    private static function tableName() {
        return $GLOBALS['dbprefix'].'SsoTicket';
    }

    public static function issue($userId, $target = '', $ttlSeconds = 120) {
        $userId = (int)$userId;
        if($userId < 1) {
            return '';
        }
        $token = bin2hex(random_bytes(32));
        $target = trim((string)$target);
        $expires = date('Y-m-d H:i:s', time() + max(30, (int)$ttlSeconds));
        $sql = sprintf(
            "INSERT INTO `%s` (`Token`, `User`, `Target`, `Expires`, `Used`) VALUES ('%s', %d, %s, '%s', 0);",
            self::tableName(),
            mysqli_real_escape_string($GLOBALS['conn'], $token),
            $userId,
            $target !== '' ? "'".mysqli_real_escape_string($GLOBALS['conn'], $target)."'" : 'NULL',
            mysqli_real_escape_string($GLOBALS['conn'], $expires)
        );
        mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        return $token;
    }

    /**
     * Redeem token; mark used. Returns user id or null.
     */
    public static function redeem($token) {
        $token = trim((string)$token);
        if($token === '') {
            return null;
        }
        $esc = mysqli_real_escape_string($GLOBALS['conn'], $token);
        $table = self::tableName();
        $sql = sprintf(
            "SELECT `Index`, `User` FROM `%s` WHERE `Token` = '%s' AND `Used` = 0 AND `Expires` > NOW() LIMIT 1;",
            $table,
            $esc
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr || !($row = mysqli_fetch_assoc($dbr))) {
            return null;
        }
        $idx = (int)$row['Index'];
        $userId = (int)$row['User'];
        $upd = sprintf(
            "UPDATE `%s` SET `Used` = 1 WHERE `Index` = %d AND `Used` = 0;",
            $table,
            $idx
        );
        mysqli_query($GLOBALS['conn'], $upd);
        sqlerror();
        if(mysqli_affected_rows($GLOBALS['conn']) < 1) {
            return null;
        }
        return $userId;
    }
}
?>
