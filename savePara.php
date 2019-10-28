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
    $sql = sprintf('SELECT * FROM `%sconfig`;',
    $GLOBALS['dbprefix']
    );
    $dbr = mysqli_query($conn, $sql);
    sqlerror();
    while($row = mysqli_fetch_array($dbr)) {
        switch($row['Type']) {
        case "bool":
        case "uint":
        case "int":
        case "time":
        case "string":
        case "color":
        default:
            if(isset($_GET['value'])) {
                $sql = sprintf('UPDATE `%sconfig` SET `Value` = "%s" WHERE `Parameter` = "%s";',
                $GLOBALS['dbprefix'],
                mysqli_real_escape_string($conn, $_GET['value']),
                $_GET['para']
                );
                if($_GET['value'] == $row['Value']) break;
                $dbr2 = mysqli_query($conn, $sql);
                sqlerror();
            }
        break;
        }
    }
    break;
default:
    break;
}
?>