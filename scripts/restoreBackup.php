<?php
/**
 * CLI restore from a meldeliste backup ZIP (MELD-90).
 *
 * Usage: php scripts/restoreBackup.php /path/to/backup.zip --yes
 */
if(php_sapi_name() !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__);
$configFile = $root.'/common/config.php';
if(!is_readable($configFile)) {
    fwrite(STDERR, "Missing common/config.php — copy from common/config_sample.php\n");
    exit(1);
}

$zipPath = isset($argv[1]) ? $argv[1] : '';
$confirm = in_array('--yes', $argv, true);

if($zipPath === '' || $zipPath === '--yes') {
    fwrite(STDERR, "Usage: php scripts/restoreBackup.php /path/to/backup.zip --yes\n");
    exit(1);
}
if(!$confirm) {
    fwrite(STDERR, "Refusing to run without --yes (destructive).\n");
    exit(1);
}

require_once $configFile;

if(!function_exists('sqlerror')) {
    function sqlerror() {
        if(!isset($GLOBALS['conn']) || !mysqli_errno($GLOBALS['conn'])) return;
        fwrite(STDERR, "SQL ERROR ".mysqli_errno($GLOBALS['conn']).": ".mysqli_error($GLOBALS['conn'])."\n");
    }
}

require_once $root.'/config/ConfigDefaults.php';
require_once $root.'/config/SchemaVersion.php';
require_once $root.'/common/version.php';
require_once $root.'/libs/helpers.php';
require_once $root.'/libs/SQLtable.php';
require_once $root.'/libs/DatabaseManager.php';
require_once $root.'/libs/backup.php';

if(isset($GLOBALS['conn']) && $GLOBALS['conn']) {
    mysqli_set_charset($GLOBALS['conn'], 'utf8mb4');
    @mysqli_query($GLOBALS['conn'], "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    if(isset($sql['database'])) {
        mysqli_select_db($GLOBALS['conn'], $sql['database']);
    }
}

try {
    $result = restoreBackupZip($zipPath, true);
    if($result['manifest']) {
        echo "Manifest version: ".(isset($result['manifest']['version']['String']) ? $result['manifest']['version']['String'] : '?')."\n";
        echo "Manifest createdAt: ".(isset($result['manifest']['createdAt']) ? $result['manifest']['createdAt'] : '?')."\n";
    }
    echo "Statements OK: ".$result['statements']."\n";
    echo "Schema repair: ".($result['repaired'] ? 'yes' : 'no')."\n";
    if(!empty($result['errors'])) {
        fwrite(STDERR, "Errors:\n");
        foreach($result['errors'] as $err) {
            fwrite(STDERR, "  ".$err."\n");
        }
        exit(1);
    }
    echo "Restore finished.\n";
    exit(0);
}
catch(Throwable $e) {
    fwrite(STDERR, $e->getMessage()."\n");
    exit(1);
}
