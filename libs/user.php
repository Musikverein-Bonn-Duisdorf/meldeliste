<?php
class User
{
    private $_data = array('Index' => null, 'Nachname' => null, 'Vorname' => null, 'RefID' => null, 'login' => null, 'Passhash' => null, 'activeLink' => null, 'Mitglied' => null, 'Instrument' => null, 'iName' => null, 'Email' => null, 'Email2' => null, 'Birthday' => null, 'getMail' => null, 'Admin' => null, 'singleUsePW' => null, 'RegisterLead' => null, 'LastLogin' => null, 'Joined' => null, 'Deleted' => null, 'DeletedOn' => null);
    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'Nachname':
	    case 'Vorname':
        case 'RefID':
        case 'login':
	    case 'Passhash':
	    case 'activeLink':
	    case 'Mitglied':
	    case 'Instrument':
	    case 'iName':
	    case 'Email':
	    case 'Email2':
	    case 'Birthday':
	    case 'getMail':
	    case 'Admin':
        case 'singleUsePW':
        case 'RegisterLead':
        case 'LastLogin':
        case 'Joined':
        case 'Deleted':
        case 'DeletedOn':
            return $this->_data[$key];
            break;
        default:
            return $this->_data[$key];
            break;
        }
    }
    public function __set($key, $val) {
        if($val === null) {
            $this->_data[$key] = null;
            return;
        }
        switch($key) {
	    case 'Index':
	    case 'Instrument':
        case 'RefID':
	    case 'Mitglied':
	    case 'getMail':
	    case 'Admin':
	    case 'singleUsePW':
	    case 'RegisterLead':
	    case 'Deleted':
            $this->_data[$key] = (int)$val;
            break;
	    case 'login':
	    case 'iName':
	    case 'Email':
	    case 'Email2':
	    case 'LastLogin':
	    case 'DeletedOn':
	    case 'Birthday':
            if($val !== '' && $val !== null) {
                $this->_data[$key] = trim((string)$val);
            }
            else {
                $this->_data[$key] = "";
            }
            break;
	    case 'Nachname':
	    case 'Vorname':
	    case 'Passhash':
	    case 'activeLink':
        default:
            $this->_data[$key] = $val;
            break;
        }	
    }
    
    public function getChanges() {
        $old = new User;
        $old->load_by_id($this->Index);

        $str = sprintf("User-ID: %d <b>%s %s</b>",
        $this->Index,
        $this->Vorname,
        $this->Nachname
        );
        if($this->Vorname != $old->Vorname) {
            $this->Vorname = trim((string)$this->Vorname);
            $str.=", Vorname: ".$old->Vorname." &rArr; <b>".$this->Vorname."</b>";
        }
        if($this->Nachname != $old->Nachname) {
            $this->Nachname = trim((string)$this->Nachname);
            $str.=", Nachname: ".$old->Nachname." &rArr; <b>".$this->Nachname."</b>";
        }
        if($this->RefID != $old->RefID) $str.=", RefID: ".$old->RefID." &rArr; <b>".$this->RefID."</b>";
        if($this->login != $old->login) $str.=", login: ".$old->login." &rArr; <b>".$this->login."</b>";
        if($this->Passhash != $old->Passhash) $str.=", Passhash: ".$old->Passhash." &rArr; <b>".$this->Passhash."</b>";
        if($this->activeLink != $old->activeLink) $str.=", activeLink: ".$old->activeLink." &rArr; <b>".$this->activeLink."</b>";
        if(boolsDiffer($this->Mitglied, $old->Mitglied)) $str.=", Mitglied: ".bool2string($old->Mitglied)." &rArr; <b>".bool2string($this->Mitglied)."</b>";
        if($this->Email != $old->Email) $str.=", Email: ".$old->Email." &rArr; <b>".$this->Email."</b>";
        if($this->Email2 != $old->Email2) $str.=", Email2: ".$old->Email2." &rArr; <b>".$this->Email2."</b>";
        if($this->Birthday != $old->Birthday) $str.=", Geburtstag: ".germanDate($old->Birthday, true)." &rArr; <b>".germanDate($this->Birthday, true)."</b>";
        if(boolsDiffer($this->getMail, $old->getMail)) $str.=", getMail: ".bool2string($old->getMail)." &rArr; <b>".bool2string($this->getMail)."</b>";
        if(boolsDiffer($this->Admin, $old->Admin)) $str.=", Admin: ".bool2string($old->Admin)." &rArr; <b>".bool2string($this->Admin)."</b>";
        if(boolsDiffer($this->RegisterLead, $old->RegisterLead)) $str.=", RegisterLead: ".bool2string($old->RegisterLead)." &rArr; <b>".bool2string($this->RegisterLead)."</b>";
        if($this->Instrument != $old->Instrument) {
            $newinstr = new Instrument;
            $newinstr->load_by_id($this->Instrument);

            $oldinstr = new Instrument;
            $oldinstr->load_by_id($old->Instrument);
            
            $str.=", Instrument: ".$oldinstr->Name." &rArr; <b>".$newinstr->Name."</b>";
        }

        return $str;
    }
    
    public function getVars() {
        if(!$this->iName && (int)$this->Instrument > 0) {
            $sql = sprintf('SELECT `Name` FROM `%sInstrument` WHERE `Index` = %d;',
            $GLOBALS['dbprefix'],
            (int)$this->Instrument
            );
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
            sqlerror();
            $row = $dbr ? mysqli_fetch_array($dbr) : null;
            if($row && isset($row['Name'])) {
                $this->iName = $row['Name'];
            }
        }
        $parts = array();
        $parts[] = sprintf('User-ID: %d', (int)$this->Index);
        logAppendFilled($parts, 'Vorname', $this->Vorname, (string)$this->Vorname);
        logAppendFilled($parts, 'Nachname', $this->Nachname, (string)$this->Nachname);
        logAppendFilled($parts, 'RefID', $this->RefID, (string)$this->RefID);
        logAppendFilled($parts, 'Login', $this->login, (string)$this->login);
        $parts[] = logPart('Mitglied', bool2string($this->Mitglied));
        logAppendFilled($parts, 'Instrument', $this->iName, (string)$this->iName);
        logAppendFilled($parts, 'Email', $this->Email, (string)$this->Email);
        logAppendFilled($parts, 'Email2', $this->Email2, (string)$this->Email2);
        $bday = germanDate($this->Birthday, true);
        logAppendFilled($parts, 'Geburtstag', $bday, (string)$bday);
        $parts[] = logPart('Mailverteiler', bool2string($this->getMail));
        logAppendTrue($parts, 'Admin', $this->Admin);
        logAppendTrue($parts, 'RegisterLead', $this->RegisterLead);
        logAppendFilled($parts, 'LastLogin', $this->LastLogin, (string)$this->LastLogin);
        return implode(', ', $parts);
    }
    public function getShort() {
        if(strlen($this->Vorname) >=2) {
            $end=2;
            if(substr($this->Vorname,1,1)=="&") {
                $end = strpos($this->Vorname, ";");
            }
            $short1 = substr($this->Vorname,0,$end);
        }
        else {
            $short1 = $this->Vorname;
        }
        if(strlen($this->Nachname) >=2) {
            $narray = explode(" ", $this->Nachname);
            $end=2;
            if(substr($narray[sizeof($narray)-1],1,1)=="&") {
                $end = strpos($narray[sizeof($narray)-1], ";");
            }
            $short2 = substr($narray[sizeof($narray)-1],0,$end);
        }
        else {
            $short2 = $this->Nachname;
        }
        return $short1.$short2;
    }
    public function save() {
        if($this->activeLink == '' || $this->activeLink === null) $this->generateLink();
        if($this->Passhash === null) $this->Passhash = '';
        if($this->login === null) $this->login = '';
        if($this->Email === null) $this->Email = '';
        if($this->Email2 === null) $this->Email2 = '';
        if($this->Admin === null) $this->Admin = 0;
        if($this->RegisterLead === null) $this->RegisterLead = 0;
        if($this->Mitglied === null) $this->Mitglied = 0;
        if($this->getMail === null) $this->getMail = 0;
        if($this->Instrument === null) $this->Instrument = 0;
        if(!$this->is_valid()) return false;
        if($this->Index > 0) {
            $logentry = new Log;
            $logentry->DBupdate($this->getChanges());
            return $this->update();
        }
        $this->Vorname = trim((string)$this->Vorname);
        $this->Nachname = trim((string)$this->Nachname);
        if(!$this->insert()) return false;
        $logentry = new Log;
        $logentry->DBinsert($this->getVars());
        return true;
    }
    public function singleUsePW($val) {
        $sql = sprintf('UPDATE `%sUser` SET `singleUsePW` = %d WHERE `Index` = %d;',
        $GLOBALS['dbprefix'],
        (int)(bool)$val,
        (int)$this->Index
        );
        mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(isset($_SESSION['userid']) && (int)$_SESSION['userid'] === (int)$this->Index) {
            $_SESSION['singleUsePW'] = (bool)$val;
        }
    }
    public function newmail() {
        $mail = new Usermail;
        $mail->source = 'welcome';
        $mail->singleUser($this->Index, $GLOBALS['optionsDB']['newMailSubject'], $GLOBALS['optionsDB']['newMailText']."\n".$GLOBALS['optionsDB']['MailGreetings']);
    }
    public function passwd($password) {
        try {
            if(!(int)$this->Index) {
                $logentry = new Log;
                $logentry->error("Passwort setzen fehlgeschlagen: keine User-ID.");
                return false;
            }
            if($this->login === null || $this->login === '') {
                $logentry = new Log;
                $logentry->error(sprintf(
                    "Passwort setzen fehlgeschlagen: User-ID <b>%d</b> hat keinen Loginname.",
                    (int)$this->Index
                ));
                return false;
            }
            $arbPW = false;
            if($password === null || $password === '') {
                $password = uniqid('', true);
                $arbPW = true;
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            if(!$hash) {
                $logentry = new Log;
                $logentry->error(sprintf(
                    "password_hash fehlgeschlagen | User-ID: <b>%d</b>, Login: <b>%s</b>",
                    (int)$this->Index,
                    htmlspecialchars((string)$this->login)
                ));
                return false;
            }
            $this->Passhash = $hash;
            if($arbPW) {
                $singleUse = 1;
            }
            else {
                $this->generateLink();
                $singleUse = 0;
            }
            $sql = sprintf(
                'UPDATE `%sUser` SET `Passhash` = "%s", `singleUsePW` = %d, `activeLink` = "%s" WHERE `Index` = %d;',
                $GLOBALS['dbprefix'],
                mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Passhash),
                $singleUse,
                mysqli_real_escape_string($GLOBALS['conn'], (string)$this->activeLink),
                (int)$this->Index
            );
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
            sqlerror();
            if(!$dbr) {
                $logentry = new Log;
                $logentry->error(sprintf(
                    "Passwort-SQL fehlgeschlagen | User-ID: <b>%d</b>, Login: <b>%s</b>, MySQL: <b>%s</b>",
                    (int)$this->Index,
                    htmlspecialchars((string)$this->login),
                    htmlspecialchars(mysqli_error($GLOBALS['conn']))
                ));
                return false;
            }
            $checkSql = sprintf(
                'SELECT `Passhash`, `singleUsePW` FROM `%sUser` WHERE `Index` = %d;',
                $GLOBALS['dbprefix'],
                (int)$this->Index
            );
            $check = mysqli_query($GLOBALS['conn'], $checkSql);
            sqlerror();
            $row = $check ? mysqli_fetch_assoc($check) : null;
            if(!$row || !password_verify($password, (string)$row['Passhash'])) {
                $logentry = new Log;
                $logentry->error(sprintf(
                    "Passwort nach Speichern nicht verifizierbar | User-ID: <b>%d</b>, Login: <b>%s</b>",
                    (int)$this->Index,
                    htmlspecialchars((string)$this->login)
                ));
                return false;
            }
            if(isset($_SESSION['userid']) && (int)$_SESSION['userid'] === (int)$this->Index) {
                $_SESSION['singleUsePW'] = (bool)$singleUse;
            }
            $logentry = new Log;
            $logentry->info(sprintf(
                "Passwort gesetzt | User-ID: <b>%d</b>, Login: <b>%s</b>, Einmalpasswort: <b>%s</b>",
                (int)$this->Index,
                htmlspecialchars((string)$this->login),
                $arbPW ? 'ja' : 'nein'
            ));
            try {
                $mail = new Usermail;
                $mail->source = 'passwd';
                if($arbPW) {
                    $mail->singleUser($this->Index, $GLOBALS['optionsDB']['SubjectPW'], "ein neues Passwort wurde erstellt. Beim n&auml;chsten Login wirst du aufgefordert, dieses zu &auml;ndern.\nDu kannst dich nun unter\n\n<a href=\"".$GLOBALS['optionsDB']['WebSiteURL']."\">".$GLOBALS['optionsDB']['WebSiteURL']."</a>\n\neinloggen.\nBenutzername: ".$this->login."\nPasswort: ".$password);
                }
                else {
                    $mail->singleUser($this->Index, $GLOBALS['optionsDB']['SubjectPW'], "dein neues Passwort wurde gespeichert. Damit ist auch der alte Login-Link ungültig. Bitte nutze ab sofort den Link unter dieser Email.\n\nBenutzername: ".$this->login);
                }
            }
            catch(Throwable $e) {
                $logentry = new Log;
                $logentry->error(sprintf(
                    "Passwort gespeichert, aber Mailversand fehlgeschlagen | User-ID: <b>%d</b>, Login: <b>%s</b>, Fehler: <b>%s</b>",
                    (int)$this->Index,
                    htmlspecialchars((string)$this->login),
                    htmlspecialchars($e->getMessage())
                ));
            }
            return true;
        }
        catch(Throwable $e) {
            $logentry = new Log;
            $logentry->error(sprintf(
                "Passwort setzen Exception | User-ID: <b>%d</b>, Fehler: <b>%s</b>",
                (int)$this->Index,
                htmlspecialchars($e->getMessage())
            ));
            return false;
        }
    }
    public function is_valid() {
        if(!$this->Nachname) return false;
        if(!$this->Vorname) return false;
        return true;
    }
    protected function generateLink() {
        $this->activeLink = uniqid();
    }
    public function getLink() {
        return $GLOBALS['optionsDB']['WebSiteURL']."/login.php?alink=".$this->activeLink;
    }
    public function getCalendarLink() {
        return $GLOBALS['optionsDB']['WebSiteURL']."/calendars/MVDcal_".$this->activeLink.".ics";
    }
    protected function insert() {
        $sql = sprintf('INSERT INTO `%sUser` (`Nachname`, `Vorname`, `RefID`, `login`, `Passhash`, `activeLink`, `Mitglied`, `Instrument`, `Email`, `Email2`, `Birthday`, `getMail`, `Admin`, `RegisterLead`) VALUES ("%s", "%s", %s, "%s", "%s", "%s", %d, "%d", "%s", "%s", %s, "%d", "%d", "%d");',
        $GLOBALS['dbprefix'],
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Nachname),
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Vorname),
        mkNULL($this->RefID),
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->login),
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Passhash),
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->activeLink),
        (int)$this->Mitglied,
        (int)$this->Instrument,
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Email),
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Email2),
        mkNULLstr($this->Birthday),
        (int)$this->getMail,
        (int)$this->Admin,
        (int)$this->RegisterLead
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }
    protected function update() {
        $sql = sprintf('UPDATE `%sUser` SET `Nachname` = "%s", `Vorname` = "%s", `RefID` = %s, `login` = "%s", `Passhash` = "%s", `activeLink` = "%s", `Mitglied` = "%d", `Instrument` = "%d", `Email` = "%s", `Email2` = "%s", `Birthday` = %s, `getMail` = "%d", `Admin` = "%d", `RegisterLead` = "%d" WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Nachname),
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Vorname),
        mkNULL($this->RefID),
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->login),
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Passhash),
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->activeLink),
        (int)$this->Mitglied,
        (int)$this->Instrument,
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Email),
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Email2),
        mkNULLstr($this->Birthday),
        (int)$this->getMail,
        (int)$this->Admin,
        (int)$this->RegisterLead,
        (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        return true;
    }
    public function getName() {
        return $this->Vorname." ".$this->Nachname;
    }
    public function getInstrument() {
        if(!$this->Instrument || $this->Instrument == 0) return "";
        $i = new Instrument;
        $i->load_by_id($this->Instrument);
        return $i->Name;
    }
    public function getRegister() {
        if(!$this->Instrument) return false;
        $sql = sprintf('SELECT * FROM `%sInstrument` WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        $this->Instrument
        );        
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = mysqli_fetch_array($dbr);
        if(is_array($row)) {
            return $row['Register'];
        }
        return 0;
    }
    public function getRegisterName() {
        $register = $this->getRegister();
        if($register < 1) return '';
        $sql = sprintf('SELECT * FROM `%sRegister` WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        $register
        );        
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = mysqli_fetch_array($dbr);
        if(is_array($row)) {
            return $row['Name'];
        }
        return 0;
    }
    public function getMeldeQuote() {
        $sql = sprintf('SELECT COUNT(`Index`) AS `CNT` FROM `%sTermine` WHERE `Datum` >= "%s" AND `Datum` <= CURRENT_TIMESTAMP;',
        $GLOBALS['dbprefix'],
        $this->Joined
        );        
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = mysqli_fetch_array($dbr);
        $termine = $row['CNT'];

        $sql = sprintf('SELECT COUNT(`Index`) AS `CNT` FROM `%sMeldungen` INNER JOIN (SELECT `Index` AS `tIndex`, `Datum` FROM `%sTermine`) `%sTermine` ON `tIndex` = `Termin` WHERE `User` = "%d" AND `Datum` >= "%s" AND `Datum` <= CURRENT_TIMESTAMP;',
        $GLOBALS['dbprefix'],
        $GLOBALS['dbprefix'],
        $GLOBALS['dbprefix'],
        $this->Index,
        $this->Joined
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = mysqli_fetch_array($dbr);
        $meldungen = $row['CNT'];
        if($termine > 0) {
            $r = sprintf("%.3f", $meldungen/$termine);
        }
        else {
            $r=0;
        }
        return $r;
    }
    public function delete() {
        if(!$this->Index) return false;
        $sql = sprintf('UPDATE `%sUser` SET `Deleted` = 1, `DeletedOn` = CURRENT_TIMESTAMP, `Vorname` = "gelöschter", `Nachname` = "Benutzer", `Email` = "", `Email2` = "", `login` = "", `Passhash` = "", `getMail` = 0 WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $logentry = new Log;
        $logentry->DBdelete($this->getVars());
        $this->_data['Index'] = null;
        return true;
    }
    public function fill_from_array($row) {
        if(!is_array($row)) return;
        foreach($row as $key => $val) {
            if(is_int($key)) continue;
            $this->__set($key, $val);
        }
    }
    public function load_by_id($Index) {
        $Index = (int) $Index;
        $sql = sprintf('SELECT * FROM `%sUser` WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        $Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = $dbr ? mysqli_fetch_assoc($dbr) : null;
        if(is_array($row)) {
            $this->fill_from_array($row);
        }
        $this->iName = $this->getInstrument();
    }

    public function getLastVisit() {
        $sql = sprintf('SELECT `Datum` FROM `%sMeldungen` INNER JOIN (SELECT `Index` AS `tIndex`, `Datum` FROM `%sTermine` WHERE `DATUM` <= CURRENT_DATE() ORDER BY `Datum` DESC) `%sTermine` ON `Termin` = `tIndex` WHERE `Wert` = "1" AND `User` = "%d" ORDER BY `Datum` DESC LIMIT 1;',
        $GLOBALS['dbprefix'],
        $GLOBALS['dbprefix'],
        $GLOBALS['dbprefix'],
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = mysqli_fetch_array($dbr);
        if($row) {
            return $row['Datum'];
        }
    }

    public function getInventoriesLoans() {
        $sql = sprintf('SELECT `Index` FROM `%sInventoriesLoans` WHERE `User` = %d AND `EndDate` IS NULL;',
        $GLOBALS['dbprefix'],
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $loans = array();
        while($row = mysqli_fetch_array($dbr)) {
            array_push($loans, $row['Index']);
        }
        return $loans;
    }

    public function getInventories() {
        $sql = sprintf(
            'SELECT `Index` FROM `%sInventories` WHERE `Owner` = %d',
            $GLOBALS['dbprefix'],
            $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $inventories = array();
        while($row = mysqli_fetch_array($dbr)) {
            array_push($inventories, $row['Index']);
        }
        return $inventories;
    }

    public function hasInventories() {
        return count($this->getInventoriesLoans()) + count($this->getInventories());
    }
    
    public function getModalHtml($forceEditButton = false) {
        $showUserDetails = requirePermission("perm_showUsers");
        $permissionsHtml = '';
        if($showUserDetails) {
            $p = new Permissions;
            $p->load_by_user($this->Index);
            $permissionsHtml = $p->printShort();
        }
        $registerLeadName = null;
        if($this->RegisterLead) {
            $r = new Register;
            $r->load_by_id($this->getRegister());
            $registerLeadName = $r->Name;
        }
        return render('user/modal', array(
            'user' => $this,
            'showUserDetails' => $showUserDetails,
            'permissionsHtml' => $permissionsHtml,
            'registerLeadName' => $registerLeadName,
            'showEditButton' => ($forceEditButton || requirePermission("perm_editUsers")),
            'returnTo' => pageToReturnUrl(isset($_SESSION['page']) ? $_SESSION['page'] : 'musiker'),
        ));
    }

    public function printTableLine() {
        if($this->Mitglied) {
            echo "<div class=\"w3-row list-row ".$GLOBALS['optionsDB']['HoverEffect']." w3-padding ".$GLOBALS['optionsDB']['colorUserMember']." w3-mobile w3-border-bottom w3-border-black\">\n";
        }
        else {
            echo "<div class=\"w3-row list-row ".$GLOBALS['optionsDB']['HoverEffect']." w3-padding ".$GLOBALS['optionsDB']['colorUserNoMember']." w3-mobile w3-border-bottom w3-border-black\">\n";
        }
        echo "  <div onclick=\"openModal('user', ".$this->Index.")\" class=\"w3-col l3 m6 s12 w3-container list-primary\"><b>".$this->Vorname." ".$this->Nachname."</b></div>\n";
        echo "  <div class=\"w3-col l2 m6 s12 w3-container list-secondary\">".$this->iName."</div>\n";
        echo "  <div class=\"w3-col l3 m12 s12 w3-container list-secondary\"><a href=\"mailto:".$this->Email."\">".$this->Email."</a></div>\n";
        echo "  <div class=\"w3-col l2 m6 s12 w3-container list-meta\">".germanDate($this->LastLogin, 1)."</div>\n";
        echo "  <div class=\"w3-col l2 m6 s12 w3-container list-meta\">".germanDate($this->getLastVisit(), 1)."</div>\n";
        echo "</div>\n";
    }

    public function printUserTableLine() {
        $main = new div;
        $main->class="w3-row list-row w3-padding w3-mobile w3-border-bottom w3-border-black";
        $main->class=$GLOBALS['optionsDB']['HoverEffect'];
        $main->onclick="openModal('user', ".$this->Index.")";
        if(!$this->Instrument) {
            $main->class=$GLOBALS['optionsDB']['colorDisabled'];
        }
        echo $main->open();
        echo "  <div class=\"w3-col l1 m2 s12 w3-container list-meta\">".$this->Index."</div>\n";
        echo "  <div class=\"w3-col l3 m5 s12 w3-container list-primary\"><b>".$this->Vorname." ".$this->Nachname."</b></div>\n";
        echo "  <div class=\"w3-col l3 m5 s12 w3-container list-secondary\"><a href=\"mailto:".$this->Email."\">".$this->Email."</a></div>\n";
        echo "  <div class=\"w3-col l2 m6 s12 w3-container list-meta\">".germanDate($this->LastLogin, 1)."</div>\n";
        echo "  <div class=\"w3-col l2 m6 s12 w3-container list-meta\">".germanDate($this->getLastVisit(), 1)."</div>\n";
        echo $main->close();
    }
};
?>
