<?php
/**
 * Schema create / check / repair based on config/DBconfig.json.
 * Safe for IONOS shared hosting (mysqli only, no shell).
 */
class DatabaseManager
{
    private $schema = array();
    private $schemaPath;
    private $report = array();

    public function __construct($schemaPath = null) {
        if($schemaPath === null) {
            $schemaPath = dirname(__DIR__).'/config/DBconfig.json';
        }
        $this->schemaPath = $schemaPath;
        $this->loadSchema();
    }

    private function loadSchema() {
        if(!is_readable($this->schemaPath)) {
            throw new RuntimeException('DBconfig not readable: '.$this->schemaPath);
        }
        $json = file_get_contents($this->schemaPath);
        $data = json_decode($json, true);
        if(!is_array($data)) {
            throw new RuntimeException('Invalid DBconfig JSON');
        }
        $this->schema = $data;
    }

    public function getSchema() {
        return $this->schema;
    }

    public function getReport() {
        return $this->report;
    }

    public function hasErrors() {
        foreach($this->report as $entry) {
            if($entry['status'] === 'error' || $entry['status'] === 'missing' || $entry['status'] === 'mismatch') {
                return true;
            }
        }
        return false;
    }

    public function hasChanges() {
        foreach($this->report as $entry) {
            if(in_array($entry['status'], array('created', 'fixed', 'missing', 'mismatch'), true)) {
                return true;
            }
        }
        return false;
    }

    private function addReport($level, $target, $status, $message = '', $detail = null) {
        $this->report[] = array(
            'level' => $level,
            'target' => $target,
            'status' => $status,
            'message' => $message,
            'detail' => $detail,
        );
    }

    /**
     * True if User table is missing or has zero rows — safe for install.php.
     */
    public function isFreshInstall() {
        $userTable = new SQLtable('User');
        if(!$userTable->exists()) {
            return true;
        }
        $sql = sprintf('SELECT COUNT(`Index`) AS `CNT` FROM `%sUser`;', $GLOBALS['dbprefix']);
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if(!$dbr) return true;
        $row = mysqli_fetch_array($dbr);
        return !(isset($row['CNT']) && (int)$row['CNT'] > 0);
    }

