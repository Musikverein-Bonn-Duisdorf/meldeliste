<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));

/**
 * Append query parameter to URL.
 */
function ssoAppendParam($url, $key, $value) {
    $sep = (strpos($url, '?') !== false) ? '&' : '?';
    return $url.$sep.rawurlencode($key).'='.rawurlencode($value);
}

/**
 * True if absolute redirect URL is allowed by ssoRedirectAllowlist / same host.
 */
function ssoRedirectAllowed($url) {
    $url = trim((string)$url);
    if($url === '') {
        return false;
    }
    $parts = parse_url($url);
    if(!$parts || empty($parts['scheme']) || empty($parts['host'])) {
        return false;
    }
    if(!in_array(strtolower($parts['scheme']), array('http', 'https'), true)) {
        return false;
    }
    $host = strtolower($parts['host']);
    $path = isset($parts['path']) ? $parts['path'] : '/';

    global $optionsDB;
    $allowlist = isset($optionsDB['ssoRedirectAllowlist']) ? trim((string)$optionsDB['ssoRedirectAllowlist']) : '';
    if($allowlist === '') {
        $base = parse_url(isset($optionsDB['WebSiteURL']) ? $optionsDB['WebSiteURL'] : '');
        $baseHost = ($base && !empty($base['host'])) ? strtolower($base['host']) : strtolower($_SERVER['HTTP_HOST'] ?? '');
        return $host === $baseHost;
    }

    $entries = array_map('trim', explode(',', $allowlist));
    foreach($entries as $entry) {
        if($entry === '') {
            continue;
        }
        if($entry[0] === '/') {
            if(strpos($path, $entry) === 0) {
                return true;
            }
            continue;
        }
        $suffix = strtolower($entry);
        if($host === $suffix || substr($host, -strlen($suffix)) === $suffix) {
            return true;
        }
    }
    return false;
}

function ssoLoginByUserId($userId) {
    $userId = (int)$userId;
    if($userId < 1) {
        return false;
    }
    $u = new User();
    $u->load_by_id($userId);
    if(!$u->Index || (int)$u->Deleted === 1) {
        return false;
    }
    $_SESSION['userid'] = (int)$u->Index;
    $_SESSION['Vorname'] = $u->Vorname;
    $_SESSION['Nachname'] = $u->Nachname;
    $_SESSION['username'] = $u->Vorname.' '.$u->Nachname;
    $_SESSION['singleUsePW'] = (bool)$u->singleUsePW;
    $_SESSION['permissions'] = loadPermissions((int)$u->Index);
    $_SESSION['admin'] = isAdmin() ? 1 : 0;
    recordLogin();
    $logentry = new Log();
    $logentry->info('Login via SSO ticket.');
    return true;
}

// Redeem mode (same-app testing)
if(isset($_GET['ticket'])) {
    $userId = SsoTicket::redeem($_GET['ticket']);
    if($userId && ssoLoginByUserId($userId)) {
        header('Location: index.php');
        exit;
    }
    header('Location: login.php');
    exit;
}

$redirect = isset($_GET['redirect']) ? trim((string)$_GET['redirect']) : '';
if($redirect === '') {
    header('Location: index.php');
    exit;
}

if(!loggedIn()) {
    $_SESSION['login_return'] = 'sso.php?redirect='.rawurlencode($redirect);
    header('Location: login.php');
    exit;
}

if(!ssoRedirectAllowed($redirect)) {
    http_response_code(403);
    die('<div class="w3-panel w3-red w3-padding"><b>SSO-Weiterleitung nicht erlaubt.</b></div>');
}

$targetHint = '';
if(strpos($redirect, 'notenarchiv') !== false || strpos($redirect, 'archiv') !== false) {
    $targetHint = 'archiv';
}
elseif(strpos($redirect, 'mitglied') !== false || strpos($redirect, 'mit-') !== false) {
    $targetHint = 'mit';
}

$token = SsoTicket::issue((int)$_SESSION['userid'], $targetHint);
if($token === '') {
    http_response_code(500);
    die('<div class="w3-panel w3-red w3-padding"><b>SSO-Ticket konnte nicht erstellt werden.</b></div>');
}

header('Location: '.ssoAppendParam($redirect, 'sso', $token));
exit;
