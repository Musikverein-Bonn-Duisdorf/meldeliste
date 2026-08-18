<?php
/**
 * Database + uploads backup / restore helpers (MELD-90 / MELD-131 / MELD-210).
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
        'includesUploads' => true,
        'uploadFiles' => 0,
    );
}

/**
 * Absolute uploads directory. Tests may override via $GLOBALS['backupUploadsRoot'].
 */
function backupUploadsRoot() {
    if(!empty($GLOBALS['backupUploadsRoot'])) {
        return rtrim((string)$GLOBALS['backupUploadsRoot'], '/');
    }
    return dirname(__DIR__).'/uploads';
}

/**
 * PHP/session settings for long-running backup or restore.
 */
function backupPrepareLongRun() {
    @set_time_limit(0);
    ignore_user_abort(true);
    if(function_exists('session_write_close') && session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}

/**
 * @param string $relPath Zip/relative path using forward slashes
 */
function backupIsSkippedUploadFile($relPath) {
    $base = basename(str_replace('\\', '/', (string)$relPath));
    return $base === '.mail_queue.lock'
        || $base === '.DS_Store'
        || $base === 'Thumbs.db';
}

/**
 * @param string $name
 * @return string
 */
function backupNormalizeZipEntryName($name) {
    $name = str_replace('\\', '/', (string)$name);
    $name = str_replace("\0", '', $name);
    return ltrim($name, '/');
}

/**
 * Whether a ZIP entry may be restored under uploads/.
 *
 * @param string $name
 * @return bool
 */
function backupIsSafeUploadZipPath($name) {
    $name = backupNormalizeZipEntryName($name);
    if($name === '' || $name === 'uploads' || $name === 'uploads/') {
        return true;
    }
    if(strpos($name, '..') !== false) {
        return false;
    }
    if(strpos($name, 'uploads/') !== 0) {
        return false;
    }
    return true;
}

/**
 * Regular files under uploads/ to pack (relative zip path => absolute path).
 *
 * @return array<int,array{path:string,zip:string}>
 */
function backupListUploadFiles() {
    $root = backupUploadsRoot();
    $out = array();
    if(!is_dir($root)) {
        return $out;
    }
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );
    foreach($iter as $file) {
        if(!$file->isFile() || $file->isLink()) {
            continue;
        }
        $full = $file->getPathname();
        $rel = substr($full, strlen($root));
        $rel = str_replace('\\', '/', $rel);
        $zip = 'uploads'.($rel === '' ? '' : $rel);
        $zip = str_replace('//', '/', $zip);
        if(backupIsSkippedUploadFile($zip)) {
            continue;
        }
        if(!backupIsSafeUploadZipPath($zip)) {
            continue;
        }
        $out[] = array('path' => $full, 'zip' => $zip);
    }
    return $out;
}

/**
 * Recursively delete a file or directory (does not follow symlinks).
 */
function backupRemovePath($path) {
    $path = (string)$path;
    if($path === '' || $path === '/' || $path === '.') {
        return;
    }
    if(is_link($path) || is_file($path)) {
        @unlink($path);
        return;
    }
    if(!is_dir($path)) {
        return;
    }
    $items = @scandir($path);
    if($items === false) {
        return;
    }
    foreach($items as $item) {
        if($item === '.' || $item === '..') {
            continue;
        }
        backupRemovePath($path.'/'.$item);
    }
    @rmdir($path);
}

/**
 * Move a directory, copying if rename fails (cross-filesystem).
 */
function backupMoveDir($src, $dest) {
    if(@rename($src, $dest)) {
        return;
    }
    if(!@mkdir($dest, 0775, true) && !is_dir($dest)) {
        throw new RuntimeException('Could not create directory: '.$dest);
    }
    $items = @scandir($src);
    if($items === false) {
        throw new RuntimeException('Could not read directory: '.$src);
    }
    foreach($items as $item) {
        if($item === '.' || $item === '..') {
            continue;
        }
        $from = $src.'/'.$item;
        $to = $dest.'/'.$item;
        if(is_link($from) || is_file($from)) {
            if(!@copy($from, $to)) {
                throw new RuntimeException('Could not copy file: '.$from);
            }
            continue;
        }
        if(is_dir($from)) {
            backupMoveDir($from, $to);
        }
    }
    backupRemovePath($src);
}

/**
 * Replace the live uploads directory with $newUploadsDir (moved into place).
 */
