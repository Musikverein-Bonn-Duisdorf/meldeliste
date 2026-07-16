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
        $sql = sprintf(
            'SELECT MAX(`RegNumber`) AS `M` FROM `%sInstruments`;',
            $GLOBALS['dbprefix']
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = $dbr ? mysqli_fetch_array($dbr) : null;
        $max = ($row && $row['M'] !== null) ? (int)$row['M'] : 0;
        return $max + 1;
    }

    /**
     * Map of inventory type Index => next RegNumber (excludes INSTR series types for item forms).
     */
    public static function nextMapForInventoryTypes() {
        $map = array();
        $sql = sprintf(
            'SELECT `Index` FROM `%sInventory` WHERE `Prefix` IS NULL OR `Prefix` != "%s" ORDER BY `Sortierung`;',
            $GLOBALS['dbprefix'],
            mysqli_real_escape_string($GLOBALS['conn'], self::DEFAULT_INSTR_PREFIX)
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
     * Port existing instrument RegNumbers into INSTR series (display mapping; INT unchanged).
     */
    public static function migrateInstruments() {
        self::ensureInstrType();
        $sql = sprintf(
            'SELECT COUNT(`Index`) AS `CNT`, MAX(`RegNumber`) AS `M` FROM `%sInstruments` WHERE `RegNumber` IS NOT NULL AND `RegNumber` > 0;',
            $GLOBALS['dbprefix']
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        $row = $dbr ? mysqli_fetch_array($dbr) : null;
        $count = $row ? (int)$row['CNT'] : 0;
        $max = ($row && $row['M'] !== null) ? (int)$row['M'] : 0;

        $already = false;
        if(isset($GLOBALS['optionsDB'][self::CONFIG_MIGRATED]) && $GLOBALS['optionsDB'][self::CONFIG_MIGRATED]) {
            $already = true;
        }
        else {
            // Persist flag in config if table exists
            $check = sprintf(
                "SELECT `Parameter` FROM `%sconfig` WHERE `Parameter` = '%s' LIMIT 1;",
                $GLOBALS['dbprefix'],
                self::CONFIG_MIGRATED
            );
            $dbr2 = mysqli_query($GLOBALS['conn'], $check);
            $r2 = $dbr2 ? mysqli_fetch_array($dbr2) : null;
            if($r2) {
                $already = true;
            }
            else {
                $ins = sprintf(
                    "INSERT INTO `%sconfig` (`Parameter`, `Value`, `Type`, `Description`) VALUES ('%s', '1', 'bool', 'Instrument-RegNumbers dem INSTR-Kreis zugeordnet');",
                    $GLOBALS['dbprefix'],
                    self::CONFIG_MIGRATED
                );
                mysqli_query($GLOBALS['conn'], $ins);
            }
        }

        $prefix = self::instrumentPrefix();
        return array(
            'status' => $already ? 'ok' : 'created',
            'message' => sprintf(
                '%d Instrument(e) im Kreis %s (max %d)%s',
                $count,
                $prefix,
                $max,
                $already ? ' — bereits migriert' : ' — Migration markiert'
            ),
            'count' => $count,
            'max' => $max
        );
    }
}
?>
