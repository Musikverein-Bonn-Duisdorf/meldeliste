<?php
session_start();
include 'common/include.php';
/* mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn'])); */
requireAdmin();

$target_dir = "uploads/";
$filecount = count($_FILES['attachment']['name']);

/* $file = $_FILES["attachment"]; */
for ($i=0; $i < $filecount; $i++) {
    $file = $_FILES["attachment"]['name'][$i];
    $tmpfile = $_FILES["attachment"]['tmp_name'][$i];
    $filesize = $_FILES["attachment"]['size'][$i];

    $target_file = $target_dir.basename($file);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    $filename=htmlspecialchars(basename($file));

    // Check file size
    if ($filesize > 20e6) {
        echo "<div class=\"w3-row w3-red\">Sorry, your file is too large.</div>";
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.";
    // if everything is ok, try to upload file
    } else {
        if (move_uploaded_file($tmpfile, $target_file)) {
            $hash = md5_file($target_file);
            echo "<div class=\"w3-row\" id=\"".$hash."\"><div class=\"w3-green w3-col l6 w3-padding\">".htmlspecialchars($file)."</div><button class=\"w3-text-red fas fa-times w3-col l1 w3-padding\" onclick=\"delFile('".$hash."')\"></button><div class=\"w3-col l5 w3-padding\">&nbsp;</div></div>\n";
        } else {
            echo "<div class=\"w3-row w3-red\">Sorry, there was an error uploading your file.</div>";
        }
    }
}
?>