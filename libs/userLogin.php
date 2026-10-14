<?php
/**
 * MELD-248: Login-Normalisierung, Format, Uniqueness, Vorschlag aus Namen.
 */
class UserLogin {
    const MIN_LENGTH = 5;

    /**
     * Lowercase, Umlaute expandieren, nur [a-z0-9] behalten.
     *
     * @param mixed $raw
     * @return string
     */
    public static function normalizeLoginCandidate($raw) {
        $s = trim((string)$raw);
        if($s === '') {
            return '';
        }
        if(function_exists('mb_strtolower')) {
            $s = mb_strtolower($s, 'UTF-8');
        }
        else {
            $s = strtolower($s);
        }
        $map = array(
            'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u',
            'ý' => 'y', 'ÿ' => 'y',
            'ñ' => 'n', 'ç' => 'c',
        );
        $s = strtr($s, $map);
        $s = preg_replace('/[^a-z0-9]+/', '', $s);
        return is_string($s) ? $s : '';
    }

    /**
     * @param mixed $login already normalized preferred
     * @return bool
     */
    public static function isLoginFormatValid($login) {
        $login = (string)$login;
        if(strlen($login) < self::MIN_LENGTH) {
            return false;
        }
        return (bool)preg_match('/^[a-z0-9]+$/', $login);
    }

    /**
     * Non-deleted user already uses this login (collation case-insensitive).
     *
     * @param string $login
     * @param int $excludeUserId
     * @return bool
     */
    public static function loginExists($login, $excludeUserId = 0) {
        $login = self::normalizeLoginCandidate($login);
        if($login === '') {
            return false;
        }
        $excludeUserId = (int)$excludeUserId;
        $sql = sprintf(
            'SELECT `Index` FROM `%sUser`
             WHERE `Deleted` != 1
               AND `login` != \'\'
               AND LOWER(`login`) = LOWER("%s")
               %s
             LIMIT 1;',
            $GLOBALS['dbprefix'],
            mysqli_real_escape_string($GLOBALS['conn'], $login),
            $excludeUserId > 0 ? ('AND `Index` != '.$excludeUserId) : ''
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if(!$dbr) {
            return false;
        }
        return (bool)mysqli_fetch_assoc($dbr);
    }

    /**
     * First letter(s) of Vorname + Nachname; grow Vorname; then numeric suffix.
     *
     * @param string $vorname
     * @param string $nachname
     * @param int $excludeUserId
     * @return string
     */
    public static function suggestLogin($vorname, $nachname, $excludeUserId = 0) {
        $vor = self::normalizeLoginCandidate($vorname);
        $nach = self::normalizeLoginCandidate($nachname);
        $excludeUserId = (int)$excludeUserId;
        $vorLen = strlen($vor);

        for($i = 1; $i <= max(1, $vorLen); $i++) {
            $prefix = $vorLen > 0 ? substr($vor, 0, $i) : '';
            $cand = $prefix.$nach;
            if(strlen($cand) < self::MIN_LENGTH) {
                continue;
            }
            if(!self::loginExists($cand, $excludeUserId)) {
                return $cand;
            }
        }

        $base = $vor.$nach;
        if($base === '') {
            $base = 'user';
        }
        for($n = 2; $n < 100000; $n++) {
            $cand = $base.(string)$n;
            if(strlen($cand) < self::MIN_LENGTH) {
                continue;
            }
            if(!self::loginExists($cand, $excludeUserId)) {
                return $cand;
            }
        }
        return $base.(string)time();
    }

    /**
     * Resolve posted login for save: empty → suggest; unchanged legacy keep; else validate.
     *
     * @param string $postedLogin
     * @param string $vorname
     * @param string $nachname
     * @param int $excludeUserId
     * @param string|null $previousLogin existing DB value before overwrite
     * @return array{ok:bool,login?:string,error?:string}
     */
    public static function resolveForSave($postedLogin, $vorname, $nachname, $excludeUserId = 0, $previousLogin = null) {
        $posted = trim((string)$postedLogin);
        $previousLogin = $previousLogin === null ? null : (string)$previousLogin;

        if($posted === '') {
            return array(
                'ok' => true,
                'login' => self::suggestLogin($vorname, $nachname, $excludeUserId),
            );
        }

        if($previousLogin !== null && $previousLogin !== '') {
            $sameRaw = strcasecmp($posted, $previousLogin) === 0;
            $sameNorm = self::normalizeLoginCandidate($posted) === self::normalizeLoginCandidate($previousLogin);
            if($sameRaw || $sameNorm) {
                return array('ok' => true, 'login' => $previousLogin);
            }
        }

        $normalized = self::normalizeLoginCandidate($posted);
        if(!self::isLoginFormatValid($normalized)) {
            return array(
                'ok' => false,
                'error' => 'Login mindestens 5 Zeichen (a–z, 0–9).',
            );
        }
        if(self::loginExists($normalized, $excludeUserId)) {
            return array(
                'ok' => false,
                'error' => 'Login bereits vergeben.',
            );
        }
        return array('ok' => true, 'login' => $normalized);
    }
}
