<?php
/**
 * Shared inventory registration numbers for Inventories and Instruments.
 * Storage remains INT; display uses Inventory.Prefix (e.g. UNI-001, INSTR-42).
 */
class RegNumber
{
    const DEFAULT_INSTR_PREFIX = 'INSTR';
    const DEFAULT_INSTR_LABEL = 'Instrument';
    const CONFIG_MIGRATED = 'regNumberInstrumentsMigrated';

    public static function normalizePrefix($prefix) {
        $prefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)$prefix));
        return $prefix;
    }

    public static function derivePrefixFromLabel($label) {
        $label = trim((string)$label);
        if($label === '') return '';
        // Take leading letters/digits from words, e.g. "Marschtasche" -> MARSCH (first word upper, max 8)
        $clean = self::normalizePrefix($label);
        if($clean === '') return 'TYP';
        return substr($clean, 0, 8);
    }

    /**
     * @param bool $pad true => PREFIX-001, false => PREFIX-42
     */
    public static function format($prefix, $number, $pad = true) {
        $prefix = self::normalizePrefix($prefix);
        if($prefix === '') $prefix = 'X';
        $n = (int)$number;
        if($pad) {
            return sprintf('%s-%03d', $prefix, $n);
        }
        return $prefix.'-'.$n;
    }

    public static function loadType($inventoryTypeId) {
        $inventoryTypeId = (int)$inventoryTypeId;
        if($inventoryTypeId < 1) return null;
        $t = new Inventory;
        $t->load_by_id($inventoryTypeId);
        if(!$t->Index) return null;
        return $t;
    }

    public static function loadInstrType() {
        $sql = sprintf(
            'SELECT `Index` FROM `%sInventory` WHERE `Prefix` = "%s" LIMIT 1;',
            $GLOBALS['dbprefix'],
            mysqli_real_escape_string($GLOBALS['conn'], self::DEFAULT_INSTR_PREFIX)
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if(!$dbr) return null;
        $row = mysqli_fetch_array($dbr);
        if(!$row) return null;
        return self::loadType((int)$row['Index']);
    }

    public static function instrumentPrefix() {
        $t = self::loadInstrType();
        if($t && $t->Prefix) return self::normalizePrefix($t->Prefix);
        return self::DEFAULT_INSTR_PREFIX;
    }

    public static function displayInventory($inventoryTypeId, $number) {
        $t = self::loadType($inventoryTypeId);
        $prefix = ($t && $t->Prefix) ? $t->Prefix : self::derivePrefixFromLabel($t ? $t->Typ : 'TYP');
        return self::format($prefix, $number, true);
    }

    public static function displayInstrument($number) {
        return self::format(self::instrumentPrefix(), $number, false);
    }

    public static function nextForType($inventoryTypeId) {
        $inventoryTypeId = (int)$inventoryTypeId;
        if($inventoryTypeId < 1) return 1;
        $sql = sprintf(
            'SELECT MAX(`RegNumber`) AS `M` FROM `%sInventories` WHERE `Inventory` = %d;',
            $GLOBALS['dbprefix'],
            $inventoryTypeId
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = $dbr ? mysqli_fetch_array($dbr) : null;
        $max = ($row && $row['M'] !== null) ? (int)$row['M'] : 0;
        return $max + 1;
    }

    public static function nextForInstruments() {
        $type = self::loadInstrType();
        if(!$type || !$type->Index) return 1;
        $sql = sprintf(
            'SELECT MAX(`RegNumber`) AS `M` FROM `%sInventories` WHERE `Inventory` = %d;',
            $GLOBALS['dbprefix'],
            (int)$type->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = $dbr ? mysqli_fetch_array($dbr) : null;
        $max = ($row && $row['M'] !== null) ? (int)$row['M'] : 0;
        return $max + 1;
    }

    /**
     * Map of inventory type Index => next RegNumber (includes INSTR series types).
     */
    public static function nextMapForInventoryTypes() {
        $map = array();
        $sql = sprintf(
            'SELECT `Index` FROM `%sInventory` ORDER BY `Sortierung`;',
            $GLOBALS['dbprefix']
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if(!$dbr) return $map;
        while($row = mysqli_fetch_array($dbr)) {
            $id = (int)$row['Index'];
            $map[$id] = self::nextForType($id);
        }
        return $map;
    }

    public static function ensureInstrType() {
        $existing = self::loadInstrType();
        if($existing) {
            return array('status' => 'ok', 'message' => 'INSTR-Typ vorhanden', 'id' => (int)$existing->Index);
        }
        $sql = sprintf(
            'INSERT INTO `%sInventory` (`Typ`, `Prefix`, `Protected`, `Sortierung`) VALUES ("%s", "%s", 1, 0);',
            $GLOBALS['dbprefix'],
            mysqli_real_escape_string($GLOBALS['conn'], self::DEFAULT_INSTR_LABEL),
            mysqli_real_escape_string($GLOBALS['conn'], self::DEFAULT_INSTR_PREFIX)
        );
        $ok = mysqli_query($GLOBALS['conn'], $sql);
        if(!$ok) {
            return array(
                'status' => 'error',
                'message' => 'INSTR-Typ konnte nicht angelegt werden',
                'detail' => mysqli_errno($GLOBALS['conn']).': '.mysqli_error($GLOBALS['conn'])
            );
        }
        return array('status' => 'created', 'message' => 'INSTR-Typ angelegt', 'id' => (int)mysqli_insert_id($GLOBALS['conn']));
    }

    public static function migrateInventoryPrefixes() {
        $updated = 0;
        $sql = sprintf(
            'SELECT `Index`, `Typ`, `Prefix` FROM `%sInventory`;',
            $GLOBALS['dbprefix']
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if(!$dbr) {
            return array('status' => 'error', 'message' => 'Inventory nicht lesbar', 'updated' => 0);
        }
        while($row = mysqli_fetch_array($dbr)) {
            if($row['Prefix'] !== null && trim($row['Prefix']) !== '') continue;
            $prefix = self::derivePrefixFromLabel($row['Typ']);
            $upd = sprintf(
                'UPDATE `%sInventory` SET `Prefix` = "%s" WHERE `Index` = %d;',
                $GLOBALS['dbprefix'],
                mysqli_real_escape_string($GLOBALS['conn'], $prefix),
                (int)$row['Index']
            );
            if(mysqli_query($GLOBALS['conn'], $upd)) $updated++;
        }
        return array(
            'status' => $updated > 0 ? 'created' : 'ok',
            'message' => $updated > 0 ? "$updated Prefix(e) gesetzt" : 'Keine leeren Prefixes',
            'updated' => $updated
        );
    }

    /**
     * Migrate legacy Instruments (+ Loans) into Inventories (+ InventoriesLoans), then DROP old tables.
     * Idempotent: if Instruments table is already gone, reports ok.
     */
    public static function migrateInstruments() {
        $instrTable = new SQLtable('Instruments');
        if(!$instrTable->exists()) {
            return array(
                'status' => 'ok',
                'message' => 'Instruments-Tabelle bereits migriert/entfernt',
                'count' => 0,
                'dropped' => false
            );
        }

        $ensured = self::ensureInstrType();
        if(isset($ensured['status']) && $ensured['status'] === 'error') {
            return array('status' => 'error', 'message' => $ensured['message'], 'detail' => isset($ensured['detail']) ? $ensured['detail'] : null);
        }
        $type = self::loadInstrType();
        if(!$type || !$type->Index) {
            return array('status' => 'error', 'message' => 'INSTR-Typ fehlt, Migration abgebrochen');
        }
        $typeId = (int)$type->Index;

        $idMap = array(); // old Instruments.Index => new Inventories.Index
        $sql = sprintf('SELECT * FROM `%sInstruments` ORDER BY `Index`;', $GLOBALS['dbprefix']);
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if(!$dbr) {
            return array(
                'status' => 'error',
                'message' => 'Instruments nicht lesbar',
                'detail' => mysqli_errno($GLOBALS['conn']).': '.mysqli_error($GLOBALS['conn'])
            );
        }

        $copied = 0;
        while($row = mysqli_fetch_array($dbr, MYSQLI_ASSOC)) {
            $oldId = (int)$row['Index'];
            $desc = trim(
                (isset($row['Vendor']) ? $row['Vendor'] : '').
                ((isset($row['Model']) && $row['Model'] !== '') ? ' '.$row['Model'] : '')
            );
            $insert = sprintf(
                'INSERT INTO `%sInventories` (`RegNumber`, `Inventory`, `Instrument`, `Description`, `Vendor`, `Model`, `SerialNr`, `PurchaseDate`, `PurchasePrize`, `Owner`, `Insurance`, `Comment`) VALUES (%s, %d, %s, "%s", "%s", "%s", "%s", %s, "%s", %s, %d, "%s");',
                $GLOBALS['dbprefix'],
                $row['RegNumber'] === null || $row['RegNumber'] === '' ? 'NULL' : (int)$row['RegNumber'],
                $typeId,
                $row['Instrument'] === null || $row['Instrument'] === '' ? 'NULL' : (int)$row['Instrument'],
                mysqli_real_escape_string($GLOBALS['conn'], $desc),
                mysqli_real_escape_string($GLOBALS['conn'], (string)$row['Vendor']),
                mysqli_real_escape_string($GLOBALS['conn'], (string)$row['Model']),
                mysqli_real_escape_string($GLOBALS['conn'], (string)$row['SerialNr']),
                mkNULLstr(isset($row['PurchaseDate']) ? $row['PurchaseDate'] : null),
                mysqli_real_escape_string($GLOBALS['conn'], (string)mkEmpty($row['PurchasePrize'])),
                $row['Owner'] === null || $row['Owner'] === '' ? 'NULL' : (int)$row['Owner'],
                (int)$row['Insurance'],
                mysqli_real_escape_string($GLOBALS['conn'], (string)$row['Comment'])
            );
            if(!mysqli_query($GLOBALS['conn'], $insert)) {
                return array(
                    'status' => 'error',
                    'message' => "Instrument $oldId konnte nicht übernommen werden",
                    'detail' => mysqli_errno($GLOBALS['conn']).': '.mysqli_error($GLOBALS['conn']),
                    'copied' => $copied
                );
            }
            $idMap[$oldId] = (int)mysqli_insert_id($GLOBALS['conn']);
            $copied++;
        }

        $loansCopied = 0;
        $loansTable = new SQLtable('Loans');
        if($loansTable->exists()) {
            $sql = sprintf('SELECT * FROM `%sLoans` ORDER BY `Index`;', $GLOBALS['dbprefix']);
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
            if($dbr) {
                while($row = mysqli_fetch_array($dbr, MYSQLI_ASSOC)) {
                    $oldInstr = (int)$row['Instrument'];
                    if(!isset($idMap[$oldInstr])) continue;
                    $ins = sprintf(
                        'INSERT INTO `%sInventoriesLoans` (`User`, `Inventory`, `StartDate`, `EndDate`, `ContractFile`) VALUES (%d, %d, %s, %s, "%s");',
                        $GLOBALS['dbprefix'],
                        (int)$row['User'],
                        $idMap[$oldInstr],
                        mkNULLstr(isset($row['StartDate']) ? $row['StartDate'] : null),
                        mkNULLstr(isset($row['EndDate']) ? $row['EndDate'] : null),
                        mysqli_real_escape_string($GLOBALS['conn'], (string)$row['ContractFile'])
                    );
                    if(mysqli_query($GLOBALS['conn'], $ins)) $loansCopied++;
                }
            }
        }

        // Drop legacy tables only after successful copy
        $dropOk = true;
        $detail = '';
        if($loansTable->exists()) {
            $drop = sprintf('DROP TABLE `%sLoans`;', $GLOBALS['dbprefix']);
            if(!mysqli_query($GLOBALS['conn'], $drop)) {
                $dropOk = false;
                $detail = mysqli_errno($GLOBALS['conn']).': '.mysqli_error($GLOBALS['conn']);
            }
        }
        $drop = sprintf('DROP TABLE `%sInstruments`;', $GLOBALS['dbprefix']);
        if(!mysqli_query($GLOBALS['conn'], $drop)) {
            $dropOk = false;
            $detail = mysqli_errno($GLOBALS['conn']).': '.mysqli_error($GLOBALS['conn']);
        }

        if(!$dropOk) {
            return array(
                'status' => 'error',
                'message' => "Daten kopiert ($copied Instrumente, $loansCopied Ausleihen), aber DROP fehlgeschlagen",
                'detail' => $detail,
                'count' => $copied,
                'loans' => $loansCopied,
                'dropped' => false
            );
        }

        // Mark migrated in config (optional bookkeeping)
        $check = sprintf(
            "SELECT `Parameter` FROM `%sconfig` WHERE `Parameter` = '%s' LIMIT 1;",
            $GLOBALS['dbprefix'],
            self::CONFIG_MIGRATED
        );
        $dbr2 = mysqli_query($GLOBALS['conn'], $check);
        $r2 = $dbr2 ? mysqli_fetch_array($dbr2) : null;
        if(!$r2) {
            $ins = sprintf(
                "INSERT INTO `%sconfig` (`Parameter`, `Value`, `Type`, `Description`) VALUES ('%s', '1', 'bool', 'Instruments nach Inventories migriert, alte Tabelle entfernt');",
                $GLOBALS['dbprefix'],
                self::CONFIG_MIGRATED
            );
            mysqli_query($GLOBALS['conn'], $ins);
        }
        else {
            $upd = sprintf(
                "UPDATE `%sconfig` SET `Value` = '1', `Description` = 'Instruments nach Inventories migriert, alte Tabelle entfernt' WHERE `Parameter` = '%s';",
                $GLOBALS['dbprefix'],
                self::CONFIG_MIGRATED
            );
            mysqli_query($GLOBALS['conn'], $upd);
        }

        return array(
            'status' => 'created',
            'message' => sprintf(
                '%d Instrument(e) und %d Ausleihe(n) nach Inventories übernommen; Instruments/Loans gelöscht',
                $copied,
                $loansCopied
            ),
            'count' => $copied,
            'loans' => $loansCopied,
            'dropped' => true
        );
    }
}
?>
