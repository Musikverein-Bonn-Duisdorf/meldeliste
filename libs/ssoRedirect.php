<?php
/**
 * SSO redirect allowlist helpers (MELD-171).
 */

/**
 * Host from an absolute URL, lowercased, or empty string.
 */
function ssoHostFromUrl($url) {
    $url = trim((string)$url);
    if($url === '') {
        return '';
    }
    $parts = parse_url($url);
    if(!$parts || empty($parts['host'])) {
        return '';
    }
    return strtolower((string)$parts['host']);
}

/**
 * Melde app host from WebSiteURL / HTTP_HOST.
 */
function ssoMeldeHost() {
    global $optionsDB;
    $base = parse_url(isset($optionsDB['WebSiteURL']) ? (string)$optionsDB['WebSiteURL'] : '');
    if($base && !empty($base['host'])) {
        return strtolower((string)$base['host']);
    }
    return strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
}

/**
 * True if absolute redirect URL is allowed (same host, configured module URLs, or ssoRedirectAllowlist).
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
    if(!in_array(strtolower((string)$parts['scheme']), array('http', 'https'), true)) {
        return false;
    }
    $host = strtolower((string)$parts['host']);
    $path = isset($parts['path']) ? (string)$parts['path'] : '/';

    $meldeHost = ssoMeldeHost();
    if($meldeHost !== '' && $host === $meldeHost) {
        return true;
    }

    global $optionsDB;
    foreach(array('urlNotenarchiv', 'urlMitgliederverwaltung') as $key) {
        $cfgHost = ssoHostFromUrl(isset($optionsDB[$key]) ? (string)$optionsDB[$key] : '');
        if($cfgHost !== '' && $host === $cfgHost) {
            return true;
        }
    }

    $allowlist = isset($optionsDB['ssoRedirectAllowlist']) ? trim((string)$optionsDB['ssoRedirectAllowlist']) : '';
    if($allowlist === '') {
        return false;
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

/**
 * Permission key required for redirect to a configured module URL, or null.
 *
 * @return string|null
 */
function ssoPermissionForRedirect($url) {
    $host = ssoHostFromUrl($url);
    if($host === '') {
        return null;
    }
    global $optionsDB;
    $archivHost = ssoHostFromUrl(isset($optionsDB['urlNotenarchiv']) ? (string)$optionsDB['urlNotenarchiv'] : '');
    if($archivHost !== '' && $host === $archivHost) {
        return 'perm_accessNotenarchiv';
    }
    $mitHost = ssoHostFromUrl(isset($optionsDB['urlMitgliederverwaltung']) ? (string)$optionsDB['urlMitgliederverwaltung'] : '');
    if($mitHost !== '' && $host === $mitHost) {
        return 'perm_accessMitgliederverwaltung';
    }
    return null;
}
?>
