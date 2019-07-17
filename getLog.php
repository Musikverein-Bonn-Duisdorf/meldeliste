<?php
session_start();
include 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
if(!isset($_GET['id'])) {
    die('invalid');
}
if($_GET['id'] == $GLOBALS['cronID']) {
    $sql = sprintf('SELECT `Index` FROM `%sLog` WHERE `Index` > %d ORDER BY `Timestamp` ASC LIMIT 1;',
    $GLOBALS['dbprefix'],
    $_GET['maxIndex']
    );
    $dbr = mysqli_query($conn, $sql);
    sqlerror();
    while($row = mysqli_fetch_array($dbr)) {
        $M = new Log;
        $M->load_by_id($row['Index']);
        if($M->Index > 0) {
            echo $M->printTableLine();
        }
    }
}
?>