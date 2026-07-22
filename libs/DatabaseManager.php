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

    /**
     * Statuses that matter in check/repair UI (skip noisy "ok").
     * @param string $status
     * @return bool
     */
    public static function isNotableStatus($status) {
        return in_array((string)$status, array(
            'created',
            'fixed',
            'removed',
            'missing',
            'mismatch',
            'error',
            'obsolete',
        ), true);
    }

    /**
     * Report without "ok" noise — changes, problems, mismatches only.
     * @return array<int,array{level:string,target:string,status:string,message:string,detail:mixed}>
     */
    public function getNotableReport() {
        $out = array();
        foreach($this->report as $entry) {
            if(self::isNotableStatus(isset($entry['status']) ? $entry['status'] : '')) {
                $out[] = $entry;
            }
        }
        return $out;
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
            if(self::isNotableStatus($entry['status'])) {
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
        $this->pruneObsoleteSchema(false);
        $this->checkConfigDefaults(false);
        return $this->report;
    }

    public function create() {
        $this->report = array();
        $this->processSchema(true, false);
        $this->migrateAudienceLegacyColumns();
        $this->pruneObsoleteSchema(true);
        $this->checkConfigDefaults(true);
        $this->ensureDefaultVehicle();
        $this->ensureDefaultRegisters();
        $this->ensureDefaultInstruments();
        $this->ensureDefaultAdmin();
        $this->migrateRegNumbers();
        $this->ensureMailTableUtf8mb4();
        $this->ensureAllTablesUtf8mb4();
        $this->migrateHtmlEntitiesInTextFields();
        $this->finalizeSchemaVersion();
        return $this->report;
    }

    public function repair() {
        $this->report = array();
        $this->processSchema(true, true);
        $this->migrateAudienceLegacyColumns();
        $this->pruneObsoleteSchema(true);
        $this->checkConfigDefaults(true);
        $this->ensureDefaultVehicle();
        $this->ensureDefaultRegisters();
        $this->ensureDefaultInstruments();
        $this->ensureDefaultAdmin();
        $this->migrateRegNumbers();
        $this->ensureMailTableUtf8mb4();
        $this->ensureAllTablesUtf8mb4();
        $this->migrateHtmlEntitiesInTextFields();
        $this->finalizeSchemaVersion();
        return $this->report;
    }

    /**
     * Expected schema version from config/SchemaVersion.php.
     */
    public function getExpectedSchemaVersion($forceReload = false) {
        if(!function_exists('getExpectedSchemaVersion')) {
            require_once dirname(__DIR__).'/config/SchemaVersion.php';
        }
        return (int)call_user_func('getExpectedSchemaVersion', $forceReload);
    }

    /**
     * Installed schema version from config table (0 if missing).
     */
    public function getInstalledSchemaVersion() {
        $configTable = new SQLtable('config');
        if(!$configTable->exists()) {
            return 0;
        }
        $sql = sprintf(
            "SELECT `Value` FROM `%sconfig` WHERE `Parameter` = 'SchemaVersion' LIMIT 1;",
            $GLOBALS['dbprefix']
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        $row = $dbr ? mysqli_fetch_array($dbr) : null;
        if(!$row || !isset($row['Value'])) {
            return 0;
        }
        return (int)$row['Value'];
    }

    public function isSchemaOutdated($forceReload = false) {
        return $this->getInstalledSchemaVersion() < $this->getExpectedSchemaVersion($forceReload);
    }

    /**
     * Persist SchemaVersion in config table (insert or update).
     */
    public function setInstalledSchemaVersion($version) {
        $version = (int)$version;
        $configTable = new SQLtable('config');
        if(!$configTable->exists()) {
            $this->addReport('data', 'SchemaVersion', 'error', 'config-Tabelle fehlt — Version nicht gesetzt');
            return false;
        }
        $param = 'SchemaVersion';
        $sql = sprintf(
            "SELECT `Parameter` FROM `%sconfig` WHERE `Parameter` = '%s' LIMIT 1;",
            $GLOBALS['dbprefix'],
            mysqli_real_escape_string($GLOBALS['conn'], $param)
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        $row = $dbr ? mysqli_fetch_array($dbr) : null;
        $exists = $row && isset($row['Parameter']) && $row['Parameter'] === $param;

        if($exists) {
            $update = sprintf(
                "UPDATE `%sconfig` SET `Value` = '%d' WHERE `Parameter` = '%s';",
                $GLOBALS['dbprefix'],
                $version,
                mysqli_real_escape_string($GLOBALS['conn'], $param)
            );
            $ok = mysqli_query($GLOBALS['conn'], $update);
        }
        else {
            $insert = sprintf(
                "INSERT INTO `%sconfig` (`Parameter`, `Value`, `Type`, `Description`) VALUES ('%s', '%d', 'int', '%s');",
                $GLOBALS['dbprefix'],
                mysqli_real_escape_string($GLOBALS['conn'], $param),
                $version,
                mysqli_real_escape_string($GLOBALS['conn'], 'Installierte DB-Schema-Version (Soll: config/SchemaVersion.php)')
            );
            $ok = mysqli_query($GLOBALS['conn'], $insert);
        }

        if($ok) {
            if(isset($GLOBALS['optionsDB']) && is_array($GLOBALS['optionsDB'])) {
                $GLOBALS['optionsDB']['SchemaVersion'] = (string)$version;
            }
            return true;
        }
        $this->addReport(
            'data',
            'SchemaVersion',
            'error',
            'SchemaVersion konnte nicht gespeichert werden',
            mysqli_errno($GLOBALS['conn']).': '.mysqli_error($GLOBALS['conn'])
        );
        return false;
    }

    /**
     * After successful create/repair, bump installed version to expected.
     */
    private function finalizeSchemaVersion() {
        $expected = $this->getExpectedSchemaVersion();
        $installed = $this->getInstalledSchemaVersion();
        if($this->hasErrors()) {
            $this->addReport(
                'data',
                'SchemaVersion',
                'mismatch',
                sprintf('Version nicht gesetzt (Fehler vorhanden). Installiert: %d, Soll: %d', $installed, $expected)
            );
            return;
        }
        if($installed === $expected) {
            $this->addReport('data', 'SchemaVersion', 'ok', 'Schema-Version '.$expected);
            return;
        }
        if($this->setInstalledSchemaVersion($expected)) {
            $this->addReport(
                'data',
                'SchemaVersion',
                'fixed',
                sprintf('Schema-Version %d → %d', $installed, $expected)
            );
        }
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
            $color = isset($row['Color']) && $row['Color'] !== ''
                ? '"'.mysqli_real_escape_string($GLOBALS['conn'], $row['Color']).'"'
                : 'NULL';
            $insert = sprintf(
                'INSERT INTO `%sInstrument` (`Index`, `Name`, `Register`, `Sortierung`, `Spielbar`, `Color`) VALUES (%d, "%s", %d, %d, %d, %s);',
                $GLOBALS['dbprefix'],
                $id,
                mysqli_real_escape_string($GLOBALS['conn'], $row['Name']),
                (int)$row['Register'],
                (int)$row['Sortierung'],
                (int)$row['Spielbar'],
                $color
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

    /**
     * MELD-61: fold legacy Termine.published into VisibilitySpec (Alle User / versteckt).
     * Idempotent via config flag; recovers if published was already dropped with empty specs.
     */
    private function migrateAudienceLegacyColumns() {
        if(!class_exists('AudienceSpec')) {
            require_once dirname(__DIR__).'/libs/audienceSpec.php';
        }

        $flag = 'visibilitySpecFromPublishedMigrated';
        $termine = new SQLtable('Termine');
        if($termine->exists() && $termine->columnExists('VisibilitySpec')) {
            $already = $this->getConfigFlag($flag);
            if($already) {
                $this->addReport('data', 'Termine.VisibilitySpec', 'ok', 'Sichtbarkeit bereits aus published migriert');
            }
            else {
                $hasPublished = $termine->columnExists('published');
                $defaultJson = json_encode(AudienceSpec::defaultVisibilitySpec());
                $sql = sprintf(
                    'SELECT `Index`, `VisibilitySpec`%s FROM `%sTermine`;',
                    $hasPublished ? ', `published`' : '',
                    $GLOBALS['dbprefix']
                );
                $dbr = mysqli_query($GLOBALS['conn'], $sql);
                $toAlleUser = 0;
                $toHidden = 0;
                $errors = 0;
                if(!$dbr) {
                    $this->addReport(
                        'data',
                        'Termine.VisibilitySpec',
                        'error',
                        'Migration konnte Termine nicht lesen',
                        mysqli_errno($GLOBALS['conn']).': '.mysqli_error($GLOBALS['conn'])
                    );
                }
                else {
                    while($row = mysqli_fetch_array($dbr, MYSQLI_ASSOC)) {
                        $id = (int)$row['Index'];
                        $rawVis = isset($row['VisibilitySpec']) ? $row['VisibilitySpec'] : null;
                        $spec = AudienceSpec::normalize($rawVis, array(
                            'allowMailGroups' => true,
                            'defaultGroups' => null,
                        ));
                        $isEmpty = AudienceSpec::isEmpty($spec);

                        $wantAlleUser = false;
                        $wantHidden = false;
                        if($hasPublished) {
                            $published = (int)$row['published'];
                            if($published > 0) {
                                if($isEmpty) {
                                    $wantAlleUser = true;
                                }
                            }
                            else {
                                // unpublished → versteckt
                                if($rawVis !== null && $rawVis !== '') {
                                    $wantHidden = true;
                                }
                            }
                        }
                        else {
                            // Recovery: published already gone — empty Spec was former „sichtbar“
                            if($isEmpty) {
                                $wantAlleUser = true;
                            }
                        }

                        if(!$wantAlleUser && !$wantHidden) {
                            continue;
                        }
                        if($wantHidden) {
                            $update = sprintf(
                                'UPDATE `%sTermine` SET `VisibilitySpec` = NULL WHERE `Index` = %d;',
                                $GLOBALS['dbprefix'],
                                $id
                            );
                        }
                        else {
                            $update = sprintf(
                                'UPDATE `%sTermine` SET `VisibilitySpec` = \'%s\' WHERE `Index` = %d;',
                                $GLOBALS['dbprefix'],
                                mysqli_real_escape_string($GLOBALS['conn'], $defaultJson),
                                $id
                            );
                        }
                        if(mysqli_query($GLOBALS['conn'], $update)) {
                            if($wantHidden) {
                                $toHidden++;
                            }
                            else {
                                $toAlleUser++;
                            }
                        }
                        else {
                            $errors++;
                            $this->addReport(
                                'data',
                                'Termine.VisibilitySpec',
                                'error',
                                'Migration Termin #'.$id.' fehlgeschlagen',
                                mysqli_errno($GLOBALS['conn']).': '.mysqli_error($GLOBALS['conn'])
                            );
                        }
                    }
                }

                if($errors === 0) {
                    $this->setConfigFlag($flag, true, 'published/VisibilitySpec-Migration (MELD-61) erledigt');
                    $this->addReport(
                        'data',
                        'Termine.VisibilitySpec',
                        ($toAlleUser + $toHidden) > 0 ? 'fixed' : 'ok',
                        sprintf(
                            'Sichtbarkeit migriert: %d → Alle User, %d → versteckt%s',
                            $toAlleUser,
                            $toHidden,
                            $hasPublished ? '' : ' (Recovery ohne published-Spalte)'
                        )
                    );
                }
            }
        }

        $mailJob = new SQLtable('MailJob');
        if($mailJob->exists() && $mailJob->columnExists('RecipientSpec')) {
            $hasMemberOnly = $mailJob->columnExists('MemberOnly');
            $hasRegister = $mailJob->columnExists('Register');
            if($hasMemberOnly || $hasRegister) {
                $cols = '`Index`, `RecipientSpec`';
                if($hasMemberOnly) $cols .= ', `MemberOnly`';
                if($hasRegister) $cols .= ', `Register`';
                $sql = sprintf('SELECT %s FROM `%sMailJob`;', $cols, $GLOBALS['dbprefix']);
                $dbr = mysqli_query($GLOBALS['conn'], $sql);
                $updated = 0;
                if($dbr) {
                    while($row = mysqli_fetch_array($dbr, MYSQLI_ASSOC)) {
                        $id = (int)$row['Index'];
                        $legacyRegister = $hasRegister ? (int)$row['Register'] : 0;
                        $legacyMemberOnly = $hasMemberOnly ? (int)$row['MemberOnly'] : 0;
                        $spec = AudienceSpec::normalize(
                            isset($row['RecipientSpec']) ? $row['RecipientSpec'] : null,
                            array(
                                'allowMailGroups' => true,
                                'defaultGroups' => null,
                                'legacyRegister' => $legacyRegister,
                                'legacyMemberOnly' => $legacyMemberOnly,
                            )
                        );
                        $raw = isset($row['RecipientSpec']) ? trim((string)$row['RecipientSpec']) : '';
                        if($raw !== '' && !AudienceSpec::isEmpty(AudienceSpec::normalize($raw, array('allowMailGroups' => true, 'defaultGroups' => null)))) {
                            continue;
                        }
                        if(AudienceSpec::isEmpty($spec) && $legacyRegister <= 0 && !$legacyMemberOnly) {
                            continue;
                        }
                        $payload = json_encode(array(
                            'groups' => $spec['groups'],
                            'registers' => $spec['registers'],
                            'users' => $spec['users'],
                            'mailGroups' => $spec['mailGroups'],
                            'termine' => isset($spec['termine']) ? $spec['termine'] : array(),
                        ));
                        $update = sprintf(
                            'UPDATE `%sMailJob` SET `RecipientSpec` = \'%s\' WHERE `Index` = %d;',
                            $GLOBALS['dbprefix'],
                            mysqli_real_escape_string($GLOBALS['conn'], $payload),
                            $id
                        );
                        if(mysqli_query($GLOBALS['conn'], $update)) {
                            $updated++;
                        }
                        else {
                            $this->addReport(
                                'data',
                                'MailJob.RecipientSpec',
                                'error',
                                'Migration MailJob #'.$id.' fehlgeschlagen',
                                mysqli_errno($GLOBALS['conn']).': '.mysqli_error($GLOBALS['conn'])
                            );
                        }
                    }
                }
                if($updated > 0) {
                    $this->addReport('data', 'MailJob.RecipientSpec', 'fixed', $updated.' MailJob(s) RecipientSpec migriert');
                }
                else {
                    $this->addReport('data', 'MailJob.RecipientSpec', 'ok', 'RecipientSpec bereits migriert');
                }
            }
        }
    }

    /**
     * @param string $param
     * @return bool
     */
    private function getConfigFlag($param) {
        $configTable = new SQLtable('config');
        if(!$configTable->exists()) {
            return false;
        }
        $sql = sprintf(
            "SELECT `Value` FROM `%sconfig` WHERE `Parameter` = '%s' LIMIT 1;",
            $GLOBALS['dbprefix'],
            mysqli_real_escape_string($GLOBALS['conn'], $param)
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        $row = $dbr ? mysqli_fetch_array($dbr) : null;
        if(!$row || !isset($row['Value'])) {
            return false;
        }
        return (string)$row['Value'] === '1' || (string)$row['Value'] === 'true';
    }

    /**
     * @param string $param
     * @param bool $value
     * @param string $description
     */
    private function setConfigFlag($param, $value, $description = '') {
        $configTable = new SQLtable('config');
        if(!$configTable->exists()) {
            return;
        }
        $val = $value ? '1' : '0';
        $escParam = mysqli_real_escape_string($GLOBALS['conn'], $param);
        $escDesc = mysqli_real_escape_string($GLOBALS['conn'], $description);
        $sql = sprintf(
            "SELECT `Parameter` FROM `%sconfig` WHERE `Parameter` = '%s' LIMIT 1;",
            $GLOBALS['dbprefix'],
            $escParam
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        $row = $dbr ? mysqli_fetch_array($dbr) : null;
        if($row && isset($row['Parameter'])) {
            $update = sprintf(
                "UPDATE `%sconfig` SET `Value` = '%s'%s WHERE `Parameter` = '%s';",
                $GLOBALS['dbprefix'],
                $val,
                $description !== '' ? ", `Description` = '".$escDesc."'" : '',
                $escParam
            );
            mysqli_query($GLOBALS['conn'], $update);
            return;
        }
        $insert = sprintf(
            "INSERT INTO `%sconfig` (`Parameter`, `Value`, `Type`, `Description`) VALUES ('%s', '%s', 'bool', '%s');",
            $GLOBALS['dbprefix'],
            $escParam,
            $val,
            $escDesc !== '' ? $escDesc : $escParam
        );
        mysqli_query($GLOBALS['conn'], $insert);
    }

    /**
     * Drop columns / tables that are no longer in DBconfig (or known obsolete leftovers).
     *
     * @param bool $apply
     */
    private function pruneObsoleteSchema($apply) {
        foreach($this->schema as $tableName => $columns) {
            $SQL = new SQLtable($tableName);
            if(!$SQL->exists()) {
                continue;
            }
            $defined = array_keys($columns);
            foreach($SQL->listColumns() as $columnName) {
                if(in_array($columnName, $defined, true)) {
                    continue;
                }
                $target = $tableName.'.'.$columnName;
                if(!$apply) {
                    $this->addReport('column', $target, 'obsolete', 'Spalte nicht mehr in DBconfig');
                    continue;
                }
                if($SQL->dropColumn($columnName)) {
                    $this->addReport('column', $target, 'removed', 'Veraltete Spalte entfernt');
                }
                else {
                    $this->addReport(
                        'column',
                        $target,
                        'error',
                        'Veraltete Spalte konnte nicht entfernt werden',
                        $SQL->getLastError()
                    );
                }
            }
        }

        // Leftover tables after inventory migration / MELD-134 Gastmusiker.
        foreach(array('Instruments', 'Loans', 'Aushilfen', 'AushilfenShift') as $obsoleteTable) {
            $SQL = new SQLtable($obsoleteTable);
            if(!$SQL->exists()) {
                continue;
            }
            if(!$apply) {
                $this->addReport('table', $obsoleteTable, 'obsolete', 'Tabelle nicht mehr benötigt');
                continue;
            }
            if($SQL->dropTable()) {
                $this->addReport('table', $obsoleteTable, 'removed', 'Veraltete Tabelle entfernt');
            }
            else {
                $this->addReport(
                    'table',
                    $obsoleteTable,
                    'error',
                    'Veraltete Tabelle konnte nicht entfernt werden',
                    $SQL->getLastError()
                );
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

        // MELD-84: Termine-Seite entfällt; verwaiste Schalter entfernen
        $obsoleteParams = array('entriesMainPage', 'showAppmntPage');
        foreach($obsoleteParams as $param) {
            $sql = sprintf(
                "SELECT `Parameter` FROM `%sconfig` WHERE `Parameter` = '%s' LIMIT 1;",
                $GLOBALS['dbprefix'],
                mysqli_real_escape_string($GLOBALS['conn'], $param)
            );
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
            $row = $dbr ? mysqli_fetch_array($dbr) : null;
            $exists = $row && isset($row['Parameter']) && $row['Parameter'] === $param;
            if(!$exists) {
                continue;
            }
            if(!$apply) {
                $this->addReport('config', $param, 'obsolete', 'Config-Parameter veraltet');
                continue;
            }
            $delete = sprintf(
                "DELETE FROM `%sconfig` WHERE `Parameter` = '%s' LIMIT 1;",
                $GLOBALS['dbprefix'],
                mysqli_real_escape_string($GLOBALS['conn'], $param)
            );
            if(mysqli_query($GLOBALS['conn'], $delete)) {
                $this->addReport('config', $param, 'removed', 'Veralteter Config-Parameter entfernt');
            }
            else {
                $this->addReport(
                    'config',
                    $param,
                    'error',
                    'Veralteter Config-Parameter konnte nicht entfernt werden',
                    mysqli_errno($GLOBALS['conn']).': '.mysqli_error($GLOBALS['conn'])
                );
            }
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
     * MailJob/MailOutbox need utf8mb4 for WYSIWYG HTML (TinyMCE) and Unicode.
     */
    private function ensureMailTableUtf8mb4() {
        if(!isset($GLOBALS['conn']) || !isset($GLOBALS['dbprefix'])) {
            return;
        }
        foreach(array('MailJob', 'MailOutbox') as $short) {
            $table = new SQLtable($short);
            if(!$table->exists()) {
                continue;
            }
            $name = $GLOBALS['dbprefix'].$short;
            $check = mysqli_query(
                $GLOBALS['conn'],
                "SELECT `TABLE_COLLATION` AS `c` FROM INFORMATION_SCHEMA.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '"
                .mysqli_real_escape_string($GLOBALS['conn'], $name)."' LIMIT 1"
            );
            $row = $check ? mysqli_fetch_assoc($check) : null;
            $collation = $row && isset($row['c']) ? (string)$row['c'] : '';
            if($collation !== '' && stripos($collation, 'utf8mb4') === 0) {
                $this->addReport('table', $short, 'ok', 'utf8mb4 (Mail/WYSIWYG)');
                continue;
            }
            $ok = mysqli_query(
                $GLOBALS['conn'],
                'ALTER TABLE `'.str_replace('`', '``', $name).'` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
            );
            if($ok) {
                $this->addReport('table', $short, 'fixed', 'Nach utf8mb4 konvertiert (Mail/WYSIWYG)');
            }
            else {
                $this->addReport(
                    'table',
                    $short,
                    'error',
                    'utf8mb4-Konvertierung fehlgeschlagen',
                    mysqli_errno($GLOBALS['conn']).': '.mysqli_error($GLOBALS['conn'])
                );
            }
        }
    }

    /**
     * Convert all schema tables to utf8mb4 (MELD-56). Mail tables already handled above.
     */
    private function ensureAllTablesUtf8mb4() {
        if(!isset($GLOBALS['conn']) || !isset($GLOBALS['dbprefix'])) {
            return;
        }
        foreach(array_keys($this->schema) as $short) {
            $table = new SQLtable($short);
            if(!$table->exists()) {
                continue;
            }
            $name = $GLOBALS['dbprefix'].$short;
            $check = mysqli_query(
                $GLOBALS['conn'],
                "SELECT `TABLE_COLLATION` AS `c` FROM INFORMATION_SCHEMA.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '"
                .mysqli_real_escape_string($GLOBALS['conn'], $name)."' LIMIT 1"
            );
            $row = $check ? mysqli_fetch_assoc($check) : null;
            $collation = $row && isset($row['c']) ? (string)$row['c'] : '';
            if($collation !== '' && stripos($collation, 'utf8mb4') === 0) {
                $this->addReport('table', $short, 'ok', 'utf8mb4');
                continue;
            }
            $ok = mysqli_query(
                $GLOBALS['conn'],
                'ALTER TABLE `'.str_replace('`', '``', $name).'` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
            );
            if($ok) {
                $this->addReport('table', $short, 'fixed', 'Nach utf8mb4 konvertiert (MELD-56)');
            }
            else {
                $this->addReport(
                    'table',
                    $short,
                    'error',
                    'utf8mb4-Konvertierung fehlgeschlagen',
                    mysqli_errno($GLOBALS['conn']).': '.mysqli_error($GLOBALS['conn'])
                );
            }
        }
    }

    /**
     * Decode legacy HTML entities stored in text fields (MELD-56). Idempotent.
     */
    private function migrateHtmlEntitiesInTextFields() {
        if(!isset($GLOBALS['conn']) || !isset($GLOBALS['dbprefix'])) {
            return;
        }
        $targets = array(
            'User' => array('Vorname', 'Nachname'),
            'Termine' => array('Name', 'Beschreibung', 'Ort1', 'Ort2', 'Ort3', 'Ort4', 'defaultFreeText'),
            'Schichten' => array('Name'),
            'vehicle' => array('Name'),
            'Register' => array('Name'),
            'Instrument' => array('Name'),
            'Inventory' => array('Typ', 'Prefix'),
            'Inventories' => array('Description', 'Vendor', 'Model', 'SerialNr', 'Comment'),
        );
        $flags = ENT_QUOTES;
        if(defined('ENT_HTML5')) {
            $flags = ENT_QUOTES | ENT_HTML5;
        }
        $totalFixed = 0;
        foreach($targets as $short => $columns) {
            $table = new SQLtable($short);
            if(!$table->exists()) {
                continue;
            }
            $name = $GLOBALS['dbprefix'].$short;
            $safeName = str_replace('`', '``', $name);
            $whereParts = array();
            foreach($columns as $col) {
                $safeCol = str_replace('`', '``', $col);
                $whereParts[] = '`'.$safeCol.'` LIKE \'%&%;%\'';
            }
            if(!count($whereParts)) {
                continue;
            }
            $sql = 'SELECT `Index`, `'.implode('`, `', array_map(function($c) {
                return str_replace('`', '``', $c);
            }, $columns)).'` FROM `'.$safeName.'` WHERE '.implode(' OR ', $whereParts);
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
            if(!$dbr) {
                $this->addReport(
                    'data',
                    $short,
                    'error',
                    'Entity-Migration Lesen fehlgeschlagen',
                    mysqli_errno($GLOBALS['conn']).': '.mysqli_error($GLOBALS['conn'])
                );
                continue;
            }
            $fixed = 0;
            while($row = mysqli_fetch_assoc($dbr)) {
                $sets = array();
                foreach($columns as $col) {
                    if(!isset($row[$col]) || $row[$col] === null || $row[$col] === '') {
                        continue;
                    }
                    $raw = (string)$row[$col];
                    if(strpos($raw, '&') === false || strpos($raw, ';') === false) {
                        continue;
                    }
                    $decoded = html_entity_decode($raw, $flags, 'UTF-8');
                    if($decoded === $raw) {
                        continue;
                    }
                    $sets[] = '`'.str_replace('`', '``', $col).'` = "'
                        .mysqli_real_escape_string($GLOBALS['conn'], $decoded).'"';
                }
                if(!count($sets)) {
                    continue;
                }
                $upd = 'UPDATE `'.$safeName.'` SET '.implode(', ', $sets)
                    .' WHERE `Index` = '.(int)$row['Index'];
                if(mysqli_query($GLOBALS['conn'], $upd)) {
                    $fixed++;
                }
            }
            if($fixed > 0) {
                $totalFixed += $fixed;
                $this->addReport('data', $short, 'fixed', $fixed.' Zeile(n) HTML-Entities dekodiert');
            }
            else {
                $this->addReport('data', $short, 'ok', 'keine HTML-Entities');
            }
        }
        if($totalFixed > 0) {
            $this->addReport('data', 'HtmlEntities', 'fixed', 'Gesamt '.$totalFixed.' Zeile(n) migriert');
        }
    }

    /**
     * Plain-text summary for CLI.
     */
    public function formatReportText($notableOnly = true) {
        $entries = $notableOnly ? $this->getNotableReport() : $this->report;
        if(!count($entries)) {
            return "OK\tnothing to report\n";
        }
        $lines = array();
        foreach($entries as $entry) {
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
