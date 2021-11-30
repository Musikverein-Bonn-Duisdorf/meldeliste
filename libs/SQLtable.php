<?php
class SQLtable
{
    private $_data = array('Name' => null, 'struct' => null);
    public function getStruct() {
        if(!$this->Name) return;
        $sql = sprintf("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '%s%s';",
        $GLOBALS['dbprefix'],
        $this->Name
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        while($row = mysqli_fetch_array($dbr)) {}
    }
    public function exists() {
        $sql = sprintf("SELECT object_id FROM sys.tables WHERE name = '%s%s';",
        $GLOBALS['dbprefix'],
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
        if($this->exists()) return;
        $sql = sprintf("CREATE TABLE `%s%s` ( `Index` INT NOT NULL AUTO_INCREMENT , PRIMARY KEY (`Index`)) ENGINE = InnoDB;",
        $GLOBALS['dbprefix'],
        $this->Name
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();        
    }
    public function delete() {}
    public function columnExists($columnName) {
        
    }
    public function addColumn($columnName, $type, $null) {
        if($this->columnExistS($columnName)) return;
        $sql = sprintf("ALTER TABLE `%s%s` ADD `%s` %s %s AFTER `Index`;",
        $GLOBALS['dbprefix'],
        $this->Name,
        $columnName,
        $type,
        $null
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();                
    }
    public function deleteColumn($columnName) {}
};
?>