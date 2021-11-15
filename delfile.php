<?php
session_start();
include 'common/include.php';

$hash = null;
if(isset($_GET['hash'])) {
    $hash = $_GET['hash'];
}
if($hash) {
    $target_dir = "uploads/";
    $files = scandir($target_dir);
    foreach($files as $file) {
        echo md5_file($target_dir.$file)." ".$hash;
        if(md5_file($target_dir.$file) == $hash) {
            unlink($target_dir.$file);
            echo "deleted ".$file;
            break;
        }
    }
}
else {
    echo "no hash found";
}
?>