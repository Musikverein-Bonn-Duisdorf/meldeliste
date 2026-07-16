<?php
session_start();
include 'common/include.php';
if(!requirePermission("perm_sendEmail")) die();

$jobId = isset($_POST['job']) ? (int)$_POST['job'] : (isset($_GET['job']) ? (int)$_GET['job'] : 0);
if($jobId < 1) {
    echo "<div class=\"w3-row w3-red\">Keine Email-ID (job) angegeben.</div>";
    exit;
}

$job = new MailJob;
$job->load_by_id($jobId);
if(!$job->Index || $job->Status !== 'draft') {
    echo "<div class=\"w3-row w3-red\">Anhänge nur für Entwürfe möglich.</div>";
    exit;
}
$job->ensureAttachmentDir();
$target_dir = rtrim((string)$job->AttachmentPath, '/').'/';
if(!is_dir($target_dir)) {
    echo "<div class=\"w3-row w3-red\">Anhang-Verzeichnis fehlt.</div>";
    exit;
}

if(!isset($_FILES['attachment'])) {
    echo "<div class=\"w3-row w3-red\">Keine Datei.</div>";
    exit;
}

$filecount = count($_FILES['attachment']['name']);

for ($i=0; $i < $filecount; $i++) {
    $file = $_FILES["attachment"]['name'][$i];
    $tmpfile = $_FILES["attachment"]['tmp_name'][$i];
    $filesize = $_FILES["attachment"]['size'][$i];

    $target_file = $target_dir.basename($file);
    $uploadOk = 1;

    if ($filesize > 20e6) {
        echo "<div class=\"w3-row w3-red\">Datei zu groß: ".htmlspecialchars($file)."</div>";
        $uploadOk = 0;
    }

    if ($uploadOk == 0) {
        continue;
    }
    if (move_uploaded_file($tmpfile, $target_file)) {
        $hash = md5_file($target_file);
        echo "<div class=\"w3-row\" id=\"".$hash."\"><div class=\"w3-green w3-col l6 w3-padding\">".htmlspecialchars($file)."</div><button class=\"w3-text-red fas fa-times w3-col l1 w3-padding\" onclick=\"delFile('".$hash."')\"></button><div class=\"w3-col l5 w3-padding\">&nbsp;</div></div>\n";
    } else {
        echo "<div class=\"w3-row w3-red\">Upload fehlgeschlagen: ".htmlspecialchars($file)."</div>";
    }
}
?>
