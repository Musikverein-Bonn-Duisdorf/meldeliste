<?php
class User
{
    private $_data = array('Index' => null, 'Nachname' => null, 'Vorname' => null, 'login' => null, 'Passhash' => null, 'activeLink' => null, 'Mitglied' => null, 'Instrument' => null, 'iName' => null, 'Email' => null, 'getMail' => null, 'Admin' => null, 'singleUsePW' => null, 'RegisterLead' => null, 'LastLogin' => null, 'Joined' => null);
    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'Nachname':
	    case 'Vorname':
        case 'login':
	    case 'Passhash':
	    case 'activeLink':
	    case 'Mitglied':
	    case 'Instrument':
	    case 'iName':
	    case 'Email':
	    case 'getMail':
	    case 'Admin':
        case 'singleUsePW':
        case 'RegisterLead':
        case 'LastLogin':
        case 'Joined':
            return $this->_data[$key];
            break;
        default:
            break;
        }
    }
    public function __set($key, $val) {
        switch($key) {
	    case 'Index':
            $this->_data[$key] = (int)$val;
            break;
	    case 'Nachname':
            $this->_data[$key] = htmlentities(trim($val));
            break;
	    case 'Vorname':
            $this->_data[$key] = htmlentities(trim($val));
            break;
	    case 'login':
            $this->_data[$key] = trim($val);
            break;
	    case 'Passhash':
            $this->_data[$key] = $val;
            break;
	    case 'activeLink':
            $this->_data[$key] = $val;
            break;
	    case 'Mitglied':
            $this->_data[$key] = (bool) $val;
            break;
	    case 'Instrument':
            $this->_data[$key] = (int) $val;
            break;
	    case 'iName':
            $this->_data[$key] = trim($val);
            break;
	    case 'Email':
            $this->_data[$key] = trim($val);
            break;
	    case 'getMail':
            $this->_data[$key] = (bool) $val;
            break;
	    case 'Admin':
            $this->_data[$key] = (bool) $val;
            break;
	    case 'singleUsePW':
            $this->_data[$key] = (bool) $val;
            break;
	    case 'RegisterLead':
            $this->_data[$key] = (bool)$val;
            break;
	    case 'LastLogin':
            $this->_data[$key] = trim($val);
            break;
        default:
            break;
        }	
    }
    public function getVars() {
        return sprintf("User-ID: %d, Vorname: %s, Nachname: %s, Login: %s, Mitglied: %s, Istrument: %s, Email: %s, Mailverteiler: %s, Admin: %s, RegisterLead: %d, LastLogin: %s",
        $this->Index,
        $this->Vorname,
        $this->Nachname,
        $this->login,
        bool2string($this->Mitglied),
        $this->iName,
        $this->Email,
        bool2string($this->getMail),
        bool2string($this->Admim),
        $this->RegisterLead,
        $this->LastLogin
        );
    }
    public function getShort() {
        if(strlen($this->Vorname) >=2) {
            $short1 = substr($this->Vorname,0,2);
            echo $short1;
        }
        else {
            $short1 = $this->Vorname;
        }
        if(strlen($this->Nachname) >=2) {
            $narray = explode(" ", $this->Nachname);
            $short2 = substr($narray[sizeof($narray)-1],0,2);
            echo $short2;
        }
        else {
            $short2 = $this->Nachname;
        }
        echo $short1.$short2;
        return $short1.$short2;
    }
    public function save() {
        if($this->activeLink == '') $this->generateLink();
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
                $mail->singleUser($this->Index, $GLOBALS['commonStrings']['newPWSubject'], $GLOBALS['commonStrings']['newPWText']."\n\nBenutzername: ".$this->login."\nPasswort: ".$password);
            }
            else {
                $this->generateLink();
                $this->singleUsePW(0);
                $this->update();
                $mail->singleUser($this->Index, $GLOBALS['commonStrings']['PWChangeSubject'], $GLOBALS['commonStrings']['PWChangeText']."\n\nBenutzername: ".$this->login);
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
    protected function insert() {
        $sql = sprintf('INSERT INTO `%sUser` (`Nachname`, `Vorname`, `login`, `Passhash`, `activeLink`, `Mitglied`, `Instrument`, `Email`, `getMail`, `Admin`, `RegisterLead`) VALUES ("%s", "%s", "%s", "%s", "%s", "%d", "%d", "%s", "%d", "%d", "%d");',
        $GLOBALS['dbprefix'],
        mysqli_real_escape_string($GLOBALS['conn'], $this->Nachname),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Vorname),
        mysqli_real_escape_string($GLOBALS['conn'], $this->login),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Passhash),
        mysqli_real_escape_string($GLOBALS['conn'], $this->activeLink),
        $this->Mitglied,
        $this->Instrument,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Email),
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
        $sql = sprintf('UPDATE `%sUser` SET `Nachname` = "%s", `Vorname` = "%s", `login` = "%s", `Passhash` = "%s", `activeLink` = "%s", `Mitglied` = "%d", `Instrument` = "%d", `Email` = "%s", `getMail` = "%d", `Admin` = "%d", `RegisterLead` = "%d" WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        mysqli_real_escape_string($GLOBALS['conn'], $this->Nachname),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Vorname),
        mysqli_real_escape_string($GLOBALS['conn'], $this->login),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Passhash),
        mysqli_real_escape_string($GLOBALS['conn'], $this->activeLink),
        $this->Mitglied,
        $this->Instrument,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Email),
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
    public function delete() {
        if(!$this->Index) return false;
        $sql = sprintf('DELETE FROM `%sUser` WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = null;
        $logentry = new Log;
        $logentry->DBdelete($this->getVars());
        return true;
    }
    public function fill_from_array($row) {
        foreach($row as $key => $val) {
                $this->_data[$key] = $val;
        }
    }
    public function load_by_id($Index) {
        $Index = (int) $Index;
        $sql = sprintf('SELECT * FROM `%sUser` INNER JOIN (SELECT `Index` AS `iIndex`, `Name` AS `iName` FROM `%sInstrument`) `%sInstrument` ON `iIndex` = `Instrument` WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        $GLOBALS['dbprefix'],
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

    public function printTableLine() {
        if($this->Mitglied) {
            echo "<div class=\"w3-row ".$GLOBALS['commonColors']['Hover']." w3-padding ".$GLOBALS['commonColors']['UserMember']." w3-mobile w3-border-bottom w3-border-black\">\n";
        }
        else {
            echo "<div class=\"w3-row ".$GLOBALS['commonColors']['Hover']." w3-padding ".$GLOBALS['commonColors']['UserNoMember']." w3-mobile w3-border-bottom w3-border-black\">\n";            
        }
        echo "  <div onclick=\"document.getElementById('id".$this->Index."').style.display='block'\" class=\"w3-col l3 w3-container\"><b>".$this->Vorname." ".$this->Nachname."</b></div>\n";
        echo "  <div class=\"w3-col l3 w3-container\">".$this->iName."</div>\n";
        echo "  <div class=\"w3-col l3 w3-container\"><a href=\"mailto:".$this->Email."\">".$this->Email."</a></div>\n";
        echo "  <div class=\"w3-col l3 w3-container\">".germanDate($this->LastLogin, 1)."</div>\n";
        echo "</div>";
        ?>
        <div id="id<?php echo $this->Index; ?>" class="w3-modal">
        <div class="w3-modal-content">

        <header class="w3-container <?php echo $GLOBALS['commonColors']['titlebar']; ?>"> 
      <span onclick="document.getElementById('id<?php echo $this->Index; ?>').style.display='none'" 
      class="w3-button w3-display-topright">&times;</span>
      <h2><?php echo $this->Vorname." ".$this->Nachname; ?></h2>
    </header>
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
      if($_SESSION['admin']) {
      ?>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l6">Admin:</div><div class="w3-col l6"><b><?php echo bool2string($this->Admin); ?></b></div>
    </div>
    <div class="w3-container w3-row w3-margin">
          <div class="w3-col l6">Account erstellt:</div><div class="w3-col l6"><b><?php echo germanDate($this->Joined, 1); ?></b></div>
    </div>
      <?php
      }
      ?>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l6">Emailadresse:</div><div class="w3-col l6"><b><a href="mailto:<?php echo $this->Email; ?>"><?php echo $this->Email; ?></a></b></div>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l6">Loginname:</div><div class="w3-col l6"><b><?php echo $this->login; ?></b></div>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l6">Letzter Login:</div><div class="w3-col l6"><b><?php echo germanDate($this->LastLogin, 1); ?></b></div>
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
      <button class="w3-button w3-center w3-mobile w3-block <?php echo $GLOBALS['commonColors']['BtnEdit']; ?>" type="submit" name="id" value="<?php echo $this->Index; ?>">bearbeiten</button>
      </form>
      </div>
      </div>
        <?php
    }
};
?>
