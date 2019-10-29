<?php
class Termin
{
    private $_data = array('Index' => null, 'Datum' => null, 'Uhrzeit' => null, 'Uhrzeit2' => null, 'Abfahrt' => null, 'Vehicle' => null, 'Name' => null, 'Auftritt' => null, 'Ort1' => null, 'Ort2' => null, 'Ort3' => null, 'Ort4' => null, 'Beschreibung' => null, 'published' => null, 'Wert' => null, 'Children' => null, 'Guests' => null, 'new' => null, 'vName' => null);
    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'Datum':
	    case 'Uhrzeit':
	    case 'Uhrzeit2':
	    case 'Abfahrt':
	    case 'Vehicle':
	    case 'Name':
	    case 'Auftritt':
	    case 'Ort1':
	    case 'Ort2':
	    case 'Ort3':
	    case 'Ort4':
	    case 'Beschreibung':
	    case 'published':
	    case 'Wert':
	    case 'Children':
	    case 'Guests':
	    case 'vName':
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
	    case 'Uhrzeit2':
	    case 'Abfahrt':
            $this->_data[$key] = trim($val);
            break;
	    case 'Name':
	    case 'Beschreibung':
	    case 'vName':
            $this->_data[$key] = htmlentities(trim($val));
            break;
	    case 'Auftritt':
            $this->_data[$key] = (bool)$val;
            break;
	    case 'Ort1':
	    case 'Ort2':
	    case 'Ort3':
	    case 'Ort4':
            $this->_data[$key] = htmlentities(trim($val));
            break;
	    case 'published':
	    case 'new':
            $this->_data[$key] = (bool)$val;
            break;
	    case 'Vehicle':
	    case 'Wert':
	    case 'Children':
	    case 'Guests':
            $this->_data[$key] = (int)$val;
            break;
        default:
            break;
        }	
    }
    public function getVars() {
        if(!$this->vName) {
            $sql = sprintf('SELECT * FROM `%svehicle` WHERE `Index` = %d;',
            $GLOBALS['dbprefix'],
            $this->Vehicle
            );
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
            sqlerror();
            $row = mysqli_fetch_array($dbr);
            $this->vName = $row['Name'];
        }
        return sprintf("Termin-ID: %d, Datum: %s, Beginn: %s, Ende: %s, Abfahrt: %s, mit: %s, Name: %s, Auftritt: %s, Ort1: %s, Ort2: %s, Ort3: %s, Ort4: %s, Beschreibung: %s, sichtbar: %s",
        $this->Index,
        $this->Datum,
        $this->Uhrzeit,
        $this->Uhrzeit2,
        $this->Abfahrt,
        $this->vName,
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
        $sql = sprintf('INSERT INTO `%sTermine` (`Datum`, `Uhrzeit`, `Uhrzeit2`, `Abfahrt`, `Vehicle`, `Name`, `Beschreibung`, `Auftritt`, `Ort1`, `Ort2`, `Ort3`, `Ort4`, `published`) VALUES ("%s", %s, %s, %s, "%d", "%s", "%s", "%d", "%s", "%s", "%s", "%s", "%d");',
        $GLOBALS['dbprefix'],
        mysqli_real_escape_string($GLOBALS['conn'], $this->Datum),
        $this->Uhrzeit == '' ? 'NULL': "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->Uhrzeit)."\"",
        $this->Uhrzeit2 == '' ? 'NULL': "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->Uhrzeit2)."\"",
        $this->Abfahrt == '' ? 'NULL': "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->Abfahrt)."\"",
        $this->Vehicle,
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
        $sql = sprintf('UPDATE `%sTermine` SET `Datum` = "%s", `Uhrzeit` = %s, `Uhrzeit2` = %s, `Abfahrt` = %s, `Vehicle`= "%d", `Name` = "%s", `Beschreibung` = "%s", `Auftritt` = "%d", `Ort1` = "%s", `Ort2` = "%s", `Ort3` = "%s", `Ort4` = "%s", `published` = "%d", `new` = "%d" WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        mysqli_real_escape_string($GLOBALS['conn'], $this->Datum),
        $this->Uhrzeit == '' ? 'NULL': "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->Uhrzeit)."\"",
        $this->Uhrzeit2 == '' ? 'NULL': "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->Uhrzeit2)."\"",
        $this->Abfahrt == '' ? 'NULL': "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->Abfahrt)."\"",
        $this->Vehicle,
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
        $sql = sprintf('SELECT * FROM `%sTermine` INNER JOIN (SELECT `Index` AS `vIndex`, `Name` AS `vName` FROM `%svehicle`) `%svehicle` ON `vIndex` = `Vehicle` WHERE `Index` = "%d";',
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
            $sql = sprintf('SELECT `Wert`, `Children`, `Guests` FROM `%sMeldungen` WHERE `Termin` = "%d" AND `User` = "%d";',
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
            $str=$str."\t<div class=\"w3-col l3 m3 s6\">".germanDate($this->Datum, 1).", ".sql2time($this->Uhrzeit);
            if($this->Uhrzeit2) $str=$str." - ".sql2time($this->Uhrzeit2);
            $str=$str."</div>";
        }
        else {
            $str=$str."\t<div class=\"w3-col l3 m3 s6\">".germanDate($this->Datum, 1)."</div>";
        }
        $str=$str."\t<div class=\"w3-col l3 m3 s6\">".$this->Ort1."</div>";
        $str=$str."\t<div class=\"w3-col l3 m3 s6\">".$this->Beschreibung."</div>";
        $str=$str."</div>";

        return $str;
    }
    public function printMailResponse() {
        $wertval = array('Zusagen', 'Absagen', 'unsicher');
        $colorval = array($GLOBALS['commonColors']['AppmntYes'], $GLOBALS['commonColors']['AppmntNo'], $GLOBALS['commonColors']['AppmntMaybe']);
        $colsize=4;
        if($GLOBALS['optionsDB']['showChildOption'] || $GLOBALS['optionsDB']['showGuestOption']) {
            $colsize=3;
        }
        $str="<div class=\"w3-container ".$GLOBALS['commonColors']['titlebar']."\"><h3>".$this->Name."</h3></div>";
        $sumJa=0;
        $sumJaG=0;
        $sumJaC=0;
        $sumV=0;
        $sumVG=0;
        $sumVC=0;
        for($wert = 1; $wert <= 3; $wert++) {
            $sql = sprintf('SELECT * FROM `%sMeldungen` INNER JOIN (SELECT `Index` AS `uIndex`, `Vorname`, `Nachname`, `Instrument` FROM `%sUser`) `%sUser` ON `User` = `uIndex` INNER JOIN (SELECT `Index` AS `iIndex`, `Name` AS `iName`, `Register` FROM `%sInstrument`) `%sInstrument` ON `Instrument` = `iIndex` INNER JOIN (SELECT `Index` AS `rIndex`, `Name` AS `rName`, `Sortierung` FROM `%sRegister`) `%sRegister` ON `Register` = `rIndex` WHERE `Termin` = "%d" AND `Wert` = "%d" AND `rName` != "keins" ORDER BY `Sortierung`, `Nachname`, `Vorname`;',
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $this->Index,
            $wert
            );
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
            sqlerror();
            
            while($row = mysqli_fetch_array($dbr)) {
                if($wert==1) {
                    $sumJa++;
                    $sumJaG+=$row['Guests'];
                    $sumJaC+=$row['Children'];
                }
                if($wert==3) {
                    $sumV++;
                    $sumVG+=$row['Guests'];
                    $sumVC+=$row['Children'];
                }
                $str=$str."\t<div class=\"w3-container w3-border-bottom w3-border-black w3-mobile ".$colorval[$wert-1]."\">";
                $str=$str."\t\t<div class=\"w3-col l".$colsize." m".$colsize." s".$colsize."\">".$row['Vorname']." ".$row['Nachname']."</div>";
                $str=$str."\t\t<div class=\"w3-col l".$colsize." m".$colsize." s".$colsize."\">".$row['iName']."</div>";
                if($GLOBALS['optionsDB']['showChildOption'] || $GLOBALS['optionsDB']['showGuestOption']) {
                    $str=$str."\t\t<div class=\"w3-col l".$colsize." m".$colsize." s".$colsize."\">1 + ".$row['Children']." + ".$row['Guests']."</div>";
                }
                $str=$str."\t\t<div class=\"w3-col l".$colsize." m".$colsize." s".$colsize."\">".$row['Timestamp']."</div>";
                $str=$str."\t</div>";
            }
        }
        $str=$str."\t<div class=\"w3-row w3-container ".$GLOBALS['commonColors']['AppmntYes']." w3-border-bottom w3-border-black w3-mobile w3-margin-top\">";
        $str=$str."\t\t<div class=\"w3-col l".$colsize." m".$colsize." s".$colsize."\"><b>".Summe."</b></div>";
        $str=$str."\t\t<div class=\"w3-col l".$colsize." m".$colsize." s".$colsize."\"><b>&nbsp;</b></div>";
        if($GLOBALS['optionsDB']['showChildOption'] || $GLOBALS['optionsDB']['showGuestOption']) {
            $str=$str."\t\t<div class=\"w3-col l".(2*$colsize)." m".(2*$colsize)." s".(2*$colsize)."\"><b>".$sumJa." + ".$sumJaC." K + ".$sumJaG." G = ".($sumJa+$sumJaC+$sumJaG)."</b></div>";
            $str=$str."\t</div>";
            $str=$str."\t<div class=\"w3-row w3-container ".$GLOBALS['commonColors']['AppmntMaybe']." w3-border-bottom w3-border-black w3-mobile\">";
            $str=$str."\t\t<div class=\"w3-col l".$colsize." m".$colsize." s".$colsize."\">&nbsp;</div>";
            $str=$str."\t\t<div class=\"w3-col l".$colsize." m".$colsize." s".$colsize."\"><b>&nbsp;</b></div>";
            $str=$str."\t\t<div class=\"w3-col l".(2*$colsize)." m".(2*$colsize)." s".(2*$colsize)."\"><b>".$sumV." + ".$sumVC." K + ".$sumVG." G = ".($sumV+$sumVC+$sumVG)."</b></div>";
            $str=$str."\t</div>";
        }
        $str=$str."\t</div>";
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
        }
        else {
            $str=$str."  <div class=\"w3-col l3 w3-container\">".germanDate($this->Datum, 1);
        }
        if($GLOBALS['optionsDB']['showTravelTime'] || $GLOBALS['optionsDB']['showVehicle']) {
            if($this->Abfahrt || $this->vName) {
                $str=$str." (";
            }
        }
        if($this->Abfahrt && $GLOBALS['optionsDB']['showTravelTime']) {
            $str=$str.sql2timeRaw($this->Abfahrt);
        }
        if($this->Abfahrt && $GLOBALS['optionsDB']['showTravelTime'] && $GLOBALS['optionsDB']['showVehicle']) {
            $str=$str." ";
        }
        if($GLOBALS['optionsDB']['showVehicle']) {
            $str=$str.$this->vName;
        }
        if($GLOBALS['optionsDB']['showTravelTime'] || $GLOBALS['optionsDB']['showVehicle']) {
            if($this->Abfahrt || $this->vName) {
                $str=$str.")";
            }
        }
        $str=$str."</div>\n";
        $str=$str."  <div class=\"w3-col l3 w3-container\">".$this->Ort1."</div>\n";
        $str=$str."<div class=\"w3-col l2 w3-row w3-mobile\">";
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
        $str=$str." w3-border w3-border-black w3-margin-left w3-margin-top w3-margin-right w3-center w3-col s3 m3 l2\" name=\"meldung\" value=\"1\" onclick=\"melde(".$user.", ".$this->Index.", 1, ".(int)$this->Children.", ".(int)$this->Guests.")\">&#10004;</button>";
        $str=$str."<button class=\"w3-btn ";
        if($this->Wert == 1 || $this->Wert == 3 ) $str=$str.$GLOBALS['commonColors']['Disabled'];
        else $str=$str.$GLOBALS['commonColors']['AppmntBtnNo'];
        $str=$str." w3-border w3-border-black w3-margin-top w3-center w3-col s3 m3 l2\" name=\"meldung\" value=\"2\" onclick=\"melde(".$user.", ".$this->Index.", 2, ".(int)$this->Children.", ".(int)$this->Guests.")\">&#10008;</button>";
        $str=$str."<button class=\"w3-btn ";
        if($this->Wert == 1 || $this->Wert == 2 ) $str=$str.$GLOBALS['commonColors']['Disabled'];
        else $str=$str.$GLOBALS['commonColors']['AppmntBtnMaybe'];
        $str=$str." w3-border w3-border-black w3-margin-left w3-margin-top w3-center w3-col s3 m3 l2\" name=\"meldung\" value=\"3\" onclick=\"melde(".$user.", ".$this->Index.", 3, ".(int)$this->Children.", ".(int)$this->Guests.")\"><b>?</b></button>";
        /* $str=$str."</form>"; */
        $str=$str."</div>";
        if($_SESSION['admin']) {
            $str=$str."<button class=\"w3-btn ".$GLOBALS['commonColors']['submit']." w3-border w3-border-black w3-col l1 m12 s12 w3-row w3-margin-top\" onclick=\"getStatus(".$user.", ".$this->Index.")\">Status per Mail</button>";
        }
        if(($GLOBALS['optionsDB']['showGuestOption'] || $GLOBALS['optionsDB']['showChildOption']) && ($this->Wert == 1 || $this->Wert == 3) && $this->vName == "Bus") {
            if($GLOBALS['optionsDB']['showChildOption']) {
                $str=$str."<div class=\"w3-hide-small w3-hide-medium w3-col l9\">&nbsp;</div><div class=\"w3-col l1 m6 s6 w3-row w3-container w3-margin-top\">Kinder</div><div class=\"w3-col l1 m6 s6 w3-row w3-container w3-margin-top\"><input style=\"width: 5em\" type=\"number\" name=\"Children\" id=\"Children".$this->Index."\" min=\"0\" value=\"".$this->Children."\" default=\"0\" title=\"Kinder\"\"/>";
                $str=$str."</div>";
            }
            if($GLOBALS['optionsDB']['showGuestOption']) {
                $str=$str."<div class=\"w3-hide-small w3-hide-medium w3-col l9\">&nbsp;</div><div class=\"w3-col l1 m6 s6 w3-row w3-container w3-margin-top\">G&auml;ste</div><div class=\"w3-col l1 m6 s6 w3-row w3-container w3-margin-top\"><input style=\"width: 5em\" type=\"number\" name=\"Guests\" id=\"Guests".$this->Index."\" min=\"0\" value=\"".$this->Guests."\" default=\"0\" title=\"G&auml;ste\"/>";
                $str=$str."</div>";
            }
            $str=$str."<div class=\"w3-hide-small w3-hide-medium w3-col l9\">&nbsp;</div><button class=\"w3-btn ".$GLOBALS['commonColors']['submit']." w3-border w3-border-black w3-col l2 m12 s12 w3-row w3-margin-top\" name=\"meldungGC\" onclick=\"melde(".$user.", ".$this->Index.", ".$this->Wert.", -1, -1)\"><b>speichern</b></button>";
        }
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
        if($this->Ende) {
            $str=$str."<div class=\"w3-container w3-row w3-margin\">";
            $str=$str."<div class=\"w3-col l3\">Ende:</div><div class=\"w3-col l9\"><b>".sql2time($this->Uhrzeit2)."</b></div>";
            $str=$str."</div>";
        }
        if($this->Abfahrt && $GLOBALS['optionsDB']['showTravelTime']) {
            $str=$str."<div class=\"w3-container w3-row w3-margin\">";
            $str=$str."<div class=\"w3-col l3\">Abfahrt:</div><div class=\"w3-col l9\"><b>".sql2time($this->Abfahrt)."</b></div>";
            $str=$str."</div>";
        }
        if($GLOBALS['optionsDB']['showVehicle']) {
            $str=$str."<div class=\"w3-container w3-row w3-margin\">";
            $str=$str."<div class=\"w3-col l3\">Anfahrt mit:</div><div class=\"w3-col l9\"><b>".$this->vName."</b></div>";
            $str=$str."</div>";
        }
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
        if($this->vName == "Bus") {
            $cols = (int)$GLOBALS['optionsDB']['showChildOption']+(int)$GLOBALS['optionsDB']['showGuestOption']+2;
            $bus=true;
        }
        else {
            $cols = 2;
            $bus=false;
        }
        switch($cols) {
        case 3:
            $colsize = array(5,5,2);
            break;
        case 4:
            $colsize = array(4,4,2,2);
            break;
        case 2:
        default:
            $colsize = array(6,6);
            break;
        }

        $str = "<div onclick=\"document.getElementById('id".$this->Index."').style.display='block'\" class=\"w3-container w3-margin-top w3-border-top w3-border-black w3-center ".$GLOBALS['commonColors']['titlebar']."\"><h3>".$this->Name."</h3><p>".germanDate($this->Datum, 1)."</p></div>\n";
        $str=$str."<div onclick=\"document.getElementById('id".$this->Index."').style.display='block'\" class=\"w3-container w3-border-bottom w3-border-black\">\n";
        $whoYes = '';
        $whoNo = '';
        $whoMaybe = '';
        if($this->Auftritt) {
            if($filterregister) {
                $sql = sprintf("SELECT * FROM `%sRegister` WHERE `Name` != 'keins' AND `Index` = '%d' ORDER BY `Sortierung`;",
                $GLOBALS['dbprefix'],
                $filterregister
                );
            }
            else { // $filterregister
                if($GLOBALS['optionsDB']['showConductor']) {
                    $sql = sprintf("SELECT * FROM `%sRegister` WHERE `Name` != 'keins' ORDER BY `Sortierung`;",
                    $GLOBALS['dbprefix']
                    );
                }
                else {
                    $sql = sprintf("SELECT * FROM `%sRegister` WHERE `Name` != 'Dirigent' AND `Name` != 'keins' ORDER BY `Sortierung`;",
                    $GLOBALS['dbprefix']
                    );
                }
            }
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
            sqlerror();
            $sja=0;
            $sall=0;
            $snReg=0;
            $snein=0;
            $svielleicht=0;
            $childrenYes=0;
            $guestsYes=0;
            $childrenMaybe=0;
            $guestsMaybe=0;
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
                        $childrenYes+=$row2['Children'];
                        $guestsYes+=$row2['Guests'];
                        $antwort='ja';
                        $whoYes = $whoYes."<div class=\"w3-row ".$GLOBALS['commonColors']['AppmntBtnYes']."\"><div class=\"w3-col l".$colsize[0]." m".$colsize[0]." s".$colsize[0]."\">".$row2['Vorname']." ".$row2['Nachname']."</div><div class=\"w3-col l".$colsize[1]." m".$colsize[1]." s".$colsize[1]."\">".$row2['iName']."</div>";
                        $actcol=2;
                        if(($GLOBALS['optionsDB']['showChildOption'] && $bus) || $row2['Children']) {
                            $whoYes=$whoYes."<div class=\"w3-col l".$colsize[$actcol]." m".$colsize[$actcol]." s".$colsize[$actcol]."\">";
                            if($row2['Children'] > 0) {
                                $whoYes=$whoYes."+ ".$row2['Children'];
                            }
                            $whoYes=$whoYes."</div>";
                            $actcol++;
                        }
                        if(($GLOBALS['optionsDB']['showGuestOption'] && $bus) || $row2['Guests']) {
                            $whoYes=$whoYes."<div class=\"w3-col l".$colsize[$actcol]." m".$colsize[$actcol]." s".$colsize[$actcol]."\">";
                            if($row2['Guests'] > 0) {
                                $whoYes=$whoYes."+ ".$row2['Guests'];
                            }
                            $whoYes=$whoYes."</div>";
                        }
                        $whoYes=$whoYes."</div>\n";
                        break;
                    case 2:
                        $nein++;
                        $snein++;
                        $antwort='nein';
                        $whoNo = $whoNo."<div class=\"w3-row ".$GLOBALS['commonColors']['AppmntBtnNo']."\"><div class=\"w3-col l".$colsize[0]." m".$colsize[0]." s".$colsize[0]."\">".$row2['Vorname']." ".$row2['Nachname']."</div><div class=\"w3-col l".$colsize[1]." m".$colsize[1]." s".$colsize[1]."\">".$row2['iName']."</div>";
                        $actcol=2;
                        if($GLOBALS['optionsDB']['showChildOption'] && $bus) {
                            $whoNo=$whoNo."<div class=\"w3-col l".$colsize[$actcol]." m".$colsize[$actcol]." s".$colsize[$actcol]."\"></div>";
                            $actcol++;
                        }
                        if($GLOBALS['optionsDB']['showGuestOption'] && $bus) {
                            $whoNo=$whoNo."<div class=\"w3-col l".$colsize[$actcol]." m".$colsize[$actcol]." s".$colsize[$actcol]."\"></div>";
                        }
                        $whoNo=$whoNo."</div>\n";
                        break;
                    case 3:
                        $vielleicht++;
                        $svielleicht++;
                        $childrenMaybe+=$row2['Children'];
                        $guestsMaybe+=$row2['Guests'];
                        $antwort='vielleicht';
                        $whoMaybe = $whoMaybe."<div class=\"w3-row ".$GLOBALS['commonColors']['AppmntBtnMaybe']."\"><div class=\"w3-col l".$colsize[0]." m".$colsize[0]." s".$colsize[0]."\">".$row2['Vorname']." ".$row2['Nachname']."</div><div class=\"w3-col l".$colsize[1]." m".$colsize[1]." s".$colsize[1]."\">".$row2['iName']."</div>";
                        $actcol=2;
                        if(($GLOBALS['optionsDB']['showChildOption'] && $bus) || $row2['Children']) {
                            $whoMaybe=$whoMaybe."<div class=\"w3-col l".$colsize[$actcol]." m".$colsize[$actcol]." s".$colsize[$actcol]."\">";
                            if($row2['Children'] > 0) {
                                $whoMaybe=$whoMaybe."+ ".$row2['Children'];
                            }
                            $whoMaybe=$whoMaybe."</div>";
                            $actcol++;
                        }
                        if(($GLOBALS['optionsDB']['showGuestOption'] && $bus) || $row2['Guests']) {
                            $whoMaybe=$whoMaybe."<div class=\"w3-col l".$colsize[$actcol]." m".$colsize[$actcol]." s".$colsize[$actcol]."\">";
                            if($row2['Guests'] > 0) {
                                $whoMaybe=$whoMaybe."+ ".$row2['Guests'];
                            }
                            $whoMaybe=$whoMaybe."</div>";
                        }
                        $whoMaybe=$whoMaybe."</div>\n";
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
                if(($bus && $GLOBALS['optionsDB']['showChildOption']) || $childrenYes || $childrenMaybe) {
                $str=$str."<div class=\"w3-row\"><div class=\"w3-col l9 m6 s6\">Kinder</div><div class=\"".$GLOBALS['commonColors']['AppmntBtnYes']." w3-col l1 m2 s2 w3-center\">&#10004; ".$childrenYes."</div><div class=\"".$GLOBALS['commonColors']['AppmntBtnNo']." w3-col l1 m2 s2 w3-center\">&#10008; ".$nein."</div><div class=\"".$GLOBALS['commonColors']['AppmntBtnMaybe']." w3-col l1 m2 s2 w3-center\">? ".$childrenMaybe."</div></div>\n";
            }
                if(($bus && $GLOBALS['optionsDB']['showGuestOption']) || $guestsYes || $guestsMaybe) {
                $str=$str."<div class=\"w3-row\"><div class=\"w3-col l9 m6 s6\">G&auml;ste</div><div class=\"".$GLOBALS['commonColors']['AppmntBtnYes']." w3-col l1 m2 s2 w3-center\">&#10004; ".$guestsYes."</div><div class=\"".$GLOBALS['commonColors']['AppmntBtnNo']." w3-col l1 m2 s2 w3-center\">&#10008; ".$nein."</div><div class=\"".$GLOBALS['commonColors']['AppmntBtnMaybe']." w3-col l1 m2 s2 w3-center\">? ".$guestsMaybe."</div></div>\n";
            }
            $str=$str."<div class=\"w3-row\"><div class=\"".$GLOBALS['commonColors']['Hover']." w3-col l7 m4 s4\"><b>Summe</b></div><div class=\"w3-col l2 m2 s2\"><b>".$sall;
            if(($bus && ($GLOBALS['optionsDB']['showChildOption'] || $GLOBALS['optionsDB']['showGuestOption'])) || $childrenYes || $childrenMaybe || $guestsYes || $guestsMaybe) {
                $str=$str."+".($childrenYes+$childrenMaybe+$guestsYes+$guestsMaybe);
            }
            $str=$str." / ".sprintf("%02d", $snReg)."</b></div><div class=\"".$GLOBALS['commonColors']['AppmntBtnYes']." w3-col l1 m2 s2 w3-center\">&#10004; ".($sja+$childrenYes+$guestsYes)."</div><div class=\"".$GLOBALS['commonColors']['AppmntBtnNo']." w3-col l1 m2 s2 w3-center\">&#10008; ".$snein."</div><div class=\"".$GLOBALS['commonColors']['AppmntBtnMaybe']." w3-col l1 m2 s2 w3-center\">? ".($svielleicht+$childrenMaybe+$guestsMaybe)."</div></div>\n";
            }
        }
        else { // $this->Auftritt
            $sql = sprintf("SELECT * FROM `%sMeldungen` INNER JOIN (SELECT `Index` AS `uIndex`, `Vorname`, `Nachname` FROM `%sUser`) `%sUser` ON `User` = `uIndex` WHERE `Termin` = '%d' ORDER BY `Nachname`, `Vorname`;",
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
            $childrenYes=0;
            $guestsYes=0;
            $childrenMaybe=0;
            $guestsMaybe=0;
            while($row = mysqli_fetch_array($dbr)) {
                switch($row['Wert']) {
                case 1:
                    $ja++;
                    $childrenYes+=$row['Children'];
                    $guestsYes+=$row['Guests'];
                    $antwort='ja';
                    $whoYes = $whoYes."<div class=\"w3-row ".$GLOBALS['commonColors']['AppmntBtnYes']."\"><div class=\"w3-col l".$colsize[0]." m".$colsize[0]." s".$colsize[0]."\">".$row['Vorname']." ".$row['Nachname']."</div><div class=\"w3-col l".$colsize[1]." m".$colsize[1]." s".$colsize[1]."\">&nbsp;</div>";
                    $actcol=2;
                    if(($GLOBALS['optionsDB']['showChildOption'] && $bus) || $row['Children']) {
                        $whoYes=$whoYes."<div class=\"w3-col l".$colsize[$actcol]." m".$colsize[$actcol]." s".$colsize[$actcol]."\">";
                        if($row['Children'] > 0) {
                            $whoYes=$whoYes."+ ".$row['Children'];
                        }
                        $whoYes=$whoYes."</div>";
                        $actcol++;
                    }
                    if(($GLOBALS['optionsDB']['showGuestOption'] && $bus) || $row['Guests']) {
                        $whoYes=$whoYes."<div class=\"w3-col l".$colsize[$actcol]." m".$colsize[$actcol]." s".$colsize[$actcol]."\">";
                        if($row['Guests'] > 0) {
                            $whoYes=$whoYes."+ ".$row['Guests'];
                        }
                        $whoYes=$whoYes."</div>";
                    }
                    $whoYes=$whoYes."</div>\n";
                    /* $whoYes = $whoYes."<div class=\"w3-container ".$GLOBALS['commonColors']['AppmntBtnYes']."\"><div class=\"w3-row\"><div class=\"w3-col l12 m12 s12\">".$row['Vorname']." ".$row['Nachname']."</div></div></div>\n"; */
                    break;
                case 2:
                    $nein++;
                    $antwort='nein';
                        $whoNo = $whoNo."<div class=\"w3-row ".$GLOBALS['commonColors']['AppmntBtnNo']."\"><div class=\"w3-col l".$colsize[0]." m".$colsize[0]." s".$colsize[0]."\">".$row['Vorname']." ".$row['Nachname']."</div><div class=\"w3-col l".$colsize[1]." m".$colsize[1]." s".$colsize[1]."\">&nbsp;</div>";
                        $actcol=2;
                        if($GLOBALS['optionsDB']['showChildOption'] && $bus) {
                            $whoNo=$whoNo."<div class=\"w3-col l".$colsize[$actcol]." m".$colsize[$actcol]." s".$colsize[$actcol]."\"></div>";
                            $actcol++;
                        }
                        if($GLOBALS['optionsDB']['showGuestOption'] && $bus) {
                            $whoNo=$whoNo."<div class=\"w3-col l".$colsize[$actcol]." m".$colsize[$actcol]." s".$colsize[$actcol]."\"></div>";
                        }
                        $whoNo=$whoNo."</div>\n";
                    /* $whoNo = $whoNo."<div class=\"w3-container ".$GLOBALS['commonColors']['AppmntBtnNo']."\"><div class=\"w3-row\"><div class=\"w3-col l12 m12 s12\">".$row['Vorname']." ".$row['Nachname']."</div></div></div>\n"; */
                    break;
                case 3:
                    $vielleicht++;
                    $antwort='vielleicht';
                    $childrenMaybe+=$row['Children'];
                    $guestsMaybe+=$row['Guests'];
                        $whoMaybe = $whoMaybe."<div class=\"w3-row ".$GLOBALS['commonColors']['AppmntBtnMaybe']."\"><div class=\"w3-col l".$colsize[0]." m".$colsize[0]." s".$colsize[0]."\">".$row['Vorname']." ".$row['Nachname']."</div><div class=\"w3-col l".$colsize[1]." m".$colsize[1]." s".$colsize[1]."\">&nbsp;</div>";
                        $actcol=2;
                        if(($GLOBALS['optionsDB']['showChildOption'] && $bus) || $row['Children']) {
                            $whoMaybe=$whoMaybe."<div class=\"w3-col l".$colsize[$actcol]." m".$colsize[$actcol]." s".$colsize[$actcol]."\">";
                            if($row['Children'] > 0) {
                                $whoMaybe=$whoMaybe."+ ".$row['Children'];
                            }
                            $whoMaybe=$whoMaybe."</div>";
                            $actcol++;
                        }
                        if(($GLOBALS['optionsDB']['showGuestOption'] && $bus) || $row['Guests']) {
                            $whoMaybe=$whoMaybe."<div class=\"w3-col l".$colsize[$actcol]." m".$colsize[$actcol]." s".$colsize[$actcol]."\">";
                            if($row['Guests'] > 0) {
                                $whoMaybe=$whoMaybe."+ ".$row['Guests'];
                            }
                            $whoMaybe=$whoMaybe."</div>";
                        }
                        $whoMaybe=$whoMaybe."</div>\n";
                    /* $whoMaybe = $whoMaybe."<div class=\"w3-container ".$GLOBALS['commonColors']['AppmntBtnMaybe']."\"><div class=\"w3-row\"><div class=\"w3-col l12 m12 s12\">".$row['Vorname']." ".$row['Nachname']."</div></div></div>\n"; */
                    break;
                default:
                    break;
                }
            }
            if(($bus && $GLOBALS['optionsDB']['showChildOption']) || $childrenYes || $childrenMaybe) {
                $str=$str."<div class=\"w3-row\"><div class=\"w3-col l9 m6 s6\">Kinder</div><div class=\"".$GLOBALS['commonColors']['AppmntBtnYes']." w3-col l1 m2 s2 w3-center\">&#10004; ".$childrenYes."</div><div class=\"".$GLOBALS['commonColors']['AppmntBtnNo']." w3-col l1 m2 s2 w3-center\">&#10008; ".$nein."</div><div class=\"".$GLOBALS['commonColors']['AppmntBtnMaybe']." w3-col l1 m2 s2 w3-center\">? ".$childrenMaybe."</div></div>\n";
            }
            if(($bus && $GLOBALS['optionsDB']['showGuestOption']) || $guestsYes || $guestsMaybe) {
                $str=$str."<div class=\"w3-row\"><div class=\"w3-col l9 m6 s6\">G&auml;ste</div><div class=\"".$GLOBALS['commonColors']['AppmntBtnYes']." w3-col l1 m2 s2 w3-center\">&#10004; ".$guestsYes."</div><div class=\"".$GLOBALS['commonColors']['AppmntBtnNo']." w3-col l1 m2 s2 w3-center\">&#10008; ".$nein."</div><div class=\"".$GLOBALS['commonColors']['AppmntBtnMaybe']." w3-col l1 m2 s2 w3-center\">? ".$guestsMaybe."</div></div>\n";
            }
            $str=$str."<div class=\"w3-row\"><div class=\"w3-col l9 m6 s6\"><b>Summe</b></div><div class=\"".$GLOBALS['commonColors']['AppmntBtnYes']." w3-col l1 m2 s2 w3-center\">&#10004; ".($ja+$childrenYes+$guestsYes)."</div><div class=\"".$GLOBALS['commonColors']['AppmntBtnNo']." w3-col l1 m2 s2 w3-center\">&#10008; ".$nein."</div><div class=\"".$GLOBALS['commonColors']['AppmntBtnMaybe']." w3-col l1 m2 s2 w3-center\">? ".($vielleicht+$childrenMaybe+$guestsMaybe)."</div></div>\n";
        }
        $str=$str."</div>\n";

        $str=$str."<div id=\"id".$this->Index."\" class=\"w3-modal\">";
        $str=$str."<div class=\"w3-modal-content\">";
        $str=$str."<header class=\"w3-container ".$GLOBALS['commonColors']['titlebar']."\">";
        $str=$str."<span onclick=\"document.getElementById('id".$this->Index."').style.display='none'\""; 
        $str=$str."class=\"w3-button w3-display-topright\">&times;</span>";
        $str=$str."<h2>".$this->Name."</h2>";
        $str=$str."</header>";

        $str = $str."<div class=\"w3-container w3-margin-top\"><div class=\"w3-row\"><div class=\"w3-col l".$colsize[0]." m".$colsize[0]." s".$colsize[0]."\"><b>Zusagen</b></div><div class=\"w3-col l".$colsize[1]." m".$colsize[1]." s".$colsize[1]."\">&nbsp;</div>";
        $actcol=2;
        if($GLOBALS['optionsDB']['showChildOption'] && $bus) {
            $str=$str."<div class=\"w3-col l".$colsize[$actcol]." m".$colsize[$actcol]." s".$colsize[$actcol]."\">Kinder</div>";
            $actcol++;
        }
        if($GLOBALS['optionsDB']['showGuestOption'] && $bus) {
            $str=$str."<div class=\"w3-col l".$colsize[$actcol]." m".$colsize[$actcol]." s".$colsize[$actcol]."\">G&auml;ste</div>";
        }
        $str=$str."</div></div>\n";

        $str = $str."<div class=\"w3-container\">";
        $str=$str.$whoYes;
        $str = $str."</div>";
        $str = $str."<div class=\"w3-container w3-margin-top\"><b>unsicher</b></div>\n";
        $str = $str."<div class=\"w3-container\">";
        $str=$str.$whoMaybe;
        $str = $str."</div>";
        $str = $str."<div class=\"w3-container w3-margin-top\"><b>Absagen</b></div>\n";
        $str = $str."<div class=\"w3-container\">";
        $str=$str.$whoNo;
        $str = $str."</div>";
        $str=$str."<div class=\"w3-container w3-margin-bottom\"><br />";
        $str=$str."</div>";
        $str=$str."</div>";
        $str=$str."</div>";

        return $str;
    }
};
?>
