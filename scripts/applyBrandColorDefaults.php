<?php
/**
 * Seed/update color schemes and apply the classic (or given) scheme.
 *
 * Usage:
 *   php scripts/applyBrandColorDefaults.php
 *   php scripts/applyBrandColorDefaults.php classic|light|dark|gold|soft
 *   php scripts/applyBrandColorDefaults.php --reset-schemes
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
require_once $root.'/libs/helpers.php';
require_once $root.'/libs/colorschemes.php';

if(!isset($GLOBALS['conn']) || !$GLOBALS['conn']) {
    fwrite(STDERR, "No DB connection.\n");
    exit(1);
}

$conn = $GLOBALS['conn'];
$prefix = $GLOBALS['dbprefix'];

// Ensure new config params exist
foreach(getConfigDefaults() as $item) {
    $param = $item['Parameter'];
    $sql = sprintf(
        "SELECT `Parameter` FROM `%sconfig` WHERE `Parameter` = '%s' LIMIT 1;",
        $prefix,
        mysqli_real_escape_string($conn, $param)
    );
    $dbr = mysqli_query($conn, $sql);
    $row = $dbr ? mysqli_fetch_assoc($dbr) : null;
    if($row) {
        continue;
    }
    $insert = sprintf(
        "INSERT INTO `%sconfig` (`Parameter`, `Value`, `Type`, `Description`) VALUES ('%s', '%s', '%s', '%s');",
        $prefix,
        mysqli_real_escape_string($conn, $param),
        mysqli_real_escape_string($conn, (string)$item['Value']),
        mysqli_real_escape_string($conn, $item['Type']),
        mysqli_real_escape_string($conn, $item['Description'])
    );
    if(mysqli_query($conn, $insert)) {
        echo "CREATED\t".$param."\n";
    }
    else {
        fwrite(STDERR, "SQL ERROR ".mysqli_errno($conn).": ".mysqli_error($conn)."\n");
        exit(1);
    }
}

$resetSchemes = in_array('--reset-schemes', $argv, true);
$schemeId = 'classic';
foreach(array_slice($argv, 1) as $arg) {
    if($arg === '--reset-schemes') continue;
    $schemeId = $arg;
}

if($resetSchemes || getConfigParamRawValue('colorSchemes') === null || trim((string)getConfigParamRawValue('colorSchemes')) === '') {
    saveColorSchemes(getDefaultColorSchemes());
    echo "SCHEMES\tseeded factory defaults\n";
}
else {
    ensureColorSchemesStored();
}

$schemes = loadColorSchemes();
if(!isset($schemes[$schemeId])) {
    fwrite(STDERR, "Unknown scheme: ".$schemeId."\n");
    fwrite(STDERR, "Available: ".implode(', ', array_keys($schemes))."\n");
    exit(1);
}

if(!applyColorScheme($schemeId)) {
    fwrite(STDERR, "Failed to apply scheme ".$schemeId."\n");
    exit(1);
}

echo "APPLIED\t".$schemeId." (".$schemes[$schemeId]['name'].")\n";
echo "Done.\n";
exit(0);
