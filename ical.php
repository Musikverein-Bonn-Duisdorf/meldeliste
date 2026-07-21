<?php
/**
 * On-demand personal ICS subscription (MELD-127).
 * Auth: secret token in ?t= (User.activeLink). No session required.
 */
require_once __DIR__.'/common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));

$token = '';
if(isset($_GET['t'])) {
    $token = trim((string)$_GET['t']);
}
elseif(isset($_GET['alink'])) {
    $token = trim((string)$_GET['alink']);
}

if($token === '' || !preg_match('/^[a-zA-Z0-9]+$/', $token)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Not found\n";
    exit;
}

$row = findUserByActiveLink($token);
if(!$row) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Not found\n";
    exit;
}

$userId = (int)$row['Index'];
$loaded = icalFeedLoadForUser($userId);
$etag = icalFeedEtag($userId, $loaded['events'], $loaded['from'], $loaded['to']);

$inm = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim((string)$_SERVER['HTTP_IF_NONE_MATCH']) : '';
if($inm !== '' && $inm === $etag) {
    http_response_code(304);
    header('ETag: '.$etag);
    header('Cache-Control: private, max-age=300');
    exit;
}

$body = icalFeedBuild($loaded['events']);

header('Content-Type: text/calendar; charset=utf-8');
header('ETag: '.$etag);
header('Cache-Control: private, max-age=300');
header('X-Content-Type-Options: nosniff');
echo $body;
