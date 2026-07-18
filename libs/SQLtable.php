<?php
class SQLtable
{
    private $_data = array('Name' => null, 'struct' => null);
    private $lastError = '';

    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'Name':
            return $this->_data[$key];
        default:
            break;
        }
    }

    public function __set($key, $val) {
        switch($key) {
	    case 'Index':
	    case 'Name':
            $this->_data[$key] = trim((string)$val);
            break;
        default:
            $this->_data[$key] = $val;
            break;
        }
    }

    public function __construct($tableName) {
        $this->Name = $GLOBALS['dbprefix'].$tableName;
    }

    public function getLastError() {
        return $this->lastError;
    }

    private function schemaName() {
        $db = mysqli_query($GLOBALS['conn'], "SELECT DATABASE()");
        if(!$db) return '';
        $row = mysqli_fetch_row($db);
        return $row ? $row[0] : '';
    }

    private function escapeIdent($name) {
        return mysqli_real_escape_string($GLOBALS['conn'], $name);
    }

    private function query($sql) {
        $this->lastError = '';
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if(mysqli_errno($GLOBALS['conn'])) {
            $this->lastError = mysqli_errno($GLOBALS['conn']).": ".mysqli_error($GLOBALS['conn']);
            if(function_exists('sqlerror')) {
                sqlerror();
            }
        }
        return $dbr;
    }

    public function exists() {
        $schema = $this->escapeIdent($this->schemaName());
        $name = $this->escapeIdent($this->Name);
        $sql = sprintf(
            "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '%s' AND TABLE_TYPE = 'BASE TABLE' AND TABLE_NAME = '%s' LIMIT 1;",
            $schema,
            $name
        );
        $dbr = $this->query($sql);
        if(!$dbr) return false;
        return (bool)mysqli_fetch_array($dbr);
    }

    public function columnExists($columnName) {
        $schema = $this->escapeIdent($this->schemaName());
        $name = $this->escapeIdent($this->Name);
        $column = $this->escapeIdent($columnName);
        $sql = sprintf(
            "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '%s' AND TABLE_NAME = '%s' AND COLUMN_NAME = '%s' LIMIT 1;",
            $schema,
            $name,
            $column
        );
        $dbr = $this->query($sql);
        if(!$dbr) return false;
        return (bool)mysqli_fetch_array($dbr);
    }

    public function getColumnSetting($columnName) {
        $schema = $this->escapeIdent($this->schemaName());
        $name = $this->escapeIdent($this->Name);
        $column = $this->escapeIdent($columnName);
        $sql = sprintf(
            "SELECT `COLUMN_NAME` AS `ColumnName`, `COLUMN_DEFAULT` AS `Default`, `DATA_TYPE` AS `Type`, `IS_NULLABLE` AS `Null`, `COLLATION_NAME` AS `Collation`, `EXTRA` AS `Extra` FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '%s' AND TABLE_NAME = '%s' AND COLUMN_NAME = '%s' LIMIT 1;",
            $schema,
            $name,
            $column
        );
        $dbr = $this->query($sql);
        if(!$dbr) return false;
        $row = mysqli_fetch_array($dbr, MYSQLI_ASSOC);
        return $row ? $row : false;
    }

    /**
     * Build a MySQL column definition fragment from DBconfig.json options.
     */
    public function buildColumnDefinition($columnName, $definition) {
        $type = isset($definition['Type']) ? strtolower($definition['Type']) : 'text';
        $null = isset($definition['Null']) && strtoupper($definition['Null']) === 'YES';
        $extra = isset($definition['Extra']) ? strtoupper($definition['Extra']) : '';
        $hasDefault = array_key_exists('Default', $definition);
        $default = $hasDefault ? $definition['Default'] : null;
        $collation = isset($definition['Collation']) ? $definition['Collation'] : null;

        $sqlType = $type;
        if($collation && in_array($type, array('text', 'varchar', 'char', 'mediumtext', 'longtext'), true)) {
            $charset = 'utf8mb4';
            if(stripos($collation, 'utf8mb4') !== false) {
                $charset = 'utf8mb4';
            }
            elseif(stripos($collation, 'utf8') !== false) {
                $charset = 'utf8';
            }
            elseif(stripos($collation, 'latin1') !== false) {
                $charset = 'latin1';
            }
            $sqlType .= ' CHARACTER SET '.$charset.' COLLATE '.$collation;
        }

        $parts = array($sqlType);
        $parts[] = $null ? "NULL" : "NOT NULL";

        if($hasDefault) {
            if($default === null) {
                $parts[] = "DEFAULT NULL";
            }
            elseif(is_string($default) && strtoupper($default) === 'CURRENT_TIMESTAMP') {
                $parts[] = "DEFAULT CURRENT_TIMESTAMP";
            }
            elseif(is_numeric($default) && !is_string($default)) {
                $parts[] = "DEFAULT ".$default;
            }
            elseif(is_string($default) && is_numeric($default) && in_array($type, array('int', 'tinyint', 'smallint', 'mediumint', 'bigint', 'double', 'float', 'decimal'), true)) {
                $parts[] = "DEFAULT ".$default;
            }
            else {
                $parts[] = "DEFAULT '".mysqli_real_escape_string($GLOBALS['conn'], (string)$default)."'";
            }
        }

        if(strpos($extra, 'AUTO_INCREMENT') !== false) {
            $parts[] = "AUTO_INCREMENT";
        }

        return implode(' ', $parts);
    }

    public function create() {
        if($this->exists()) return -1;
        $sql = sprintf(
            "CREATE TABLE `%s` ( `Index` INT NOT NULL AUTO_INCREMENT , PRIMARY KEY (`Index`)) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
            $this->Name
        );
        $this->query($sql);
        if($this->exists()) return true;
        return false;
    }

    public function createString() {
        switch($this->create()) {
        case true:
            echo "TABLE '$this->Name' created successfully.";
            break;
        case -1:
            echo "TABLE '$this->Name' already exists.";
            break;
        case false:
            echo "TABLE '$this->Name' could not be created.";
            break;
        default:
            break;
        }
    }

    /**
     * @param string $columnName
     * @param string|array $definition Type string or DBconfig column definition array
     * @return bool|int true created, -1 exists, false error
     */
    public function createColumn($columnName, $definition) {
        if($this->columnExists($columnName)) return -1;

        if(is_array($definition)) {
            $defSql = $this->buildColumnDefinition($columnName, $definition);
        }
        else {
            $defSql = $definition;
        }

        $sql = sprintf("ALTER TABLE `%s` ADD `%s` %s;", $this->Name, $columnName, $defSql);
        $this->query($sql);
        if($this->columnExists($columnName)) return true;
        return false;
    }

    public function createColumnString($columnName, $type, $null = null) {
        $definition = $type;
        if(!is_array($type) && $null !== null) {
            $definition = array(
                'Type' => $type,
                'Null' => (strtoupper($null) === 'NULL' || strtoupper($null) === 'YES') ? 'YES' : 'NO'
            );
        }
        switch($this->createColumn($columnName, $definition)) {
        case true:
            echo "COLUMN '$columnName' created successfully.";
            break;
        case -1:
            echo "COLUMN '$columnName' already exists.";
            break;
        case false:
            echo "COLUMN '$columnName' could not be created.";
            break;
        default:
            break;
        }
    }

    /**
     * Modify an existing column to match the desired definition.
     * @return bool
     */
    public function modifyColumn($columnName, $definition) {
        if(!$this->columnExists($columnName)) return false;
        $defSql = $this->buildColumnDefinition($columnName, $definition);
        $sql = sprintf("ALTER TABLE `%s` MODIFY `%s` %s;", $this->Name, $columnName, $defSql);
        $this->query($sql);
        return $this->lastError === '';
    }

    /**
     * Compare current DB settings to desired definition for keys present in $definition.
     * Returns array of mismatched keys => [expected, actual] or empty array if ok.
     */
    public function compareColumn($columnName, $definition) {
        $current = $this->getColumnSetting($columnName);
        if($current === false) {
            return array('_missing' => array(true, false));
        }
        $diffs = array();
        foreach($definition as $key => $expected) {
            if($key === 'Type') {
                $actual = isset($current['Type']) ? strtolower($current['Type']) : '';
                $want = strtolower($expected);
                if($actual !== $want) {
                    $diffs[$key] = array($want, $actual);
                }
                continue;
            }
            if($key === 'Null') {
                $actual = isset($current['Null']) ? strtoupper($current['Null']) : 'NO';
                $want = strtoupper($expected);
                if($actual !== $want) {
                    $diffs[$key] = array($want, $actual);
                }
                continue;
            }
            if($key === 'Default') {
                $actual = isset($current['Default']) ? $current['Default'] : null;
                $want = $expected;
                if($this->normalizeDefault($want) !== $this->normalizeDefault($actual)) {
                    $diffs[$key] = array($want, $actual);
                }
                continue;
            }
            if($key === 'Extra') {
                $actual = isset($current['Extra']) ? strtoupper($current['Extra']) : '';
                $want = strtoupper($expected);
                // DEFAULT_GENERATED is MySQL 8 noise for CURRENT_TIMESTAMP defaults
                $actualNorm = trim(str_replace('DEFAULT_GENERATED', '', $actual));
                $wantNorm = trim(str_replace('DEFAULT_GENERATED', '', $want));
                if($wantNorm !== '' && strpos($actualNorm, $wantNorm) === false && $actualNorm !== $wantNorm) {
                    $diffs[$key] = array($want, $actual);
                }
                continue;
            }
            if($key === 'Collation') {
                $actual = isset($current['Collation']) ? $current['Collation'] : null;
                if($actual === null) continue; // non-string types have no collation
                if(strtoupper((string)$actual) !== strtoupper((string)$expected)) {
                    $diffs[$key] = array($expected, $actual);
                }
                continue;
            }
        }
        return $diffs;
    }

    private function normalizeDefault($value) {
        if($value === null) return 'NULL';
        if(is_string($value) && strtoupper($value) === 'CURRENT_TIMESTAMP') return 'CURRENT_TIMESTAMP';
        return strtoupper((string)$value);
    }

    /** @deprecated use modifyColumn */
    public function setColumnSetting($columnName, $setting, $value) {
        $current = $this->getColumnSetting($columnName);
        if($current === false) return false;
        $definition = array(
            'Type' => $current['Type'],
            'Null' => $current['Null'],
        );
        if($current['Default'] !== null) $definition['Default'] = $current['Default'];
        if($current['Collation']) $definition['Collation'] = $current['Collation'];
        if($current['Extra']) $definition['Extra'] = $current['Extra'];
        $definition[$setting] = $value;
        if($this->modifyColumn($columnName, $definition)) {
            return $this->getColumnSetting($columnName);
        }
        return false;
    }
};
?>
