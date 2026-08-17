<?php
/**
 * Download mail job attachment (inbox / outbox).
 * GET job, file — optional outbox for recipient auth
 */
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
requireLoggedInOrRedirect();

$userId = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
$jobId = isset($_GET['job']) ? (int)$_GET['job'] : 0;
$file = isset($_GET['file']) ? (string)$_GET['file'] : '';
$outboxId = isset($_GET['outbox']) ? (int)$_GET['outbox'] : 0;

if($jobId < 1 || $file === '') {
    denyAccess('Anhang nicht gefunden.');
}

MailJob::ensureSchema();
$job = new MailJob;
$job->load_by_id($jobId);
if(!(int)$job->Index) {
    denyAccess('Email nicht gefunden.');
}

if(!$job->userMayDownloadAttachment($userId, $file, $outboxId)) {
    denyAccess();
}

$path = $job->resolveAttachmentPath($file);
if($path === null) {
    denyAccess('Anhang nicht gefunden.');
}

$mime = 'application/octet-stream';
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$map = array(
    'pdf' => 'application/pdf',
    'html' => 'text/html; charset=utf-8',
    'htm' => 'text/html; charset=utf-8',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'txt' => 'text/plain; charset=utf-8',
);
if(isset($map[$ext])) {
    $mime = $map[$ext];
}

header('Content-Type: '.$mime);
header('Content-Length: '.(string)filesize($path));
header('Content-Disposition: inline; filename="'.basename($path).'"');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
