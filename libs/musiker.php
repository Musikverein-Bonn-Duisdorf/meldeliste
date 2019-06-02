<?php
class User
{
    private $_data = array('Index' => null, 'Nachname' => null, 'Vorname' => null, 'login' => null, 'Passhash' => null, 'activeLink' => null, 'Mitglied' => null, 'Instrument' => null, 'iName' => null, 'Stimme' => null, 'Email' => null, 'getMail' => null, 'Admin' => null);
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
	    case 'Stimme':
	    case 'Email':
	    case 'getMail':
	    case 'Admin':
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
            $this->_data[$key] = trim($val);
            break;
	    case 'Vorname':
            $this->_data[$key] = trim($val);
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
        default:
            break;
        }	
    }
    public function getVars() {
        return sprintf("User-ID: %d, Vorname: %s, Nachname: %s, Login: %s, Mitglied: %d, Istrument: %s, Email: %s, Mailverteiler: %s, Admin: %s",
        $this->Index,
        $this->Nachname,
        $this->Vorname,
        $this->login,
        bool2string($this->Mitglied),
        $this->iName,
        $this->Email,
        bool2string($this->getMail),
        bool2string($this->Admim)
        );
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
    public function passwd() {
        $this->Passhash = password_hash("1949eV", PASSWORD_DEFAULT);
        $this->update();
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
        $sql = sprintf('INSERT INTO `MVD`.`User` (`Nachname`, `Vorname`, `login`, `Passhash`, `activeLink`, `Mitglied`, `Instrument`, `Email`, `getMail`) VALUES ("%s", "%s", "%s", "%s", "%d", "%d", "%d", "%s", "%d");',
        mysqli_real_escape_string($GLOBALS['conn'], $this->Nachname),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Vorname),
        mysqli_real_escape_string($GLOBALS['conn'], $this->login),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Passhash),
        mysqli_real_escape_string($GLOBALS['conn'], $this->activeLink),
        $this->Mitglied,
        $this->Instrument,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Email),
        $this->getMail
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }
    protected function update() {
        $sql = sprintf('UPDATE `MVD`.`User` SET `Nachname` = "%s", `Vorname` = "%s", `login` = "%s", `Passhash` = "%s", `activeLink` = "%s", `Mitglied` = "%d", `Instrument` = "%d", `Stimme` = "%d", `Email` = "%s", `getMail` = "%d" WHERE `Index` = "%d";',
        mysqli_real_escape_string($GLOBALS['conn'], $this->Nachname),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Vorname),
        mysqli_real_escape_string($GLOBALS['conn'], $this->login),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Passhash),
        mysqli_real_escape_string($GLOBALS['conn'], $this->activeLink),
        $this->Mitglied,
        $this->Instrument,
        $this->Stimme,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Email),
        $this->getMail,
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if(!$dbr) return false;
        return true;
    }
    public function getName() {
        return $this->Vorname." ".$this->Nachname;
    }
    public function getRegister() {
        if(!$this->Instrument) return false;
        $sql = sprintf('SELECT * FROM `Instrument` WHERE `Index` = "%d";',
        $this->Instrument
        );        
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        $row = mysqli_fetch_array($dbr);
        if(is_array($row)) {
            return $row['Register'];
        }
        return 0;
    }
    public function delete() {
        if(!$this->Index) return false;
        $sql = sprintf('DELETE FROM `MVD`.`User` WHERE `Index` = "%d";',
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
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
        $sql = sprintf('SELECT * FROM `MVD`.`User` INNER JOIN (SELECT `Index` AS `iIndex`, `Name` AS `iName` FROM `Instrument`) `Instrument` ON `iIndex` = `Instrument` WHERE `Index` = "%d";',
        $Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        $row = mysqli_fetch_array($dbr);
        if(is_array($row)) {
            $this->fill_from_array($row);
        }
    }

    public function printTableLine() {
        if($this->Mitglied) {
            echo "<div class=\"w3-row w3-hover-gray w3-padding w3-pale-yellow w3-mobile w3-border-bottom w3-border-black\">\n";            
        }
        else {
            echo "<div class=\"w3-row w3-hover-gray w3-padding w3-light-pale-green w3-mobile w3-border-bottom w3-border-black\">\n";            
        }
        echo "  <div onclick=\"document.getElementById('id".$this->Index."').style.display='block'\" class=\"w3-col l3 w3-container\"><b>".$this->Vorname." ".$this->Nachname."</b></div>\n";
        echo "  <div class=\"w3-col l3 w3-container\">".$this->iName."</div>\n";
        echo "  <div class=\"w3-col l3 w3-container\"><a href=\"mailto:\"".$this->Email."\">".$this->Email."</a></div>\n";
        echo "</div>";
        ?>
        <div id="id<?php echo $this->Index; ?>" class="w3-modal">
        <div class="w3-modal-content">

        <header class="w3-container w3-teal"> 
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
      <div class="w3-col l6">erhält Emails von der Meldeliste:</div><div class="w3-col l6"><b><?php echo bool2string($this->getMail); ?></b></div>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l6">Emailadresse:</div><div class="w3-col l6"><b><a href="mailto:<?php echo $this->Email; ?>"><?php echo $this->Email; ?></a></b></div>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l6">Loginname:</div><div class="w3-col l6"><b><?php echo $this->login; ?></b></div>
    </div>
      <form class="w3-center w3-bar w3-mobile" action="new-musiker.php" method="POST">
      <button class="w3-button w3-center w3-mobile w3-block w3-teal" type="submit" name="id" value="<?php echo $this->Index; ?>">bearbeiten</button>
      </form>
      </div>
      </div>
        <?php
    }
};
?>
