<?php
class database
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
    public function generate() {}
    public function delete() {}
    public function update() {}
};
?>