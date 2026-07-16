<?php
session_start();
include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));

header('Content-Type: application/json; charset=UTF-8');

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
            if(in_array((string)$job->Status, array('queued', 'processing'), true)) {
                $job->refreshCounts();
                $job->load_by_id($id);
            }
            $jobs[] = $job;
        }
    }
}
else {
    foreach(MailJob::listJobs(null, 300) as $job) {
        if(in_array((string)$job->Status, array('queued', 'processing'), true)) {
            $job->refreshCounts();
            $job->load_by_id((int)$job->Index);
            $jobs[] = $job;
        }
    }
}

$out = array('jobs' => array());
foreach($jobs as $job) {
    $sending = in_array((string)$job->Status, array('queued', 'processing'), true);
    $counts = (int)$job->Sent.'/'.(int)$job->Total;
    if((int)$job->Failed > 0) {
        $counts .= ' ('.(int)$job->Failed.' Fehler)';
    }
    $out['jobs'][] = array(
        'id' => (int)$job->Index,
        'status' => (string)$job->Status,
        'statusLabel' => $job->statusLabel(),
        'statusClass' => $job->statusClass(),
        'sent' => (int)$job->Sent,
        'total' => (int)$job->Total,
        'failed' => (int)$job->Failed,
        'counts' => $counts,
        'sending' => $sending,
        'canCancel' => $job->canCancel(),
        'canDelete' => $job->canDelete(),
    );
}

echo json_encode($out);
?>