    /**
     * True if at least one non-deleted Admin user exists.
     */
    public function hasAdminUsers() {
        $userTable = new SQLtable('User');
        if(!$userTable->exists()) {
            return false;
        }
        $sql = sprintf(
            'SELECT COUNT(`Index`) AS `CNT` FROM `%sUser` WHERE `Admin` = 1 AND `Deleted` = 0;',
            $GLOBALS['dbprefix']
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if(!$dbr) return false;
        $row = mysqli_fetch_array($dbr);
        return isset($row['CNT']) && (int)$row['CNT'] > 0;
    }

    /**
     * Create default admin MVD / MVD1949eV if no admin users exist yet.
     */
    public function ensureDefaultAdmin() {
        if($this->hasAdminUsers()) {
            $this->addReport('user', 'Admin', 'ok', 'Admin-Benutzer vorhanden — Default übersprungen');
            return false;
        }

        $userTable = new SQLtable('User');
        if(!$userTable->exists()) {
            $this->addReport('user', 'Admin', 'error', 'User-Tabelle fehlt — Default-Admin nicht angelegt');
            return false;
        }

        $login = 'MVD';
        $password = 'MVD1949eV';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $link = uniqid('', true);

        $sql = sprintf(
            'INSERT INTO `%sUser` (`Nachname`, `Vorname`, `login`, `Passhash`, `activeLink`, `Mitglied`, `Instrument`, `Email`, `Email2`, `getMail`, `Admin`, `RegisterLead`, `singleUsePW`, `Deleted`) VALUES ("%s", "%s", "%s", "%s", "%s", 1, 0, "", "", 0, 1, 0, 0, 0);',
            $GLOBALS['dbprefix'],
            'Admin',
            'MVD',
            mysqli_real_escape_string($GLOBALS['conn'], $login),
            mysqli_real_escape_string($GLOBALS['conn'], $hash),
            mysqli_real_escape_string($GLOBALS['conn'], $link)
        );
        $ok = mysqli_query($GLOBALS['conn'], $sql);
        if(!$ok) {
            $this->addReport(
                'user',
                'Admin',
                'error',
                'Default-Admin konnte nicht angelegt werden',
                mysqli_errno($GLOBALS['conn']).': '.mysqli_error($GLOBALS['conn'])
            );
            return false;
        }

        $userId = (int)mysqli_insert_id($GLOBALS['conn']);
        $this->ensureAdminPermissions($userId);
        $this->addReport('user', 'Admin', 'created', 'Default-Admin angelegt (Login: MVD)');
        return true;
    }

    private function ensureAdminPermissions($userId) {
        $permTable = new SQLtable('Permissions');
        if(!$permTable->exists() || $userId < 1) {
            return;
        }
        $sql = sprintf(
            'SELECT `Index` FROM `%sPermissions` WHERE `User` = %d LIMIT 1;',
            $GLOBALS['dbprefix'],
            $userId
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        $row = $dbr ? mysqli_fetch_array($dbr) : null;
        if($row && isset($row['Index'])) {
            return;
        }

        $insert = sprintf(
            'INSERT INTO `%sPermissions` (`User`, `perm_showHiddenAppmnts`, `perm_showUsers`, `perm_editUsers`, `perm_editAppmnts`, `perm_showLog`, `perm_showInstruments`, `perm_editInstruments`, `perm_showInventories`, `perm_editInventories`, `perm_sendEmail`, `perm_showResponse`, `perm_editResponse`, `perm_editConfig`, `perm_editPermissions`) VALUES (%d, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);',
            $GLOBALS['dbprefix'],
            $userId
        );
        mysqli_query($GLOBALS['conn'], $insert);
    }

    /**
     * Ensure default vehicle "keins" exists (Termine default Vehicle=1).
     */
    public function ensureDefaultVehicle() {
        $table = new SQLtable('vehicle');
        if(!$table->exists()) {
            $this->addReport('data', 'vehicle', 'error', 'vehicle-Tabelle fehlt');
            return false;
        }

        $sql = sprintf(
            'SELECT `Index`, `Name` FROM `%svehicle` WHERE `Name` = "keins" OR `Index` = 1 LIMIT 1;',
            $GLOBALS['dbprefix']
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        $row = $dbr ? mysqli_fetch_array($dbr) : null;
        if($row) {
            if(isset($row['Name']) && $row['Name'] === 'keins') {
                $this->addReport('data', 'vehicle', 'ok', 'Default-Fahrzeug "keins" vorhanden');
                return false;
            }
            // Index 1 exists under another name — still fine for FK joins
            $this->addReport('data', 'vehicle', 'ok', 'Fahrzeug Index 1 vorhanden');
            return false;
        }

        $insert = sprintf(
            'INSERT INTO `%svehicle` (`Index`, `Name`) VALUES (1, "keins");',
            $GLOBALS['dbprefix']
        );
        $ok = mysqli_query($GLOBALS['conn'], $insert);
        if($ok) {
            $this->addReport('data', 'vehicle', 'created', 'Default-Fahrzeug "keins" angelegt');
            return true;
        }

        // Fallback without explicit Index (e.g. if 1 was reserved)
        $insert = sprintf(
            'INSERT INTO `%svehicle` (`Name`) VALUES ("keins");',
            $GLOBALS['dbprefix']
        );
        $ok = mysqli_query($GLOBALS['conn'], $insert);
        if($ok) {
            $this->addReport('data', 'vehicle', 'created', 'Default-Fahrzeug "keins" angelegt');
            return true;
        }

        $this->addReport(
            'data',
            'vehicle',
            'error',
            'Default-Fahrzeug konnte nicht angelegt werden',
            mysqli_errno($GLOBALS['conn']).': '.mysqli_error($GLOBALS['conn'])
        );
        return false;
    }

    public function check() {
        $this->report = array();
        $this->processSchema(false, false);
        $this->checkConfigDefaults(false);
        return $this->report;
    }

    public function create() {
        $this->report = array();
        $this->processSchema(true, false);
        $this->checkConfigDefaults(true);
        $this->ensureDefaultVehicle();
        $this->ensureDefaultRegisters();
        $this->ensureDefaultInstruments();
        $this->ensureDefaultAdmin();
        $this->migrateRegNumbers();
        return $this->report;
    }

    public function repair() {
        $this->report = array();
        $this->processSchema(true, true);
        $this->checkConfigDefaults(true);
        $this->ensureDefaultVehicle();
        $this->ensureDefaultRegisters();
        $this->ensureDefaultInstruments();
        $this->ensureDefaultAdmin();
        $this->migrateRegNumbers();
        return $this->report;
    }

    /**
     * Seed default register rows (idempotent: insert missing Index only).
     * Required so Instrument types (Flöte, …) can join / sort by register.
     */
    public function ensureDefaultRegisters() {
        $table = new SQLtable('Register');
        if(!$table->exists()) {
            $this->addReport('data', 'Register', 'missing', 'Register-Tabelle fehlt');
            return false;
        }
        if(!function_exists('getRegisterDefaults')) {
            require_once dirname(__DIR__).'/config/RegisterDefaults.php';
        }
        $created = 0;
        $skipped = 0;
        foreach(getRegisterDefaults() as $row) {
            $id = (int)$row['Index'];
            $sql = sprintf(
                'SELECT `Index` FROM `%sRegister` WHERE `Index` = %d LIMIT 1;',
                $GLOBALS['dbprefix'],
                $id
            );
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
            $exists = $dbr && mysqli_fetch_array($dbr);
            if($exists) {
                $skipped++;
                continue;
            }
            $insert = sprintf(
                'INSERT INTO `%sRegister` (`Index`, `Name`, `Sortierung`, `Row`, `ArcMin`, `ArcMax`, `Color`) VALUES (%d, "%s", %d, %d, %s, %s, "%s");',
                $GLOBALS['dbprefix'],
                $id,
                mysqli_real_escape_string($GLOBALS['conn'], $row['Name']),
                (int)$row['Sortierung'],
                (int)$row['Row'],
                (float)$row['ArcMin'],
                (float)$row['ArcMax'],
                mysqli_real_escape_string($GLOBALS['conn'], $row['Color'])
            );
            if(mysqli_query($GLOBALS['conn'], $insert)) {
                $created++;
            }
            else {
                $this->addReport(
                    'data',
                    'Register.'.$id,
                    'error',
                    'Register konnte nicht angelegt werden',
                    mysqli_errno($GLOBALS['conn']).': '.mysqli_error($GLOBALS['conn'])
                );
            }
        }
        if($created > 0) {
            $maxId = 0;
            foreach(getRegisterDefaults() as $row) {
                if((int)$row['Index'] > $maxId) $maxId = (int)$row['Index'];
            }
            mysqli_query(
                $GLOBALS['conn'],
                sprintf('ALTER TABLE `%sRegister` AUTO_INCREMENT = %d;', $GLOBALS['dbprefix'], $maxId + 1)
            );
            $this->addReport('data', 'Register', 'created', "$created Register angelegt ($skipped bereits vorhanden)");
            return true;
        }
        $this->addReport('data', 'Register', 'ok', "Register vorhanden ($skipped)");
        return false;
    }

    /**
     * Seed default instrument types (idempotent: insert missing Index only).
     * These are playable types (Flöte, Trompete, …) — not inventory items / Inventory prefixes.
     */
    public function ensureDefaultInstruments() {
        $table = new SQLtable('Instrument');
        if(!$table->exists()) {
            $this->addReport('data', 'Instrument', 'missing', 'Instrument-Tabelle fehlt');
            return false;
        }
        if(!function_exists('getInstrumentDefaults')) {
            require_once dirname(__DIR__).'/config/InstrumentDefaults.php';
        }
        $created = 0;
        $skipped = 0;
        foreach(getInstrumentDefaults() as $row) {
            $id = (int)$row['Index'];
            $sql = sprintf(
                'SELECT `Index` FROM `%sInstrument` WHERE `Index` = %d LIMIT 1;',
                $GLOBALS['dbprefix'],
                $id
            );
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
            $exists = $dbr && mysqli_fetch_array($dbr);
            if($exists) {
                $skipped++;
                continue;
            }
            $insert = sprintf(
                'INSERT INTO `%sInstrument` (`Index`, `Name`, `Register`, `Sortierung`, `Spielbar`) VALUES (%d, "%s", %d, %d, %d);',
                $GLOBALS['dbprefix'],
                $id,
                mysqli_real_escape_string($GLOBALS['conn'], $row['Name']),
                (int)$row['Register'],
                (int)$row['Sortierung'],
                (int)$row['Spielbar']
            );
            if(mysqli_query($GLOBALS['conn'], $insert)) {
                $created++;
            }
            else {
                $this->addReport(
                    'data',
                    'Instrument.'.$id,
                    'error',
                    'Instrument-Typ konnte nicht angelegt werden',
                    mysqli_errno($GLOBALS['conn']).': '.mysqli_error($GLOBALS['conn'])
                );
            }
        }
        if($created > 0) {
            // Keep AUTO_INCREMENT above highest seeded id
            $maxId = 0;
            foreach(getInstrumentDefaults() as $row) {
                if((int)$row['Index'] > $maxId) $maxId = (int)$row['Index'];
            }
            mysqli_query(
                $GLOBALS['conn'],
                sprintf('ALTER TABLE `%sInstrument` AUTO_INCREMENT = %d;', $GLOBALS['dbprefix'], $maxId + 1)
            );
            $this->addReport('data', 'Instrument', 'created', "$created Instrument-Typ(en) angelegt ($skipped bereits vorhanden)");
            return true;
        }
        $this->addReport('data', 'Instrument', 'ok', "Instrument-Typen vorhanden ($skipped)");
        return false;
    }

    /**
     * Inventory prefixes, INSTR type, instrument series migration.
     */
    private function migrateRegNumbers() {
        if(!class_exists('RegNumber')) {
            require_once dirname(__DIR__).'/libs/RegNumber.php';
        }
        if(!class_exists('Inventory')) {
            require_once dirname(__DIR__).'/libs/inventory.php';
        }
        $table = new SQLtable('Inventory');
        if(!$table->exists()) {
            $this->addReport('data', 'RegNumber', 'missing', 'Inventory-Tabelle fehlt — Nummernmigration übersprungen');
            return;
        }

        $r = RegNumber::ensureInstrType();
        $this->addReport('data', 'INSTR', $r['status'], $r['message'], isset($r['detail']) ? $r['detail'] : null);

        $r = RegNumber::migrateInventoryPrefixes();
        $this->addReport('data', 'Prefix', $r['status'], $r['message']);

        $r = RegNumber::migrateInstruments();
        $this->addReport('data', 'Instruments', $r['status'], $r['message']);
    }

    /**
     * @param bool $applyCreate create missing tables/columns
     * @param bool $applyRepair modify mismatched column definitions
     */
    private function processSchema($applyCreate, $applyRepair) {
        foreach($this->schema as $tableName => $columns) {
            $SQL = new SQLtable($tableName);

            if(!$SQL->exists()) {
                if($applyCreate) {
                    $result = $SQL->create();
                    if($result === true) {
                        $this->addReport('table', $tableName, 'created', 'Tabelle angelegt');
                    }
                    else {
                        $this->addReport('table', $tableName, 'error', 'Tabelle konnte nicht angelegt werden', $SQL->getLastError());
                        continue;
                    }
                }
                else {
                    $this->addReport('table', $tableName, 'missing', 'Tabelle fehlt');
                    continue;
                }
            }
            else {
                $this->addReport('table', $tableName, 'ok', 'Tabelle vorhanden');
            }

            foreach($columns as $columnName => $definition) {
                $target = $tableName.'.'.$columnName;
                if(!$SQL->columnExists($columnName)) {
                    if($applyCreate) {
                        $result = $SQL->createColumn($columnName, $definition);
                        if($result === true) {
                            $this->addReport('column', $target, 'created', 'Spalte angelegt');
                        }
                        elseif($result === -1) {
                            $this->addReport('column', $target, 'ok', 'Spalte vorhanden');
                        }
                        else {
                            $this->addReport('column', $target, 'error', 'Spalte konnte nicht angelegt werden', $SQL->getLastError());
                        }
                    }
                    else {
                        $this->addReport('column', $target, 'missing', 'Spalte fehlt');
                    }
                    continue;
                }

                $diffs = $SQL->compareColumn($columnName, $definition);
                if(empty($diffs)) {
                    $this->addReport('column', $target, 'ok', 'Spalte ok');
                    continue;
                }

                if($applyRepair) {
                    if($SQL->modifyColumn($columnName, $definition)) {
                        $newDiffs = $SQL->compareColumn($columnName, $definition);
                        if(empty($newDiffs)) {
                            $this->addReport('column', $target, 'fixed', 'Spalte angepasst', $diffs);
                        }
                        else {
                            $this->addReport('column', $target, 'mismatch', 'Abweichung nach Repair noch vorhanden', $newDiffs);
                        }
                    }
                    else {
                        $this->addReport('column', $target, 'error', 'Spalte konnte nicht angepasst werden', $SQL->getLastError());
                    }
                }
                else {
                    $this->addReport('column', $target, 'mismatch', 'Spalte weicht ab', $diffs);
                }
            }
        }
    }

    private function checkConfigDefaults($apply) {
        if(!function_exists('getConfigDefaults')) {
            require_once dirname(__DIR__).'/config/ConfigDefaults.php';
        }
        $defaults = getConfigDefaults();
        $configTable = new SQLtable('config');
        if(!$configTable->exists()) {
            $this->addReport('config', 'config', 'missing', 'config-Tabelle fehlt — Defaults übersprungen');
            return;
        }

        foreach($defaults as $item) {
            $param = $item['Parameter'];
            $sql = sprintf(
                "SELECT `Parameter` FROM `%sconfig` WHERE `Parameter` = '%s' LIMIT 1;",
                $GLOBALS['dbprefix'],
                mysqli_real_escape_string($GLOBALS['conn'], $param)
            );
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
            $row = $dbr ? mysqli_fetch_array($dbr) : null;
            $exists = $row && isset($row['Parameter']) && $row['Parameter'] === $param;

            if($exists) {
                $this->addReport('config', $param, 'ok', 'Config-Parameter vorhanden');
                continue;
            }

            if(!$apply) {
                $this->addReport('config', $param, 'missing', 'Config-Parameter fehlt');
                continue;
            }

            $insert = sprintf(
                "INSERT INTO `%sconfig` (`Parameter`, `Value`, `Type`, `Description`) VALUES ('%s', '%s', '%s', '%s');",
                $GLOBALS['dbprefix'],
                mysqli_real_escape_string($GLOBALS['conn'], $param),
                mysqli_real_escape_string($GLOBALS['conn'], (string)$item['Value']),
                mysqli_real_escape_string($GLOBALS['conn'], $item['Type']),
                mysqli_real_escape_string($GLOBALS['conn'], $item['Description'])
            );
            $ok = mysqli_query($GLOBALS['conn'], $insert);
            if($ok) {
                $this->addReport('config', $param, 'created', 'Config-Parameter eingefügt');
            }
            else {
                $this->addReport(
                    'config',
                    $param,
                    'error',
                    'Config-Parameter konnte nicht eingefügt werden',
                    mysqli_errno($GLOBALS['conn']).': '.mysqli_error($GLOBALS['conn'])
                );
            }
        }
    }

    /**
     * Plain-text summary for CLI.
     */
    public function formatReportText() {
        $lines = array();
        foreach($this->report as $entry) {
            $line = strtoupper($entry['status'])."\t[".$entry['level']."]\t".$entry['target'];
            if($entry['message']) $line .= "\t".$entry['message'];
            if($entry['detail'] && is_string($entry['detail'])) $line .= "\t".$entry['detail'];
            if($entry['detail'] && is_array($entry['detail'])) {
                $line .= "\t".json_encode($entry['detail']);
            }
            $lines[] = $line;
        }
        return implode("\n", $lines)."\n";
    }
}
?>