function backupReplaceUploadsDir($newUploadsDir) {
    $target = backupUploadsRoot();
    $parent = dirname($target);
    if(!is_dir($parent) && !@mkdir($parent, 0775, true)) {
        throw new RuntimeException('Could not create uploads parent directory.');
    }
    $bak = $parent.'/'.basename($target).'.bak-'.bin2hex(random_bytes(4));
    $hadTarget = is_dir($target) || is_link($target);
    if($hadTarget) {
        try {
            backupMoveDir($target, $bak);
        }
        catch(Throwable $e) {
            throw new RuntimeException('Could not move current uploads aside.');
        }
    }
    try {
        backupMoveDir($newUploadsDir, $target);
    }
    catch(Throwable $e) {
        if($hadTarget && is_dir($bak)) {
            try {
                backupMoveDir($bak, $target);
            }
            catch(Throwable $ignored) {
            }
        }
        throw new RuntimeException('Could not install restored uploads.');
    }
    if($hadTarget) {
        backupRemovePath($bak);
    }
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

    backupPrepareLongRun();

    $uploadFiles = backupListUploadFiles();
    $manifest = buildBackupManifest();
    $manifest['includesUploads'] = true;
    $manifest['uploadFiles'] = count($uploadFiles);

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
    $zip->addEmptyDir('uploads');
    foreach($uploadFiles as $file) {
        if(!$zip->addFile($file['path'], $file['zip'])) {
            $zip->close();
            @unlink($zipPath);
            throw new RuntimeException('Could not add file to backup ZIP: '.$file['zip']);
        }
    }
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
 * Format a byte size for log display (B / KB / MB / GB).
 *
 * @param int $bytes
 * @return string
 */
function backupFormatBytes($bytes) {
    $bytes = max(0, (int)$bytes);
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $value = (float)$bytes;
    $unit = 0;
    while($value >= 1024 && $unit < count($units) - 1) {
        $value /= 1024;
        $unit++;
    }
    if($unit === 0) {
        return ((int)$value).' B';
    }
    $rounded = round($value, 1);
    if(abs($rounded - round($rounded)) < 0.05) {
        return ((int)round($rounded)).' '.$units[$unit];
    }
    return number_format($rounded, 1, '.', '').' '.$units[$unit];
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
        $logentry->error('<b>Backup failed</b>: '.$detail.' (via '.$viaLabel.')');
        throw new RuntimeException('Backup ZIP missing or empty.');
    }

    $filesPart = '';
    if(isset($backup['manifest']['uploadFiles'])) {
        $filesPart = ', '.(int)$backup['manifest']['uploadFiles'].' files';
    }
    $logentry = new Log;
    $logentry->info('<b>Backup</b> '.htmlspecialchars($filename, ENT_QUOTES, 'UTF-8')
        .' ('.backupFormatBytes((int)$size).$filesPart.', via '.$viaLabel.')');
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
    $logentry->error('<b>Backup failed</b>: '.$safe.' (via '.$viaLabel.')');
}

/**
 * Send backup ZIP to the HTTP client and exit.
 * Logs success (info) after confirming the ZIP, or error on failure (MELD-132).
 */
