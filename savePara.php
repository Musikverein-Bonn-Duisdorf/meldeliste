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
switch($_GET['cmd']) {
case "change":
    if(!isset($_GET['para']) || !isset($_GET['value'])) {
        die('missing para/value');
    }
    $para = (string)$_GET['para'];
    $value = (string)$_GET['value'];

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
        break;
    }
    $sql = sprintf(
        'UPDATE `%sconfig` SET `Value` = "%s" WHERE `Parameter` = "%s";',
        $GLOBALS['dbprefix'],
        mysqli_real_escape_string($conn, $value),
        mysqli_real_escape_string($conn, $para)
    );
    $dbr2 = mysqli_query($conn, $sql);
    sqlerror();
    break;
default:
    break;
}
?>
