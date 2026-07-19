<?php
/**
 * GET /api/notify/poll.php?since=<ISO8601|MySQL datetime>
 * Auth: Bearer token or existing PHP session.
 * Returns MailOutbox events for local notifications (MELD-49 pull).
 */
require_once dirname(__DIR__).'/_bootstrap.php';

if(strtoupper($_SERVER['REQUEST_METHOD']) !== 'GET') {
    apiJsonExit(array('error' => 'method_not_allowed'), 405);
}

$userId = 0;
if(loggedIn()) {
    $userId = (int)$_SESSION['userid'];
} else {
    $token = readAppTokenFromRequest();
    if($token === '' || !validateAppToken($token)) {
        apiJsonExit(array('error' => 'unauthorized'), 401);
    }
    $userId = (int)$_SESSION['userid'];
}

$since = isset($_GET['since']) ? $_GET['since'] : '';
$events = pollNotifyEvents($userId, $since);

apiJsonExit(array(
    'events' => $events,
    'serverTime' => date('c'),
    'unreadMail' => MailOutbox::countUnreadForUser($userId),
));
?>
