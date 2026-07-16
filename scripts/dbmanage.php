<?php
/**
 * CLI for database check / create / repair.
 * Optional on IONOS; useful for local Ubuntu / SSH.
 *
 * Usage: php scripts/dbmanage.php check|create|repair
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

require_once $configFile;

if(!function_exists('sqlerror')) {
    function sqlerror() {
        if(!isset($GLOBALS['conn']) || !mysqli_errno($GLOBALS['conn'])) return;
        fwrite(STDERR, "SQL ERROR ".mysqli_errno($GLOBALS['conn']).": ".mysqli_error($GLOBALS['conn'])."\n");
    }
}

require_once $root.'/libs/SQLtable.php';
require_once $root.'/config/ConfigDefaults.php';
require_once $root.'/libs/DatabaseManager.php';

$mode = isset($argv[1]) ? strtolower($argv[1]) : '';
if(!in_array($mode, array('check', 'create', 'repair'), true)) {
    fwrite(STDERR, "Usage: php scripts/dbmanage.php check|create|repair\n");
    exit(1);
}

try {
    $manager = new DatabaseManager($root.'/config/DBconfig.json');
    if($mode === 'check') {
        $manager->check();
    }
    elseif($mode === 'create') {
        $manager->create();
    }
    else {
        $manager->repair();
    }
    echo $manager->formatReportText();
    if($mode === 'check') {
        exit($manager->hasChanges() ? 2 : 0);
    }
    exit($manager->hasErrors() ? 1 : 0);
}
catch(Exception $e) {
    fwrite(STDERR, $e->getMessage()."\n");
    exit(1);
}
