<?php
/**
 * Expected database schema version (MELD-51).
 * The integer lives in schema_version_number.php so it can be re-read after git pull.
 * Bump with ./scripts/bump-schema-version.sh when DBconfig.json, ConfigDefaults.php,
 * LoanClauseDefaults.php, or DatabaseManager migrations change — otherwise existing
 * installs skip Repair.
 */

/**
 * Drop opcache for schema-related PHP so a mid-request git pull sees new defaults/version.
 */
function meldeInvalidateSchemaOpcache() {
    $files = array(
        __DIR__.'/schema_version_number.php',
        __DIR__.'/SchemaVersion.php',
        __DIR__.'/ConfigDefaults.php',
        __DIR__.'/LoanClauseDefaults.php',
        dirname(__DIR__).'/libs/DatabaseManager.php',
    );
    foreach($files as $file) {
        clearstatcache(true, $file);
        if(function_exists('opcache_invalidate')) {
            @opcache_invalidate($file, true);
        }
    }
    // getConfigDefaults() has no static cache, but a stale compiled ConfigDefaults.php
    // would skip new parameters while still bumping SchemaVersion — invalidate above.
}

/**
 * @param bool $forceReload re-read schema_version_number.php from disk (after git pull)
 * @return int
 */
function getExpectedSchemaVersion($forceReload = false) {
    static $cached = null;
    if($forceReload || $cached === null) {
        $file = __DIR__.'/schema_version_number.php';
        if($forceReload) {
            meldeInvalidateSchemaOpcache();
        }
        // Read from disk — do not rely on include/opcache return value after a pull.
        $raw = @file_get_contents($file);
        if(is_string($raw) && preg_match('/\breturn\s+(\d+)\s*;/', $raw, $m)) {
            $cached = (int)$m[1];
        }
        else {
            $cached = (int)include $file;
        }
    }
    return $cached;
}
?>
