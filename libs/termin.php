<?php
class Termin
{
    private $_data = array('Index' => null, 'Datum' => null, 'Uhrzeit' => null, 'Uhrzeit2' => null, 'Abfahrt' => null, 'Vehicle' => 1, 'Name' => null, 'Auftritt' => null, 'Ort1' => null, 'Ort2' => null, 'Ort3' => null, 'Ort4' => null, 'Beschreibung' => null, 'Shifts' => null, 'published' => null, 'open' => 1, 'Wert' => null, 'Children' => null, 'Guests' => null, 'new' => null, 'vName' => null);
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
        case 'Shifts':
	    case 'published':
	    case 'open':
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
	    case 'Vehicle':
	    case 'Wert':
	    case 'Children':
	    case 'Guests':
            $this->_data[$key] = (int)$val;
            break;
	    case 'Datum':
	    case 'Uhrzeit':
	    case 'Uhrzeit2':
	    case 'Abfahrt':
            $this->_data[$key] = trim($val);
            break;
	    case 'Name':
	    case 'Beschreibung':
	    case 'vName':
	    case 'Ort1':
	    case 'Ort2':
	    case 'Ort3':
	    case 'Ort4':
            $this->_data[$key] = htmlentities(trim($val));
            break;
	    case 'Auftritt':
	    case 'Shifts':
	    case 'published':
	    case 'open':
	    case 'new':
            $this->_data[$key] = (bool)$val;
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
        return sprintf("Termin-ID: %d, Datum: %s, Beginn: %s, Ende: %s, Abfahrt: %s, mit: %s, Name: %s, Auftritt: %s, Ort1: %s, Ort2: %s, Ort3: %s, Ort4: %s, Beschreibung: %s, Schichten: %s, sichtbar: %s, offen: %s",
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
        bool2string($this->Shifts),
        bool2string($this->published),
        bool2string($this->open)
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
        if(!$this->Vehicle) $this->Vehicle=1;
        return true;
    }
    protected function insert() {
        $sql = sprintf('INSERT INTO `%sTermine` (`Datum`, `Uhrzeit`, `Uhrzeit2`, `Abfahrt`, `Vehicle`, `Name`, `Beschreibung`, `Shifts`, `Auftritt`, `Ort1`, `Ort2`, `Ort3`, `Ort4`, `published`, `open`) VALUES ("%s", %s, %s, %s, "%d", "%s", "%s", "%d", "%d", "%s", "%s", "%s", "%s", "%d", "%d");',
        $GLOBALS['dbprefix'],
        mysqli_real_escape_string($GLOBALS['conn'], $this->Datum),
        $this->Uhrzeit == '' ? 'NULL': "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->Uhrzeit)."\"",
        $this->Uhrzeit2 == '' ? 'NULL': "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->Uhrzeit2)."\"",
        $this->Abfahrt == '' ? 'NULL': "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->Abfahrt)."\"",
        $this->Vehicle,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Beschreibung),
        $this->Shifts,
        $this->Auftritt,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Ort1),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Ort2),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Ort3),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Ort4),
        $this->published,
        $this->open
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }
    public function getShifts() {
        $sql = sprintf("SELECT * FROM `%sSchichten` WHERE `Termin` = %d ORDER BY `Name`, `Start`;",
            $GLOBALS['dbprefix'],
            $this->Index
            );
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
            sqlerror();
            $shifts=array();
            while($row = mysqli_fetch_array($dbr)) {
                $shifts[] = $row['Index'];
            }
            return $shifts;
    }
    protected function getUser() {
        if(isset($_POST['proxy'])) {
            $user = $_POST['proxy'];
        }
        elseif(isset($_GET['user'])) {
            $user = $_GET['user'];
        }
        elseif(isset($_SESSION['userid'])) {
            $user = $_SESSION['userid'];
        }
        return $user;
    }
    public function getShiftsStatus() {
        $user=$this->getUser();
        $sql = sprintf("SELECT * FROM `%sSchichtmeldung` INNER JOIN (SELECT `Index` AS `sIndex`, `Termin` FROM `%sSchichten`) `%sSchichten` ON `sIndex` = `Shift` WHERE `Termin` = %d AND `User` = %d;",
        $GLOBALS['dbprefix'],
        $GLOBALS['dbprefix'],
        $GLOBALS['dbprefix'],
        $this->Index,
        $user
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $no=false;
        $maybe=false;
        while($row = mysqli_fetch_array($dbr)) {
            if($row['Wert'] == 1) return 1;
            if($row['Wert'] == 2) $no=true;
            if($row['Wert'] == 3) $maybe=true;
        }
        if($maybe) return 3;
        if($no) return 2;
        return 0;
    }
    protected function update() {
        $sql = sprintf('UPDATE `%sTermine` SET `Datum` = "%s", `Uhrzeit` = %s, `Uhrzeit2` = %s, `Abfahrt` = %s, `Vehicle`= "%d", `Name` = "%s", `Beschreibung` = "%s", `Shifts` = "%d", `Auftritt` = "%d", `Ort1` = "%s", `Ort2` = "%s", `Ort3` = "%s", `Ort4` = "%s", `published` = "%d", `open` = "%d", `new` = "%d" WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        mysqli_real_escape_string($GLOBALS['conn'], $this->Datum),
        $this->Uhrzeit == '' ? 'NULL': "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->Uhrzeit)."\"",
        $this->Uhrzeit2 == '' ? 'NULL': "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->Uhrzeit2)."\"",
        $this->Abfahrt == '' ? 'NULL': "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->Abfahrt)."\"",
        $this->Vehicle,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Beschreibung),
        $this->Shifts,
        $this->Auftritt,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Ort1),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Ort2),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Ort3),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Ort4),
        $this->published,
        $this->open,
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

        $sql = sprintf('DELETE FROM `%sMeldungen` WHERE `Termin` = "%d";',
        $GLOBALS['dbprefix'],
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();

        $sql = sprintf('SELECT * FROM `%sSchichten` WHERE `Termin` = "%d";',
        $GLOBALS['dbprefix'],
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        while($row = mysqli_fetch_array($dbr)) {
            $n = new Schicht;
            $n->load_by_id($row['Index']);
            $n->delete();
        }

        $sql = sprintf('DELETE FROM `%sTermine` WHERE `Index` = "%d" LIMIT 1;',
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
    public function close() {
        $this->open = 0;
        $this->save();
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
        $user=$this->getUser();
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
        $str=$str."<div class=\"w3-row ";
        if($this->Auftritt) {
            $str=$str.$GLOBALS['optionsDB']['colorAppmntConcert'];
        }
        else {
            $str=$str.$GLOBALS['optionsDB']['colorAppmntNoConcert'];
        }
        $str=$str." w3-mobile w3-border-black w3-padding\">";
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
        $colorval = array($GLOBALS['optionsDB']['colorAppmntYes'], $GLOBALS['optionsDB']['colorAppmntNo'], $GLOBALS['optionsDB']['colorAppmntMaybe']);
        $colsize=4;
        if($GLOBALS['optionsDB']['showChildOption'] || $GLOBALS['optionsDB']['showGuestOption']) {
            $colsize=3;
        }
        $str="<div class=\"w3-container ".$GLOBALS['optionsDB']['colorTitleBar']."\"><h3>".$this->Name."</h3></div>";
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
        $str=$str."\t<div class=\"w3-row w3-container ".$GLOBALS['optionsDB']['colorAppmntYes']." w3-border-bottom w3-border-black w3-mobile w3-margin-top\">";
        $str=$str."\t\t<div class=\"w3-col l".$colsize." m".$colsize." s".$colsize."\"><b>".Summe."</b></div>";
        $str=$str."\t\t<div class=\"w3-col l".$colsize." m".$colsize." s".$colsize."\"><b>&nbsp;</b></div>";
        if($GLOBALS['optionsDB']['showChildOption'] || $GLOBALS['optionsDB']['showGuestOption']) {
            $str=$str."\t\t<div class=\"w3-col l".(2*$colsize)." m".(2*$colsize)." s".(2*$colsize)."\"><b>".$sumJa." + ".$sumJaC." K + ".$sumJaG." G = ".($sumJa+$sumJaC+$sumJaG)."</b></div>";
            $str=$str."\t</div>";
            $str=$str."\t<div class=\"w3-row w3-container ".$GLOBALS['optionsDB']['colorAppmntMaybe']." w3-border-bottom w3-border-black w3-mobile\">";
            $str=$str."\t\t<div class=\"w3-col l".$colsize." m".$colsize." s".$colsize."\">&nbsp;</div>";
            $str=$str."\t\t<div class=\"w3-col l".$colsize." m".$colsize." s".$colsize."\"><b>&nbsp;</b></div>";
            $str=$str."\t\t<div class=\"w3-col l".(2*$colsize)." m".(2*$colsize)." s".(2*$colsize)."\"><b>".$sumV." + ".$sumVC." K + ".$sumVG." G = ".($sumV+$sumVC+$sumVG)."</b></div>";
            $str=$str."\t</div>";
        }
        else {
            $str=$str."\t\t<div class=\"w3-col l".(2*$colsize)." m".(2*$colsize)." s".(2*$colsize)."\"><b>".$sumJa."</b></div>";
            $str=$str."\t</div>";
            $str=$str."\t<div class=\"w3-row w3-container ".$GLOBALS['optionsDB']['colorAppmntMaybe']." w3-border-bottom w3-border-black w3-mobile\">";
            $str=$str."\t\t<div class=\"w3-col l".$colsize." m".$colsize." s".$colsize."\">&nbsp;</div>";
            $str=$str."\t\t<div class=\"w3-col l".$colsize." m".$colsize." s".$colsize."\"><b>&nbsp;</b></div>";
            $str=$str."\t\t<div class=\"w3-col l".(2*$colsize)." m".(2*$colsize)." s".(2*$colsize)."\"><b>".$sumV."</b></div>";
            $str=$str."\t</div>";
        }
        $str=$str."\t</div>";
        $str=$str."</div>";
        return $str;
    }
    protected function globalShiftColor() {
        switch($this->getShiftsStatus()) {
        case 1:
            return $GLOBALS['optionsDB']['colorAppmntYes'];
            break;
        case 2:
            return $GLOBALS['optionsDB']['colorAppmntNo'];
            break;
        case 3:
            return $GLOBALS['optionsDB']['colorAppmntMaybe'];
            break;
        }            
    }
    protected function mainColor() {
        switch($this->Wert) {
        case 1:
            return $GLOBALS['optionsDB']['colorAppmntYes'];
            break;
        case 2:
            return $GLOBALS['optionsDB']['colorAppmntNo'];
            break;
        case 3:
            return $GLOBALS['optionsDB']['colorAppmntMaybe'];
            break;
        }
        $c = $this->globalShiftColor();
        if($c) return;
        if($this->Auftritt) {
            return $GLOBALS['optionsDB']['colorAppmntConcert'];
        }
        else {
            return $GLOBALS['optionsDB']['colorAppmntNoConcert'];
        }
    }
    protected function mainHover() {
        if(!$this->Shifts) {
            return $GLOBALS['optionsDB']['HoverEffect'];
        }
    }
    protected function lineHover() {
        if($this->Shifts) {
            return $GLOBALS['optionsDB']['HoverEffect'];
        }
    }
    protected function makeTimeInfo() {
        $str="";
        if($this->Uhrzeit) {
            $str=$str.germanDate($this->Datum, 1).", ".sql2timeRaw($this->Uhrzeit);
            if($this->Uhrzeit2) $str=$str." - ".sql2time($this->Uhrzeit2);
        }
        else {
            $str=$str.germanDate($this->Datum, 1);
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
        return $str;
    }
    protected function makeButtons($N, $indent, $val) {
        $symbols = array("&#10004;", "&#10008;", "<b>?</b>");
        $colors = array($GLOBALS['optionsDB']['colorBtnYes'], $GLOBALS['optionsDB']['colorBtnNo'], $GLOBALS['optionsDB']['colorBtnMaybe']);
        
        $str="";
        for($i=1; $i<=$N; $i++) {
            $btn = new div;
            $btn->indent = $indent;
            $btn->tag="button";

            $btn->class="w3-btn";
            $btn->class="w3-border";
            $btn->class="w3-border-black";
            $btn->class="w3-margin-left";
            /* $btn->class="w3-margin-top"; */
            $btn->class="w3-center";
            $btn->class="w3-col s3 m3 l3";
            $btn->body=$symbols[$i-1];

            if($val && $val != $i) {
                $btn->class=$GLOBALS['optionsDB']['colorDisabled'];
            }
            else {
                $btn->class=$colors[$i-1];
            }
            if($val != $i) {
                $btn->onclick="melde('".$GLOBALS['cronID']."', ".$this->getUser().", ".$this->Index.", ".$i.", ".(int)$this->Children.", ".(int)$this->Guests.")";
                $btn->name="meldung";
                $btn->value=$i;
            }
            $str=$str.$btn->print();
        }
        return $str;
    }
    protected function makeShiftButtons($N, $indent, $shift, $val) {
        $symbols = array("&#10004;", "&#10008;", "<b>?</b>");
        $colors = array($GLOBALS['optionsDB']['colorBtnYes'], $GLOBALS['optionsDB']['colorBtnNo'], $GLOBALS['optionsDB']['colorBtnMaybe']);
        
        $str="";
        for($i=1; $i<=$N; $i++) {
            $btn = new div;
            $btn->indent = $indent;
            $btn->tag="button";

            $btn->class="w3-btn";
            $btn->class="w3-border";
            $btn->class="w3-border-black";
            $btn->class="w3-margin-left";
            /* $btn->class="w3-margin-top"; */
            $btn->class="w3-center";
            $btn->class="w3-col s3 m3 l3";
            $btn->body=$symbols[$i-1];

            if($val && $val != $i) {
                $btn->class=$GLOBALS['optionsDB']['colorDisabled'];
            }
            else {
                $btn->class=$colors[$i-1];
            }
            if($val != $i) {
                $btn->onclick="meldeShift('".$GLOBALS['cronID']."', ".$this->getUser().", ".$shift.", ".$this->Index.", ".$i.")";
                $btn->name="meldungShift";
                $btn->value=$i;
            }
            $str=$str.$btn->print();
        }
        return $str;
    }
    protected function statusMailBtn($indent) {
        $user=$this->getUser();
        $str="";
        $admStatusDiv = new Div;
        $admStatusDiv->indent = $indent;
        $admStatusDiv->class="w3-col l1 m12 s12";
        $admStatusDiv->class="w3-row";
        $admStatusDiv->class="w3-margin-top";
        $admStatusDiv->class="w3-mobile";
        if($_SESSION['admin'] && $GLOBALS['optionsDB']['statusPerMail']) {
            $admStatusDiv->tag="button";
            $admStatusDiv->class="w3-btn";
            $admStatusDiv->class="w3-border";
            $admStatusDiv->class="w3-border-black";
            $admStatusDiv->class=$GLOBALS['optionsDB']['colorBtnSubmit'];
            $admStatusDiv->onclick="getStatus('".$GLOBALS['cronID']."', ".$user.", ".$this->Index.")";
            $admStatusDiv->body="Status per Mail";
        }
        else {
            $admStatusDiv->class="w3-hide-small";
            $admStatusDiv->class="w3-hide-medium";
        }
        $str=$str.$admStatusDiv->print();
        return $str;
    }
    public function getLineColor($val) {
        $c="";
        switch($val) {
        case 1:
            $c=$GLOBALS['optionsDB']['colorAppmntYes'];
            break;
        case 2:
            $c=$GLOBALS['optionsDB']['colorAppmntNo'];
            break;
        case 3:
            $c=$GLOBALS['optionsDB']['colorAppmntMaybe'];
            break;
        }
        return $c;
    }
    public function printBasicTableLine() {
        $user=$this->getUser();
        $str="";
        $indent=0;
        
        $main = new div;
        $main->indent = $indent;
        $main->id="entry".$this->Index;
        $main->class="w3-card-4 w3-margin";
        $main->class=$this->mainColor();
        $main->class=$this->mainHover();
        if(!$this->published) $main->class=$GLOBALS['optionsDB']['styleAppmntUnpublished'];
        $str=$str.$main->open();

        $indent++;
        $mainline = new div;
        $mainline->indent = $indent;
        $mainline->class="w3-row w3-padding";
        $mainline->class=$this->lineHover();
        $mainline->class=$this->globalShiftColor();
        $str=$str.$mainline->open();

        $indent++;
        $nameDiv = new div;
        $nameDiv->indent = $indent;
        $nameDiv->onclick="document.getElementById('id".$this->Index."').style.display='block'";
        $nameDiv->col(3, 0, 0);
        $nameDiv->bold();
        $nameDiv->body="<i class=\"fa fa-info-circle\"></i>&nbsp;&nbsp;".$this->Name;
        $str=$str.$nameDiv->print();

        $startDiv = new div;
        $startDiv->indent=$indent;
        $startDiv->col(3, 0, 0);
        $startDiv->body=$this->makeTimeInfo();
        $str=$str.$startDiv->print();

        $ortDiv = new div;
        $ortDiv->indent=$indent;
        $ortDiv->class="w3-margin-bottom";
        $ortDiv->col(3, 0, 0);
        $ortDiv->body=$this->Ort1;
        $str=$str.$ortDiv->print();

        $btnDiv = new Div;
        $btnDiv->indent = $indent;
        $btnDiv->class="w3-col l2";
        $btnDiv->class="w3-row w3-mobile";
        if($this->open) {
            if($this->Shifts) {
                $str=$str.$btnDiv->print();
            }
            else {
                $str=$str.$btnDiv->open();
                $indent++;
                $str=$str.$this->makeButtons(3, $indent, $this->Wert);
                $indent--;
                $str=$str.$btnDiv->close();
            }
        }
        $str=$str.$this->statusMailBtn($indent);
        $str=$str.$mainline->close();
        $indent--;
        $indent--;
        
        if(($GLOBALS['optionsDB']['showGuestOption'] || $GLOBALS['optionsDB']['showChildOption']) && ($this->Wert == 1 || $this->Wert == 3) && $this->vName == "Bus") {
            $guestChildLine = new div;
            $guestChildLine->indent = $indent;
            $guestChildLine->class="w3-row w3-padding";
            $str=$str.$guestChildLine->open();
            $indent++;

            if($GLOBALS['optionsDB']['showChildOption']) {
                $emptyDiv = new div;
                $emptyDiv->indent=$indent;
                $emptyDiv->col(9, 0, 0);
                $emptyDiv->class="w3-container";
                $str=$str.$emptyDiv->print();

                $childDiv = new div;
                $childDiv->indent=$indent;
                $childDiv->col(1, 6, 6);
                $childDiv->class="w3-row w3-margin-top w3-container";
                $childDiv->body="Kinder";
                $str=$str.$childDiv->print();

                $childInDiv = new div;
                $childInDiv->indent=$indent;
                $childInDiv->col(1, 6, 6);
                $childInDiv->class="w3-row w3-margin-top w3-container";
                $childInDiv->type="number";
                $childInDiv->tag="input";
                $childInDiv->style="width: 5em";
                $childInDiv->min=0;
                $childInDiv->defaultt=0;
                $childInDiv->value=$this->Children;
                $childInDiv->id="Children".$this->Index;
                $childInDiv->name="Children";
                $childInDiv->emptyBody=true;
                $str=$str.$childInDiv->print();

                $childSpacerDiv = new div;
                $childSpacerDiv->indent=$indent;
                $childSpacerDiv->col(1, 6, 6);
                $childSpacerDiv->class="w3-hide-small w3-hide-medium";
                $str=$str.$childSpacerDiv->print();
            }
            if($GLOBALS['optionsDB']['showGuestOption']) {
                $emptyDiv = new div;
                $emptyDiv->indent=$indent;
                $emptyDiv->col(9, 0, 0);
                $emptyDiv->class="w3-container";
                $str=$str.$emptyDiv->print();

                $guestDiv = new div;
                $guestDiv->indent=$indent;
                $guestDiv->col(1, 6, 6);
                $guestDiv->class="w3-row w3-margin-top w3-container";
                $guestDiv->body="G&auml;ste";
                $str=$str.$guestDiv->print();

                $guestInDiv = new div;
                $guestInDiv->indent=$indent;
                $guestInDiv->col(1, 6, 6);
                $guestInDiv->class="w3-row w3-margin-top w3-container";
                $guestInDiv->type="number";
                $guestInDiv->tag="input";
                $guestInDiv->style="width: 5em";
                $guestInDiv->min=0;
                $guestInDiv->default=0;
                $guestInDiv->value=$this->Guests;
                $guestInDiv->id="Guests".$this->Index;
                $guestInDiv->name="Guests";
                $guestInDiv->emptyBody=true;
                $str=$str.$guestInDiv->print();

                $guestSpacerDiv = new div;
                $guestSpacerDiv->indent=$indent;
                $guestSpacerDiv->col(1, 6, 6);
                $guestSpacerDiv->class="w3-hide-small w3-hide-medium";
                $str=$str.$guestSpacerDiv->print();
            }
            $emptyDiv = new div;
            $emptyDiv->indent=$indent;
            $emptyDiv->col(9, 0, 0);
            $emptyDiv->class="w3-hide-small w3-hide-medium";
            $str=$str.$emptyDiv->print();
            
            $saveBtn = new div;
            $saveBtn->indent=$indent;
            $saveBtn->tag="button";
            $saveBtn->class="w3-btn w3-row w3-border w3-border-black w3-margin-top";
            $saveBtn->col(2, 12, 12);
            $saveBtn->class=$GLOBALS['optionsDB']['colorBtnSubmit'];
            $saveBtn->name="meldungGC";
            $saveBtn->onclick="melde('".$GLOBALS['cronID']."', ".$user.", ".$this->Index.", ".$this->Wert.", -1, -1)";
            $saveBtn->bold();
            $saveBtn->body="speichern";
            $str=$str.$saveBtn->print();

            $SpacerDiv = new div;
            $SpacerDiv->indent=$indent;
            $SpacerDiv->col(1, 6, 6);
            $SpacerDiv->class="w3-hide-small, w3-hide-medium";
            $str=$str.$SpacerDiv->print();

            $str=$str.$guestChildLine->close();
            $indent--;
        }
        if($this->Shifts) {
            $shifts = $this->getShifts();
            for($i=0; $i<count($shifts); $i++) {
                $s = new Shift;
                $s->load_by_id($shifts[$i]);
                $m = new Shiftmeldung;
                $m->load_by_user_event($user, $s->Index);
                
                $shiftmain = new div;
                $shiftmain->indent=$indent;
                $shiftmain->class="w3-container w3-border-top w3-border-white w3-padding";
                $shiftmain->class=$GLOBALS['optionsDB']['HoverEffect'];
                $shiftmain->class=$this->getLineColor($m->Wert);
                $str=$str.$shiftmain->open();
                $indent++;

                $shiftSpacer = new div;
                $shiftSpacer->indent=$indent;
                $shiftSpacer->class="w3-hide-small w3-hide-medium";
                $shiftSpacer->col(3, 0, 0);
                $str=$str.$shiftSpacer->print();

                $shiftName = new div;
                $shiftName->indent=$indent;
                /* $shiftName->class="w3-margin-top"; */
                $shiftName->col(3, 0, 0);
                $shiftName->bold();
                $shiftName->body=$s->Name;
                $str=$str.$shiftName->print();

                $shiftTime = new div;
                $shiftTime->indent=$indent;
                $shiftTime->class="w3-margin-bottom";
                $shiftTime->col(3, 0, 0);
                $shiftTime->body=$s->getTime();
                $str=$str.$shiftTime->print();
                
                $btnDiv = new div;
                $btnDiv->indent=$indent;
                $btnDiv->col(2, 0, 0);
                $str=$str.$btnDiv->open();
                $indent++;
                $str=$str.$this->makeShiftButtons(3, $indent, $s->Index, $m->Wert);
                $str=$str.$btnDiv->close();
                $indent--;
                
                $valdiv = new div;
                $valdiv->indent=$indent;
                $valdiv->class="w3-center w3-padding";
                $valdiv->col(1, 0, 0);
                $valdiv->body="<i class=\"fas fa-user-friends\"></i>&nbsp;&nbsp;".$s->getResponseString();
                $str=$str.$valdiv->print();
                
                $str=$str.$shiftmain->close();
                $indent--;
            }
        }
        $str=$str.$main->close();

        $str=$str."\t<div id=\"id".$this->Index."\" class=\"w3-modal\">\n";
        $str=$str."\t\t<div class=\"w3-modal-content\">\n";
        $str=$str."\t\t\t<header class=\"w3-container ".$GLOBALS['optionsDB']['colorTitleBar']."\">\n";
        $str=$str."\t\t\t\t<span onclick=\"document.getElementById('id".$this->Index."').style.display='none'\" "; 
        $str=$str."class=\"w3-button w3-display-topright\">&times;</span>\n";
        $str=$str."\t\t\t<h2>".$this->Name."</h2>\n";
        $str=$str."\t\t</header>\n";
        $str=$str."\t\t<div class=\"w3-container w3-row w3-margin\">\n";
        $str=$str."\t\t\t<div class=\"w3-col l3\">Datum:</div>\n<div class=\"w3-col l9\"><b>".germanDate($this->Datum, 1)."</b></div>\n";
        $str=$str."\t\t</div>\n";
        $str=$str."\t\t<div class=\"w3-container w3-row w3-margin\">\n";
        $str=$str."\t\t\t<div class=\"w3-col l3\">Beginn:</div>\n<div class=\"w3-col l9\"><b>".sql2time($this->Uhrzeit)."</b></div>\n";
        $str=$str."\t\t</div>\n";
        if($this->Ende) {
            $str=$str."\t\t<div class=\"w3-container w3-row w3-margin\">\n";
            $str=$str."\t\t\t<div class=\"w3-col l3\">Ende:</div>\n<div class=\"w3-col l9\"><b>".sql2time($this->Uhrzeit2)."</b></div>\n";
            $str=$str."\t\t</div>\n";
        }
        if($this->Abfahrt && $GLOBALS['optionsDB']['showTravelTime']) {
            $str=$str."\t\t<div class=\"w3-container w3-row w3-margin\">\n";
            $str=$str."\t\t\t<div class=\"w3-col l3\">Abfahrt:</div>\n<div class=\"w3-col l9\"><b>".sql2time($this->Abfahrt)."</b></div>\n";
            $str=$str."\t\t</div>\n";
        }
        if($GLOBALS['optionsDB']['showVehicle']) {
            $str=$str."\t\t<div class=\"w3-container w3-row w3-margin\">\n";
            $str=$str."\t\t\t<div class=\"w3-col l3\">Anfahrt mit:</div>\n<div class=\"w3-col l9\"><b>".$this->vName."</b></div>\n";
            $str=$str."\t\t</div>\n";
        }
        $str=$str."\t\t<div class=\"w3-container w3-row w3-margin\">\n";
        $str=$str."\t\t\t<div class=\"w3-col l3\">Beschreibung:</div>\n<div class=\"w3-col l9\"><b>".$this->Beschreibung."</b></div>\n";
        $str=$str."\t\t</div>\n";
        $str=$str."\t\t<div class=\"w3-container w3-row w3-margin\">\n";
        $str=$str."\t\t\t<div class=\"w3-col l3\">Ort:</div>\n<div class=\"w3-col l9\"><b>".$this->Ort1."</b><br>".$this->Ort2."<br>".$this->Ort3."<br>".$this->Ort4."</div>\n";
        $str=$str."\t\t</div>\n";
        if($GLOBALS['googlemapsapi'] && ($this->Ort1 || $this->Ort2)) {
            $str=$str."\t\t<div class=\"w3-container w3-row w3-margin\">\n";
            $str=$str."\t\t\t<div class=\"w3-col l3\">Karte:</div>\n<div class=\"w3-col l9\"><iframe width=\"auto\" height=\"auto\" frameborder=\"0\" style=\"border:0\" src=\"https://www.google.com/maps/embed/v1/place?key=".$GLOBALS['googlemapsapi']."&q=".$this->Ort1."+".$this->Ort2."+".$this->Ort3."+".$this->Ort4."\" allowfullscreen></iframe></div>\n";
            $str=$str."\t\t</div>\n";
        }
        $str=$str."\t\t<div class=\"w3-container w3-row w3-margin\">\n";
        $str=$str."\t\t\t<div class=\"w3-col l3\">Auftritt:</div>\n<div class=\"w3-col l9\"><b>".bool2string($this->Auftritt)."</b></div>\n";
        $str=$str."\t\t</div>\n";
        $str=$str."\t\t<div class=\"w3-container w3-row w3-margin\">\n";
        $str=$str."\t\t\t<div class=\"w3-col l3\">Schichten zu besetzen:</div>\n<div class=\"w3-col l9\"><b>".bool2string($this->Shifts)."</b></div>\n";
        $str=$str."\t\t</div>\n";
        if($_SESSION['admin']) {
            $str=$str."\t\t<div class=\"w3-container w3-row w3-margin\">\n";
            $str=$str."\t\t\t<div class=\"w3-col l3\">sichtbar:</div>\n<div class=\"w3-col l9\"><b>".bool2string($this->published)."</b></div>\n";
            $str=$str."\t\t</div>\n";
            $str=$str."\t\t<div class=\"w3-container w3-row w3-margin\">\n";
            $str=$str."\t\t\t<div class=\"w3-col l3\">neu:</div>\n<div class=\"w3-col l9\"><b>".bool2string($this->new)."</b></div>\n";
            $str=$str."\t\t</div>\n";
            $str=$str."\t\t<form class=\"w3-center w3-bar w3-mobile\" action=\"new-termin.php\" method=\"POST\">\n";
            $str=$str."\t\t\t<button class=\"w3-button w3-center w3-mobile w3-block ".$GLOBALS['optionsDB']['colorBtnEdit']."\" type=\"submit\" name=\"id\" value=\"".$this->Index."\">bearbeiten</button>\n";
        }
        $str=$str."\t\t</form>\n";
        $str=$str."\t</div>\n";
        $str=$str."\t</div> <! -- Woher -->\n";
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

        $str = "<div onclick=\"document.getElementById('id".$this->Index."').style.display='block'\" class=\"w3-container w3-margin-top w3-border-top w3-border-black w3-center ".$GLOBALS['optionsDB']['colorTitleBar']."\"><h3>".$this->Name."</h3><p>".germanDate($this->Datum, 1)."</p></div>\n";
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
                        $antwort='ja';
                        $whoYes = $whoYes."<div class=\"w3-row ".$GLOBALS['optionsDB']['colorBtnYes']."\"><div class=\"w3-col l".$colsize[0]." m".$colsize[0]." s".$colsize[0]."\">".$row2['Vorname']." ".$row2['Nachname']."</div>\n<div class=\"w3-col l".$colsize[1]." m".$colsize[1]." s".$colsize[1]."\">".$row2['iName']."</div>";
                        $actcol=2;
                        if($GLOBALS['optionsDB']['showChildOption'] && $bus) {
                            $childrenYes+=$row2['Children'];
                            $whoYes=$whoYes."<div class=\"w3-col l".$colsize[$actcol]." m".$colsize[$actcol]." s".$colsize[$actcol]."\">";
                            if($row2['Children'] > 0) {
                                $whoYes=$whoYes."+ ".$row2['Children'];
                            }
                            else {
                                $whoYes=$whoYes."&nbsp;";
                            }
                            $whoYes=$whoYes."</div>";
                            $actcol++;
                        }
                        if($GLOBALS['optionsDB']['showGuestOption'] && $bus) {
                            $guestsYes+=$row2['Guests'];
                            $whoYes=$whoYes."<div class=\"w3-col l".$colsize[$actcol]." m".$colsize[$actcol]." s".$colsize[$actcol]."\">";
                            if($row2['Guests'] > 0) {
                                $whoYes=$whoYes."+ ".$row2['Guests'];
                            }
                            else {
                                $whoYes=$whoYes."&nbsp;";
                            }
                            $whoYes=$whoYes."</div>";
                        }
                        $whoYes=$whoYes."</div>\n";
                        break;
                    case 2:
                        $nein++;
                        $snein++;
                        $antwort='nein';
                        $whoNo = $whoNo."<div class=\"w3-row ".$GLOBALS['optionsDB']['colorBtnNo']."\"><div class=\"w3-col l".$colsize[0]." m".$colsize[0]." s".$colsize[0]."\">".$row2['Vorname']." ".$row2['Nachname']."</div>\n<div class=\"w3-col l".$colsize[1]." m".$colsize[1]." s".$colsize[1]."\">".$row2['iName']."</div>";
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
                        $antwort='vielleicht';
                        $whoMaybe = $whoMaybe."<div class=\"w3-row ".$GLOBALS['optionsDB']['colorBtnMaybe']."\"><div class=\"w3-col l".$colsize[0]." m".$colsize[0]." s".$colsize[0]."\">".$row2['Vorname']." ".$row2['Nachname']."</div>\n<div class=\"w3-col l".$colsize[1]." m".$colsize[1]." s".$colsize[1]."\">".$row2['iName']."</div>";
                        $actcol=2;
                        if($GLOBALS['optionsDB']['showChildOption'] && $bus) {
                            $childrenMaybe+=$row2['Children'];
                            $whoMaybe=$whoMaybe."<div class=\"w3-col l".$colsize[$actcol]." m".$colsize[$actcol]." s".$colsize[$actcol]."\">";
                            if($row2['Children'] > 0) {
                                $whoMaybe=$whoMaybe."+ ".$row2['Children'];
                            }
                            else {
                                $whoMaybe=$whoMaybe."&nbsp;";
                            }
                            $whoMaybe=$whoMaybe."</div>";
                            $actcol++;
                        }
                        if($GLOBALS['optionsDB']['showGuestOption'] && $bus) {
                            $guestsMaybe+=$row2['Guests'];
                            $whoMaybe=$whoMaybe."<div class=\"w3-col l".$colsize[$actcol]." m".$colsize[$actcol]." s".$colsize[$actcol]."\">";
                            if($row2['Guests'] > 0) {
                                $whoMaybe=$whoMaybe."+ ".$row2['Guests'];
                            }
                            else {
                                $whoMaybe=$whoMaybe."&nbsp;";
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
                    $str=$str."<div class=\"w3-row w3-border-bottom w3-border-black\"><div class=\"".$GLOBALS['optionsDB']['HoverEffect']." w3-col l7 m4 s4\">".$row['Name']."</div>\n<div class=\"w3-col l2 m2 s2\">".$all." / ".sprintf("%02d", $nReg)."</div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnYes']." w3-col l1 m2 s2 w3-center w3-opacity-min\">&#10004; ".$ja."</div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnNo']." w3-col l1 m2 s2 w3-center w3-opacity-min\">&#10008; ".$nein."</div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnMaybe']." w3-col l1 m2 s2 w3-center w3-opacity-min\">? ".$vielleicht."</div>\n</div>\n";
                }
            }
            if(!$filterregister) {
                if($bus && $GLOBALS['optionsDB']['showChildOption']) {
                $str=$str."<div class=\"w3-row\"><div class=\"w3-col l9 m6 s6\">Kinder</div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnYes']." w3-col l1 m2 s2 w3-center\">&#10004; ".$childrenYes."</div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnNo']." w3-col l1 m2 s2 w3-center\">&#10008; 0</div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnMaybe']." w3-col l1 m2 s2 w3-center\">? ".$childrenMaybe."</div>\n</div>\n";
            }
                if($bus && $GLOBALS['optionsDB']['showGuestOption']) {
                $str=$str."<div class=\"w3-row\"><div class=\"w3-col l9 m6 s6\">G&auml;ste</div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnYes']." w3-col l1 m2 s2 w3-center\">&#10004; ".$guestsYes."</div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnNo']." w3-col l1 m2 s2 w3-center\">&#10008; 0</div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnMaybe']." w3-col l1 m2 s2 w3-center\">? ".$guestsMaybe."</div>\n</div>\n";
            }
            $str=$str."<div class=\"w3-row\"><div class=\"".$GLOBALS['optionsDB']['HoverEffect']." w3-col l7 m4 s4\"><b>Summe</b></div>\n<div class=\"w3-col l2 m2 s2\"><b>".$sall;
            if($bus && ($GLOBALS['optionsDB']['showChildOption'] || $GLOBALS['optionsDB']['showGuestOption'])) {
                $str=$str."+".($childrenYes+$childrenMaybe+$guestsYes+$guestsMaybe);
            }
            $str=$str." / ".sprintf("%02d", $snReg)."</b></div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnYes']." w3-col l1 m2 s2 w3-center\">&#10004; ".($sja+$childrenYes+$guestsYes)."</div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnNo']." w3-col l1 m2 s2 w3-center\">&#10008; ".$snein."</div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnMaybe']." w3-col l1 m2 s2 w3-center\">? ".($svielleicht+$childrenMaybe+$guestsMaybe)."</div>\n</div>\n";
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
                    $antwort='ja';
                    $whoYes = $whoYes."<div class=\"w3-row ".$GLOBALS['optionsDB']['colorBtnYes']."\"><div class=\"w3-col l".$colsize[0]." m".$colsize[0]." s".$colsize[0]."\">".$row['Vorname']." ".$row['Nachname']."</div>\n<div class=\"w3-col l".$colsize[1]." m".$colsize[1]." s".$colsize[1]."\">&nbsp;</div>";
                    $actcol=2;
                    if($GLOBALS['optionsDB']['showChildOption'] && $bus) {
                        $childrenYes+=$row['Children'];
                        $whoYes=$whoYes."<div class=\"w3-col l".$colsize[$actcol]." m".$colsize[$actcol]." s".$colsize[$actcol]."\">";
                        if($row['Children'] > 0) {
                            $whoYes=$whoYes."+ ".$row['Children'];
                        }
                        $whoYes=$whoYes."</div>";
                        $actcol++;
                    }
                    if($GLOBALS['optionsDB']['showGuestOption'] && $bus) {
                        $guestsYes+=$row['Guests'];
                        $whoYes=$whoYes."<div class=\"w3-col l".$colsize[$actcol]." m".$colsize[$actcol]." s".$colsize[$actcol]."\">";
                        if($row['Guests'] > 0) {
                            $whoYes=$whoYes."+ ".$row['Guests'];
                        }
                        $whoYes=$whoYes."</div>";
                    }
                    $whoYes=$whoYes."</div>\n";
                    break;
                case 2:
                    $nein++;
                    $antwort='nein';
                        $whoNo = $whoNo."<div class=\"w3-row ".$GLOBALS['optionsDB']['colorBtnNo']."\"><div class=\"w3-col l".$colsize[0]." m".$colsize[0]." s".$colsize[0]."\">".$row['Vorname']." ".$row['Nachname']."</div>\n<div class=\"w3-col l".$colsize[1]." m".$colsize[1]." s".$colsize[1]."\">&nbsp;</div>";
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
                    $antwort='vielleicht';
                    $childrenMaybe+=$row['Children'];
                    $guestsMaybe+=$row['Guests'];
                        $whoMaybe = $whoMaybe."<div class=\"w3-row ".$GLOBALS['optionsDB']['colorBtnMaybe']."\"><div class=\"w3-col l".$colsize[0]." m".$colsize[0]." s".$colsize[0]."\">".$row['Vorname']." ".$row['Nachname']."</div>\n<div class=\"w3-col l".$colsize[1]." m".$colsize[1]." s".$colsize[1]."\">&nbsp;</div>";
                        $actcol=2;
                        if($GLOBALS['optionsDB']['showChildOption'] && $bus) {
                            $whoMaybe=$whoMaybe."<div class=\"w3-col l".$colsize[$actcol]." m".$colsize[$actcol]." s".$colsize[$actcol]."\">";
                            if($row['Children'] > 0) {
                                $whoMaybe=$whoMaybe."+ ".$row['Children'];
                            }
                            $whoMaybe=$whoMaybe."</div>";
                            $actcol++;
                        }
                        if($GLOBALS['optionsDB']['showGuestOption'] && $bus) {
                            $whoMaybe=$whoMaybe."<div class=\"w3-col l".$colsize[$actcol]." m".$colsize[$actcol]." s".$colsize[$actcol]."\">";
                            if($row['Guests'] > 0) {
                                $whoMaybe=$whoMaybe."+ ".$row['Guests'];
                            }
                            $whoMaybe=$whoMaybe."</div>";
                        }
                        $whoMaybe=$whoMaybe."</div>\n";
                    break;
                default:
                    break;
                }
            }
            if($bus && $GLOBALS['optionsDB']['showChildOption']) {
                $str=$str."<div class=\"w3-row\"><div class=\"w3-col l9 m6 s6\">Kinder</div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnYes']." w3-col l1 m2 s2 w3-center\">&#10004; ".$childrenYes."</div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnNo']." w3-col l1 m2 s2 w3-center\">&#10008; ".$nein."</div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnMaybe']." w3-col l1 m2 s2 w3-center\">? ".$childrenMaybe."</div>\n</div>\n";
            }
            if($bus && $GLOBALS['optionsDB']['showGuestOption']) {
                $str=$str."<div class=\"w3-row\"><div class=\"w3-col l9 m6 s6\">G&auml;ste</div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnYes']." w3-col l1 m2 s2 w3-center\">&#10004; ".$guestsYes."</div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnNo']." w3-col l1 m2 s2 w3-center\">&#10008; ".$nein."</div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnMaybe']." w3-col l1 m2 s2 w3-center\">? ".$guestsMaybe."</div>\n</div>\n";
            }
            $str=$str."<div class=\"w3-row\"><div class=\"w3-col l9 m6 s6\"><b>Summe</b></div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnYes']." w3-col l1 m2 s2 w3-center\">&#10004; ".($ja+$childrenYes+$guestsYes)."</div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnNo']." w3-col l1 m2 s2 w3-center\">&#10008; ".$nein."</div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnMaybe']." w3-col l1 m2 s2 w3-center\">? ".($vielleicht+$childrenMaybe+$guestsMaybe)."</div>\n</div>\n";
        }
        $str=$str."</div>\n";

        $str=$str."<div id=\"id".$this->Index."\" class=\"w3-modal\">";
        $str=$str."<div class=\"w3-modal-content\">";
        $str=$str."<header class=\"w3-container ".$GLOBALS['optionsDB']['colorTitleBar']."\">";
        $str=$str."<span onclick=\"document.getElementById('id".$this->Index."').style.display='none'\""; 
        $str=$str."class=\"w3-button w3-display-topright\">&times;</span>";
        $str=$str."<h2>".$this->Name."</h2>";
        $str=$str."</header>";

        $str = $str."<div class=\"w3-container w3-margin-top\"><div class=\"w3-row\"><div class=\"w3-col l".$colsize[0]." m".$colsize[0]." s".$colsize[0]."\"><b>Zusagen</b></div>\n<div class=\"w3-col l".$colsize[1]." m".$colsize[1]." s".$colsize[1]."\">&nbsp;</div>";
        $actcol=2;
        if($GLOBALS['optionsDB']['showChildOption'] && $bus) {
            $str=$str."<div class=\"w3-col l".$colsize[$actcol]." m".$colsize[$actcol]." s".$colsize[$actcol]."\">Kinder</div>";
            $actcol++;
        }
        if($GLOBALS['optionsDB']['showGuestOption'] && $bus) {
            $str=$str."<div class=\"w3-col l".$colsize[$actcol]." m".$colsize[$actcol]." s".$colsize[$actcol]."\">G&auml;ste</div>";
        }
        $str=$str."</div>\n</div>\n";

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
