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
    public function create() {
        if($this->exists()) return 1;
        $sql = sprintf("CREATE TABLE `%s` ( `Index` INT NOT NULL AUTO_INCREMENT , PRIMARY KEY (`Index`)) ENGINE = InnoDB;",
        $this->Name
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if($this->exists()) return 0;
        else return -1;
    }
    public function createString() {
        switch($this->create()) {
        case 0:
            echo "TABLE '$this->Name' created successfully.";
            break;
        case 1:
            echo "TABLE '$this->Name' already exists.";
            break;
        case -1:
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
    public function createColumn($columnName, $type, $null) {
        if($this->columnExistS($columnName)) return 1;
        $sql = sprintf("ALTER TABLE `%s` ADD `%s` %s %s AFTER `Index`;",
        $this->Name,
        $columnName,
        $type,
        $null
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if($this->exists()) return 0;
        else return -1;
    }
    public function createColumnString($columnName, $type, $null) {
        switch($this->createColumn($columnName, $type, $null)) {
        case 0:
            echo "TABLE '$columnName' created successfully.";
            break;
        case 1:
            echo "TABLE '$columnName' already exists.";
            break;
        case -1:
            echo "TABLE '$columnName' could not be created.";
            break;
        default:
            break;
        }
    }
    public function deleteColumn($columnName) {}
};
?>