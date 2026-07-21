<?php
/**
 * Database backup / restore helpers (MELD-90 / MELD-131).
 */

/** Minimum length for HTTP remote backup token (opt-in). */
define('BACKUP_HTTP_TOKEN_MIN_LEN', 32);

/**
 * Configured HTTP backup token, or empty if unset.
 */
function backupHttpTokenConfiguredValue() {
    if(!isset($GLOBALS['backupToken'])) {
        return '';
    }
    return trim((string)$GLOBALS['backupToken']);
}

/**
 * Whether a usable HTTP backup token is configured.
 */
function backupHttpTokenConfigured() {
    return strlen(backupHttpTokenConfiguredValue()) >= BACKUP_HTTP_TOKEN_MIN_LEN;
}

/**
 * Token from query id=… or Authorization: Bearer ….
 */
function backupHttpExtractProvidedToken() {
    if(isset($_GET['id']) && (string)$_GET['id'] !== '') {
        return (string)$_GET['id'];
    }
    $header = '';
    if(!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $header = (string)$_SERVER['HTTP_AUTHORIZATION'];
    }
    elseif(!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $header = (string)$_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
    if(preg_match('/^Bearer\s+(\S+)/i', $header, $m)) {
        return $m[1];
    }
    return '';
}

/**
 * Validate a provided HTTP backup token (timing-safe). Never accepts $cronID.
 */
function backupHttpTokenValid($provided) {
    $provided = (string)$provided;
    if($provided === '' || !backupHttpTokenConfigured()) {
        return false;
    }
    $expected = backupHttpTokenConfiguredValue();
    if(strlen($provided) !== strlen($expected)) {
        return false;
    }
    return hash_equals($expected, $provided);
}

/**
 * @return array
 */
function buildBackupManifest() {
    $version = isset($GLOBALS['version']) && is_array($GLOBALS['version'])
        ? $GLOBALS['version']
        : array('String' => '', 'Date' => '', 'Hash' => '');

    $installed = null;
    $expected = null;
    if(class_exists('DatabaseManager')) {
        try {
            $mgr = new DatabaseManager();
            $installed = $mgr->getInstalledSchemaVersion();
            $expected = $mgr->getExpectedSchemaVersion();
        }
        catch(Throwable $e) {
            // leave nulls
        }
    }

    return array(
        'app' => 'meldeliste',
        'createdAt' => gmdate('c'),
        'dbprefix' => isset($GLOBALS['dbprefix']) ? (string)$GLOBALS['dbprefix'] : '',
        'version' => array(
            'String' => isset($version['String']) ? (string)$version['String'] : '',
            'Date' => isset($version['Date']) ? (string)$version['Date'] : '',
            'Hash' => isset($version['Hash']) ? (string)$version['Hash'] : '',
        ),
        'schemaVersion' => array(
            'installed' => $installed,
            'expected' => $expected,
        ),
    );
}

/**
 * List tables matching the configured db prefix.
 *
 * @return string[]
 */
function backupListPrefixedTables() {
    $conn = $GLOBALS['conn'];
    $prefix = isset($GLOBALS['dbprefix']) ? (string)$GLOBALS['dbprefix'] : '';
    $like = mysqli_real_escape_string($conn, $prefix.'%');
    $dbr = mysqli_query($conn, "SHOW TABLES LIKE '".$like."'");
    if(!$dbr) {
        sqlerror();
        return array();
    }
    $tables = array();
    while($row = mysqli_fetch_row($dbr)) {
        if(!empty($row[0])) {
            $tables[] = $row[0];
        }
    }
    sort($tables);
    return $tables;
}

/**
 * Escape a SQL literal for dump INSERT statements.
 *
 * @param mixed $value
 * @return string
 */
function backupSqlLiteral($value) {
    if($value === null) {
        return 'NULL';
    }
    if(is_int($value) || is_float($value)) {
        return (string)$value;
    }
    return "'".mysqli_real_escape_string($GLOBALS['conn'], (string)$value)."'";
}

/**
 * Build a full SQL dump for all prefixed tables.
 *
 * @return string
 */
function exportDatabaseSql() {
    $conn = $GLOBALS['conn'];
    $tables = backupListPrefixedTables();
    $out = array();
    $out[] = '-- Meldeliste database backup';
    $out[] = '-- Created: '.gmdate('c');
    $out[] = 'SET NAMES utf8mb4;';
    $out[] = 'SET FOREIGN_KEY_CHECKS=0;';
    $out[] = 'SET UNIQUE_CHECKS=0;';
    $out[] = 'SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";';
    $out[] = '';

    foreach($tables as $table) {
        $safe = str_replace('`', '``', $table);
        $createRes = mysqli_query($conn, 'SHOW CREATE TABLE `'.$safe.'`');
        if(!$createRes) {
            sqlerror();
            continue;
        }
        $createRow = mysqli_fetch_assoc($createRes);
        $createSql = '';
        if(is_array($createRow)) {
            foreach($createRow as $k => $v) {
                if(stripos((string)$k, 'Create') === 0) {
                    $createSql = $v;
                    break;
                }
            }
        }
        if($createSql === '') {
            continue;
        }

        $out[] = '-- Table `'.$table.'`';
        $out[] = 'DROP TABLE IF EXISTS `'.$safe.'`;';
        $out[] = $createSql.';';
        $out[] = '';

        $dataRes = mysqli_query($conn, 'SELECT * FROM `'.$safe.'`');
        if(!$dataRes) {
            sqlerror();
            continue;
        }
        $fields = array();
        $fieldInfo = mysqli_fetch_fields($dataRes);
        if($fieldInfo) {
            foreach($fieldInfo as $f) {
                $fields[] = '`'.str_replace('`', '``', $f->name).'`';
            }
        }
        $batch = array();
        $batchSize = 50;
        while($row = mysqli_fetch_row($dataRes)) {
            $vals = array();
            foreach($row as $col) {
                $vals[] = backupSqlLiteral($col);
            }
            $batch[] = '('.implode(',', $vals).')';
            if(count($batch) >= $batchSize) {
                $out[] = 'INSERT INTO `'.$safe.'` ('.implode(',', $fields).') VALUES';
                $out[] = implode(",\n", $batch).';';
                $out[] = '';
                $batch = array();
            }
        }
        if($batch) {
            $out[] = 'INSERT INTO `'.$safe.'` ('.implode(',', $fields).') VALUES';
            $out[] = implode(",\n", $batch).';';
            $out[] = '';
        }
        mysqli_free_result($dataRes);
    }

    $out[] = 'SET FOREIGN_KEY_CHECKS=1;';
    $out[] = 'SET UNIQUE_CHECKS=1;';
    $out[] = '';
    return implode("\n", $out);
}

/**
 * Create a backup ZIP in a temp file.
 *
 * @return array{path:string,filename:string,manifest:array}
 */
function createBackupZipFile() {
    if(!class_exists('ZipArchive')) {
        throw new RuntimeException('PHP ZipArchive extension is required for backups.');
    }

    $manifest = buildBackupManifest();
    $sql = exportDatabaseSql();
    $stamp = gmdate('Y-m-d-His');
    $filename = 'meldeliste-backup-'.$stamp.'.zip';
    $path = tempnam(sys_get_temp_dir(), 'meldbackup_');
    if($path === false) {
        throw new RuntimeException('Could not create temporary file for backup.');
    }
    $zipPath = $path.'.zip';
    @unlink($path);

    $zip = new ZipArchive();
    if($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Could not open ZIP for writing.');
    }
    $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
    $zip->addFromString('database.sql', $sql);
    $zip->close();

    return array(
        'path' => $zipPath,
        'filename' => $filename,
        'manifest' => $manifest,
    );
}

/**
 * How the backup was requested (for log messages).
 *
 * @return string cli|http|ui
 */
function backupDownloadVia() {
    if(PHP_SAPI === 'cli') {
        return 'cli';
    }
    $script = isset($_SERVER['SCRIPT_NAME']) ? basename((string)$_SERVER['SCRIPT_NAME']) : '';
    if($script === 'cron.php') {
        return 'http';
    }
    return 'ui';
}

/**
 * Confirm a backup ZIP is readable and non-empty, then write an info Log entry.
 * On failure writes an error Log entry and throws.
 *
 * @param array{path:string,filename:string,manifest?:array} $backup
 * @param string|null $via Override via label (cli|http|ui); null = detect
 * @return int Byte size of the ZIP
 */
function confirmAndLogBackupSuccess($backup, $via = null) {
    $path = isset($backup['path']) ? (string)$backup['path'] : '';
    $filename = isset($backup['filename']) ? (string)$backup['filename'] : '';
    $viaLabel = $via !== null ? (string)$via : backupDownloadVia();
    $size = ($path !== '' && is_file($path)) ? filesize($path) : false;

    if($path === '' || $filename === '' || $size === false || $size <= 0) {
        $detail = ($path === '' || !is_file($path)) ? 'ZIP missing' : 'ZIP empty';
        $logentry = new Log;
        $logentry->error('<b>Database backup failed</b>: '.$detail.' (via '.$viaLabel.')');
        throw new RuntimeException('Backup ZIP missing or empty.');
    }

    $logentry = new Log;
    $logentry->info('<b>Database backup</b> '.htmlspecialchars($filename, ENT_QUOTES, 'UTF-8')
        .' ('.$size.' bytes, via '.$viaLabel.')');
    return (int)$size;
}

/**
 * Log a failed backup attempt (MELD-132).
 *
 * @param string $message
 * @param string|null $via
 */
function logBackupFailure($message, $via = null) {
    $viaLabel = $via !== null ? (string)$via : backupDownloadVia();
    $safe = htmlspecialchars(trim((string)$message), ENT_QUOTES, 'UTF-8');
    if($safe === '') {
        $safe = 'unknown error';
    }
    $logentry = new Log;
    $logentry->error('<b>Database backup failed</b>: '.$safe.' (via '.$viaLabel.')');
}

/**
 * Send backup ZIP to the HTTP client and exit.
 * Logs success (info) after confirming the ZIP, or error on failure (MELD-132).
 */
function sendBackupDownload() {
    try {
        $backup = createBackupZipFile();
    }
    catch(Throwable $e) {
        logBackupFailure($e->getMessage());
        throw $e;
    }

    $size = confirmAndLogBackupSuccess($backup);
    $path = $backup['path'];
    $filename = $backup['filename'];

    if(function_exists('header_remove')) {
        @header_remove();
    }
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Content-Length: '.$size);
    header('Cache-Control: no-store');
    readfile($path);
    @unlink($path);
    exit;
}

/**
 * Split SQL dump into executable statements (naive but sufficient for our exporter).
 *
 * @param string $sql
 * @return string[]
 */
function backupSplitSqlStatements($sql) {
    $statements = array();
    $buffer = '';
    $lines = preg_split("/\r\n|\n|\r/", $sql);
    foreach($lines as $line) {
        $trim = ltrim($line);
        if($trim === '' || strpos($trim, '--') === 0) {
            continue;
        }
        $buffer .= $line."\n";
        if(substr(rtrim($line), -1) === ';') {
            $stmt = trim($buffer);
            if($stmt !== '' && $stmt !== ';') {
                $statements[] = $stmt;
            }
            $buffer = '';
        }
    }
    $tail = trim($buffer);
    if($tail !== '') {
        $statements[] = $tail;
    }
    return $statements;
}

/**
 * Execute a SQL dump against the current connection.
 *
 * @param string $sql
 * @return array{statements:int,errors:string[]}
 */
function restoreDatabaseSql($sql) {
    $conn = $GLOBALS['conn'];
    $statements = backupSplitSqlStatements($sql);
    $errors = array();
    $ok = 0;

    mysqli_query($conn, 'SET FOREIGN_KEY_CHECKS=0');
    foreach($statements as $stmt) {
        if(!mysqli_query($conn, $stmt)) {
            $errors[] = mysqli_errno($conn).': '.mysqli_error($conn);
            // continue to collect more errors but stop after a few
            if(count($errors) >= 10) {
                break;
            }
        }
        else {
            $ok++;
        }
    }
    mysqli_query($conn, 'SET FOREIGN_KEY_CHECKS=1');

    return array(
        'statements' => $ok,
        'errors' => $errors,
    );
}

/**
 * Restore from a backup ZIP path.
 *
 * @param string $zipPath
 * @param bool $runRepair
 * @return array{manifest:?array,statements:int,errors:string[],repaired:bool}
 */
function restoreBackupZip($zipPath, $runRepair = true) {
    if(!class_exists('ZipArchive')) {
        throw new RuntimeException('PHP ZipArchive extension is required for restore.');
    }
    if(!is_readable($zipPath)) {
        throw new RuntimeException('Backup ZIP not readable: '.$zipPath);
    }

    $zip = new ZipArchive();
    if($zip->open($zipPath) !== true) {
        throw new RuntimeException('Could not open backup ZIP.');
    }
    $sql = $zip->getFromName('database.sql');
    $manifestRaw = $zip->getFromName('manifest.json');
    $zip->close();

    if($sql === false || $sql === '') {
        throw new RuntimeException('ZIP does not contain database.sql');
    }

    $manifest = null;
    if($manifestRaw !== false && $manifestRaw !== '') {
        $decoded = json_decode($manifestRaw, true);
        if(is_array($decoded)) {
            $manifest = $decoded;
        }
    }

    $result = restoreDatabaseSql($sql);
    $repaired = false;
    if($runRepair && empty($result['errors']) && class_exists('DatabaseManager')) {
        $mgr = new DatabaseManager();
        $mgr->repair();
        $repaired = true;
    }

    return array(
        'manifest' => $manifest,
        'statements' => $result['statements'],
        'errors' => $result['errors'],
        'repaired' => $repaired,
    );
}
?>
