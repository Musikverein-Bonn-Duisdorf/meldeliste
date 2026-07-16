<?php
/**
 * Five editable brand color schemes for Meldeliste.
 * Live color* config params mirror the active scheme.
 *
 * Markenkit:
 *   #FDFFFC Weiß | #040006 Schwarz | #345A95 Blau | #969696 Grau
 *   #454545 Dunkelgrau | #FDF9E7 Creme | #FFC300 Gold | #7F9DC1 Hellblau
 */

function getColorSchemeIds() {
    return array('classic', 'light', 'dark', 'gold', 'soft');
}

/** Brand palette tokens (uppercase hex). */
function getBrandPalette() {
    return array(
        'white' => '#FDFFFC',
        'black' => '#040006',
        'blue' => '#345A95',
        'gray' => '#969696',
        'darkGray' => '#454545',
        'egg' => '#FDF9E7',
        'gold' => '#FFC300',
        'lightBlue' => '#7F9DC1',
    );
}

/**
 * Complete color map for one scheme — every color* config key must be present.
 * Only brand-kit hex (or empty) allowed.
 */
function buildBrandSchemeColors($chrome) {
    $p = getBrandPalette();
    $c = array_merge(array(
        // Surfaces / chrome
        'colorBackground' => $p['egg'],
        'colorInputBackground' => $p['egg'],
        'colorTitle' => $p['blue'],
        'colorTitleBar' => $p['blue'],
        'colorNav' => $p['blue'],
        'colorNavAdmin' => $p['darkGray'],
        'colorBtnSubmit' => $p['blue'],
        'colorBtnEdit' => $p['lightBlue'],
        'colorBtnMaybe' => $p['lightBlue'],
        'colorWarning' => $p['gold'],
        'colorDisabled' => $p['gray'],
        // Status (Vereins-Muster: Blau = positiv, Dunkelgrau = negativ, Gold = unsicher)
        'colorSuccess' => $p['blue'],
        'colorBtnYes' => $p['blue'],
        'colorBtnNo' => $p['darkGray'],
        'colorBtnDelete' => $p['darkGray'],
        'colorAppmntYes' => $p['blue'],
        'colorAppmntNo' => $p['darkGray'],
        'colorAppmntMaybe' => $p['gold'],
        // People / events
        'colorUserMember' => $p['lightBlue'],
        'colorUserNoMember' => '',
        'colorAppmntConcert' => $p['lightBlue'],
        'colorAppmntNoConcert' => '',
        // Log rows
        'colorLogDefault' => $p['gray'],
        'colorLogFatal' => $p['black'],
        'colorLogError' => $p['darkGray'],
        'colorLogWarning' => $p['gold'],
        'colorLogDBDelete' => $p['darkGray'],
        'colorLogDBInsert' => $p['gold'],
        'colorLogDBUpdate' => $p['egg'],
        'colorLogEmail' => $p['gold'],
        'colorLogInfo' => $p['lightBlue'],
    ), $chrome);

    return $c;
}

