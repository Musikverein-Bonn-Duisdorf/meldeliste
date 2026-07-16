<?php
/**
 * Five editable brand color schemes for Meldeliste.
 * Live color* config params mirror the active scheme.
 */

function getColorSchemeIds() {
    return array('classic', 'light', 'dark', 'gold', 'soft');
}

function getSemanticColorDefaults() {
    return array(
        'colorLogFatal' => '#F44336',
        'colorLogError' => '#FF5722',
        'colorBtnYes' => '#4CAF50',
        'colorBtnNo' => '#F44336',
        'colorBtnDelete' => '#F44336',
        'colorSuccess' => '#4CAF50',
        'colorAppmntYes' => '#004D33',
        'colorAppmntNo' => '#A6001A',
        'colorUserNoMember' => '',
        'colorAppmntNoConcert' => '',
    );
}

function getDefaultColorSchemes() {
    $sem = getSemanticColorDefaults();
    return array(
        'classic' => array(
            'name' => 'Klassisch',
            'colors' => array_merge($sem, array(
                'colorBackground' => '#FDF9E7',
                'colorTitle' => '#345A95',
                'colorTitleBar' => '#345A95',
                'colorNav' => '#345A95',
                'colorNavAdmin' => '#454545',
                'colorBtnSubmit' => '#345A95',
                'colorBtnEdit' => '#7F9DC1',
                'colorBtnMaybe' => '#345A95',
                'colorInputBackground' => '#FDF9E7',
                'colorWarning' => '#FFC300',
                'colorDisabled' => '#969696',
                'colorUserMember' => '#7F9DC1',
                'colorAppmntConcert' => '#7F9DC1',
                'colorAppmntMaybe' => '#345A95',
                'colorLogDefault' => '#969696',
                'colorLogWarning' => '#345A95',
                'colorLogDBDelete' => '#7F9DC1',
                'colorLogDBInsert' => '#FFC300',
                'colorLogDBUpdate' => '#FDF9E7',
                'colorLogEmail' => '#FFC300',
                'colorLogInfo' => '#7F9DC1',
            )),
        ),
        'light' => array(
            'name' => 'Hell',
            'colors' => array_merge($sem, array(
                'colorBackground' => '#FDFFFC',
                'colorTitle' => '#345A95',
                'colorTitleBar' => '#7F9DC1',
                'colorNav' => '#345A95',
                'colorNavAdmin' => '#969696',
                'colorBtnSubmit' => '#345A95',
                'colorBtnEdit' => '#7F9DC1',
                'colorBtnMaybe' => '#7F9DC1',
                'colorInputBackground' => '#FDFFFC',
                'colorWarning' => '#FFC300',
                'colorDisabled' => '#969696',
                'colorUserMember' => '#7F9DC1',
                'colorAppmntConcert' => '#345A95',
                'colorAppmntMaybe' => '#7F9DC1',
                'colorLogDefault' => '#969696',
                'colorLogWarning' => '#345A95',
                'colorLogDBDelete' => '#7F9DC1',
                'colorLogDBInsert' => '#FFC300',
                'colorLogDBUpdate' => '#FDFFFC',
                'colorLogEmail' => '#FFC300',
                'colorLogInfo' => '#7F9DC1',
            )),
        ),
        'dark' => array(
            'name' => 'Dunkel',
            'colors' => array_merge($sem, array(
                'colorBackground' => '#040006',
                'colorTitle' => '#345A95',
                'colorTitleBar' => '#454545',
                'colorNav' => '#345A95',
                'colorNavAdmin' => '#454545',
                'colorBtnSubmit' => '#7F9DC1',
                'colorBtnEdit' => '#7F9DC1',
                'colorBtnMaybe' => '#7F9DC1',
                'colorInputBackground' => '#454545',
                'colorWarning' => '#FFC300',
                'colorDisabled' => '#969696',
                'colorUserMember' => '#7F9DC1',
                'colorAppmntConcert' => '#345A95',
                'colorAppmntMaybe' => '#7F9DC1',
                'colorLogDefault' => '#454545',
                'colorLogWarning' => '#7F9DC1',
                'colorLogDBDelete' => '#345A95',
                'colorLogDBInsert' => '#FFC300',
                'colorLogDBUpdate' => '#454545',
                'colorLogEmail' => '#FFC300',
                'colorLogInfo' => '#7F9DC1',
            )),
        ),
        'gold' => array(
            'name' => 'Gold',
            'colors' => array_merge($sem, array(
                'colorBackground' => '#FDF9E7',
                'colorTitle' => '#FFC300',
                'colorTitleBar' => '#345A95',
                'colorNav' => '#345A95',
                'colorNavAdmin' => '#454545',
                'colorBtnSubmit' => '#FFC300',
                'colorBtnEdit' => '#345A95',
                'colorBtnMaybe' => '#7F9DC1',
                'colorInputBackground' => '#FDFFFC',
                'colorWarning' => '#FFC300',
                'colorDisabled' => '#969696',
                'colorUserMember' => '#345A95',
                'colorAppmntConcert' => '#FFC300',
                'colorAppmntMaybe' => '#345A95',
                'colorLogDefault' => '#969696',
                'colorLogWarning' => '#FFC300',
                'colorLogDBDelete' => '#7F9DC1',
                'colorLogDBInsert' => '#FFC300',
                'colorLogDBUpdate' => '#FDF9E7',
                'colorLogEmail' => '#FFC300',
                'colorLogInfo' => '#345A95',
            )),
        ),
        'soft' => array(
            'name' => 'Soft-Blau',
            'colors' => array_merge($sem, array(
                'colorBackground' => '#FDFFFC',
                'colorTitle' => '#7F9DC1',
                'colorTitleBar' => '#7F9DC1',
                'colorNav' => '#7F9DC1',
                'colorNavAdmin' => '#969696',
                'colorBtnSubmit' => '#345A95',
                'colorBtnEdit' => '#345A95',
                'colorBtnMaybe' => '#7F9DC1',
                'colorInputBackground' => '#FDF9E7',
                'colorWarning' => '#FFC300',
                'colorDisabled' => '#969696',
                'colorUserMember' => '#7F9DC1',
                'colorAppmntConcert' => '#7F9DC1',
                'colorAppmntMaybe' => '#345A95',
                'colorLogDefault' => '#969696',
                'colorLogWarning' => '#7F9DC1',
                'colorLogDBDelete' => '#345A95',
                'colorLogDBInsert' => '#FFC300',
                'colorLogDBUpdate' => '#FDF9E7',
                'colorLogEmail' => '#FFC300',
                'colorLogInfo' => '#7F9DC1',
            )),
        ),
    );
}

