<?php
/**
 * Apply color defaults from ConfigDefaults to the existing config table.
 * Does not run automatically — for local testing / intentional brand reset.
 *
 * Usage: php scripts/applyBrandColorDefaults.php
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
require_once $root.'/config/ConfigDefaults.php';

if(!isset($GLOBALS['conn']) || !$GLOBALS['conn']) {
    fwrite(STDERR, "No DB connection.\n");
    exit(1);
}

$conn = $GLOBALS['conn'];
$prefix = $GLOBALS['dbprefix'];
$updated = 0;
$skipped = 0;
$missing = 0;

foreach(getConfigDefaults() as $item) {
    if(!isset($item['Type']) || $item['Type'] !== 'color') {
        continue;
    }
    $param = $item['Parameter'];
    $value = (string)$item['Value'];

    $sql = sprintf(
        "SELECT `Value` FROM `%sconfig` WHERE `Parameter` = '%s' LIMIT 1;",
        $prefix,
        mysqli_real_escape_string($conn, $param)
    );
    $dbr = mysqli_query($conn, $sql);
    if(!$dbr) {
        fwrite(STDERR, "SQL ERROR ".mysqli_errno($conn).": ".mysqli_error($conn)."\n");
        exit(1);
    }
    $row = mysqli_fetch_assoc($dbr);
    if(!$row) {
        echo "MISSING\t".$param."\n";
        $missing++;
        continue;
    }
    if((string)$row['Value'] === $value) {
        echo "OK\t".$param."\n";
        $skipped++;
        continue;
    }
    $sql = sprintf(
        "UPDATE `%sconfig` SET `Value` = '%s' WHERE `Parameter` = '%s';",
        $prefix,
        mysqli_real_escape_string($conn, $value),
        mysqli_real_escape_string($conn, $param)
    );
    if(!mysqli_query($conn, $sql)) {
        fwrite(STDERR, "SQL ERROR ".mysqli_errno($conn).": ".mysqli_error($conn)."\n");
        exit(1);
    }
    echo "UPDATED\t".$param."\t".($row['Value'] === '' ? '(empty)' : $row['Value'])." -> ".($value === '' ? '(empty)' : $value)."\n";
    $updated++;
}

echo "\nDone. updated=".$updated." unchanged=".$skipped." missing=".$missing."\n";
exit($missing > 0 ? 2 : 0);