function sendBackupDownload() {
    backupPrepareLongRun();
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
 * @param ZipArchive $zip
 * @return array{files:string[],hasUploads:bool}
 */
function backupCollectUploadZipEntries($zip) {
    $files = array();
    $hasUploads = false;
    $n = $zip->numFiles;
    for($i = 0; $i < $n; $i++) {
        $stat = $zip->statIndex($i);
        if(!is_array($stat) || empty($stat['name'])) {
            continue;
        }
        $name = backupNormalizeZipEntryName($stat['name']);
        if($name !== 'uploads' && $name !== 'uploads/' && strpos($name, 'uploads/') !== 0) {
            continue;
        }
        if(!backupIsSafeUploadZipPath($name)) {
            throw new RuntimeException('Unsafe path in backup ZIP: '.$name);
        }
        $hasUploads = true;
        if(substr($name, -1) === '/' || $name === 'uploads') {
            continue;
        }
        if(backupIsSkippedUploadFile($name)) {
            continue;
        }
        $mode = isset($stat['external_attr']) ? (($stat['external_attr'] >> 16) & 0xFFFF) : 0;
        if($mode !== 0 && (($mode & 0170000) === 0120000)) {
            continue;
        }
        $files[] = $stat['name'];
    }
    return array('files' => $files, 'hasUploads' => $hasUploads);
}

/**
 * Snapshot-restore uploads/ from an already-open ZIP (extract to temp, then replace).
 *
 * @param ZipArchive $zip
 * @param string[] $entryNames
 * @return int Number of files restored
 */
function backupRestoreUploadsFromZip($zip, $entryNames) {
    $tmp = sys_get_temp_dir().'/meld-restore-'.bin2hex(random_bytes(8));
    if(!@mkdir($tmp, 0700)) {
        throw new RuntimeException('Could not create temporary directory for restore.');
    }
    $extractedRoot = $tmp.'/uploads';
    if(!@mkdir($extractedRoot, 0775, true)) {
        backupRemovePath($tmp);
        throw new RuntimeException('Could not create temporary uploads directory.');
    }

    $count = 0;
    try {
        foreach($entryNames as $entryName) {
            $name = backupNormalizeZipEntryName($entryName);
            if(!backupIsSafeUploadZipPath($name) || backupIsSkippedUploadFile($name)) {
                continue;
            }
            $rel = substr($name, strlen('uploads/'));
            if($rel === false || $rel === '' || $rel === $name) {
                continue;
            }
            $dest = $extractedRoot.'/'.$rel;
            $dir = dirname($dest);
            if(!is_dir($dir) && !@mkdir($dir, 0775, true)) {
                throw new RuntimeException('Could not create restore path for '.$name);
            }
            $dirReal = realpath($dir);
            $baseReal = realpath($extractedRoot);
            $prefix = $baseReal.DIRECTORY_SEPARATOR;
            if($dirReal === false || $baseReal === false
                || ($dirReal !== $baseReal && strpos($dirReal, $prefix) !== 0)) {
                throw new RuntimeException('Unsafe restore path in backup ZIP: '.$name);
            }
            $stream = $zip->getStream($entryName);
            if($stream === false) {
                throw new RuntimeException('Could not read ZIP entry: '.$name);
            }
            $out = fopen($dest, 'wb');
            if($out === false) {
                fclose($stream);
                throw new RuntimeException('Could not write restored file: '.$name);
            }
            stream_copy_to_stream($stream, $out);
            fclose($out);
            fclose($stream);
            $count++;
        }
        backupReplaceUploadsDir($extractedRoot);
    }
    catch(Throwable $e) {
        backupRemovePath($tmp);
        throw $e;
    }
    backupRemovePath($tmp);
    return $count;
}

/**
 * Restore from a backup ZIP path.
 *
 * @param string $zipPath
 * @param bool $runRepair
 * @return array{manifest:?array,statements:int,errors:string[],repaired:bool,filesRestored:int}
 */
function restoreBackupZip($zipPath, $runRepair = true) {
    if(!class_exists('ZipArchive')) {
        throw new RuntimeException('PHP ZipArchive extension is required for restore.');
    }
    if(!is_readable($zipPath)) {
        throw new RuntimeException('Backup ZIP not readable: '.$zipPath);
    }

    backupPrepareLongRun();

    $zip = new ZipArchive();
    if($zip->open($zipPath) !== true) {
        throw new RuntimeException('Could not open backup ZIP.');
    }
    $sql = $zip->getFromName('database.sql');
    $manifestRaw = $zip->getFromName('manifest.json');

    $uploadScan = backupCollectUploadZipEntries($zip);

    if($sql === false || $sql === '') {
        $zip->close();
        throw new RuntimeException('ZIP does not contain database.sql');
    }

    $manifest = null;
    if($manifestRaw !== false && $manifestRaw !== '') {
        $decoded = json_decode($manifestRaw, true);
        if(is_array($decoded)) {
            $manifest = $decoded;
        }
    }

    $includesUploads = $uploadScan['hasUploads']
        || ($manifest !== null && !empty($manifest['includesUploads']));

    $result = restoreDatabaseSql($sql);
    $repaired = false;
    $filesRestored = 0;
    if(empty($result['errors'])) {
        if($runRepair && class_exists('DatabaseManager')) {
            $mgr = new DatabaseManager();
            $mgr->repair();
            $repaired = true;
        }
        if($includesUploads) {
            try {
                $filesRestored = backupRestoreUploadsFromZip($zip, $uploadScan['files']);
            }
            catch(Throwable $e) {
                $zip->close();
                $result['errors'][] = $e->getMessage();
                return array(
                    'manifest' => $manifest,
                    'statements' => $result['statements'],
                    'errors' => $result['errors'],
                    'repaired' => $repaired,
                    'filesRestored' => 0,
                );
            }
        }
    }
    $zip->close();

    return array(
        'manifest' => $manifest,
        'statements' => $result['statements'],
        'errors' => $result['errors'],
        'repaired' => $repaired,
        'filesRestored' => $filesRestored,
    );
}
?>
