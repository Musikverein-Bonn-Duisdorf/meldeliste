<?php
session_start();
include 'common/include.php';

header('Content-Type: text/html; charset=utf-8');

if(!isset($_SESSION['userid']) || !(int)$_SESSION['userid']) {
    http_response_code(401);
    header('X-Has-More: 0');
    echo '';
    exit;
}

$type = isset($_GET['type']) ? (string)$_GET['type'] : '';
$cursor = isset($_GET['cursor']) ? (string)$_GET['cursor'] : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$sort = isset($_GET['sort']) ? (string)$_GET['sort'] : '';
$dir = isset($_GET['dir']) ? (string)$_GET['dir'] : 'asc';

$result = array('html' => '', 'nextCursor' => $cursor, 'hasMore' => false);

switch($type) {
case 'log':
    if(!requirePermission('perm_showLog')) {
        http_response_code(403);
        header('X-Has-More: 0');
        exit;
    }
    $result = listChunkLog($cursor !== '' ? (int)$cursor : 0, $limit);
    break;

case 'termine':
    $userId = isset($_GET['user']) ? (int)$_GET['user'] : (int)$_SESSION['userid'];
    $result = listChunkTermine('future', 'basic', $cursor, $limit, $userId);
    break;

case 'termineArchiv':
    $result = listChunkTermine('past', 'basic', $cursor, $limit, (int)$_SESSION['userid']);
    break;

case 'meldungen':
    if(!requirePermission('perm_showResponse')) {
        http_response_code(403);
        header('X-Has-More: 0');
        exit;
    }
    $result = listChunkTermine('future', 'response', $cursor, $limit, (int)$_SESSION['userid']);
    break;

case 'archiv':
    if(!requirePermission('perm_showResponse')) {
        http_response_code(403);
        header('X-Has-More: 0');
        exit;
    }
    $result = listChunkTermine('past', 'response', $cursor, $limit, (int)$_SESSION['userid']);
    break;

case 'musiker':
    if(!requirePermission('perm_showUsers')) {
        http_response_code(403);
        header('X-Has-More: 0');
        exit;
    }
    $result = listChunkUsers('musiker', $cursor !== '' ? (int)$cursor : 0, $limit, $sort, $dir);
    break;

case 'users':
    if(!requirePermission('perm_showUsers')) {
        http_response_code(403);
        header('X-Has-More: 0');
        exit;
    }
    $result = listChunkUsers('users', $cursor !== '' ? (int)$cursor : 0, $limit, $sort, $dir);
    break;

case 'mitglied':
    if(!requirePermission('perm_showUsers')) {
        http_response_code(403);
        header('X-Has-More: 0');
        exit;
    }
    $result = listChunkUsers('mitglied', $cursor !== '' ? (int)$cursor : 0, $limit, $sort, $dir);
    break;

default:
    http_response_code(400);
    header('X-Has-More: 0');
    echo '';
    exit;
}

header('X-Has-More: '.($result['hasMore'] ? '1' : '0'));
header('X-Next-Cursor: '.$result['nextCursor']);
echo $result['html'];
?>
