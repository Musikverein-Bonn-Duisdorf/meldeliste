<?php
/**
 * User voice preferences for Notenarchiv Stimmsatz (MELD-117).
 */
class UserVoice
{
    private $_data = array(
        'Index' => null,
        'User' => null,
        'Instrument' => null,
        'VoiceLabel' => null,
        'Priority' => 0,
        'Active' => 1,
    );

    public function __get($key) {
        if(array_key_exists($key, $this->_data)) {
            return $this->_data[$key];
        }
        return null;
    }

    public function __set($key, $val) {
        switch($key) {
        case 'Index':
        case 'User':
        case 'Instrument':
        case 'Priority':
            $this->_data[$key] = (int)$val;
            break;
        case 'Active':
            $this->_data[$key] = (int)(bool)$val;
            break;
        case 'VoiceLabel':
            $this->_data[$key] = trim((string)$val);
            break;
        default:
            break;
        }
    }

    private static function tableName() {
        return $GLOBALS['dbprefix'].'UserVoice';
    }

    public function fill_from_array($row) {
        foreach($row as $key => $val) {
            if(array_key_exists($key, $this->_data)) {
                $this->_data[$key] = $val;
            }
        }
    }

    /**
     * @return UserVoice[]
     */
    public static function listByUser($userId, $activeOnly = true) {
        $userId = (int)$userId;
        if($userId < 1) {
            return array();
        }
        $sql = sprintf(
            'SELECT * FROM `%s` WHERE `User` = %d%s ORDER BY `Priority` ASC, `Index` ASC;',
            self::tableName(),
            $userId,
            $activeOnly ? ' AND `Active` = 1' : ''
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $list = array();
        if($dbr) {
            while($row = mysqli_fetch_assoc($dbr)) {
                $uv = new self();
                $uv->fill_from_array($row);
                $list[] = $uv;
            }
        }
        return $list;
    }

    /**
     * Replace all voice rows for user. $primary: instrument+label; $fallbacks: array of same shape.
     *
     * @param array<int, array{instrument:int, voice:string}> $fallbacks
     */
    public static function savePrimaryAndFallbacks($userId, $primaryInstrument, $primaryVoice, array $fallbacks = array()) {
        $userId = (int)$userId;
        if($userId < 1) {
            return false;
        }
        $table = self::tableName();
        $del = sprintf('DELETE FROM `%s` WHERE `User` = %d;', $table, $userId);
        mysqli_query($GLOBALS['conn'], $del);
        sqlerror();

        $rows = array();
        $primaryInstrument = (int)$primaryInstrument;
        $primaryVoice = trim((string)$primaryVoice);
        if($primaryInstrument > 0 && $primaryVoice !== '') {
            $rows[] = array(
                'Instrument' => $primaryInstrument,
                'VoiceLabel' => $primaryVoice,
                'Priority' => 0,
            );
        }
        $prio = 1;
        foreach($fallbacks as $fb) {
            if(!is_array($fb)) {
                continue;
            }
            $instr = isset($fb['instrument']) ? (int)$fb['instrument'] : 0;
            $voice = isset($fb['voice']) ? trim((string)$fb['voice']) : '';
            if($instr < 1 || $voice === '') {
                continue;
            }
            $rows[] = array(
                'Instrument' => $instr,
                'VoiceLabel' => $voice,
                'Priority' => $prio,
            );
            $prio++;
        }
        foreach($rows as $row) {
            $sql = sprintf(
                "INSERT INTO `%s` (`User`, `Instrument`, `VoiceLabel`, `Priority`, `Active`) VALUES (%d, %d, '%s', %d, 1);",
                $table,
                $userId,
                (int)$row['Instrument'],
                mysqli_real_escape_string($GLOBALS['conn'], $row['VoiceLabel']),
                (int)$row['Priority']
            );
            mysqli_query($GLOBALS['conn'], $sql);
            sqlerror();
        }
        return true;
    }

    /**
     * Load voices for Notenarchiv: primary then fallbacks.
     *
     * @return array<int, array{instrument:int, voice:string, priority:int}>
     */
    public static function voicesForUser($userId) {
        $out = array();
        foreach(self::listByUser($userId) as $uv) {
            $out[] = array(
                'instrument' => (int)$uv->Instrument,
                'voice' => (string)$uv->VoiceLabel,
                'priority' => (int)$uv->Priority,
            );
        }
        return $out;
    }
}
?>
