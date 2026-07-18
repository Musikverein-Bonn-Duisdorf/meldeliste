<?php
session_start();
include 'common/include.php';
if(!requirePermission("perm_sendEmail")) {
    http_response_code(403);
    die('Keine Berechtigung.');
}

$hash = isset($_GET['hash']) ? (string)$_GET['hash'] : '';
$jobId = isset($_GET['job']) ? (int)$_GET['job'] : 0;
if($hash === '' || $jobId < 1) {
    echo "no hash/job";
    exit;
}

$job = new MailJob;
$job->load_by_id($jobId);
if(!$job->Index || $job->Status !== 'draft') {
    echo "invalid job";
    exit;
}
$job->ensureAttachmentDir();
$target_dir = rtrim((string)$job->AttachmentPath, '/').'/';
if(!is_dir($target_dir)) {
    echo "no dir";
    exit;
}

$files = scandir($target_dir);
foreach($files as $file) {
    if($file === '.' || $file === '..' || is_dir($target_dir.$file)) continue;
    if(md5_file($target_dir.$file) == $hash) {
        unlink($target_dir.$file);
        echo "deleted ".$file;
        exit;
    }
}
echo "not found";
?>
