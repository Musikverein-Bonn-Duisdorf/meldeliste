<?php
class User
{
    private $_data = array('Index' => null, 'Nachname' => null, 'Vorname' => null, 'RefID' => null, 'login' => null, 'Passhash' => null, 'activeLink' => null, 'Mitglied' => null, 'Active' => 1, 'Instrument' => null, 'iName' => null, 'Email' => null, 'Email2' => null, 'Birthday' => null, 'getMail' => null, 'notifyInbox' => null, 'notifyAppMail' => null, 'notifyAppTerminNew' => null, 'notifyAppTerminChange' => null, 'notifyAppTerminSoon' => null, 'Admin' => null, 'singleUsePW' => null, 'RegisterLead' => null, 'LastLogin' => null, 'Joined' => null, 'Deleted' => null, 'DeletedOn' => null);
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
	    case 'Active':
	    case 'Instrument':
	    case 'iName':
	    case 'Email':
	    case 'Email2':
	    case 'Birthday':
	    case 'getMail':
	    case 'notifyInbox':
	    case 'notifyAppMail':
	    case 'notifyAppTerminNew':
	    case 'notifyAppTerminChange':
	    case 'notifyAppTerminSoon':
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
	    case 'Active':
	    case 'getMail':
	    case 'notifyInbox':
	    case 'notifyAppMail':
	    case 'notifyAppTerminNew':
	    case 'notifyAppTerminChange':
	    case 'notifyAppTerminSoon':
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
        $detail = $this->getChangeDetail();
        if($detail['parts'] === array()) {
            return $detail['header'];
        }
        return $detail['header'].', '.implode(', ', $detail['parts']);
    }

    /**
     * @return array{header:string,parts:string[]}
     */
    public function getChangeDetail() {
        $old = new User;
        $old->load_by_id($this->Index);

        $header = sprintf(
            'User-ID: %d <b>%s %s</b>',
            (int)$this->Index,
            htmlspecialchars((string)$this->Vorname, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string)$this->Nachname, ENT_QUOTES, 'UTF-8')
        );
        $parts = array();

        if($this->Vorname != $old->Vorname) {
            $this->Vorname = trim((string)$this->Vorname);
            $parts[] = 'Vorname: '.htmlspecialchars((string)$old->Vorname, ENT_QUOTES, 'UTF-8')
                .' &rArr; <b>'.htmlspecialchars((string)$this->Vorname, ENT_QUOTES, 'UTF-8').'</b>';
        }
        if($this->Nachname != $old->Nachname) {
            $this->Nachname = trim((string)$this->Nachname);
            $parts[] = 'Nachname: '.htmlspecialchars((string)$old->Nachname, ENT_QUOTES, 'UTF-8')
                .' &rArr; <b>'.htmlspecialchars((string)$this->Nachname, ENT_QUOTES, 'UTF-8').'</b>';
        }
        if($this->RefID != $old->RefID) {
            $parts[] = 'Mitglieds-Nr.: '.htmlspecialchars((string)$old->RefID, ENT_QUOTES, 'UTF-8')
                .' &rArr; <b>'.htmlspecialchars((string)$this->RefID, ENT_QUOTES, 'UTF-8').'</b>';
        }
        if($this->login != $old->login) {
            $parts[] = 'Login: '.htmlspecialchars((string)$old->login, ENT_QUOTES, 'UTF-8')
                .' &rArr; <b>'.htmlspecialchars((string)$this->login, ENT_QUOTES, 'UTF-8').'</b>';
        }
        if($this->Passhash != $old->Passhash) {
            $parts[] = 'Passhash geändert';
        }
        if($this->activeLink != $old->activeLink) {
            $parts[] = 'activeLink geändert';
        }
        if(boolsDiffer($this->Mitglied, $old->Mitglied)) {
            $parts[] = 'Mitglied: '.bool2string($old->Mitglied).' &rArr; <b>'.bool2string($this->Mitglied).'</b>';
        }
        if(boolsDiffer($this->Active, $old->Active)) {
            $parts[] = 'Aktiv: '.bool2string($old->Active).' &rArr; <b>'.bool2string($this->Active).'</b>';
        }
        if($this->Email != $old->Email) {
            $parts[] = 'Email: '.htmlspecialchars((string)$old->Email, ENT_QUOTES, 'UTF-8')
                .' &rArr; <b>'.htmlspecialchars((string)$this->Email, ENT_QUOTES, 'UTF-8').'</b>';
        }
        if($this->Email2 != $old->Email2) {
            $parts[] = 'Email2: '.htmlspecialchars((string)$old->Email2, ENT_QUOTES, 'UTF-8')
                .' &rArr; <b>'.htmlspecialchars((string)$this->Email2, ENT_QUOTES, 'UTF-8').'</b>';
        }
        if($this->Birthday != $old->Birthday) {
            $parts[] = 'Geburtstag: '.germanDate($old->Birthday, true)
                .' &rArr; <b>'.germanDate($this->Birthday, true).'</b>';
        }
        if(boolsDiffer($this->getMail, $old->getMail)) {
            $parts[] = 'Benachrichtigung E-Mail: '.bool2string($old->getMail).' &rArr; <b>'.bool2string($this->getMail).'</b>';
        }
        if(boolsDiffer($this->notifyInbox, $old->notifyInbox)) {
            $parts[] = 'Benachrichtigung Nachrichten: '.bool2string($old->notifyInbox).' &rArr; <b>'.bool2string($this->notifyInbox).'</b>';
        }
        if(boolsDiffer($this->notifyAppMail, $old->notifyAppMail)) {
            $parts[] = 'App: Nachrichten: '.bool2string($old->notifyAppMail).' &rArr; <b>'.bool2string($this->notifyAppMail).'</b>';
        }
        if(boolsDiffer($this->notifyAppTerminNew, $old->notifyAppTerminNew)) {
            $parts[] = 'App: neuer Termin: '.bool2string($old->notifyAppTerminNew).' &rArr; <b>'.bool2string($this->notifyAppTerminNew).'</b>';
        }
        if(boolsDiffer($this->notifyAppTerminChange, $old->notifyAppTerminChange)) {
            $parts[] = 'App: Termin geändert: '.bool2string($old->notifyAppTerminChange).' &rArr; <b>'.bool2string($this->notifyAppTerminChange).'</b>';
        }
        if(boolsDiffer($this->notifyAppTerminSoon, $old->notifyAppTerminSoon)) {
            $parts[] = 'App: Termin bald: '.bool2string($old->notifyAppTerminSoon).' &rArr; <b>'.bool2string($this->notifyAppTerminSoon).'</b>';
        }
        if(boolsDiffer($this->Admin, $old->Admin)) {
            $parts[] = 'Admin: '.bool2string($old->Admin).' &rArr; <b>'.bool2string($this->Admin).'</b>';
        }
        if(boolsDiffer($this->RegisterLead, $old->RegisterLead)) {
            $parts[] = 'Registerführer: '.bool2string($old->RegisterLead).' &rArr; <b>'.bool2string($this->RegisterLead).'</b>';
        }
        if((int)$this->Instrument !== (int)$old->Instrument) {
            $newinstr = new Instrument;
            $newinstr->load_by_id($this->Instrument);
            $oldinstr = new Instrument;
            $oldinstr->load_by_id($old->Instrument);
            $parts[] = 'Instrument: '.htmlspecialchars((string)$oldinstr->Name, ENT_QUOTES, 'UTF-8')
                .' &rArr; <b>'.htmlspecialchars((string)$newinstr->Name, ENT_QUOTES, 'UTF-8').'</b>';
        }

        return array('header' => $header, 'parts' => $parts);
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
        $parts[] = logPart('E-Mail', bool2string($this->getMail));
        $parts[] = logPart('Nachrichten', bool2string($this->notifyInbox));
        $parts[] = logPart('App: Nachrichten', bool2string($this->notifyAppMail));
        $parts[] = logPart('App: neuer Termin', bool2string($this->notifyAppTerminNew));
        $parts[] = logPart('App: Termin geändert', bool2string($this->notifyAppTerminChange));
        $parts[] = logPart('App: Termin bald', bool2string($this->notifyAppTerminSoon));
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
        if($this->notifyInbox === null) $this->notifyInbox = 1;
        if($this->notifyAppMail === null) $this->notifyAppMail = 1;
        if($this->notifyAppTerminNew === null) $this->notifyAppTerminNew = 1;
        if($this->notifyAppTerminChange === null) $this->notifyAppTerminChange = 1;
        if($this->notifyAppTerminSoon === null) $this->notifyAppTerminSoon = 0;
        if($this->Instrument === null) $this->Instrument = 0;
        if(!$this->is_valid()) return false;
        if($this->Index > 0) {
            $detail = $this->getChangeDetail();
            if(count($detail['parts'])) {
                $logentry = new Log;
                $logentry->DBupdate($detail['header'].', '.implode(', ', $detail['parts']));
            }
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
        $this->activeLink = bin2hex(random_bytes(16));
    }
    public function getLink() {
        return $GLOBALS['optionsDB']['WebSiteURL']."/login.php?alink=".$this->activeLink;
    }
    public function getCalendarLink() {
        return $GLOBALS['optionsDB']['WebSiteURL']."/ical.php?t=".$this->activeLink;
    }

    /** webcal:// variant of getCalendarLink() for calendar apps (MELD-127). */
    public function getCalendarWebcalLink() {
        $https = $this->getCalendarLink();
        return (string)preg_replace('#^https?://#i', 'webcal://', $https);
    }
    protected function insert() {
        $sql = sprintf('INSERT INTO `%sUser` (`Nachname`, `Vorname`, `RefID`, `login`, `Passhash`, `activeLink`, `Mitglied`, `Active`, `Instrument`, `Email`, `Email2`, `Birthday`, `getMail`, `notifyInbox`, `notifyAppMail`, `notifyAppTerminNew`, `notifyAppTerminChange`, `notifyAppTerminSoon`, `Admin`, `RegisterLead`) VALUES ("%s", "%s", %s, "%s", "%s", "%s", %d, %d, "%d", "%s", "%s", %s, "%d", "%d", "%d", "%d", "%d", "%d", "%d", "%d");',
        $GLOBALS['dbprefix'],
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Nachname),
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Vorname),
        mkNULL($this->RefID),
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->login),
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Passhash),
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->activeLink),
        (int)$this->Mitglied,
        (int)$this->Active === 0 ? 0 : 1,
        (int)$this->Instrument,
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Email),
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Email2),
        mkNULLstr($this->Birthday),
        (int)$this->getMail,
        (int)$this->notifyInbox,
        (int)$this->notifyAppMail,
        (int)$this->notifyAppTerminNew,
        (int)$this->notifyAppTerminChange,
        (int)$this->notifyAppTerminSoon,
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
        $sql = sprintf('UPDATE `%sUser` SET `Nachname` = "%s", `Vorname` = "%s", `RefID` = %s, `login` = "%s", `Passhash` = "%s", `activeLink` = "%s", `Mitglied` = "%d", `Active` = "%d", `Instrument` = "%d", `Email` = "%s", `Email2` = "%s", `Birthday` = %s, `getMail` = "%d", `notifyInbox` = "%d", `notifyAppMail` = "%d", `notifyAppTerminNew` = "%d", `notifyAppTerminChange` = "%d", `notifyAppTerminSoon` = "%d", `Admin` = "%d", `RegisterLead` = "%d" WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Nachname),
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Vorname),
        mkNULL($this->RefID),
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->login),
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Passhash),
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->activeLink),
        (int)$this->Mitglied,
        (int)$this->Active === 0 ? 0 : 1,
        (int)$this->Instrument,
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Email),
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Email2),
        mkNULLstr($this->Birthday),
        (int)$this->getMail,
        (int)$this->notifyInbox,
        (int)$this->notifyAppMail,
        (int)$this->notifyAppTerminNew,
        (int)$this->notifyAppTerminChange,
        (int)$this->notifyAppTerminSoon,
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
    /**
     * Remove responses for termine on/after today (MELD-152). Past meldungen stay.
     *
     * @param int $userId
     * @return array{meldungen:int,schichtmeldungen:int}
     */
    public static function deleteFutureMeldungenForUser($userId) {
        $userId = (int)$userId;
        $out = array('meldungen' => 0, 'schichtmeldungen' => 0);
        if($userId <= 0 || !isset($GLOBALS['conn']) || !isset($GLOBALS['dbprefix'])) {
            return $out;
        }
        $p = $GLOBALS['dbprefix'];
        $sql = sprintf(
            'DELETE `m` FROM `%sMeldungen` `m`
             INNER JOIN `%sTermine` `t` ON `t`.`Index` = `m`.`Termin`
             WHERE `m`.`User` = %d AND `t`.`Datum` >= CURDATE();',
            $p,
            $p,
            $userId
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if($dbr) {
            $out['meldungen'] = (int)mysqli_affected_rows($GLOBALS['conn']);
        }
        $sql = sprintf(
            'DELETE `sm` FROM `%sSchichtmeldung` `sm`
             INNER JOIN `%sSchichten` `s` ON `s`.`Index` = `sm`.`Shift`
             INNER JOIN `%sTermine` `t` ON `t`.`Index` = `s`.`Termin`
             WHERE `sm`.`User` = %d AND `t`.`Datum` >= CURDATE();',
            $p,
            $p,
            $p,
            $userId
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if($dbr) {
            $out['schichtmeldungen'] = (int)mysqli_affected_rows($GLOBALS['conn']);
        }
        return $out;
    }

    /**
     * Idempotent cleanup: future responses of already soft-deleted users (MELD-152).
     *
     * @return array{meldungen:int,schichtmeldungen:int}
     */
    public static function deleteFutureMeldungenForDeletedUsers() {
        $out = array('meldungen' => 0, 'schichtmeldungen' => 0);
        if(!isset($GLOBALS['conn']) || !isset($GLOBALS['dbprefix'])) {
            return $out;
        }
        $p = $GLOBALS['dbprefix'];
        $sql = sprintf(
            'DELETE `m` FROM `%sMeldungen` `m`
             INNER JOIN `%sTermine` `t` ON `t`.`Index` = `m`.`Termin`
             INNER JOIN `%sUser` `u` ON `u`.`Index` = `m`.`User`
             WHERE `u`.`Deleted` = 1 AND `t`.`Datum` >= CURDATE();',
            $p,
            $p,
            $p
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if($dbr) {
            $out['meldungen'] = (int)mysqli_affected_rows($GLOBALS['conn']);
        }
        $sql = sprintf(
            'DELETE `sm` FROM `%sSchichtmeldung` `sm`
             INNER JOIN `%sSchichten` `s` ON `s`.`Index` = `sm`.`Shift`
             INNER JOIN `%sTermine` `t` ON `t`.`Index` = `s`.`Termin`
             INNER JOIN `%sUser` `u` ON `u`.`Index` = `sm`.`User`
             WHERE `u`.`Deleted` = 1 AND `t`.`Datum` >= CURDATE();',
            $p,
            $p,
            $p,
            $p
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if($dbr) {
            $out['schichtmeldungen'] = (int)mysqli_affected_rows($GLOBALS['conn']);
        }
        return $out;
    }

    public function delete() {
        if(!$this->Index) return false;
        // Inventar zuerst prüfen – sonst keine Soft-Delete und keine Melde-Bereinigung
        if($this->hasInventories()) {
            return false;
        }
        $userId = (int)$this->Index;
        self::deleteFutureMeldungenForUser($userId);
        $sql = sprintf('UPDATE `%sUser` SET `Deleted` = 1, `DeletedOn` = CURRENT_TIMESTAMP, `Vorname` = "gelöschter", `Nachname` = "Benutzer", `Email` = "", `Email2` = "", `login` = "", `Passhash` = "", `getMail` = 0, `notifyInbox` = 0, `notifyAppMail` = 0, `notifyAppTerminNew` = 0, `notifyAppTerminChange` = 0, `notifyAppTerminSoon` = 0 WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        $userId
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

    /** Gastmusiker-Defaults: keine Mail/App-Benachrichtigungen. */
    public function applyGuestMusicianDefaults() {
        $this->Active = 0;
        $this->getMail = 0;
        $this->notifyAppMail = 0;
        $this->notifyAppTerminNew = 0;
        $this->notifyAppTerminChange = 0;
        $this->notifyAppTerminSoon = 0;
    }

    public function isGuestMusician() {
        return (int)$this->Active === 0;
    }

    /**
     * Existing non-deleted user with same Vor-/Nachname (case-insensitive), or null.
     *
     * @param string $vorname
     * @param string $nachname
     * @param int $excludeIndex
     * @return array{Index:int,Vorname:string,Nachname:string}|null
     */
    public static function findExistingByName($vorname, $nachname, $excludeIndex = 0) {
        $vorname = trim((string)$vorname);
        $nachname = trim((string)$nachname);
        if($vorname === '' || $nachname === '') {
            return null;
        }
        $excludeIndex = (int)$excludeIndex;
        $sql = sprintf(
            'SELECT `Index`, `Vorname`, `Nachname` FROM `%sUser`
             WHERE `Deleted` != 1
               AND LOWER(TRIM(`Vorname`)) = LOWER("%s")
               AND LOWER(TRIM(`Nachname`)) = LOWER("%s")
               %s
             ORDER BY `Index` ASC LIMIT 1;',
            $GLOBALS['dbprefix'],
            mysqli_real_escape_string($GLOBALS['conn'], $vorname),
            mysqli_real_escape_string($GLOBALS['conn'], $nachname),
            $excludeIndex > 0 ? ('AND `Index` != '.$excludeIndex) : ''
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) {
            return null;
        }
        $row = mysqli_fetch_assoc($dbr);
        if(!is_array($row)) {
            return null;
        }
        return array(
            'Index' => (int)$row['Index'],
            'Vorname' => (string)$row['Vorname'],
            'Nachname' => (string)$row['Nachname'],
        );
    }

    public function canLogin() {
        if ((int)$this->Deleted === 1) {
            return false;
        }
        if ($this->isGuestMusician() && trim((string)$this->Passhash) === '') {
            return false;
        }
        return true;
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
        $userId = (int)$this->Index;
        // Owner=0 = Verein; ohne gültige User-ID darf nichts als „mein“ gelten
        if($userId < 1) {
            return array();
        }
        // Aktive Leihe (auch mit geplanter Rückgabe in der Zukunft); nur existierende Stücke
        $sql = sprintf(
            'SELECT l.`Index` FROM `%sInventoriesLoans` l
             INNER JOIN `%sInventories` i ON i.`Index` = l.`Inventory`
             WHERE l.`User` = %d
               AND (l.`EndDate` IS NULL OR l.`EndDate` > CURDATE())
             ORDER BY l.`StartDate` DESC;',
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $userId
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
        $userId = (int)$this->Index;
        // Owner=0 = Verein (orgNameShort); nie mit „persönlichem“ Eigentum verwechseln
        if($userId < 1) {
            return array();
        }
        $sql = sprintf(
            'SELECT `Index` FROM `%sInventories` WHERE `Owner` = %d',
            $GLOBALS['dbprefix'],
            $userId
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $inventories = array();
        while($row = mysqli_fetch_array($dbr)) {
            array_push($inventories, $row['Index']);
        }
        return $inventories;
    }

    /** Eigentum oder aktive Ausleihe (geliehenes Vereinsinventar zählt). */
    public function hasInventories() {
        return count($this->getInventoriesLoans()) > 0 || count($this->getInventories()) > 0;
    }

    /**
     * Short label for delete/inventory warnings (nr + type/vendor/model).
     */
    private static function inventoryWarningLabel($inventoryId) {
        $inv = new Inventories;
        $inv->load_by_id((int)$inventoryId);
        if(!(int)$inv->Index) {
            return 'Inventar #'.(int)$inventoryId;
        }
        $sql = sprintf(
            'SELECT `Typ` FROM `%sInventory` WHERE `Index` = %d;',
            $GLOBALS['dbprefix'],
            (int)$inv->Inventory
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = $dbr ? mysqli_fetch_array($dbr) : null;
        $typeName = ($row && isset($row['Typ'])) ? (string)$row['Typ'] : '';
        $family = method_exists($inv, 'getInstrumentName') ? trim((string)$inv->getInstrumentName()) : '';
        if($family !== '') {
            $typeName = $family;
        }
        $parts = array_filter(array(
            RegNumber::displayInventory($inv->Inventory, $inv->RegNumber),
            $typeName,
            trim((string)$inv->Vendor),
            trim((string)$inv->Model),
        ), function ($p) {
            return $p !== '';
        });
        return implode(' · ', $parts);
    }

    /**
     * HTML warning for user delete modal when Eigentum or active loans exist (MELD-152).
     */
    public function getDeleteInventoryWarningHtml() {
        $ownedIds = $this->getInventories();
        $loanIds = $this->getInventoriesLoans();
        if(!count($ownedIds) && !count($loanIds)) {
            return '';
        }
        $h = function ($s) {
            return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
        };
        $html = '<div class="confirm-delete-inventory-warn '.$h($GLOBALS['optionsDB']['colorBtnNo']).'">';
        $html .= '<p><b>Achtung Inventar</b></p>';
        if(count($ownedIds)) {
            $labels = array();
            foreach($ownedIds as $id) {
                $labels[] = self::inventoryWarningLabel($id);
            }
            $n = count($ownedIds);
            $html .= '<p>Diese Person ist Eigentümer von <b>'.$n.'</b> Inventarstück'
                .($n === 1 ? '' : 'en').':</p><ul>';
            foreach($labels as $label) {
                $html .= '<li>'.$h($label).'</li>';
            }
            $html .= '</ul>';
        }
        if(count($loanIds)) {
            $labels = array();
            foreach($loanIds as $loanId) {
                $loan = new InventoriesLoan;
                $loan->load_by_id((int)$loanId);
                $labels[] = self::inventoryWarningLabel($loan->Inventory);
            }
            $n = count($loanIds);
            $html .= '<p>Diese Person hat <b>'.$n.'</b> aktive Ausleihe'
                .($n === 1 ? '' : 'n').':</p><ul>';
            foreach($labels as $label) {
                $html .= '<li>'.$h($label).'</li>';
            }
            $html .= '</ul>';
        }
        $html .= '<p>Löschen ist erst möglich, wenn Eigentum umgetragen und aktive Ausleihen beendet sind.</p>';
        $html .= '</div>';
        return $html;
    }

    /**
     * Plain-text reason when soft-delete is blocked by inventory (MELD-152).
     */
    public function getDeleteInventoryBlockMessage() {
        $owned = count($this->getInventories());
        $loans = count($this->getInventoriesLoans());
        if($owned < 1 && $loans < 1) {
            return '';
        }
        $parts = array('Löschen nicht möglich:');
        if($owned > 0) {
            $parts[] = $owned === 1
                ? '1 Inventarstück als Eigentum'
                : $owned.' Inventarstücke als Eigentum';
        }
        if($loans > 0) {
            $parts[] = $loans === 1
                ? '1 aktive Ausleihe'
                : $loans.' aktive Ausleihen';
        }
        return implode(' ', $parts).'. Bitte zuerst Eigentum/Ausleihen klären.';
    }
    
    public function getModalHtml($forceEditButton = false) {
        $showUserDetails = requirePermission("perm_showUsers");
        $permissions = null;
        if($showUserDetails) {
            $permissions = new Permissions;
            $permissions->load_by_user($this->Index);
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
            'permissions' => $permissions,
            'registerLeadName' => $registerLeadName,
            'showEditButton' => ($forceEditButton || requirePermission("perm_editUsers")),
            'returnTo' => pageToReturnUrl(isset($_SESSION['page']) ? $_SESSION['page'] : 'musiker'),
            'returnToken' => issueReturnToken(pageToReturnUrl(isset($_SESSION['page']) ? $_SESSION['page'] : 'musiker')),
        ));
    }

    /** Personen-/Register-Listen (MELD-155). */
    public function printTableLine() {
        $this->renderUserListRow();
    }

    /** Alias: gleiche Zeile (ID in Meta). */
    public function printUserTableLine() {
        $this->renderUserListRow();
    }

    /**
     * Register meta for list rows (cached per Instrument).
     *
     * @return array{id:int,name:string,sort:int,color:string}
     */
    private function getRegisterMeta() {
        static $byInstrument = array();
        $instr = (int)$this->Instrument;
        if($instr <= 0) {
            return array('id' => 0, 'name' => '', 'sort' => 9999, 'color' => '');
        }
        if(isset($byInstrument[$instr])) {
            return $byInstrument[$instr];
        }
        $meta = array('id' => 0, 'name' => '', 'sort' => 9999, 'color' => '');
        $sql = sprintf(
            'SELECT r.`Index` AS `rIndex`, r.`Name` AS `rName`, r.`Sortierung` AS `rSort`, r.`Color` AS `rColor`
             FROM `%sInstrument` i
             INNER JOIN `%sRegister` r ON i.`Register` = r.`Index`
             WHERE i.`Index` = %d
             LIMIT 1;',
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $instr
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = $dbr ? mysqli_fetch_assoc($dbr) : null;
        if(is_array($row)) {
            $rName = html_entity_decode((string)$row['rName'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if(strtolower(trim($rName)) === 'keins') {
                $rName = '';
            }
            $meta = array(
                'id' => (int)$row['rIndex'],
                'name' => $rName,
                'sort' => (int)$row['rSort'],
                'color' => normalizeHexColor(isset($row['rColor']) ? $row['rColor'] : ''),
            );
        }
        $byInstrument[$instr] = $meta;
        return $meta;
    }

    private function renderUserListRow() {
        $h = function ($s) {
            return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
        };
        $lastVisit = $this->getLastVisit();
        $name = trim((string)$this->Vorname.' '.(string)$this->Nachname);
        $instrument = trim((string)$this->iName);
        $email = trim((string)$this->Email);
        $loginLabel = germanDate($this->LastLogin, 1);
        $visitLabel = germanDate($lastVisit, 1);
        $isMember = ((int)$this->Mitglied === 1);
        $isActive = ((int)$this->Active === 1);
        $hasInstrument = (int)$this->Instrument > 0;
        $reg = $this->getRegisterMeta();
        $regId = (int)$reg['id'];
        $regSort = (int)$reg['sort'];
        $regColor = (string)$reg['color'];
        $regName = (string)$reg['name'];
        $namedGroups = AudienceSpec::namedGroupNamesByUser();
        $groupNames = isset($namedGroups[(int)$this->Index]) ? $namedGroups[(int)$this->Index] : array();
        $groupChips = array();
        foreach(AudienceSpec::previewDerivedMembership(array(
            'mitglied' => $isMember,
            'active' => $isActive,
            'registerId' => $regId,
            'registerName' => $regName,
            'userId' => (int)$this->Index,
        )) as $chip) {
            $type = isset($chip['type']) ? (string)$chip['type'] : '';
            if($type === 'group' || $type === 'register') {
                $groupChips[] = $chip;
            }
        }
        foreach($groupNames as $gName) {
            $groupChips[] = array(
                'type' => 'namedGroup',
                'label' => 'Gruppe: '.$gName,
            );
        }
        $permChips = Permissions::activePermissionChipsForUser((int)$this->Index);
        $permSearch = array();
        foreach($permChips as $pc) {
            $permSearch[] = $pc['short'];
            $permSearch[] = $pc['label'];
        }
        $groupSearch = array();
        foreach($groupChips as $gc) {
            $groupSearch[] = isset($gc['label']) ? (string)$gc['label'] : '';
        }

        $searchParts = array(
            $name,
            (string)$this->Vorname,
            (string)$this->Nachname,
            $instrument,
            $email,
            $regName,
            (string)(int)$this->Index,
            $isMember ? 'mitglied' : 'nicht-mitglied',
            $isActive ? 'aktiv' : 'gastmusiker gast inaktiv',
            !$hasInstrument ? 'ohne instrument' : '',
            implode(' ', $groupSearch),
            implode(' ', $permSearch),
        );

        $classes = array('user-row', 'list-row');
        if($isMember) {
            $classes[] = 'user-row--member';
        }
        else {
            $classes[] = 'user-row--nomember';
        }
        if(!$isActive) {
            $classes[] = 'user-row--inactive';
        }
        if(!$hasInstrument || $regId <= 0) {
            $classes[] = 'user-row--no-register';
        }
        $hover = isset($GLOBALS['optionsDB']['HoverEffect']) ? $GLOBALS['optionsDB']['HoverEffect'] : '';
        if($hover) {
            $classes[] = $hover;
        }

        $sortRegister = sprintf('%05d|%s|%s', $regSort, (string)$this->Nachname, (string)$this->Vorname);
        $style = '';
        if($regColor !== '') {
            $style = ' style="--user-register-color:'.$h($regColor).'"';
            $classes[] = 'user-row--register-color';
        }

        $attrs = 'data-sort-nachname="'.$h($this->Nachname).'"'
            .' data-sort-vorname="'.$h($this->Vorname).'"'
            .' data-sort-name="'.$h($name).'"'
            .' data-sort-instrument="'.$h($instrument).'"'
            .' data-sort-email="'.$h($email).'"'
            .' data-sort-lastlogin="'.$h($this->LastLogin).'"'
            .' data-sort-lastvisit="'.$h($lastVisit).'"'
            .' data-sort-index="'.$h((string)(int)$this->Index).'"'
            .' data-sort-register="'.$h($sortRegister).'"'
            .' data-active="'.($isActive ? '1' : '0').'"'
            .' data-mitglied="'.($isMember ? '1' : '0').'"'
            .' data-register-id="'.$h((string)$regId).'"'
            .' data-search="'.$h(trim(implode(' ', $searchParts))).'"'
            .' onclick="openModal(\'user\', '.(int)$this->Index.')"'
            .' role="button" tabindex="0"'
            .' onkeydown="if(event.key===\'Enter\'||event.key===\' \'){event.preventDefault();openModal(\'user\', '.(int)$this->Index.');}"';

        echo '<div class="'.$h(implode(' ', $classes)).'"'.$style.' '.$attrs.'>';
        echo '<div class="user-id">';
        echo '<div class="user-id-num"><span class="user-id-k">User-ID</span> '.$h((string)(int)$this->Index).'</div>';
        echo '<div class="user-id-chips" aria-label="Instrument und Mitgliedschaft">';
        if($instrument !== '') {
            echo '<div class="user-id-chip-line mail-recipient-chips">';
            echo '<span class="mail-recipient-chip mail-recipient-chip--instrument">'.$h($instrument).'</span>';
            echo '</div>';
        }
        echo '<div class="user-id-chip-line mail-recipient-chips">';
        if(!$isActive) {
            echo '<span class="mail-recipient-chip mail-recipient-chip--guestMusician">Gast</span>';
        }
        elseif($isMember) {
            echo '<span class="mail-recipient-chip mail-recipient-chip--member">Mitglied</span>';
        }
        else {
            echo '<span class="mail-recipient-chip mail-recipient-chip--nomember">kein Mitglied</span>';
        }
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '<div class="user-rail" aria-hidden="true"></div>';
        echo '<div class="user-main">';
        echo '<div class="user-name">'.$h($name).'</div>';
        if($email !== '') {
            echo '<div class="user-email"><a href="mailto:'.$h($email).'" onclick="event.stopPropagation()">'.$h($email).'</a></div>';
        }
        if(count($groupChips)) {
            echo '<div class="user-chip-row">';
            echo '<span class="user-chip-row-k">Gruppen</span>';
            echo '<div class="user-chips mail-recipient-chips" aria-label="Gruppen">';
            foreach($groupChips as $gc) {
                $type = isset($gc['type']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$gc['type']) : 'group';
                $label = isset($gc['label']) ? (string)$gc['label'] : '';
                if($label === '') {
                    continue;
                }
                echo '<span class="mail-recipient-chip mail-recipient-chip--'.$h($type).'">'.$h($label).'</span>';
            }
            echo '</div>';
            echo '</div>';
        }
        if(count($permChips)) {
            echo '<div class="user-chip-row">';
            echo '<span class="user-chip-row-k">Rechte</span>';
            echo '<div class="user-chips mail-recipient-chips profile-perm-tiles" aria-label="Rechte">';
            foreach($permChips as $pc) {
                $gid = preg_replace('/[^a-z0-9_-]/i', '', (string)$pc['groupId']);
                $inheritedOnly = empty($pc['personal']) && count($pc['groups']) > 0;
                $cls = 'mail-recipient-chip profile-perm-tile profile-perm-tile--'.$gid;
                if($inheritedOnly) {
                    $cls .= ' profile-perm-tile--inherited';
                }
                $title = '';
                if(count($pc['groups'])) {
                    $title = ($inheritedOnly ? 'Nur über Gruppe: ' : 'Auch Gruppe: ')
                        .implode(', ', $pc['groups']);
                }
                echo '<span class="'.$h($cls).'"'
                    .($title !== '' ? ' title="'.$h($title).'"' : '')
                    .'>'.$h($pc['label']).'</span>';
            }
            echo '</div>';
            echo '</div>';
        }
        echo '<div class="user-meta-line">';
        echo '<span class="user-meta-item"><span class="user-meta-k">Login</span> '.$h($loginLabel).'</span>';
        echo '<span class="user-meta-item"><span class="user-meta-k">Teilnahme</span> '.$h($visitLabel).'</span>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
};
?>
