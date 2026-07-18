<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

if(!loggedIn() || !requirePermission('perm_sendEmail')) {
    http_response_code(403);
    echo json_encode(array('error' => 'forbidden'));
    exit;
}

MailJob::ensureSchema();

$ids = array();
if(isset($_GET['ids']) && $_GET['ids'] !== '') {
    foreach(explode(',', (string)$_GET['ids']) as $part) {
        $id = (int)$part;
        if($id > 0) {
            $ids[$id] = true;
        }
    }
    $ids = array_keys($ids);
}

$jobs = array();
if(count($ids)) {
    foreach($ids as $id) {
        $job = new MailJob;
        $job->load_by_id($id);
        if($job->Index) {
            $jobs[] = $job;
        }
    }
}
else {
    foreach(MailJob::listJobs(null, 300) as $job) {
        $progress = $job->liveProgress();
        if($progress['sending'] || in_array((string)$job->Status, array('queued', 'processing'), true)) {
            $jobs[] = $job;
        }
    }
}

$out = array('jobs' => array());
foreach($jobs as $job) {
    $progress = $job->liveProgress();
    $out['jobs'][] = array(
        'id' => (int)$job->Index,
        'status' => (string)$job->Status,
        'statusLabel' => $progress['statusLabel'],
        'statusClass' => $progress['statusClass'],
        'sent' => $progress['sent'],
        'total' => $progress['total'],
        'failed' => $progress['failed'],
        'counts' => $progress['counts'],
        'sending' => $progress['sending'],
        'canCancel' => $progress['sending'],
        'canDelete' => !$progress['sending'] && (int)$progress['sent'] === 0,
    );
}

echo json_encode($out);
?>