function getDefaultColorSchemes() {
    $p = getBrandPalette();
    return array(
        // Website-nah: Creme-Grund, Primärblau, Gold-Akzent
        'classic' => array(
            'name' => 'Klassisch',
            'colors' => buildBrandSchemeColors(array(
                'colorBackground' => $p['egg'],
                'colorInputBackground' => $p['white'],
                'colorTitle' => $p['blue'],
                'colorTitleBar' => $p['blue'],
                'colorNav' => $p['blue'],
                'colorNavAdmin' => $p['darkGray'],
                'colorBtnSubmit' => $p['blue'],
                'colorBtnEdit' => $p['lightBlue'],
                'colorBtnMaybe' => $p['lightBlue'],
                'colorWarning' => $p['gold'],
                'colorUserMember' => $p['lightBlue'],
                'colorAppmntConcert' => $p['lightBlue'],
                'colorAppmntMaybe' => $p['gold'],
                'colorLogWarning' => $p['blue'],
                'colorLogDBUpdate' => $p['egg'],
                'colorLogInfo' => $p['lightBlue'],
            )),
        ),
        // Hell: Weiß-Grund, Blau + Hellblau
        'light' => array(
            'name' => 'Hell',
            'colors' => buildBrandSchemeColors(array(
                'colorBackground' => $p['white'],
                'colorInputBackground' => $p['egg'],
                'colorTitle' => $p['blue'],
                'colorTitleBar' => $p['lightBlue'],
                'colorNav' => $p['blue'],
                'colorNavAdmin' => $p['gray'],
                'colorBtnSubmit' => $p['blue'],
                'colorBtnEdit' => $p['lightBlue'],
                'colorBtnMaybe' => $p['lightBlue'],
                'colorWarning' => $p['gold'],
                'colorUserMember' => $p['blue'],
                'colorAppmntConcert' => $p['blue'],
                'colorAppmntMaybe' => $p['lightBlue'],
                'colorLogDefault' => $p['gray'],
                'colorLogWarning' => $p['lightBlue'],
                'colorLogDBDelete' => $p['gray'],
                'colorLogDBInsert' => $p['gold'],
                'colorLogDBUpdate' => $p['white'],
                'colorLogEmail' => $p['gold'],
                'colorLogInfo' => $p['lightBlue'],
            )),
        ),
        // Dunkel: Schwarz/Dunkelgrau, Blau + Gold-Akzente
        'dark' => array(
            'name' => 'Dunkel',
            'colors' => buildBrandSchemeColors(array(
                'colorBackground' => $p['black'],
                'colorInputBackground' => $p['darkGray'],
                'colorTitle' => $p['blue'],
                'colorTitleBar' => $p['darkGray'],
                'colorNav' => $p['blue'],
                'colorNavAdmin' => $p['darkGray'],
                'colorBtnSubmit' => $p['lightBlue'],
                'colorBtnEdit' => $p['gold'],
                'colorBtnMaybe' => $p['lightBlue'],
                'colorWarning' => $p['gold'],
                'colorDisabled' => $p['gray'],
                'colorSuccess' => $p['lightBlue'],
                'colorBtnYes' => $p['lightBlue'],
                'colorBtnNo' => $p['gray'],
                'colorBtnDelete' => $p['gray'],
                'colorAppmntYes' => $p['lightBlue'],
                'colorAppmntNo' => $p['gray'],
                'colorAppmntMaybe' => $p['gold'],
                'colorUserMember' => $p['lightBlue'],
                'colorAppmntConcert' => $p['blue'],
                'colorLogDefault' => $p['darkGray'],
                'colorLogFatal' => $p['gold'],
                'colorLogError' => $p['gray'],
                'colorLogWarning' => $p['gold'],
                'colorLogDBDelete' => $p['gray'],
                'colorLogDBInsert' => $p['gold'],
                'colorLogDBUpdate' => $p['darkGray'],
                'colorLogEmail' => $p['gold'],
                'colorLogInfo' => $p['lightBlue'],
            )),
        ),
        // Gold: Creme-Grund, Gold-Titel/CTA, Blau als Sekundär
        'gold' => array(
            'name' => 'Gold',
            'colors' => buildBrandSchemeColors(array(
                'colorBackground' => $p['egg'],
                'colorInputBackground' => $p['white'],
                'colorTitle' => $p['gold'],
                'colorTitleBar' => $p['blue'],
                'colorNav' => $p['blue'],
                'colorNavAdmin' => $p['darkGray'],
                'colorBtnSubmit' => $p['gold'],
                'colorBtnEdit' => $p['blue'],
                'colorBtnMaybe' => $p['lightBlue'],
                'colorWarning' => $p['gold'],
                'colorSuccess' => $p['blue'],
                'colorBtnYes' => $p['blue'],
                'colorAppmntYes' => $p['blue'],
                'colorAppmntMaybe' => $p['gold'],
                'colorUserMember' => $p['blue'],
                'colorAppmntConcert' => $p['gold'],
                'colorLogDefault' => $p['gray'],
                'colorLogWarning' => $p['gold'],
                'colorLogDBDelete' => $p['darkGray'],
                'colorLogDBInsert' => $p['gold'],
                'colorLogDBUpdate' => $p['egg'],
                'colorLogEmail' => $p['gold'],
                'colorLogInfo' => $p['blue'],
            )),
        ),
        // Soft-Blau: Hellblau dominiert Chrome, Blau für CTAs
        'soft' => array(
            'name' => 'Soft-Blau',
            'colors' => buildBrandSchemeColors(array(
                'colorBackground' => $p['white'],
                'colorInputBackground' => $p['egg'],
                'colorTitle' => $p['lightBlue'],
                'colorTitleBar' => $p['lightBlue'],
                'colorNav' => $p['lightBlue'],
                'colorNavAdmin' => $p['gray'],
                'colorBtnSubmit' => $p['blue'],
                'colorBtnEdit' => $p['blue'],
                'colorBtnMaybe' => $p['lightBlue'],
                'colorWarning' => $p['gold'],
                'colorSuccess' => $p['blue'],
                'colorBtnYes' => $p['blue'],
                'colorAppmntYes' => $p['blue'],
                'colorAppmntMaybe' => $p['lightBlue'],
                'colorUserMember' => $p['lightBlue'],
                'colorAppmntConcert' => $p['lightBlue'],
                'colorLogDefault' => $p['gray'],
                'colorLogWarning' => $p['lightBlue'],
                'colorLogDBDelete' => $p['blue'],
                'colorLogDBInsert' => $p['gold'],
                'colorLogDBUpdate' => $p['egg'],
                'colorLogEmail' => $p['gold'],
                'colorLogInfo' => $p['lightBlue'],
            )),
        ),
    );
}

function getClassicBrandColorDefaults() {
    $schemes = getDefaultColorSchemes();
    return $schemes['classic']['colors'];
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
    $colors = isset($schemes[$schemeId]['colors']) && is_array($schemes[$schemeId]['colors'])
        ? $schemes[$schemeId]['colors']
        : array();
    // Prefer scheme color keys directly; fall back to known color params.
    $params = array_keys($colors);
    if(count($params) === 0) {
        $params = array_keys(getColorConfigParameters());
    }
    foreach($params as $param) {
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
            // Merge missing keys from factory, persist if anything was added
            $merged = loadColorSchemes();
            $json = json_encode($merged, JSON_UNESCAPED_UNICODE);
            if($json !== false && $json !== $raw) {
                saveColorSchemes($merged);
            }
            return true;
        }
    }
    return saveColorSchemes(getDefaultColorSchemes());
}
?>
