<?php
class Termin
{
    private $_data = array('Index' => null, 'Datum' => null, 'Uhrzeit' => null, 'Uhrzeit2' => null, 'Name' => null, 'Auftritt' => null, 'Ort1' => null, 'Ort2' => null, 'Ort3' => null, 'Ort4' => null, 'Beschreibung' => null, 'published' => null, 'Wert' => null, 'new' => null);
    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'Datum':
	    case 'Uhrzeit':
	    case 'Uhrzeit2':
	    case 'Name':
	    case 'Auftritt':
	    case 'Ort1':
	    case 'Ort2':
	    case 'Ort3':
	    case 'Ort4':
	    case 'Beschreibung':
	    case 'published':
	    case 'Wert':
	    case 'new':
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
	    case 'Datum':
		$this->_data[$key] = trim($val);
		break;
	    case 'Uhrzeit':
		$this->_data[$key] = trim($val);
		break;
	    case 'Uhrzeit2':
		$this->_data[$key] = trim($val);
		break;
	    case 'Name':
		$this->_data[$key] = trim($val);
		break;
	    case 'Beschreibung':
		$this->_data[$key] = trim($val);
		break;
	    case 'Auftritt':
		$this->_data[$key] = (bool)$val;
		break;
	    case 'Ort1':
		$this->_data[$key] = trim($val);
		break;
	    case 'Ort2':
		$this->_data[$key] = trim($val);
		break;
	    case 'Ort3':
		$this->_data[$key] = trim($val);
		break;
	    case 'Ort4':
		$this->_data[$key] = trim($val);
		break;
	    case 'published':
		$this->_data[$key] = (bool)$val;
		break;
	    case 'new':
		$this->_data[$key] = (bool)$val;
		break;
	    case 'Wert':
		$this->_data[$key] = (int)$val;
		break;
            default:
		break;
        }	
    }
    public function getVars() {
        return sprintf("Termin-ID: %d, Datum: %s, Beginn: %s, Ende: %s, Name: %s, Auftritt: %s, Ort1: %s, Ort2: %s, Ort3: %s, Ort4: %s, Beschreibung: %s, sichtbar: %s",
        $this->Index,
	    $this->Datum,
	    $this->Uhrzeit,
	    $this->Uhrzeit2,
	    $this->Name,
	    bool2string($this->Auftritt),
	    $this->Ort1,
	    $this->Ort2,
	    $this->Ort3,
	    $this->Ort4,
	    $this->Beschreibung,
	    bool2string($this->published)
        );
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
        if(!$this->Datum) return false;
        if(!$this->Name) return false;
        return true;
    }
    protected function insert() {
        $sql = sprintf('INSERT INTO `%sTermine` (`Datum`, `Uhrzeit`, `Uhrzeit2`, `Name`, `Beschreibung`, `Auftritt`, `Ort1`, `Ort2`, `Ort3`, `Ort4`, `published`) VALUES ("%s", %s, %s, "%s", "%s", "%d", "%s", "%s", "%s", "%s", "%d");',
        $GLOBALS['dbprefix'],
		       mysqli_real_escape_string($GLOBALS['conn'], $this->Datum),
		       $this->Uhrzeit == '' ? 'NULL': "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->Uhrzeit)."\"",
		       $this->Uhrzeit2 == '' ? 'NULL': "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->Uhrzeit2)."\"",
		       mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
		       mysqli_real_escape_string($GLOBALS['conn'], $this->Beschreibung),
		       $this->Auftritt,
		       mysqli_real_escape_string($GLOBALS['conn'], $this->Ort1),
		       mysqli_real_escape_string($GLOBALS['conn'], $this->Ort2),
		       mysqli_real_escape_string($GLOBALS['conn'], $this->Ort3),
		       mysqli_real_escape_string($GLOBALS['conn'], $this->Ort4),
		       $this->published
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }
    protected function update() {
        $sql = sprintf('UPDATE `%sTermine` SET `Datum` = "%s", `Uhrzeit` = %s, `Uhrzeit2` = %s, `Name` = "%s", `Beschreibung` = "%s", `Auftritt` = "%d", `Ort1` = "%s", `Ort2` = "%s", `Ort3` = "%s", `Ort4` = "%s", `published` = "%d", `new` = "%d" WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
		       mysqli_real_escape_string($GLOBALS['conn'], $this->Datum),
		       $this->Uhrzeit == '' ? 'NULL': "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->Uhrzeit)."\"",
		       $this->Uhrzeit2 == '' ? 'NULL': "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->Uhrzeit2)."\"",
		       mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
		       mysqli_real_escape_string($GLOBALS['conn'], $this->Beschreibung),
		       $this->Auftritt,
		       mysqli_real_escape_string($GLOBALS['conn'], $this->Ort1),
		       mysqli_real_escape_string($GLOBALS['conn'], $this->Ort2),
		       mysqli_real_escape_string($GLOBALS['conn'], $this->Ort3),
		       mysqli_real_escape_string($GLOBALS['conn'], $this->Ort4),
		       $this->published,
		       $this->new,
		       $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        return true;
    }
    public function delete() {
        if(!$this->Index) return false;
        $sql = sprintf('DELETE FROM `%sTermine` WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;

        $sql = sprintf('DELETE FROM `%sMeldungen` WHERE `Termin` = "%d";',
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
            $this->_data[$key] = $val;
        }
    }
    public function load_by_id($Index) {
        $Index = (int) $Index;
        $sql = sprintf('SELECT * FROM `%sTermine` WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
		       $Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = mysqli_fetch_array($dbr);
        if(is_array($row)) {
            $this->fill_from_array($row);
        }
        if(isset($_POST['proxy'])) {
            $user = $_POST['proxy'];
        }
        elseif(isset($_GET['user'])) {
            $user = $_GET['user'];
        }
        elseif(isset($_SESSION['userid'])) {
            $user = $_SESSION['userid'];
        }
        if($user > 0) {
            $sql = sprintf('SELECT `Wert` FROM `%sMeldungen` WHERE `Termin` = "%d" AND `User` = "%d";',
            $GLOBALS['dbprefix'],
            $Index,
            $user
            );
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
            sqlerror();
            $row = mysqli_fetch_array($dbr);
            if(is_array($row)) {
                $this->fill_from_array($row);
            }
        }
    }
    public function setOld() {
        $this->new = 0;
        $this->save();
    }
    public function printMailLine() {
        $str="";
        $str=$str."<div class=\"w3-row ".$GLOBALS['commonColors']['MailnewAppmnt']." w3-mobile w3-border-black w3-padding\">";
        $str=$str."\t<div class=\"w3-col l3 m3 s6\"><b>".$this->Name."</b></div>";
        if($this->Uhrzeit) {
            $str=$str."  <div class=\"w3-col l3 m3 s6\">".germanDate($this->Datum, 1).", ".sql2time($this->Uhrzeit);
            if($this->Uhrzeit2) $str=$str." - ".sql2time($this->Uhrzeit2);
            $str=$str."</div>";
        }
        else {
            $str=$str."  <div class=\"w3-col l3 m3 s6\">".germanDate($this->Datum, 1)."</div>";
        }
        $str=$str."\t<div class=\"w3-col l3 m3 s6\">".$this->Ort1."</div>";
        $str=$str."\t<div class=\"w3-col l3 m3 s6\">".$this->Beschreibung."</div>";
        $str=$str."</div>";

        return $str;
    }
    public function printBasicTableLine() {
        $opacity = "";
        if(!$this->published) {
            $opacity = $GLOBALS['commonColors']['AppmntUnpublished'];
        }
        $str="";
        if($this->Wert) {
            $str=$str."<div id=\"entry".$this->Index."\" class=\"w3-row ".$GLOBALS['commonColors']['Hover']." w3-padding w3-mobile w3-border-bottom w3-border-black ".$opacity." ";
            switch($this->Wert) {
		case 1:
                    $str=$str.$GLOBALS['commonColors']['AppmntYes'];
                    break;
		case 2:
                    $str=$str.$GLOBALS['commonColors']['AppmntNo'];
                    break;
		case 3:
                    $str=$str.$GLOBALS['commonColors']['AppmntMaybe'];
                    break;
		default:
                    $str=$str.$GLOBALS['commonColors']['AppmntDefault'];
            }
            $str=$str."\">\n";
        }
        else if($this->Auftritt) {
            $str=$str."<div id=\"entry".$this->Index."\" class=\"w3-row ".$GLOBALS['commonColors']['Hover']." w3-padding ".$GLOBALS['commonColors']['AppmntConcert']." w3-mobile w3-border-bottom w3-border-black ".$opacity." \">\n";
        }
        else {
            $str=$str."<div id=\"entry".$this->Index."\" class=\"w3-row ".$GLOBALS['commonColors']['Hover']." w3-padding ".$GLOBALS['commonColors']['AppmntConcert']." w3-mobile w3-border-bottom w3-border-black ".$opacity." \">\n";            
        }
        $str=$str."  <div onclick=\"document.getElementById('id".$this->Index."').style.display='block'\" class=\"w3-col l3 w3-container\"><b>".$this->Name."</b></div>\n";
        if($this->Uhrzeit) {
            $str=$str."  <div class=\"w3-col l3 w3-container\">".germanDate($this->Datum, 1).", ".sql2timeRaw($this->Uhrzeit);
            if($this->Uhrzeit2) $str=$str." - ".sql2time($this->Uhrzeit2);
            $str=$str."</div>\n";
        }
        else {
            $str=$str."  <div class=\"w3-col l3 w3-container\">".germanDate($this->Datum, 1)."</div>\n";
        }
        $str=$str."  <div class=\"w3-col l3 w3-container\">".$this->Ort1."</div>\n";
        $str=$str."<div class=\"w3-col l3 w3-row w3-mobile\">";
        /* $str=$str."<form action=\"#entry".$this->Index."\" method=\"POST\">"; */
        $str=$str."<input type=\"hidden\" name=\"Index\" value=\"".$this->Index."\">";
        if(isset($_POST['proxy'])) {
            /* $str=$str."<input type=\"hidden\" name=\"proxy\" value=\"".$_POST['proxy']."\">"; */
            $user = $_POST['proxy'];
        }
        elseif(isset($_GET['user'])) {
            $user = $_GET['user'];
        }
        else {
            $user = $_SESSION['userid'];                
        }
        $str=$str."<button class=\"w3-btn ";
        if($this->Wert > 1) {
            $str=$str.$GLOBALS['commonColors']['Disabled'];
        }
        else {
            $str=$str.$GLOBALS['commonColors']['AppmntBtnYes'];
        }
        $str=$str." w3-border w3-border-black w3-margin-left w3-margin-top w3-margin-right w3-center w3-col s3 m3 l2\" name=\"meldung\" value=\"1\" onclick=\"melde(".$user.", ".$this->Index.", 1)\">&#10004;</button>";
        $str=$str."<button class=\"w3-btn ";
        if($this->Wert == 1 || $this->Wert == 3 ) $str=$str.$GLOBALS['commonColors']['Disabled'];
        else $str=$str.$GLOBALS['commonColors']['AppmntBtnNo'];
        $str=$str." w3-border w3-border-black w3-margin-top w3-center w3-col s3 m3 l2\" name=\"meldung\" value=\"2\" onclick=\"melde(".$user.", ".$this->Index.", 2)\">&#10008;</button>";
        $str=$str."<button class=\"w3-btn ";
        if($this->Wert == 1 || $this->Wert == 2 ) $str=$str.$GLOBALS['commonColors']['Disabled'];
        else $str=$str.$GLOBALS['commonColors']['AppmntBtnMaybe'];
        $str=$str." w3-border w3-border-black w3-margin-left w3-margin-top w3-center w3-col s3 m3 l2\" name=\"meldung\" value=\"3\" onclick=\"melde(".$user.", ".$this->Index.", 3)\"><b>?</b></button>";
        /* $str=$str."</form>"; */
        $str=$str."</div>";
        $str=$str."</div>";

        $str=$str."<div id=\"id".$this->Index."\" class=\"w3-modal\">";
		$str=$str."<div class=\"w3-modal-content\">";
        $str=$str."<header class=\"w3-container ".$GLOBALS['commonColors']['titlebar']."\">";
        $str=$str."<span onclick=\"document.getElementById('id".$this->Index."').style.display='none'\""; 
        $str=$str."class=\"w3-button w3-display-topright\">&times;</span>";
        $str=$str."<h2>".$this->Name."</h2>";
        $str=$str."</header>";
        $str=$str."<div class=\"w3-container w3-row w3-margin\">";
        $str=$str."<div class=\"w3-col l3\">Datum:</div><div class=\"w3-col l9\"><b>".germanDate($this->Datum, 1)."</b></div>";
        $str=$str."</div>";
        $str=$str."<div class=\"w3-container w3-row w3-margin\">";
        $str=$str."<div class=\"w3-col l3\">Beginn:</div><div class=\"w3-col l9\"><b>".sql2time($this->Uhrzeit)."</b></div>";
        $str=$str."</div>";
        $str=$str."<div class=\"w3-container w3-row w3-margin\">";
        $str=$str."<div class=\"w3-col l3\">Ende:</div><div class=\"w3-col l9\"><b>".sql2time($this->Uhrzeit2)."</b></div>";
        $str=$str."</div>";
        $str=$str."<div class=\"w3-container w3-row w3-margin\">";
        $str=$str."<div class=\"w3-col l3\">Beschreibung:</div><div class=\"w3-col l9\"><b>".$this->Beschreibung."</b></div>";
        $str=$str."</div>";
        $str=$str."<div class=\"w3-container w3-row w3-margin\">";
        $str=$str."<div class=\"w3-col l3\">Ort:</div><div class=\"w3-col l9\"><b>".$this->Ort1."</b><br>".$this->Ort2."<br>".$this->Ort3."<br>".$this->Ort4."</div>";
        $str=$str."</div>";
        if($GLOBALS['googlemapsapi'] && ($this->Ort1 || $this->Ort2)) {
            $str=$str."<div class=\"w3-container w3-row w3-margin\">";
            $str=$str."<div class=\"w3-col l3\">Karte:</div><div class=\"w3-col l9\"><iframe width=\"auto\" height=\"auto\" frameborder=\"0\" style=\"border:0\" src=\"https://www.google.com/maps/embed/v1/place?key=".$GLOBALS['googlemapsapi']."&q=".$this->Ort1."+".$this->Ort2."+".$this->Ort3."+".$this->Ort4."\" allowfullscreen></iframe></div>";
            $str=$str."</div>";
        }
        $str=$str."<div class=\"w3-container w3-row w3-margin\">";
        $str=$str."<div class=\"w3-col l3\">Auftritt:</div><div class=\"w3-col l9\"><b>".bool2string($this->Auftritt)."</b></div>";
        $str=$str."</div>";
        if($_SESSION['admin']) {
            $str=$str."<div class=\"w3-container w3-row w3-margin\">";
            $str=$str."<div class=\"w3-col l3\">sichtbar:</div><div class=\"w3-col l9\"><b>".bool2string($this->published)."</b></div>";
            $str=$str."</div>";
            $str=$str."<div class=\"w3-container w3-row w3-margin\">";
            $str=$str."<div class=\"w3-col l3\">neu:</div><div class=\"w3-col l9\"><b>".bool2string($this->new)."</b></div>";
            $str=$str."</div>";
            $str=$str."<form class=\"w3-center w3-bar w3-mobile\" action=\"new-termin.php\" method=\"POST\">";
            $str=$str."<button class=\"w3-button w3-center w3-mobile w3-block ".$GLOBALS['commonColors']['BtnEdit']."\" type=\"submit\" name=\"id\" value=\"".$this->Index."\">bearbeiten</button>";
        }
        $str=$str."</form>";
		$str=$str."</div>";
	    $str=$str."</div>";
        return $str;
	}
    public function printMyResponseLine() {
        $u = new User;
        $u->load_by_id($_SESSION['userid']);
        return $this->getResponseLine($u->getRegister());
    }
	public function printResponseLine() {
        return $this->getResponseLine(0);
    }
	public function getResponseLine($filterregister) {
        $str = "<div onclick=\"document.getElementById('id".$this->Index."').style.display='block'\" class=\"w3-container w3-margin-top w3-border-top w3-border-black w3-center ".$GLOBALS['commonColors']['titlebar']."\"><h3>".$this->Name."</h3><p>".germanDate($this->Datum, 1)."</p></div>\n";
        $str=$str."<div onclick=\"document.getElementById('id".$this->Index."').style.display='block'\" class=\"w3-container w3-border-bottom w3-border-black\">\n";
        $whoYes = '';
        $whoNo = '';
        $whoMaybe = '';
        if($this->Auftritt) {
            if($filterregister) {
                $sql = sprintf("SELECT * FROM `%sRegister` WHERE `Name` != 'Dirigent' AND `Index` = '%d' ORDER BY `Sortierung`;",
                $GLOBALS['dbprefix'],
                $filterregister
                );
            }
            else {
                $sql = sprintf("SELECT * FROM `%sRegister` WHERE `Name` != 'Dirigent' ORDER BY `Sortierung`;",
                $GLOBALS['dbprefix']
                );
            }
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
            sqlerror();
            $sja=0;
            $sall=0;
            $snReg=0;
            $snein=0;
            $svielleicht=0;
            while($row = mysqli_fetch_array($dbr)) {
                $register = new Register();
                $register->load_by_id($row['Index']);
                $nReg = $register->members();
                $snReg+=$nReg;
                $sql = sprintf("SELECT * FROM `%sMeldungen`
INNER JOIN (SELECT `Index` AS `uIndex`, `Vorname`, `Nachname`, `Instrument` FROM `%sUser`) `%sUser` ON `User` = `uIndex`
INNER JOIN (SELECT `Index` AS `iIndex`, `Register`, `Name` AS `iName` FROM `%sInstrument`) `%sInstrument` ON `Instrument` = `iIndex`
INNER JOIN (SELECT `Index` AS `rIndex`, `Name` AS `rName`, `Sortierung` FROM `%sRegister`) `%sRegister` ON `Register` = `rIndex`
WHERE `Termin` = '%d'
AND `rIndex` = '%d'
ORDER BY `Nachname`, `Vorname`",
                $GLOBALS['dbprefix'],
                $GLOBALS['dbprefix'],
                $GLOBALS['dbprefix'],
                $GLOBALS['dbprefix'],
                $GLOBALS['dbprefix'],
                $GLOBALS['dbprefix'],
                $GLOBALS['dbprefix'],
                $this->Index,
                $row['Index']
                );
                $dbr2 = mysqli_query($GLOBALS['conn'], $sql);
                sqlerror();
                $ja=0;
                $nein=0;
                $vielleicht=0;
                while($row2 = mysqli_fetch_array($dbr2)) {
                    $antwort='';
                    switch($row2['Wert']) {
                    case 1:
                        $ja++;
                        $sja++;
                        $antwort='ja';
                        $whoYes = $whoYes."<div class=\"w3-row ".$GLOBALS['commonColors']['AppmntBtnYes']."\"><div class=\"w3-col l6 m6 s6\">".$row2['Vorname']." ".$row2['Nachname']."</div><div class=\"w3-col l6 m6 s6\">".$row2['iName']."</div></div>\n";
                        break;
                    case 2:
                        $nein++;
                        $snein++;
                        $antwort='nein';
                        $whoNo = $whoNo."<div class=\"w3-row ".$GLOBALS['commonColors']['AppmntBtnNo']."\"><div class=\"w3-col l6 m6 s6\">".$row2['Vorname']." ".$row2['Nachname']."</div><div class=\"w3-col l6 m6 s6\">".$row2['iName']."</div></div>\n";
                        break;
                    case 3:
                        $vielleicht++;
                        $svielleicht++;
                        $antwort='vielleicht';
                        $whoMaybe = $whoMaybe."<div class=\"w3-row ".$GLOBALS['commonColors']['AppmntBtnMaybe']."\"><div class=\"w3-col l6 m6 s6\">".$row2['Vorname']." ".$row2['Nachname']."</div><div class=\"w3-col l6 m6 s6\">".$row2['iName']."</div></div>\n";
                        break;
                    default:
                        break;
                    }
                }
                $all = $ja+$nein+$vielleicht;
                $sall=$sall+$all;
                if($filterregister) {
                    $str=$str.$whoYes;
                    $str=$str.$whoNo;
                    $str=$str.$whoMaybe;
                }
                else {
                    $str=$str."<div class=\"w3-row w3-border-bottom w3-border-black\"><div class=\"".$GLOBALS['commonColors']['Hover']." w3-col l7 m4 s4\">".$row['Name']."</div><div class=\"w3-col l2 m2 s2\">".$all." / ".sprintf("%02d", $nReg)."</div><div class=\"".$GLOBALS['commonColors']['AppmntBtnYes']." w3-col l1 m2 s2 w3-center w3-opacity-min\">&#10004; ".$ja."</div><div class=\"".$GLOBALS['commonColors']['AppmntBtnNo']." w3-col l1 m2 s2 w3-center w3-opacity-min\">&#10008; ".$nein."</div><div class=\"".$GLOBALS['commonColors']['AppmntBtnMaybe']." w3-col l1 m2 s2 w3-center w3-opacity-min\">? ".$vielleicht."</div></div>\n";
                }
            }
            if(!$filterregister) {
                $str=$str."<div class=\"w3-row\"><div class=\"".$GLOBALS['commonColors']['Hover']." w3-col l7 m4 s4\"><b>Summe</b></div><div class=\"w3-col l2 m2 s2\"><b>".$sall." / ".sprintf("%02d", $snReg)."</b></div><div class=\"".$GLOBALS['commonColors']['AppmntBtnYes']." w3-col l1 m2 s2 w3-center\">&#10004; ".$sja."</div><div class=\"".$GLOBALS['commonColors']['AppmntBtnNo']." w3-col l1 m2 s2 w3-center\">&#10008; ".$snein."</div><div class=\"".$GLOBALS['commonColors']['AppmntBtnMaybe']." w3-col l1 m2 s2 w3-center\">? ".$svielleicht."</div></div>\n";
            }
        }
        else {
            $sql = sprintf("SELECT * FROM `%sMeldungen`
INNER JOIN (SELECT `Index` AS `uIndex`, `Vorname`, `Nachname` FROM `%sUser`) `%sUser` ON `User` = `uIndex`
WHERE `Termin` = '%d' ORDER BY `Nachname`, `Vorname`;",
        $GLOBALS['dbprefix'],
        $GLOBALS['dbprefix'],
        $GLOBALS['dbprefix'],
            $this->Index
            );
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
            sqlerror();
            $ja=0;
            $nein=0;
            $vielleicht=0;
            while($row = mysqli_fetch_array($dbr)) {
                switch($row['Wert']) {
                case 1:
                    $ja++;
                    $antwort='ja';
                    $whoYes = $whoYes."<div class=\"w3-container ".$GLOBALS['commonColors']['AppmntBtnYes']."\"><div class=\"w3-row\"><div class=\"w3-col l12 m12 s12\">".$row['Vorname']." ".$row['Nachname']."</div></div></div>\n";
                    break;
                case 2:
                    $nein++;
                    $antwort='nein';
                    $whoNo = $whoNo."<div class=\"w3-container ".$GLOBALS['commonColors']['AppmntBtnNo']."\"><div class=\"w3-row\"><div class=\"w3-col l12 m12 s12\">".$row['Vorname']." ".$row['Nachname']."</div></div></div>\n";
                    break;
                case 3:
                    $vielleicht++;
                    $antwort='vielleicht';
                    $whoMaybe = $whoMaybe."<div class=\"w3-container ".$GLOBALS['commonColors']['AppmntBtnMaybe']."\"><div class=\"w3-row\"><div class=\"w3-col l12 m12 s12\">".$row['Vorname']." ".$row['Nachname']."</div></div></div>\n";
                    break;
                default:
                    break;
                }
            }
                $str=$str."<div class=\"w3-row\"><div class=\"w3-col l9 m6 s6\"><b>Summe</b></div><div class=\"".$GLOBALS['commonColors']['AppmntBtnYes']." w3-col l1 m2 s2 w3-center\">&#10004; ".$ja."</div><div class=\"".$GLOBALS['commonColors']['AppmntBtnNo']." w3-col l1 m2 s2 w3-center\">&#10008; ".$nein."</div><div class=\"".$GLOBALS['commonColors']['AppmntBtnMaybe']." w3-col l1 m2 s2 w3-center\">? ".$vielleicht."</div></div>\n";
        }
        $str=$str."</div>\n";

        $str=$str."<div id=\"id".$this->Index."\" class=\"w3-modal\">";
		$str=$str."<div class=\"w3-modal-content\">";
        $str=$str."<header class=\"w3-container ".$GLOBALS['commonColors']['titlebar']."\">";
        $str=$str."<span onclick=\"document.getElementById('id".$this->Index."').style.display='none'\""; 
        $str=$str."class=\"w3-button w3-display-topright\">&times;</span>";
        $str=$str."<h2>".$this->Name."</h2>";
        $str=$str."</header>";
        $str = $str."<div class=\"w3-container w3-margin-top\"><b>Zusagen</b></div>\n";
        $str = $str."<div class=\"w3-container\">";
        $str=$str.$whoYes;
        $str = $str."</div>";
        $str = $str."<div class=\"w3-container w3-margin-top\"><b>Absagen</b></div>\n";
        $str = $str."<div class=\"w3-container\">";
        $str=$str.$whoNo;
        $str = $str."</div>";
        $str = $str."<div class=\"w3-container w3-margin-top\"><b>unsicher</b></div>\n";
        $str = $str."<div class=\"w3-container\">";
        $str=$str.$whoMaybe;
        $str = $str."</div>";
        $str=$str."<div class=\"w3-container w3-margin-bottom\"><br />";
		$str=$str."</div>";
		$str=$str."</div>";
	    $str=$str."</div>";

        return $str;
    }
};
?>
