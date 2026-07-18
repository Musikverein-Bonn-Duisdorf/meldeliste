<?php
/**
 * Expected database schema version (MELD-51).
 * The integer lives in schema_version_number.php so it can be re-read after git pull.
 * Bump that integer when DBconfig.json, DatabaseManager migrations, or ConfigDefaults.php change.
 */
function getExpectedSchemaVersion($forceReload = false) {
    static $cached = null;
    if($forceReload || $cached === null) {
        $cached = (int)include __DIR__.'/schema_version_number.php';
    }
    return $cached;
}
?>
