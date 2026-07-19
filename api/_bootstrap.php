<?php
/**
 * Shared bootstrap for JSON API endpoints (MELD-49).
 */
require_once dirname(__DIR__).'/libs/sessionBootstrap.php';
meldeConfigureSession();
chdir(dirname(__DIR__)); // project root so relative includes in common/ work
require_once dirname(__DIR__).'/common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

function apiJsonExit($payload, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function apiRequireMethod($method) {
    if(strtoupper($_SERVER['REQUEST_METHOD']) !== strtoupper($method)) {
        apiJsonExit(array('error' => 'method_not_allowed'), 405);
    }
}
?>
