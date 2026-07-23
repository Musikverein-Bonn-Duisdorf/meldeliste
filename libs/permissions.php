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
        'perm_editRegisters' => null,
        'perm_showInventories' => null,
        'perm_editInventories' => null,
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
        if($this->perm_editRegisters != $old->perm_editRegisters) $str.=", perm_editRegisters: ".$old->perm_editRegisters." &rArr; <b>".$this->perm_editRegisters."</b>";
        if($this->perm_showInventories != $old->perm_showInventories) $str.=", perm_showInventories: ".$old->perm_showInventories." &rArr; <b>".$this->perm_showInventories."</b>";
        if($this->perm_editInventories != $old->perm_editInventories) $str.=", perm_editInventories: ".$old->perm_editInventories." &rArr; <b>".$this->perm_editInventories."</b>";
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
        $parts = array();
        $parts[] = sprintf('Permission-ID: %d', (int)$this->Index);
        $parts[] = sprintf('User: (%d) <b>%s</b>', (int)$this->User, $u->getName());
        $labels = self::permissionLabels();
        foreach(self::permissionKeys() as $key) {
            if(!$this->$key) {
                continue;
            }
            $short = isset($labels[$key]['short']) ? $labels[$key]['short'] : $key;
            $parts[] = logPart($short, bool2string($this->$key));
        }
        return implode(', ', $parts);
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
        if((int)$this->User > 0) {
            self::clearEffectiveCache((int)$this->User);
        }
    }
    
    public function is_valid() {
        if(!$this->User) return false;
        return true;
    }
    
    protected function insert() {
        $sql = sprintf('INSERT INTO `%sPermissions` (`User`, `perm_showHiddenAppmnts`, `perm_showUsers`, `perm_editUsers`, `perm_editAppmnts`, `perm_showLog`, `perm_editRegisters`, `perm_showInventories`, `perm_editInventories`, `perm_sendEmail`, `perm_showResponse`, `perm_editResponse`, `perm_editConfig`, `perm_editPermissions`) VALUES ("%d", "%d", "%d", "%d", "%d", "%d", "%d", "%d", "%d", "%d", "%d", "%d", "%d", "%d");',
                       $GLOBALS['dbprefix'],
                       $this->User,
                       $this->perm_showHiddenAppmnts,
                       $this->perm_showUsers,
                       $this->perm_editUsers,
                       $this->perm_editAppmnts,
                       $this->perm_showLog,
                       $this->perm_editRegisters,
                       $this->perm_showInventories,
                       $this->perm_editInventories,
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
        $sql = sprintf('UPDATE `%sPermissions` SET `User` = "%d", `perm_showHiddenAppmnts` = "%d", `perm_showUsers` = "%d", `perm_editUsers` = "%d", `perm_editAppmnts` = "%d", `perm_showLog` = "%d", `perm_editRegisters` = "%d", `perm_showInventories` = "%d", `perm_editInventories` = "%d", `perm_sendEmail` = "%d", `perm_showResponse` = "%d", `perm_editResponse` = "%d", `perm_editConfig` = "%d", `perm_editPermissions` = "%d" WHERE `Index` = "%d";',
                       $GLOBALS['dbprefix'],
                       $this->User,
                       $this->perm_showHiddenAppmnts,
                       $this->perm_showUsers,
                       $this->perm_editUsers,
                       $this->perm_editAppmnts,
                       $this->perm_showLog,
                       $this->perm_editRegisters,
                       $this->perm_showInventories,
                       $this->perm_editInventories,
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

    /** @var array<int,Permissions> */
    private static $effectiveCache = array();

    /**
     * Drop request-level effective-permission cache (after personal or group changes).
     * @param int|null $userId null = clear all
     */
    public static function clearEffectiveCache($userId = null) {
        if($userId === null) {
            self::$effectiveCache = array();
            return;
        }
        unset(self::$effectiveCache[(int)$userId]);
    }

    /**
     * Personal Permissions row OR-merged with Group PermissionSpec for members.
     * @param int $userId
     * @return Permissions
     */
    public static function loadEffectiveByUser($userId) {
        $userId = (int)$userId;
        if(isset(self::$effectiveCache[$userId])) {
            return self::$effectiveCache[$userId];
        }
        $p = new Permissions();
        $p->load_by_user($userId);
        foreach(Group::permissionsGrantedToUser($userId) as $key) {
            if(in_array($key, self::permissionKeys(), true)) {
                $p->$key = 1;
            }
        }
        self::$effectiveCache[$userId] = $p;
        return $p;
    }

    public function getAdmin() {
        // if($this->perm_showHiddenAppmnts) return true;
        if($this->perm_showUsers) return true;
        if($this->perm_editUsers) return true;
        if($this->perm_editAppmnts) return true;
        if($this->perm_showLog) return true;
        if($this->perm_editRegisters) return true;
        if($this->perm_showInventories) return true;
        if($this->perm_editInventories) return true;
        if($this->perm_sendEmail) return true;
        if($this->perm_showResponse) return true;
        if($this->perm_editResponse) return true;
        if($this->perm_editConfig) return true;
        if($this->perm_editPermissions) return true;
    }
    
    public function getPermission($perm) {
        if(!in_array($perm, self::permissionKeys(), true)) {
            return false;
        }
        return (bool)$this->$perm;
    }

    /**
     * @return string[]
     */
    public static function permissionKeys() {
        return array(
            'perm_showHiddenAppmnts',
            'perm_showUsers',
            'perm_editUsers',
            'perm_editAppmnts',
            'perm_showLog',
            'perm_editRegisters',
            'perm_showInventories',
            'perm_editInventories',
            'perm_sendEmail',
            'perm_showResponse',
            'perm_editResponse',
            'perm_editConfig',
            'perm_editPermissions',
        );
    }

    /**
     * @return array<string,array{short:string,label:string}>
     */
    public static function permissionLabels() {
        return array(
            'perm_showHiddenAppmnts' => array('short' => 'Versteckt', 'label' => 'Versteckte Termine anzeigen'),
            'perm_showUsers' => array('short' => 'User', 'label' => 'Benutzer anzeigen'),
            'perm_editUsers' => array('short' => 'User+', 'label' => 'Benutzer bearbeiten'),
            'perm_editAppmnts' => array('short' => 'Termine', 'label' => 'Termine bearbeiten'),
            'perm_showLog' => array('short' => 'Log', 'label' => 'Log anzeigen'),
            'perm_editRegisters' => array('short' => 'Register+', 'label' => 'Register bearbeiten'),
            'perm_showInventories' => array('short' => 'Inventar', 'label' => 'Inventar anzeigen'),
            'perm_editInventories' => array('short' => 'Inventar+', 'label' => 'Inventar bearbeiten'),
            'perm_sendEmail' => array('short' => 'Mail', 'label' => 'E-Mails senden'),
            'perm_showResponse' => array('short' => 'Melden', 'label' => 'Rückmeldungen anzeigen'),
            'perm_editResponse' => array('short' => 'Melden+', 'label' => 'Rückmeldungen bearbeiten'),
            'perm_editConfig' => array('short' => 'Config', 'label' => 'Konfiguration bearbeiten'),
            'perm_editPermissions' => array('short' => 'Rechte', 'label' => 'Berechtigungen bearbeiten'),
        );
    }

    /**
     * Logical groups: order + color id for chips/nav/heroes.
     * `color` = Akzentfarbe; Soft/Strong werden daraus abgeleitet (kein Hardcode in CSS).
     * @return array<int,array{id:string,title:string,color:string,keys:string[]}>
     */
    public static function permissionGroups() {
        return array(
            array(
                'id' => 'nutzer',
                'title' => 'Nutzer',
                'color' => '#42A5F5',
                'keys' => array('perm_showUsers', 'perm_editUsers', 'perm_editPermissions'),
            ),
            array(
                'id' => 'termine',
                'title' => 'Termine',
                'color' => '#66BB6A',
                'keys' => array(
                    'perm_showHiddenAppmnts',
                    'perm_editAppmnts',
                    'perm_showResponse',
                    'perm_editResponse',
                ),
            ),
            array(
                'id' => 'register',
                'title' => 'Register',
                'color' => '#FFA726',
                'keys' => array('perm_editRegisters'),
            ),
            array(
                'id' => 'inventar',
                'title' => 'Inventar',
                'color' => '#AB47BC',
                'keys' => array('perm_showInventories', 'perm_editInventories'),
            ),
            array(
                'id' => 'kommunikation',
                'title' => 'Kommunikation',
                'color' => '#26C6DA',
                'keys' => array('perm_sendEmail'),
            ),
            array(
                'id' => 'system',
                'title' => 'System',
                'color' => '#78909C',
                'keys' => array('perm_showLog', 'perm_editConfig'),
            ),
        );
    }

    /**
     * Accent hex for a group id (from permissionGroups).
     * @param string $groupId
     * @return string
     */
    public static function groupColor($groupId) {
        $groupId = (string)$groupId;
        foreach(self::permissionGroups() as $group) {
            if(isset($group['id']) && (string)$group['id'] === $groupId) {
                return isset($group['color']) ? (string)$group['color'] : '#78909C';
            }
        }
        return '#78909C';
    }

    /**
     * Color/group id for a permission key (matches profile chip CSS).
     * @param string $key
     * @return string
     */
    public static function groupIdForPermission($key) {
        $key = (string)$key;
        foreach(self::permissionGroups() as $group) {
            $keys = isset($group['keys']) && is_array($group['keys']) ? $group['keys'] : array();
            if(in_array($key, $keys, true)) {
                return isset($group['id']) ? (string)$group['id'] : 'system';
            }
        }
        return 'system';
    }

    /**
     * Flat catalog in group sort order for chip pickers / modal.
     * @return array<int,array{key:string,label:string,group:string,groupId:string}>
     */
    public static function permissionCatalog() {
        $labels = self::permissionLabels();
        $out = array();
        foreach(self::permissionGroups() as $group) {
            $groupId = isset($group['id']) ? (string)$group['id'] : 'sonst';
            $groupTitle = isset($group['title']) ? (string)$group['title'] : '';
            $keys = isset($group['keys']) && is_array($group['keys']) ? $group['keys'] : array();
            foreach($keys as $key) {
                $meta = isset($labels[$key]) ? $labels[$key] : array('label' => $key);
                $out[] = array(
                    'key' => (string)$key,
                    'label' => (string)$meta['label'],
                    'group' => $groupTitle,
                    'groupId' => $groupId,
                );
            }
        }
        return $out;
    }

    public function hasAnyPermission() {
        foreach(self::permissionKeys() as $key) {
            if($this->$key) {
                return true;
            }
        }
        return false;
    }

    /**
     * Apply permission checkboxes from a user create/edit POST.
     * Requires session `perm_editPermissions`.
     * @param array $posted typically $_POST
     */
    public static function applyPostedForUser($userId, array $posted, $sessionUserId = 0) {
        $userId = (int)$userId;
        if($userId < 1) {
            return false;
        }
        if(!requirePermission('perm_editPermissions')) {
            return false;
        }
        $sessionUserId = (int)$sessionUserId;
        $selected = array();
        if(isset($posted['userPermissions']) && is_array($posted['userPermissions'])) {
            foreach($posted['userPermissions'] as $key) {
                $key = (string)$key;
                if(in_array($key, self::permissionKeys(), true)) {
                    $selected[$key] = true;
                }
            }
        }
        else {
            foreach(self::permissionKeys() as $key) {
                if(!empty($posted[$key])) {
                    $selected[$key] = true;
                }
            }
        }
        $p = new Permissions;
        $p->load_by_user($userId);
        foreach(self::permissionKeys() as $key) {
            $val = !empty($selected[$key]) ? 1 : 0;
            if($sessionUserId === $userId && $key === 'perm_editPermissions' && $val === 0) {
                $val = 1;
            }
            $p->$key = $val;
        }
        $p->save();
        self::clearEffectiveCache($userId);
        if($sessionUserId === $userId) {
            $_SESSION['permissions'] = loadPermissions($userId);
            $_SESSION['admin'] = isAdmin() ? 1 : 0;
        }
        return true;
    }

    public function printShort() {
        $str="";
        $main = new div;
        $main->class="w3-col l6 w3-row";
        $str.=$main->open();

        $labels = self::permissionLabels();
        foreach(self::permissionKeys() as $key) {
            if(!$this->$key) {
                continue;
            }
            $meta = isset($labels[$key]) ? $labels[$key] : array('short' => substr($key, 5), 'label' => $key);
            $div = new div;
            $div->class="w3-col l4 w3-margin-right w3-margin-bottom w3-center ".bool2color(1);
            $div->body='<span title="'.htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8').'"><b>'.htmlspecialchars($meta['short']).'</b></span>';
            $str.=$div->print();
        }

        $str.=$main->close();
        return $str;
    }
};
?>
