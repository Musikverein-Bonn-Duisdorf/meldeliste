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
            $this->_data[$key] = trim($val);
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
            $this->Vorname = htmlentities(trim($this->Vorname));
            $str.=", Vorname: ".$old->Vorname." &rArr; <b>".$this->Vorname."</b>";
        }
        if($this->Nachname != $old->Nachname) {
            $this->Nachname = htmlentities(trim($this->Nachname));
            $str.=", Nachname: ".$old->Nachname." &rArr; <b>".$this->Nachname."</b>";
        }
        if($this->RefID != $old->RefID) $str.=", RefID: ".$old->RefID." &rArr; <b>".$this->RefID."</b>";
        if($this->login != $old->login) $str.=", login: ".$old->login." &rArr; <b>".$this->login."</b>";
        if($this->Passhash != $old->Passhash) $str.=", Passhash: ".$old->Passhash." &rArr; <b>".$this->Passhash."</b>";
        if($this->activeLink != $old->activeLink) $str.=", activeLink: ".$old->activeLink." &rArr; <b>".$this->activeLink."</b>";
        if($this->Mitglied != $old->Mitglied) $str.=", Mitglied: ".bool2string($old->Mitglied)." &rArr; <b>".bool2string($this->Mitglied)."</b>";
        if($this->Email != $old->Email) $str.=", Email: ".$old->Email." &rArr; <b>".$this->Email."</b>";
        if($this->Email2 != $old->Email2) $str.=", Email2: ".$old->Email2." &rArr; <b>".$this->Email2."</b>";
        if($this->Birthday != $old->Birthday) $str.=", Geburtstag: ".$old->Birthday." &rArr; <b>".$this->Birthday."</b>";
        if($this->getMail != $old->getMail) $str.=", getMail: ".bool2string($old->getMail)." &rArr; <b>".bool2string($this->getMail)."</b>";
        if($this->Admin != $old->Admin) $str.=", Admin: ".bool2string($old->Admin)." &rArr; <b>".bool2string($this->Admin)."</b>";
        if($this->RegisterLead != $old->RegisterLead) $str.=", RegisterLead: ".bool2string($old->RegisterLead)." &rArr; <b>".bool2string($this->RegisterLead)."</b>";
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
        if(!$this->iName) {
            $sql = sprintf('SELECT * FROM `%sInstrument` WHERE `Index` = %d;',
            $GLOBALS['dbprefix'],
            $this->Instrument
            );
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
            sqlerror();
            $row = mysqli_fetch_array($dbr);
            if($row) {
                $this->iName = $row['Name'];
            }
        }
        return sprintf("User-ID: %d, Vorname: <b>%s</b>, Nachname: <b>%s</b>, RefID: <b>%d</b>, Login: <b>%s</b>, Mitglied: <b>%s</b>, Instrument: <b>%s</b>, Email: <b>%s</b>, Email2: <b>%s</b>, Geburtstag: <b>%s</b>, Mailverteiler: <b>%s</b>, Admin: <b>%s</b>, RegisterLead: <b>%d</b>, LastLogin: <b>%s</b>",
        $this->Index,
        $this->Vorname,
        $this->Nachname,
        $this->RefID,
        $this->login,
        bool2string($this->Mitglied),
        $this->iName,
        $this->Email,
        $this->Email2,
        germanDate($this->Birthday, true),
        bool2string($this->getMail),
        bool2string($this->Admin),
        bool2string($this->RegisterLead),
        $this->LastLogin
        );
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
        if($this->activeLink == '') $this->generateLink();
        if(!$this->is_valid()) return false;
        if($this->Index > 0) {
            $logentry = new Log;
            $logentry->DBupdate($this->getChanges());
            $this->update();
        }
        else {
            $this->Vorname = htmlentities(trim($this->Vorname));
            $this->Nachname = htmlentities(trim($this->Nachname));
            $this->insert();
            $logentry = new Log;
            $logentry->DBinsert($this->getVars());
        }
    }
    public function singleUsePW($val) {
        $sql = sprintf('UPDATE `%sUser` SET `singleUsePW` = %d WHERE `Index` = %d;',
        $GLOBALS['dbprefix'],
        (bool)$val,
        $this->Index
        );
        mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if($_SESSION['userid'] == $this->Index) {
            $_SESSION['singleUsePW'] = (bool)$val;
        }
    }
    public function newmail() {
        $mail = new Usermail;
        $mail->singleUser($this->Index, $GLOBALS['optionsDB']['newMailSubject'], $GLOBALS['optionsDB']['newMailText']."\n".$GLOBALS['optionsDB']['MailGreetings']);
    }
    public function passwd($password) {
        if(!$this->login) {
            return false;
        }
        $arbPW = false;
        if($password == '') {
            $password = uniqid();
            $arbPW = true;
        }
        if($this->Index) {
            $this->Passhash = password_hash($password, PASSWORD_DEFAULT);
            $mail = new Usermail;
            if($arbPW) {
                $this->singleUsePW(1);
                $this->update();
                $mail->singleUser($this->Index, $GLOBALS['optionsDB']['SubjectPW'], "ein neues Passwort wurde erstellt. Beim n&auml;chsten Login wirst du aufgefordert, dieses zu &auml;ndern.\nDu kannst dich nun unter\n\n<a href=\"".$GLOBALS['optionsDB']['WebSiteURL']."\">".$GLOBALS['optionsDB']['WebSiteURL']."</a>\n\neinloggen.\nBenutzername: ".$this->login."\nPasswort: ".$password);
            }
            else {
                $this->generateLink();
                $this->singleUsePW(0);
                $this->update();
                $mail->singleUser($this->Index, $GLOBALS['optionsDB']['SubjectPW'], "dein neues Passwort wurde gespeichert. Damit ist auch der alte Login-Link ungültig. Bitte nutze ab sofort den Link unter dieser Email.\n\nBenutzername: ".$this->login);
            }
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
        $sql = sprintf('INSERT INTO `%sUser` (`Nachname`, `Vorname`, `RefID`, `login`, `Passhash`, `activeLink`, `Mitglied`, `Instrument`, `Email`, `Email2`, `Birthday`, `getMail`, `Admin`, `RegisterLead`) VALUES ("%s", "%s", %s, "%s", "%s", "%s", %s, "%d", "%s", "%s", "%s", "%d", "%d", "%d");',
        $GLOBALS['dbprefix'],
        mysqli_real_escape_string($GLOBALS['conn'], $this->Nachname),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Vorname),
        mkNULL($this->RefID),
        mysqli_real_escape_string($GLOBALS['conn'], $this->login),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Passhash),
        mysqli_real_escape_string($GLOBALS['conn'], $this->activeLink),
        $this->Mitglied,
        $this->Instrument,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Email),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Email2),
        mkNULLstr($this->Birthday),
        $this->getMail,
        $this->Admin,
        $this->RegisterLead
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
        mysqli_real_escape_string($GLOBALS['conn'], $this->Nachname),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Vorname),
        mkNULL($this->RefID),
        mysqli_real_escape_string($GLOBALS['conn'], $this->login),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Passhash),
        mysqli_real_escape_string($GLOBALS['conn'], $this->activeLink),
        $this->Mitglied,
        $this->Instrument,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Email),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Email2),
        mkNULLstr($this->Birthday),
        $this->getMail,
        $this->Admin,
        $this->RegisterLead,
        $this->Index
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
        $sql = sprintf('UPDATE `%sUser` SET `Deleted` = 1, `DeletedOn` = CURRENT_TIMESTAMP, `Vorname` = "gel&ouml;schter", `Nachname` = "Benutzer", `Email` = "", `Email2` = "", `login` = "", `Passhash` = "", `getMail` = 0 WHERE `Index` = "%d";',
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
        foreach($row as $key => $val) {
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
        $row = mysqli_fetch_array($dbr);
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

    public function getLoans() {
        $sql = sprintf('SELECT `Index` FROM `%sLoans` WHERE `User` = %d AND `EndDate` IS NULL;',
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

    public function getInstruments() {
        $sql = sprintf('SELECT `Index` FROM `%sInstruments` WHERE `Owner` = %d',
        $GLOBALS['dbprefix'],
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $instruments = array();
        while($row = mysqli_fetch_array($dbr)) {
            array_push($instruments, $row['Index']);
        }
        return $instruments;
    }

    public function hasInstruments() {
        return count($this->getLoans()) + count($this->getInstruments());
    }
    
    public function printTableLine() {
        if($this->Mitglied) {
            echo "<div class=\"w3-row ".$GLOBALS['optionsDB']['HoverEffect']." w3-padding ".$GLOBALS['optionsDB']['colorUserMember']." w3-mobile w3-border-bottom w3-border-black\">\n";
        }
        else {
            echo "<div class=\"w3-row ".$GLOBALS['optionsDB']['HoverEffect']." w3-padding ".$GLOBALS['optionsDB']['colorUserNoMember']." w3-mobile w3-border-bottom w3-border-black\">\n";            
        }
        echo "  <div onclick=\"document.getElementById('id".$this->Index."').style.display='block'\" class=\"w3-col l3 w3-container\"><b>".$this->Vorname." ".$this->Nachname."</b></div>\n";
        echo "  <div class=\"w3-col l2 w3-container\">".$this->iName."</div>\n";
        echo "  <div class=\"w3-col l3 w3-container\"><a href=\"mailto:".$this->Email."\">".$this->Email."</a></div>\n";
        echo "  <div class=\"w3-col l2 w3-container\">".germanDate($this->LastLogin, 1)."</div>\n";
        echo "  <div class=\"w3-col l2 w3-container\">".germanDate($this->getLastVisit(), 1)."</div>\n";
        echo "</div>\n";
        ?>
        <div id="id<?php echo $this->Index; ?>" class="w3-modal">
        <div class="w3-modal-content">

        <header class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>"> 
      <span onclick="document.getElementById('id<?php echo $this->Index; ?>').style.display='none'" 
      class="w3-button w3-display-topright">&times;</span>
      <h2><?php echo $this->Vorname." ".$this->Nachname; ?></h2>
    </header>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l6">userID:</div><div class="w3-col l6"><b><?php echo $this->Index; ?></b></div>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l6">Instrument:</div><div class="w3-col l6"><b><?php echo $this->iName; ?></b></div>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l6">Vereinsmitglied:</div><div class="w3-col l6"><b><?php echo bool2string($this->Mitglied); ?></b></div>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l6">erhält Emails:</div><div class="w3-col l6"><b><?php echo bool2string($this->getMail); ?></b></div>
    </div>
      <?php
      if(requirePermission("perm_showUsers")) {
      ?>
    <div class="w3-container w3-row w3-margin">
          <div class="w3-col l6">Account erstellt:</div><div class="w3-col l6"><b><?php echo germanDate($this->Joined, 1); ?></b></div>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l6">Mitglieds-Nr.:</div><div class="w3-col l6"><b><?php echo $this->RefID; ?></b></div>
    </div>
    <div class="w3-container w3-margin">
      <div class="w3-col l6">Berechtigungen:</div>
      <?php
         $p = new Permissions;
         $p->load_by_user($this->Index);
         echo $p->printShort();
      }
      ?>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l6">Emailadresse:</div><div class="w3-col l6"><b><a href="mailto:<?php echo $this->Email; ?>"><?php echo $this->Email; ?></a></b></div>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l6">zweite Emailadresse:</div><div class="w3-col l6"><b><a href="mailto:<?php echo $this->Email2; ?>"><?php echo $this->Email2; ?></a></b></div>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l6">Loginname:</div><div class="w3-col l6"><b><?php echo $this->login; ?></b></div>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l6">Letzter Login:</div><div class="w3-col l6"><b><?php echo germanDate($this->LastLogin, 1); ?></b></div>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l6">Letzte Anwesenheit:</div><div class="w3-col l6"><b><?php echo germanDate($this->getLastVisit(), 1); ?></b></div>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l6">Meldequote:</div><div class="w3-col l6"><b><?php echo $this->getMeldeQuote()*100; ?> %</b></div>
    </div>
      <?php
      if($this->RegisterLead) {
          $r = new Register;
          $r->load_by_id($this->getRegister());
      ?>
          <div class="w3-container w3-row w3-margin">
          <div class="w3-col l6">Registerführer:</div><div class="w3-col l6"><b><?php echo $r->Name; ?></b></div>
          </div>
      <?php
      }
      if(requirePermission("perm_editUsers")) {
      ?>
      <form class="w3-center w3-bar w3-mobile" action="new-musiker.php" method="POST">
      <button class="w3-button w3-center w3-mobile w3-block <?php echo $GLOBALS['optionsDB']['colorBtnEdit']; ?>" type="submit" name="id" value="<?php echo $this->Index; ?>">bearbeiten</button>
      </form>
              <?php
              }
        ?>
      </div>
      </div>
        <?php
    }



    public function printUserTableLine() {
        $main = new div;
        $main->class="w3-row w3-padding w3-mobile w3-border-bottom w3-border-black";
        $main->class=$GLOBALS['optionsDB']['HoverEffect'];
        $main->onclick="document.getElementById('id".$this->Index."').style.display='block'";
        if(!$this->Instrument) {
            $main->class=$GLOBALS['optionsDB']['colorDisabled'];
        }
        echo $main->open();
        echo "  <div class=\"w3-col l1 w3-container\">".$this->Index."</div>\n";
        echo "  <div class=\"w3-col l3 w3-container\"><b>".$this->Vorname." ".$this->Nachname."</b></div>\n";
        echo "  <div class=\"w3-col l3 w3-container\"><a href=\"mailto:".$this->Email."\">".$this->Email."</a></div>\n";
        echo "  <div class=\"w3-col l2 w3-container\">".germanDate($this->LastLogin, 1)."</div>\n";
        echo "  <div class=\"w3-col l2 w3-container\">".germanDate($this->getLastVisit(), 1)."</div>\n";
        echo $main->close();
        ?>
        <div id="id<?php echo $this->Index; ?>" class="w3-modal">
        <div class="w3-modal-content">

        <header class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>"> 
      <span onclick="document.getElementById('id<?php echo $this->Index; ?>').style.display='none'" 
      class="w3-button w3-display-topright">&times;</span>
      <h2><?php echo $this->Vorname." ".$this->Nachname; ?></h2>
    </header>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l6">userID:</div><div class="w3-col l6"><b><?php echo $this->Index; ?></b></div>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l6">Instrument:</div><div class="w3-col l6"><b><?php echo $this->iName; ?></b></div>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l6">Vereinsmitglied:</div><div class="w3-col l6"><b><?php echo bool2string($this->Mitglied); ?></b></div>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l6">erhält Emails:</div><div class="w3-col l6"><b><?php echo bool2string($this->getMail); ?></b></div>
    </div>
      <?php
      if(requirePermission("perm_showUsers")) {
      ?>
    <div class="w3-container w3-row w3-margin">
          <div class="w3-col l6">Account erstellt:</div><div class="w3-col l6"><b><?php echo germanDate($this->Joined, 1); ?></b></div>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l6">Mitglieds-Nr.:</div><div class="w3-col l6"><b><?php echo $this->RefID; ?></b></div>
    </div>
    <div class="w3-container w3-margin">
      <div class="w3-col l6">Berechtigungen:</div>
      <?php
         $p = new Permissions;
         $p->load_by_user($this->Index);
         echo $p->printShort();
      }
      ?>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l6">Emailadresse:</div><div class="w3-col l6"><b><a href="mailto:<?php echo $this->Email; ?>"><?php echo $this->Email; ?></a></b></div>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l6">zweite Emailadresse:</div><div class="w3-col l6"><b><a href="mailto:<?php echo $this->Email2; ?>"><?php echo $this->Email2; ?></a></b></div>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l6">Loginname:</div><div class="w3-col l6"><b><?php echo $this->login; ?></b></div>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l6">Letzter Login:</div><div class="w3-col l6"><b><?php echo germanDate($this->LastLogin, 1); ?></b></div>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l6">Letzte Anwesenheit:</div><div class="w3-col l6"><b><?php echo germanDate($this->getLastVisit(), 1); ?></b></div>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l6">Meldequote:</div><div class="w3-col l6"><b><?php echo $this->getMeldeQuote()*100; ?> %</b></div>
    </div>
      <?php
      if($this->RegisterLead) {
          $r = new Register;
          $r->load_by_id($this->getRegister());
      ?>
          <div class="w3-container w3-row w3-margin">
          <div class="w3-col l6">Registerführer:</div><div class="w3-col l6"><b><?php echo $r->Name; ?></b></div>
          </div>
      <?php
      }
      ?>
      <form class="w3-center w3-bar w3-mobile" action="new-musiker.php" method="POST">
      <button class="w3-button w3-center w3-mobile w3-block <?php echo $GLOBALS['optionsDB']['colorBtnEdit']; ?>" type="submit" name="id" value="<?php echo $this->Index; ?>">bearbeiten</button>
      </form>
      </div>
      </div>
        <?php
    }
};
?>