function getConfigParamRawValue($parameter) {
    $sql = sprintf(
        'SELECT `Value` FROM `%sconfig` WHERE `Parameter` = "%s" LIMIT 1;',
        $GLOBALS['dbprefix'],
        mysqli_real_escape_string($GLOBALS['conn'], $parameter)
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    if(!$dbr) return null;
    $row = mysqli_fetch_assoc($dbr);
    return $row ? (string)$row['Value'] : null;
}

function setConfigParamRawValue($parameter, $value) {
    $conn = $GLOBALS['conn'];
    $prefix = $GLOBALS['dbprefix'];
    $escapedParam = mysqli_real_escape_string($conn, $parameter);
    $escapedValue = mysqli_real_escape_string($conn, (string)$value);

    $sql = sprintf(
        'SELECT `Parameter` FROM `%sconfig` WHERE `Parameter` = "%s" LIMIT 1;',
        $prefix,
        $escapedParam
    );
    $dbr = mysqli_query($conn, $sql);
    $row = $dbr ? mysqli_fetch_assoc($dbr) : null;
    if($row) {
        $sql = sprintf(
            'UPDATE `%sconfig` SET `Value` = "%s" WHERE `Parameter` = "%s";',
            $prefix,
            $escapedValue,
            $escapedParam
        );
    }
    else {
        $type = 'string';
        $desc = '';
        if(function_exists('getConfigDefaults')) {
            foreach(getConfigDefaults() as $item) {
                if($item['Parameter'] === $parameter) {
                    $type = $item['Type'];
                    $desc = $item['Description'];
                    break;
                }
            }
        }
        $sql = sprintf(
            'INSERT INTO `%sconfig` (`Parameter`, `Value`, `Type`, `Description`) VALUES ("%s", "%s", "%s", "%s");',
            $prefix,
            $escapedParam,
            $escapedValue,
            mysqli_real_escape_string($conn, $type),
            mysqli_real_escape_string($conn, $desc)
        );
    }
    $ok = mysqli_query($conn, $sql);
    sqlerror();
    return (bool)$ok;
}

function loadColorSchemes() {
    $defaults = getDefaultColorSchemes();
    $raw = getConfigParamRawValue('colorSchemes');
    if($raw === null || trim($raw) === '') {
        return $defaults;
    }
    $decoded = json_decode($raw, true);
    if(!is_array($decoded)) {
        return $defaults;
    }
    foreach($defaults as $id => $scheme) {
        if(!isset($decoded[$id]) || !is_array($decoded[$id])) {
            $decoded[$id] = $scheme;
            continue;
        }
        if(!isset($decoded[$id]['name']) || $decoded[$id]['name'] === '') {
            $decoded[$id]['name'] = $scheme['name'];
        }
        if(!isset($decoded[$id]['colors']) || !is_array($decoded[$id]['colors'])) {
            $decoded[$id]['colors'] = $scheme['colors'];
        }
        else {
            foreach($scheme['colors'] as $k => $v) {
                if(!array_key_exists($k, $decoded[$id]['colors'])) {
                    $decoded[$id]['colors'][$k] = $v;
                }
            }
        }
    }
    return $decoded;
}

function saveColorSchemes($schemes) {
    $json = json_encode($schemes, JSON_UNESCAPED_UNICODE);
    if($json === false) {
        return false;
    }
    return setConfigParamRawValue('colorSchemes', $json);
}

function getActiveColorSchemeId() {
    $id = getConfigParamRawValue('colorSchemeActive');
    if($id === null || $id === '') {
        return 'classic';
    }
    $schemes = loadColorSchemes();
    return isset($schemes[$id]) ? $id : 'classic';
}

function applyColorScheme($schemeId) {
    $schemes = loadColorSchemes();
    if(!isset($schemes[$schemeId])) {
        return false;
    }
    setConfigParamRawValue('colorSchemeActive', $schemeId);
    $colors = $schemes[$schemeId]['colors'];
    $colorParams = getColorConfigParameters();
    foreach($colorParams as $param => $_) {
        if(!array_key_exists($param, $colors)) {
            continue;
        }
        $value = (string)$colors[$param];
        if($value !== '' && function_exists('isHexColor') && !isHexColor($value)) {
            continue;
        }
        if($value !== '' && function_exists('normalizeHexColor')) {
            $value = normalizeHexColor($value);
        }
        setConfigParamRawValue($param, $value);
    }
    return true;
}

function updateActiveSchemeColor($parameter, $value) {
    $schemes = loadColorSchemes();
    $id = getActiveColorSchemeId();
    if(!isset($schemes[$id])) {
        return false;
    }
    if(!isset($schemes[$id]['colors']) || !is_array($schemes[$id]['colors'])) {
        $schemes[$id]['colors'] = array();
    }
    $schemes[$id]['colors'][$parameter] = $value;
    return saveColorSchemes($schemes);
}

function renameActiveColorScheme($name) {
    $name = trim((string)$name);
    if($name === '') {
        return false;
    }
    $schemes = loadColorSchemes();
    $id = getActiveColorSchemeId();
    if(!isset($schemes[$id])) {
        return false;
    }
    $schemes[$id]['name'] = $name;
    return saveColorSchemes($schemes);
}

function resetActiveColorSchemeToFactory() {
    $defaults = getDefaultColorSchemes();
    $id = getActiveColorSchemeId();
    if(!isset($defaults[$id])) {
        return false;
    }
    $schemes = loadColorSchemes();
    $schemes[$id] = $defaults[$id];
    if(!saveColorSchemes($schemes)) {
        return false;
    }
    return applyColorScheme($id);
}

function ensureColorSchemesStored() {
    $raw = getConfigParamRawValue('colorSchemes');
    if($raw !== null && trim($raw) !== '') {
        $decoded = json_decode($raw, true);
        if(is_array($decoded) && count($decoded) > 0) {
            return true;
        }
    }
    return saveColorSchemes(getDefaultColorSchemes());
}
?>
