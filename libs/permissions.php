<?php
class Permissions
{
    private $_data = array(
        'Index' => null,
        'User' => null,
        'perm_showHiddenAppmnts' => null,
        'perm_showUsers' => null,
        'perm_editUsers' => null,
        'perm_editAppmnts' => null,
        'perm_showLog' => null,
        'perm_showInstruments' => null,
        'perm_editInstruments' => null,
        'perm_sendEmail' => null,
        'perm_showResponse' => null,
        'perm_editResponse' => null,
        'perm_editConfig' => null
    );
    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'User':
	    case 'perm_showHiddenAppmnts':
	    case 'perm_showUsers':
	    case 'perm_editUsers':
	    case 'perm_editAppmnts':
	    case 'perm_showLog':
	    case 'perm_showInstruments':
	    case 'perm_editInstruments':
	    case 'perm_sendEmail':
	    case 'perm_showResponse':
	    case 'perm_editResponse':
	    case 'perm_editConfig':
            return $this->_data[$key];
            break;
        default:
            break;
        }
    }
    public function __set($key, $val) {
        switch($key) {
	    case 'Index':
	    case 'User':
	    case 'perm_showHiddenAppmnts':
	    case 'perm_showUsers':
	    case 'perm_editUsers':
	    case 'perm_editAppmnts':
	    case 'perm_showLog':
	    case 'perm_showInstruments':
	    case 'perm_editInstruments':
	    case 'perm_sendEmail':
	    case 'perm_showResponse':
	    case 'perm_editResponse':
	    case 'perm_editConfig':
            $this->_data[$key] = (int)$val;
            break;
        default:
            break;
        }	
    }
    public function save() {
        if(!$this->is_valid()) return false;
        if($this->Index > 0) {
            $this->update();
            $logentry = new Log;
            $logentry->DBupdate($this->getVars());
        }
        else {
            $this->insert();
            $logentry = new Log;
            $logentry->DBinsert($this->getVars());
        }
    }
    
    public function is_valid() {
        if(!$this->User) return false;
        return true;
    }
    
    protected function insert() {
        $sql = sprintf('INSERT INTO `%sPermissions` (`User`, `perm_showHiddenAppmnts`, `perm_showUsers`, `perm_editUsers`, `perm_editAppmnts`, `perm_showLog`, `perm_showInstruments`, `perm_editInstruments`, `perm_sendEmail`, `perm_showResponse`, `perm_editResponse`, `perm_editConfig`) VALUES ("%d", "%d", "%d", "%d", "%d", "%d", "%d", "%d", "%d", "%d", "%d", "%d");',
                       $GLOBALS['dbprefix'],
                       $this->User,
                       $this->perm_showHiddenAppmnts,
                       $this->perm_showUsers,
                       $this->perm_editUsers,
                       $this->perm_editAppmnts,
                       $this->perm_showLog,
                       $this->perm_showInstruments,
                       $this->perm_editInstruments,
                       $this->perm_sendEmail,
                       $this->perm_showResponse,
                       $this->perm_editResponse,
                       $this->perm_editConfig
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }
    
    protected function update() {
        $sql = sprintf('UPDATE `%sPermissions` SET `User` = "%d", `perm_showHiddenAppmnts` = "%d", `perm_showUsers` = "%d", `perm_editUsers` = "%d", `perm_editAppmnts` = "%d", `perm_showLog` = "%d", `perm_showInstruments` = "%d", `perm_editInstruments` = "%d", `perm_sendEmail` = "%d", `perm_showResponse` = "%d", `perm_editResponse` = "%d", `perm_editConfig` = "%d" WHERE `Index` = "%d";',
                       $GLOBALS['dbprefix'],
                       $this->User,
                       $this->perm_showHiddenAppmnts,
                       $this->perm_showUsers,
                       $this->perm_editUsers,
                       $this->perm_editAppmnts,
                       $this->perm_showLog,
                       $this->perm_showInstruments,
                       $this->perm_editInstruments,
                       $this->perm_sendEmail,
                       $this->perm_showResponse,
                       $this->perm_editResponse,
                       $this->perm_editConfig,
                       $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        return true;
    }
    
    public function delete() {
        if(!$this->Index) return false;
        $logentry = new Log;
        $logentry->DBdelete($this->getVars());
        $sql = sprintf('DELETE FROM `%sPermissions` WHERE `Index` = "%d";',
                       $GLOBALS['dbprefix'],
                       $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = null;
        return true;
    }

    public function fill_from_array($row) {
        foreach($row as $key => $val) {
            $this->_data[$key] = $val;
        }
    }
    public function load_by_id($Index) {
        $Index = (int) $Index;
        $sql = sprintf('SELECT * FROM `%sPermissions` WHERE `Index` = "%d";',
                       $GLOBALS['dbprefix'],
                       $Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = mysqli_fetch_array($dbr);
        if(is_array($row)) {
            $this->fill_from_array($row);
        }
    }
   
    public function load_by_user($Index) {
        $Index = (int) $Index;
        $sql = sprintf('SELECT * FROM `%sPermissions` WHERE `User` = "%d";',
                       $GLOBALS['dbprefix'],
                       $Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = mysqli_fetch_array($dbr);
        if(is_array($row)) {
            $this->fill_from_array($row);
        }
        if($this->User == 0) {
            $this->User = $Index;
            $this->save();
        }
    }

    public function getAdmin() {
        if($this->perm_showHiddenAppmnts) return true;
        if($this->perm_showUsers) return true;
        if($this->perm_editUsers) return true;
        if($this->perm_editAppmnts) return true;
        if($this->perm_showLog) return true;
        if($this->perm_showInstruments) return true;
        if($this->perm_editInstruments) return true;
        if($this->perm_sendEmail) return true;
        if($this->perm_showResponse) return true;
        if($this->perm_editResponse) return true;
        if($this->perm_editConfig) return true;
    }
    
    public function getPermission($perm) {
        switch($perm) {
	    case 'perm_showHiddenAppmnts':
            return $this->perm_showHiddenAppmnts;
	    case 'perm_showUsers':
            return $this->perm_showUsers;
	    case 'perm_editUsers':
            return $this->perm_editUsers;
	    case 'perm_editAppmnts':
            return $this->perm_editAppmnts;
	    case 'perm_showLog':
            return $this->perm_showLog;
	    case 'perm_showInstruments':
            return $this->perm_showInstruments;
	    case 'perm_editInstruments':
            return $this->perm_editInstruments;
	    case 'perm_sendEmail':
            return $this->perm_sendEmail;
	    case 'perm_showResponse':
            return $this->perm_showResponse;
	    case 'perm_editResponse':
            return $this->perm_editResponse;
	    case 'perm_editConfig':
            return $this->perm_editConfig;
        default:
            break;
        }
        return false;
    }
};
?>
