<?php
session_start();
include 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
if(!isset($_GET['id'])) {
    die('no ID specified');
}
if($_GET['id'] != $GLOBALS['cronID']) {
    die('invalid ID');
}

header('Content-Type: text/plain; charset=utf-8');

switch($_GET['cmd']) {
case "change":
    if(!isset($_GET['para']) || !isset($_GET['value'])) {
        die('missing para/value');
    }
    $para = (string)$_GET['para'];
    $value = (string)$_GET['value'];

    if($para === 'colorSchemeActive') {
        ensureColorSchemesStored();
        $oldScheme = getActiveColorSchemeId();
        if(!applyColorScheme($value)) {
            die('unknown scheme');
        }
        if($oldScheme !== $value) {
            logConfigChange('colorSchemeActive', $oldScheme, $value);
        }
        else {
            $logentry = new Log;
            $logentry->DBupdate(sprintf(
                'Config Farbschema <b>%s</b> erneut angewendet',
                htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8')
            ));
        }
        echo 'ok';
        break;
    }

    $sql = sprintf(
        'SELECT `Parameter`, `Type`, `Value` FROM `%sconfig` WHERE `Parameter` = "%s" LIMIT 1;',
        $GLOBALS['dbprefix'],
        mysqli_real_escape_string($conn, $para)
    );
    $dbr = mysqli_query($conn, $sql);
    sqlerror();
    $row = $dbr ? mysqli_fetch_assoc($dbr) : null;
    if(!$row) {
        die('unknown parameter');
    }

    if($row['Type'] === 'internal') {
        die('internal parameter');
    }

    if($row['Type'] === 'color') {
        $value = trim($value);
        if($value !== '' && !isHexColor($value)) {
            $logentry = new Log;
            $logentry->error(sprintf(
                "Ungültige Farbe abgelehnt | Parameter: <b>%s</b>, Wert: <b>%s</b>",
                htmlspecialchars($para),
                htmlspecialchars($value)
            ));
            die('invalid color');
        }
        if($value !== '') {
            $value = normalizeHexColor($value);
        }
    }

    if($value === (string)$row['Value']) {
        if($row['Type'] === 'color') {
            ensureColorSchemesStored();
            updateActiveSchemeColor($para, $value);
        }
        echo 'ok';
        break;
    }
    $oldValue = (string)$row['Value'];
    $sql = sprintf(
        'UPDATE `%sconfig` SET `Value` = "%s" WHERE `Parameter` = "%s";',
        $GLOBALS['dbprefix'],
        mysqli_real_escape_string($conn, $value),
        mysqli_real_escape_string($conn, $para)
    );
    $dbr2 = mysqli_query($conn, $sql);
    sqlerror();
    if($dbr2) {
        logConfigChange($para, $oldValue, $value, $row['Type']);
    }
    if($row['Type'] === 'color') {
        ensureColorSchemesStored();
        updateActiveSchemeColor($para, $value);
    }
    echo 'ok';
    break;

case "schemeName":
    if(!isset($_GET['value'])) {
        die('missing value');
    }
    ensureColorSchemesStored();
    $oldName = '';
    $schemes = loadColorSchemes();
    $activeId = getActiveColorSchemeId();
    if(isset($schemes[$activeId]['name'])) {
        $oldName = (string)$schemes[$activeId]['name'];
    }
    $newName = trim((string)$_GET['value']);
    if(!renameActiveColorScheme($newName)) {
        die('rename failed');
    }
    $logentry = new Log;
    $logentry->DBupdate(sprintf(
        'Config Farbschema <b>%s</b> umbenannt: %s &rArr; <b>%s</b>',
        htmlspecialchars($activeId, ENT_QUOTES, 'UTF-8'),
        formatConfigLogValue($oldName),
        formatConfigLogValue($newName)
    ));
    echo 'ok';
    break;

case "schemeReset":
    ensureColorSchemesStored();
    $activeId = getActiveColorSchemeId();
    if(!resetActiveColorSchemeToFactory()) {
        die('reset failed');
    }
    $logentry = new Log;
    $logentry->DBupdate(sprintf(
        'Config Farbschema <b>%s</b> auf Werkseinstellung zurückgesetzt',
        htmlspecialchars($activeId, ENT_QUOTES, 'UTF-8')
    ));
    echo 'ok';
    break;

default:
    break;
}
?>
