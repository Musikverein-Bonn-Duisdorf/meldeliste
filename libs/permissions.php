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
        'perm_editConfig' => null,
        'perm_editPermissions' => null
    );
    public function __get($key) {
        switch($key) {
        default:
            return $this->_data[$key];
            break;
        }
    }
    public function __set($key, $val) {
        switch($key) {
        default:
            $this->_data[$key] = (int)$val;
            break;
        }	
    }

    public function getChanges() {
        $old = new Permissions;
        $old->load_by_id($this->Index);

        $u = new User;
        $u->load_by_id($this->User);

        $str = sprintf("Permission-ID: %d, User: (%d) <b>%s</b>",
        $this->Index,
        $this->User,
        $u->getName()
        );
        if($this->perm_showHiddenAppmnts != $old->perm_showHiddenAppmnts) $str.=", perm_showHiddenAppmnts: ".$old->perm_showHiddenAppmnts." &rArr; <b>".$this->perm_showHiddenAppmnts."</b>";
        if($this->perm_showUsers != $old->perm_showUsers) $str.=", perm_showUsers: ".$old->perm_showUsers." &rArr; <b>".$this->perm_showUsers."</b>";
        if($this->perm_editUsers != $old->perm_editUsers) $str.=", perm_editUsers: ".$old->perm_editUsers." &rArr; <b>".$this->perm_editUsers."</b>";
        if($this->perm_editAppmnts != $old->perm_editAppmnts) $str.=", perm_editAppmnts: ".$old->perm_editAppmnts." &rArr; <b>".$this->perm_editAppmnts."</b>";
        if($this->perm_showLog != $old->perm_showLog) $str.=", perm_showLog: ".$old->perm_showLog." &rArr; <b>".$this->perm_showLog."</b>";
        if($this->perm_showInstruments != $old->perm_showInstruments) $str.=", perm_showInstruments: ".$old->perm_showInstruments." &rArr; <b>".$this->perm_showInstruments."</b>";
        if($this->perm_editInstruments != $old->perm_editInstruments) $str.=", perm_editInstruments: ".$old->perm_editInstruments." &rArr; <b>".$this->perm_editInstruments."</b>";
        if($this->perm_sendEmail != $old->perm_sendEmail) $str.=", perm_sendEmail: ".$old->perm_sendEmail." &rArr; <b>".$this->perm_sendEmail."</b>";
        if($this->perm_showResponse != $old->perm_showResponse) $str.=", perm_showResponse: ".$old->perm_showResponse." &rArr; <b>".$this->perm_showResponse."</b>";
        if($this->perm_editResponse != $old->perm_editResponse) $str.=", perm_editResponse: ".$old->perm_editResponse." &rArr; <b>".$this->perm_editResponse."</b>";
        if($this->perm_editConfig != $old->perm_editConfig) $str.=", perm_editConfig: ".$old->perm_editConfig." &rArr; <b>".$this->perm_editConfig."</b>";
        if($this->perm_editPermissions != $old->perm_editPermissions) $str.=", perm_editPermissions: ".$old->perm_editPermissions." &rArr; <b>".$this->perm_editPermissions."</b>";

        return $str;
    }

    public function getVars() {
        $u = new User;
        $u->load_by_id($this->User);
        return sprintf("Permission-ID: %d, User: (%d) <b>%s</b>, perm_showHiddenAppmnts: <b>%s</b>, perm_showUsers: <b>%s</b>, perm_editUsers: <b>%s</b>, perm_editAppmnts: <b>%s</b>, perm_showLog: <b>%s</b>, perm_showInstruments: <b>%s</b>, perm_editInstruments: <b>%s</b>, perm_sendEmail: <b>%s</b>, perm_showResponse: <b>%s</b>, perm_editResponse: <b>%s</b>, perm_editConfig: <b>%s</b>, perm_editPermissions: <b>%s</b>",
        $this->Index,
        $this->User,
        $u->getName(),
        bool2string($this->perm_showHiddenAppmnts),
        bool2string($this->perm_showUsers),
        bool2string($this->perm_editUsers),
        bool2string($this->perm_editAppmnts),
        bool2string($this->perm_showLog),
        bool2string($this->perm_showInstruments),
        bool2string($this->perm_editInstruments),
        bool2string($this->perm_sendEmail),
        bool2string($this->perm_showResponse),
        bool2string($this->perm_editResponse),
        bool2string($this->perm_editConfig),
        bool2string($this->perm_editPermissions)
        );        
    }
    
    public function save() {
        if(!$this->is_valid()) return false;
        if($this->Index > 0) {
            $logentry = new Log;
            $logentry->DBupdate($this->getChanges());
            $this->update();
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
        $sql = sprintf('INSERT INTO `%sPermissions` (`User`, `perm_showHiddenAppmnts`, `perm_showUsers`, `perm_editUsers`, `perm_editAppmnts`, `perm_showLog`, `perm_showInstruments`, `perm_editInstruments`, `perm_sendEmail`, `perm_showResponse`, `perm_editResponse`, `perm_editConfig`, `perm_editPermissions`) VALUES ("%d", "%d", "%d", "%d", "%d", "%d", "%d", "%d", "%d", "%d", "%d", "%d", "%d");',
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
                       $this->perm_editPermissions
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }
    
    protected function update() {
        $sql = sprintf('UPDATE `%sPermissions` SET `User` = "%d", `perm_showHiddenAppmnts` = "%d", `perm_showUsers` = "%d", `perm_editUsers` = "%d", `perm_editAppmnts` = "%d", `perm_showLog` = "%d", `perm_showInstruments` = "%d", `perm_editInstruments` = "%d", `perm_sendEmail` = "%d", `perm_showResponse` = "%d", `perm_editResponse` = "%d", `perm_editConfig` = "%d", `perm_editPermissions` = "%d" WHERE `Index` = "%d";',
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
                       $this->perm_editPermissions,
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
        // if($this->perm_showHiddenAppmnts) return true;
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
        if($this->perm_editPermissions) return true;
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
	    case 'perm_editPermissions':
            return $this->perm_editPermissions;
        default:
            break;
        }
        return false;
    }

    public function printHeaderLine() {
        $str="";
        $main = new div;
        $main->class="permissions w3-center w3-border-bottom w3-border-black w3-margin-bottom w3-teal w3-padding";
        $str.=$main->open();

        foreach ($this->_data as $key => $value) {
            if($key == "Index") continue;
            $div = new div;
            if($key == "User") {
                $div->body=$key;
            }
            else {
                $div->body=substr($key,5);
            }
            $str.=$div->print();
        }
 
        $str.=$main->close();        
        return $str;
    }
    
    public function printEditLine() {
        $str="";
        $main = new div;
        $main->class="permissions w3-center w3-border-bottom w3-border-black w3-margin-bottom";
        $str.=$main->open();

        $div = new div;
        $u = new User;
        $u->load_by_id($this->User);
        $div->body=$u->getName();
        $str.=$div->print();

        $size = count($this->_data);
        $i = 0;
        foreach ($this->_data as $key => $value) {
            $i++;
            if($key == "Index" || $key == "User") continue;
            if($i == $size/2+1) break;
            $div = new div;
            $div->class=bool2color($value);
            $div->body=bool2string($value);
            $str.=$div->print();
        }
 
        $str.=$main->close();        
        return $str;
    }

    public function printShort() {
        $str="";
        $main = new div;
        $main->class="w3-col l6 w3-row";
        $str.=$main->open();

        $size = count($this->_data);
        $i = 0;
        foreach ($this->_data as $key => $value) {
            $i++;
            if($key == "Index" || $key == "User") continue;
            if($i == $size/2+1) break;
            if($value) {
                $div = new div;
                $div->class="w3-col l4 w3-margin-right w3-margin-bottom w3-center";
                $div->class=bool2color($value);
                $div->body="<b>".substr($key,5)."</b>";
                $str.=$div->print();
            }
        }
 
        $str.=$main->close();        
        return $str;
    }
};
?>
