<?php
class SQLtable
{
    private $_data = array('Name' => null, 'struct' => null);
    
    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'Name':
            return $this->_data[$key];
            break;
        default:
            break;
        }
    }
    public function __set($key, $val) {
        switch($key) {
	    case 'Index':
	    case 'Name':
            $this->_data[$key] = htmlentities(trim($val));
            break;
        default:
            $this->_data[$key] = $val;
            break;
        }	
    }
    
    public function __construct($tableName) {
        $this->Name = $GLOBALS['dbprefix'].$tableName;
    }
    public function getStruct() {
        if(!$this->Name) return;
        $sql = sprintf("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '%s';",
        $this->Name
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        while($row = mysqli_fetch_array($dbr)) {}
    }
    public function exists() {
        $sql = sprintf("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_NAME = '%s';",
        $this->Name
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        while($row = mysqli_fetch_array($dbr)) {
            return true;
        }
        return false;
    }

    public function getColumnSetting($columnName) {
        $sql = sprintf("SELECT `TABLE_SCHEMA`, `COLUMN_NAME` AS `ColumnName`, `COLUMN_DEFAULT` AS `Default`, `DATA_TYPE` AS `Type`, `IS_NULLABLE` AS `Null`, `COLLATION_NAME` AS `Collation`, `EXTRA` AS `Extra` FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '%s' AND `COLUMN_NAME` = '%s';",
        $this->Name,
        $columnName
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        while($row = mysqli_fetch_array($dbr)) {
            return $row;
        }
        return false;

        // SELECT `TABLE_SCHEMA` AS `Table`, `COLUMN_NAME` AS `ColumnName`, `COLUMN_DEFAULT` AS `Default`, `DATA_TYPE` AS `Type`, `IS_NULLABLE` AS `Null`, `COLLATION_NAME` AS `Collation`, `EXTRA` AS `Extra` FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'meldeliste_User' AND `COLUMN_NAME` = "Vorname" AND `TABLE_SCHEMA` = "MVD";
    }

    public function setColumnSetting($columnName, $setting, $value) {
        $sql = "";
        if($value == "CURRENT_TIMESTAMP" && $setting == "Default") {
            // ALTER TABLE `meldeliste_UserTest` CHANGE `Joined` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;
            
            $sql = sprintf("ALTER TABLE `%s` CHANGE `%s` `%s` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;",
            $this->Name,
            $columnName,
            $columnName
            );
        }
        elseif($setting == "Collation") {
            // ALTER TABLE `meldeliste_Instruments` CHANGE `Vendor` `Vendor` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL;
            $sql = sprintf("ALTER TABLE `%s` CHANGE `%s` `%s` TEXT CHARACTER SET latin1 COLLATE %s NOT NULL;",
            $this->Name,
            $columnName,
            $columnName,
            $value
            );
        }
        else {
            // ALTER TABLE `meldeliste_UserTest` ALTER `getMail` SET Default '1';

            $sql = sprintf("ALTER TABLE `%s` ALTER `%s` SET %s %s;",
            $this->Name,
            $columnName,
            $setting,
            $value
            );
        }
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        return $this->getColumnSetting($columnName);
    }
        
    
    public function create() {
        if($this->exists()) return -1;
        $sql = sprintf("CREATE TABLE `%s` ( `Index` INT NOT NULL AUTO_INCREMENT , PRIMARY KEY (`Index`)) ENGINE = InnoDB;",
        $this->Name
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if($this->exists()) return true;
        else return false;
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
    public function delete() {}
    public function columnExists($columnName) {
        $sql = sprintf("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '%s' AND column_name = '%s'",
        $this->Name,
        $columnName
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        while($row = mysqli_fetch_array($dbr)) {
            return true;
        }
        return false;
    }
    public function createColumn($columnName, $type) {
        if($this->columnExistS($columnName)) return -1;
        $sql = sprintf("ALTER TABLE `%s` ADD `%s` %s;",
        $this->Name,
        $columnName,
        $type
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if($this->columnExists($columnName)) return true;
        else return false;
    }
    public function createColumnString($columnName, $type, $null) {
        switch($this->createColumn($columnName, $type, $null)) {
        case true:
            echo "TABLE '$columnName' created successfully.";
            break;
        case -1:
            echo "TABLE '$columnName' already exists.";
            break;
        case false:
            echo "TABLE '$columnName' could not be created.";
            break;
        default:
            break;
        }
    }
    public function deleteColumn($columnName) {}
};
?>