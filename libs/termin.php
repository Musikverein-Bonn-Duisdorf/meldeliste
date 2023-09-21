<?php
class Termin
{
    private $_data = array('Index' => null, 'Datum' => null, 'EndDatum' => null, 'Uhrzeit' => null, 'Uhrzeit2' => null, 'Abfahrt' => null, 'Capacity' => null, 'Vehicle' => 1, 'Name' => null, 'Auftritt' => null, 'Ort1' => null, 'Ort2' => null, 'Ort3' => null, 'Ort4' => null, 'Bechreibung' => null, 'Shifts' => null, 'published' => null, 'open' => 1, 'Wert' => null, 'Children' => null, 'Guests' => null, 'new' => null, 'vName' => null);
    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'Datum':
	    case 'EndDatum':
	    case 'Uhrzeit':
	    case 'Uhrzeit2':
	    case 'Abfahrt':
        case 'Capacity':
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
        case 'Capacity':
            $this->_data[$key] = (int)$val;
            break;
	    case 'Datum':
	    case 'EndDatum':
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

    public function getChanges() {
        $old = new Termin;
        $old->load_by_id($this->Index);
        
        $str = sprintf("Termin-ID: %d, <b>%s</b>",
        $this->Index,
        $this->Name
        );
        if($this->Datum != $old->Datum) $str.=", Datum: ".$old->getDate()." &rArr; <b>".$this->getDate()."</b>";
        if($this->EndDatum != $old->EndDatum) $str.=", Enddatum: ".$old->EndDatum." &rArr; <b>".$this->EndDatum."</b>";
        if($this->Uhrzeit != $old->Uhrzeit) $str.=", Uhrzeit: ".$old->Uhrzeit." &rArr; <b>".$this->Uhrzeit."</b>";
        if($this->Uhrzeit2 != $old->Uhrzeit2) $str.=", Uhrzeit2: ".$old->Uhrzeit2." &rArr; <b>".$this->Uhrzeit2."</b>";
        if($this->Capacity != $old->Capacity) $str.=", Capacity: ".$old->Capacity." &rArr; <b>".$this->Capacity."</b>";
        if($this->Vehicle != $old->Vehicle) $str.=", Vehicle: ".$old->Vehicle." &rArr; <b>".$this->Vehicle."</b>";
        if($this->Name != $old->Name) $str.=", Name: ".$old->Name." &rArr; <b>".$this->Name."</b>";
        if($this->Auftritt != $old->Auftritt) $str.=", Auftritt: ".bool2string($old->Auftritt)." &rArr; <b>".bool2string($this->Auftritt)."</b>";
        if($this->Ort1 != $old->Ort1) $str.=", Ort1: ".$old->Ort1." &rArr; <b>".$this->Ort1."</b>";
        if($this->Ort2 != $old->Ort2) $str.=", Ort2: ".$old->Ort2." &rArr; <b>".$this->Ort2."</b>";
        if($this->Ort3 != $old->Ort3) $str.=", Ort3: ".$old->Ort3." &rArr; <b>".$this->Ort3."</b>";
        if($this->Ort4 != $old->Ort4) $str.=", Ort4: ".$old->Ort4." &rArr; <b>".$this->Ort4."</b>";
        if($this->Beschreibung != $old->Beschreibung) $str.=", Beschreibung: ".$old->Beschreibung." &rArr; <b>".$this->Beschreibung."</b>";
        if($this->published != $old->published) $str.=", published: ".bool2string($old->published)." &rArr; <b>".bool2string($this->published)."</b>";
        if($this->open != $old->open) $str.=", open: ".$old->open." &rArr; <b>".$this->open."</b>";
        if($GLOBALS['optionsDB']['showChildOption']) {
            if($this->Children != $old->Children) $str.=", Children: ".$old->Children." &rArr; <b>".$this->Children."</b>";
        }
        if($GLOBALS['optionsDB']['showGuestOption']) {
            if($this->Guests != $old->Guests) $str.=", Guests: ".$old->Guests." &rArr; <b>".$this->Guests."</b>";
        }
        
        return $str;
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
        return sprintf("Termin-ID: <b>%d</b>, Datum: <b>%s</b>, Beginn: <b>%s</b>, Ende: <b>%s</b>, Abfahrt: <b>%s</b>, mit: <b>%s</b>, max. Teilnehmer: <b>%d</b>, Name: <b>%s</b>, Auftritt: <b>%s</b>, Ort1: <b>%s</b>, Ort2: <b>%s</b>, Ort3: <b>%s</b>, Ort4: <b>%s</b>, Beschreibung: <b>%s</b>, Schichten: <b>%s</b>, sichtbar: <b>%s</b>, offen: <b>%s</b>",
        $this->Index,
        $this->getDate(),
        $this->Uhrzeit,
        $this->Uhrzeit2,
        $this->Abfahrt,
        $this->vName,
        $this->Capacity,
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
            $logentry = new Log;
            $logentry->DBupdate($this->getChanges());
            $this->update();
        }
        else {
            $this->insert();
            $logentry = new Log;
            $logentry->DBinsert($this->getVars());
            $this->makeAlwaysYes();
        }
    }
    public function is_valid() {
        if(!$this->Datum) return false;
        if(!$this->Name) return false;
        if(!$this->Vehicle) $this->Vehicle=1;
        return true;
    }
    protected function insert() {
        if($this->EndDatum) {
            $end = "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->EndDatum)."\"";
        }
        else {
            $end = "NULL";
        }
        $sql = sprintf('INSERT INTO `%sTermine` (`Datum`, `EndDatum`, `Uhrzeit`, `Uhrzeit2`, `Abfahrt`, `Capacity`, `Vehicle`, `Name`, `Beschreibung`, `Shifts`, `Auftritt`, `Ort1`, `Ort2`, `Ort3`, `Ort4`, `published`, `open`) VALUES ("%s", %s, %s, %s, %s, "%d", "%d", "%s", "%s", "%d", "%d", "%s", "%s", "%s", "%s", "%d", "%d");',
        $GLOBALS['dbprefix'],
        mysqli_real_escape_string($GLOBALS['conn'], $this->Datum),
        $end,
        $this->Uhrzeit == '' ? 'NULL': "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->Uhrzeit)."\"",
        $this->Uhrzeit2 == '' ? 'NULL': "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->Uhrzeit2)."\"",
        $this->Abfahrt == '' ? 'NULL': "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->Abfahrt)."\"",
        $this->Capacity,
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
    public function getOrt() {
        $str="";
        if($this->Ort1) $str=$str.$this->Ort1;
        if($this->Ort2) {
            if($str) $str=$str.", ";
            $str=$str.$this->Ort2;
        }
        if($this->Ort3) {
            if($str) $str=$str.", ";
            $str=$str.$this->Ort3;
        }
        if($this->Ort4) {
            if($str) $str=$str.", ";
            $str=$str.$this->Ort4;
        }
        return $str;
    }
    public function getDate() {
        if(!$this->EndDatum) return $this->Datum;
        return "(".$this->Datum." - ".$this->EndDatum.")";
    }
    public function getGermanDate() {
        if(!$this->EndDatum) return germanDate($this->Datum, 1);
        return germanDateSpan($this->Datum, $this->EndDatum);
    }
    protected function makeAlwaysYes() {
        if($this->Shifts) return;
        $users = explode(",", $GLOBALS['optionsDB']['alwaysYesNewAppmnts']);
        foreach($users as $user) {
            $m = new Meldung;
            $m->load_by_user_event(intval($user), $this->Index);
            if($m->User < 1) {
                $m = new Meldung;
                $m->User = intval($user);
                $m->Termin = $this->Index;
                $m->Wert = 1;
                $m->save();
            }
        }
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
        if($this->EndDatum) {
            $end = "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->EndDatum)."\"";
        }
        else {
            $end = "NULL";
        }
        $sql = sprintf('UPDATE `%sTermine` SET `Datum` = "%s", `EndDatum` = %s, `Uhrzeit` = %s, `Uhrzeit2` = %s, `Abfahrt` = %s, `Capacity`= "%d", `Vehicle`= "%d", `Name` = "%s", `Beschreibung` = "%s", `Shifts` = "%d", `Auftritt` = "%d", `Ort1` = "%s", `Ort2` = "%s", `Ort3` = "%s", `Ort4` = "%s", `published` = "%d", `open` = "%d", `new` = "%d" WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        mysqli_real_escape_string($GLOBALS['conn'], $this->Datum),
        $end,
        $this->Uhrzeit == '' ? 'NULL': "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->Uhrzeit)."\"",
        $this->Uhrzeit2 == '' ? 'NULL': "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->Uhrzeit2)."\"",
        $this->Abfahrt == '' ? 'NULL': "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->Abfahrt)."\"",
        $this->Capacity,
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
        $logentry = new Log;
        $logentry->DBdelete($this->getVars());

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
            $n = new Shift;
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
    public function getMeldungenByUser($user) {
        $sql = sprintf('SELECT * FROM `%sMeldungen` WHERE `Termin` = "%d" AND `User` = %d;',
        $GLOBALS['dbprefix'],
        $this->Index,
        $user
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $meldungen = array();
        while($row = mysqli_fetch_array($dbr)) {
            array_push($meldungen, $row['Index']);
        }
        return $meldungen;
    }
    public function getExtMeldungenByUser($user) {
        $sql = sprintf('SELECT * FROM `%sexternMeldungen` WHERE `Termin` = "%d" AND `User` = %d;',
        $GLOBALS['dbprefix'],
        $this->Index,
        $user
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $meldungen = array();
        while($row = mysqli_fetch_array($dbr)) {
            array_push($meldungen, $row['Index']);
        }
        return $meldungen;
    }
    public function getExtMeldungen() {
        $sql = sprintf('SELECT * FROM `%sexternMeldungen` WHERE `Termin` = "%d";',
        $GLOBALS['dbprefix'],
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $meldungen = array();
        while($row = mysqli_fetch_array($dbr)) {
            array_push($meldungen, $row['Index']);
        }
        return $meldungen;
    }
    public function getMeldungen() {
        $sql = sprintf('SELECT * FROM `%sMeldungen` WHERE `Termin` = "%d";',
        $GLOBALS['dbprefix'],
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $meldungen = array();
        while($row = mysqli_fetch_array($dbr)) {
            array_push($meldungen, $row['Index']);
        }
        return $meldungen;
    }
    public function getMeldungUsers() {
        $users = array();
        $meldungen = $this->getMeldungen();
        foreach($meldungen as &$meldung) {
            $m = new Meldung;
            $m->load_by_id($meldung);
            array_push($users, $m->User);
        }
        return $users;
    }
    public function getMissingUsers() {
        $users = getActiveUsers(null);
        $gemeldet = $this->getMeldungUsers();
        foreach($gemeldet as &$m) {
            $u = array_search($m, $users);
            unset($users[$u]);
        }
        $r = array();
        foreach($users as &$element) {
            array_push($r, $element);
        }
        return $r;
    }
    public function getMeldungenVal($val) {
        $r = 0;
        $meldungen = $this->getMeldungen();
        for($i=0; $i<count($meldungen); $i++) {
            $m = new Meldung;
            $m->load_by_id($meldungen[$i]);
            if($m->Wert == $val) $r++;
        }
        return $r;
    }
    public function getMeldungRatio() {
        $Nusers = count(getActiveUsers(NULL));
        $meldungen=count($this->getMeldungen());
        $ratio=doubleval($meldungen)/intval($Nusers);
        return $ratio;
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
    public function shiftEditLine($shift) {
        $indent=1;
        $str="";
        $s= new Shift;
        $s->load_by_id($shift);
        $shiftmain = new div;
        $shiftmain->tag = "form";
        $shiftmain->action="edit-shifts.php";
        $shiftmain->method="POST";
        $shiftmain->indent=$indent;
        $shiftmain->class="w3-border-top w3-border-white w3-padding w3-row";
        $shiftmain->class=$GLOBALS['optionsDB']['HoverEffect'];
        $shiftmain->class=$GLOBALS['optionsDB']['colorInputBackground'];
        $str=$str.$shiftmain->open();
        $indent++;

        $shiftNameStr = new div;
        $shiftNameStr->indent=$indent;
        $shiftNameStr->col(1, 6, 6);
        $shiftNameStr->class="w3-padding";
        $shiftNameStr->body="Bezeichnung:";
        $str=$str.$shiftNameStr->print();

        $shiftName = new div;
        $shiftName->indent=$indent;
        $shiftName->col(2, 0, 0);
        $shiftName->tag="input";
        $shiftName->type="text";
        $shiftName->placeholder="Bezeichnung";
        $shiftName->name="Name";
        $shiftName->class="w3-input w3-border";
        $shiftName->value=$s->Name;
        $str=$str.$shiftName->print();

        $shiftTimeStartStr = new div;
        $shiftTimeStartStr->indent=$indent;
        $shiftTimeStartStr->col(1, 6, 6);
        $shiftTimeStartStr->class="w3-padding";
        $shiftTimeStartStr->body="Beginn:";
        $str=$str.$shiftTimeStartStr->print();

        $shiftTimeStart = new div;
        $shiftTimeStart->tag="input";
        $shiftTimeStart->type="time";
        $shiftTimeStart->name="Start";
        $shiftTimeStart->indent=$indent;
        $shiftTimeStart->class="w3-input w3-border";
        $shiftTimeStart->col(1, 0, 0);
        $shiftTimeStart->value=$s->Start;
        $str=$str.$shiftTimeStart->print();

        $shiftTimeEndStr = new div;
        $shiftTimeEndStr->indent=$indent;
        $shiftTimeEndStr->col(1, 6, 6);
        $shiftTimeEndStr->class="w3-padding";
        $shiftTimeEndStr->body="Ende (optional):";
        $str=$str.$shiftTimeEndStr->print();

        $shiftTimeEnd = new div;
        $shiftTimeEnd->tag="input";
        $shiftTimeEnd->type="time";
        $shiftTimeEnd->name="End";
        $shiftTimeEnd->indent=$indent;
        $shiftTimeEnd->class="w3-input w3-border";
        $shiftTimeEnd->col(1, 0, 0);
        $shiftTimeEnd->value=$s->End;
        $str=$str.$shiftTimeEnd->print();

        $shiftBedarfStr = new div;
        $shiftBedarfStr->indent=$indent;
        $shiftBedarfStr->col(1, 6, 6);
        $shiftBedarfStr->class="w3-padding";
        $shiftBedarfStr->body="Bedarf:";
        $str=$str.$shiftBedarfStr->print();

        $shiftBedarf = new div;
        $shiftBedarf->tag="input";
        $shiftBedarf->type="number";
        $shiftBedarf->name="Bedarf";
        $shiftBedarf->min="0";
        $shiftBedarf->indent=$indent;
        $shiftBedarf->class="w3-input w3-border";
        $shiftBedarf->col(1, 0, 0);
        $shiftBedarf->value=$s->Bedarf;
        $str=$str.$shiftBedarf->print();

        $hidden = new div;
        $hidden->indent=$indent;
        $hidden->tag = "input";
        $hidden->type = "hidden";
        $hidden->name="Termin";
        $hidden->value=$this->Index;

        $str=$str.$hidden->print();

        $btncntr = new div;
        $btncntr->indent=$indent;
        $btncntr->class="w3-row w3-mobile w3-container";
        $btncntr->col(2, 12, 12);
        $str=$str.$btncntr->open();
        $indent++;
        
        $btnDiv = new div;
        $btnDiv->indent=$indent;
        $btnDiv->tag="button";
        $btnDiv->type="submit";
        $btnDiv->name="save";
        $btnDiv->body="<i class=\"fas fa-save\"></i>";
        $btnDiv->value=$s->Index;
        $btnDiv->class="w3-button w3-center w3-border w3-border-black";
        $btnDiv->class=$GLOBALS['optionsDB']['colorBtnEdit'];
        $btnDiv->col(5, 5, 5);
        $str=$str.$btnDiv->print();
        $btnSpacer = new div;
        $btnSpacer->indent=$indent;
        $btnSpacer->col(1,1,1);
        $str=$str.$btnSpacer->print();
        
        if($s->Index) {
            $btnDiv = new div;
            $btnDiv->indent=$indent;
            $btnDiv->onclick="document.getElementById('delmodal".$s->Index."').style.display='block'";
            $btnDiv->body="<i class=\"fas fa-trash-alt\"></i>";
            $btnDiv->class="w3-button w3-center w3-border w3-border-black w3-hover";
            $btnDiv->class=$GLOBALS['optionsDB']['colorBtnEdit'];
            $btnDiv->col(5, 5, 5);
            $str=$str.$btnDiv->print();
        }
        $str=$str.$btncntr->close();
        $indent--;

        $str=$str.$shiftmain->close();
        $indent--;
        
        if($s->Index) {
            $divModal = new div;
            $divModal->id="delmodal".$s->Index;
            $divModal->class="w3-modal";
            $divModal->indent=$indent;
            $str=$str.$divModal->open();
            $indent++;
        
            $divCard = new div;
            $divCard->indent=$indent;
            $divCard->class="w3-modal-content w3-card";
            $str=$str.$divCard->open();
            $indent++;
        
            $divHeader = new div;
            $divHeader->indent=$indent;
            $divHeader->tag="header";
            $divHeader->class="w3-container w3-row";
            $divHeader->class=$GLOBALS['optionsDB']['colorTitleBar'];
            $str=$str.$divHeader->open();
            $indent++;
        
            $divSpan = new div;
            $divSpan->indent=$indent;
            $divSpan->onclick="document.getElementById('delmodal".$s->Index."').style.display='none'";
            $divSpan->class="w3-button w3-display-topright";
            $str=$str.$divSpan->print();

            $divH = new div;
            $divH->indent=$indent;
            $divH->tag="h2";
            $divH->body="L&ouml;schen best&auml;tigen";
            $str=$str.$divH->print();
            
            $str=$str.$divHeader->close();
            $indent--;
        
            $divBody = new div;
            $divBody->indent=$indent;
            $divBody->class="w3-container w3-row w3-center w3-padding w3-margin w3-card";
            $divBody->class=$GLOBALS['optionsDB']['colorWarning'];
            $divBody->body="Sind Sie sicher, dass sie <b>".$s->Name." ".$s->getTime()."</b> l&ouml;schen wollen?<br />Alle Meldungen zu dieser Schicht werden ebenfalls gel&ouml;scht.";
            $str=$str.$divBody->print();

            $divForm1 = new div;
            $divForm1->indent=$indent;
            $divForm1->class="w3-container w3-mobile";
            $str=$str.$divForm1->open();
            $indent++;
        
            $divForm = new div;
            $divForm->tag="form";
            $divForm->indent=$indent;
            $divForm->action="edit-shifts.php";
            $divForm->method="POST";
            $str=$str.$divForm->open();
            $indent++;
            
            $divHiddenIndex = new div;
            $divHiddenIndex->indent=$indent;
            $divHiddenIndex->tag="input";
            $divHiddenIndex->type="hidden";
            $divHiddenIndex->name="Termin";
            $divHiddenIndex->value=$this->Index;
            $str=$str.$divHiddenIndex->print();

            $divRow = new div;
            $divRow->indent=$indent;
            $divRow->class="w3-row";
            $str=$str.$divRow->open();
            $indent++;
            
            $divSpacer = new div;
            $divSpacer->indent=$indent;
            $divSpacer->class="w3-center";
            $divSpacer->col(4, 4, 2);
            $str=$str.$divSpacer->print();

            $divBtnYes = new div;
            $divBtnYes->indent=$indent;
            $divBtnYes->tag="button";
            $divBtnYes->type="submit";
            $divBtnYes->name="delete";
            $divBtnYes->value=$s->Index;
            $divBtnYes->class="w3-btn w3-center w3-border w3-margin-bottom w3-mobile";
            $divBtnYes->class=$GLOBALS['optionsDB']['colorBtnSubmit'];
            $divBtnYes->body="ja";
            $divBtnYes->col(4, 4, 8);
            $str=$str.$divBtnYes->print();
            
            $str=$str.$divSpacer->print();
            
            $str=$str.$divRow->close();
            $indent--;
            
            $str=$str.$divForm->close();
            $indent--;
            
            $str=$str.$divRow->open();
            $indent++;
            $str=$str.$divSpacer->print();
            
            $divBtnNo = new div;
            $divBtnNo->indent=$indent;
            $divBtnNo->tag="button";
            $divBtnNo->onclick="document.getElementById('delmodal".$s->Index."').style.display='none'";
            $divBtnNo->class="w3-btn w3-center w3-border w3-margin-bottom w3-mobile";
            $divBtnNo->class=$GLOBALS['optionsDB']['colorBtnSubmit'];
            $divBtnNo->body="nein";
            $divBtnNo->col(4, 4, 8);
            $str=$str.$divBtnNo->print();
            
            $str=$str.$divSpacer->print();
            $str=$str.$divRow->close();
            $indent--;
            
            $str=$str.$divForm1->close();
            $indent--;
            $str=$str.$divCard->close();
            $indent--;
            $str=$str.$divModal->close();
            $indent--;
        }
        
        return $str;
    }
    public function printShiftEdit() {
        $str='';
        $indent = 1;
        foreach($this->getShifts() as &$shift) {
            $str=$str.$this->ShiftEditLine($shift);
        }
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
        $str=$str."\t<div class=\"w3-row w3-container ".$GLOBALS['optionsDB']['colorAppmntYes']." w3-mobile w3-margin-top\">";
        $str=$str."\t\t<div class=\"w3-col l".$colsize." m".$colsize." s".$colsize."\"><b>Summe</b></div>";
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
        default:
            return null;
        }
        return null;
    }
    protected function mainColor() {
        $c = $this->globalShiftColor();
        if($c) return;
        if(!$this->Shifts) {
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
    }
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
            $str=$str.$this->getGermanDate().", ".sql2timeRaw($this->Uhrzeit);
            if($this->Uhrzeit2) $str=$str." - ".sql2time($this->Uhrzeit2);
        }
        else {
            $str=$str.$this->getGermanDate();
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
        return $this->makeButtonsUser($N, $indent, $val, $this->getUser());
    }
    protected function makeButtonsUser($N, $indent, $val, $user) {
        $symbols = array("&#10004;", "&#10008;", "<b>?</b>");
        $colors = array($GLOBALS['optionsDB']['colorBtnYes'], $GLOBALS['optionsDB']['colorBtnNo'], $GLOBALS['optionsDB']['colorBtnMaybe']);
        
        $str="";
        for($i=1; $i<=$N; $i++) {
            $btn = new div;
            $btn->indent = $indent;
            $btn->class="w3-col s3 m3 l3";
            $btn->class="w3-margin-left";
            if($this->open == false && requirePermission("perm_editResponse") == false) {
                if($GLOBALS['optionsDB']['AppmntAlwaysDecline']) {
                    if($i != 2) {
                        $str=$str.$btn->print();
                        continue;
                    }
                }
                else {
                    $str=$str.$btn->print();
                    continue;
                }
            }
            $btn->tag="button";
            $btn->class="w3-btn";
            $btn->class="w3-border";
            $btn->class="w3-border-black";
            /* $btn->class="w3-margin-top"; */
            $btn->class="w3-center";
            $btn->body=$symbols[$i-1];

            if($val && $val != $i) {
                $btn->class=$GLOBALS['optionsDB']['colorDisabled'];
            }
            else {
                $btn->class=$colors[$i-1];
            }
            if($val != $i) {
                $btn->onclick="melde('".$GLOBALS['cronID']."', ".$user.", ".$this->Index.", ".$i.", ".(int)$this->Children.", ".(int)$this->Guests.")";
                $btn->name="meldung";
                $btn->value=$i;
            }
            $str=$str.$btn->print();
        }
        return $str;
    }
    protected function makeTrackButtonsUser($N, $indent, $val, $user) {
        $symbols = array("&#10004;", "&#10008;", "<b>?</b>");
        $colors = array($GLOBALS['optionsDB']['colorBtnYes'], $GLOBALS['optionsDB']['colorBtnNo'], $GLOBALS['optionsDB']['colorBtnMaybe']);
        
        $str="";
        for($i=1; $i<=$N; $i++) {
            $btn = new div;
            $btn->indent = $indent;
            $btn->class="w3-col s3 m3 l3";
            $btn->class="w3-margin-left";
            if($this->open == false && requirePermission("perm_editResponse") == false) {
                if($GLOBALS['optionsDB']['AppmntAlwaysDecline']) {
                    if($i != 2) {
                        $str=$str.$btn->print();
                        continue;
                    }
                }
                else {
                    $str=$str.$btn->print();
                    continue;
                }
            }
            $btn->tag="button";
            $btn->class="w3-btn";
            $btn->class="w3-border";
            $btn->class="w3-border-black";
            /* $btn->class="w3-margin-top"; */
            $btn->class="w3-center";
            $btn->body=$symbols[$i-1];

            if($val && $val != $i) {
                $btn->class=$GLOBALS['optionsDB']['colorDisabled'];
            }
            else {
                $btn->class=$colors[$i-1];
            }
            if($val != $i) {
                $btn->onclick="track('".$GLOBALS['cronID']."', ".$user.", ".$this->Index.", ".$i.", ".(int)$this->Children.", ".(int)$this->Guests.")";
                $btn->name="meldung";
                $btn->value=$i;
            }
            $str=$str.$btn->print();
        }
        return $str;
    }
    protected function makeShiftButtonsUser($N, $indent, $shift, $val, $user) {
        $symbols = array("&#10004;", "&#10008;", "<b>?</b>");
        $colors = array($GLOBALS['optionsDB']['colorBtnYes'], $GLOBALS['optionsDB']['colorBtnNo'], $GLOBALS['optionsDB']['colorBtnMaybe']);
        
        $str="";
        for($i=1; $i<=$N; $i++) {
            $btn = new div;
            $btn->indent = $indent;

            $btn->class="w3-col s3 m3 l3";
            $btn->class="w3-margin-left";
            if(!$this->open && !requirePermission("perm_editResponse")) {
                if($GLOBALS['optionsDB']['AppmntAlwaysDecline']) {
                    if($i != 2) {
                        $str=$str.$btn->print();
                        continue;
                    }
                }
                else {
                    $str=$str.$btn->print();
                    continue;
                }
            }
            $btn->tag="button";
            $btn->class="w3-btn";
            $btn->class="w3-border";
            $btn->class="w3-border-black";
            /* $btn->class="w3-margin-top"; */
            $btn->class="w3-center";
            $btn->body=$symbols[$i-1];

            if($val && $val != $i) {
                $btn->class=$GLOBALS['optionsDB']['colorDisabled'];
            }
            else {
                $btn->class=$colors[$i-1];
            }
            if($val != $i) {
                $btn->onclick="meldeShift('".$GLOBALS['cronID']."', ".$user.", ".$shift.", ".$this->Index.", ".$i.")";
                $btn->name="meldungShift";
                $btn->value=$i;
            }
            $str=$str.$btn->print();
        }
        return $str;
    }
    protected function makeShiftButtons($N, $indent, $shift, $val) {
        return makeShiftButtonsUser($N, $indent, $shift, $val, $this->User);
    }
    protected function makeExtShiftButtons($N, $indent, $shift, $val) {
        $symbols = array("&#10004;", "&#10008;", "<b>?</b>");
        $colors = array($GLOBALS['optionsDB']['colorBtnYes'], $GLOBALS['optionsDB']['colorBtnNo'], $GLOBALS['optionsDB']['colorBtnMaybe']);
        
        $str="";
        for($i=1; $i<=$N; $i++) {
            $btn = new div;
            $btn->indent = $indent;

            $btn->class="w3-col s3 m3 l3";
            $btn->class="w3-margin-left";
            if(!$this->open && !requirePermission("perm_editResponse")) {
                if($GLOBALS['optionsDB']['AppmntAlwaysDecline']) {
                    if($i != 2) {
                        $str=$str.$btn->print();
                        continue;
                    }
                }
                else {
                    $str=$str.$btn->print();
                    continue;
                }
            }
            $btn->tag="button";
            $btn->class="w3-btn";
            $btn->class="w3-border";
            $btn->class="w3-border-black";
            /* $btn->class="w3-margin-top"; */
            $btn->class="w3-center";
            $btn->body=$symbols[$i-1];

            if($val && $val != $i) {
                $btn->class=$GLOBALS['optionsDB']['colorDisabled'];
            }
            else {
                $btn->class=$colors[$i-1];
            }
            if($val != $i) {
                $btn->onclick="meldeExtShift('".$GLOBALS['cronID']."', ".$this->getUser().", ".$shift.", ".$this->Index.", ".$i.")";
                $btn->name="meldungExtShift";
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
        $admStatusDiv->class="w3-mobile";
        if(requirePermission("perm_sendEmail") && $GLOBALS['optionsDB']['statusPerMail']) {
            $admStatusDiv->tag="button";
            $admStatusDiv->class="w3-margin-top";
            $admStatusDiv->class="w3-btn";
            $admStatusDiv->class="w3-border";
            $admStatusDiv->class="w3-border-black";
            $admStatusDiv->class=$GLOBALS['optionsDB']['colorBtnSubmit'];
            $admStatusDiv->onclick="getStatus('".$GLOBALS['cronID']."', ".$user.", ".$this->Index.")";
            $admStatusDiv->body="Status per Mail";
        }
        else {
            $admStatusDiv->class="w3-center";
            $admStatusDiv->class="w3-padding";
            if($this->Capacity) {
                $admStatusDiv->body="<i class=\"fas fa-user-friends\"></i>&nbsp;&nbsp;".$this->getResponseString();
            }
            else {
                $admStatusDiv->class="w3-hide-small";
                $admStatusDiv->class="w3-hide-medium";
            }
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
        $main->id="entry".$this->Index."_user".$user;
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
        //$nameDiv->onclick="document.getElementById('id".$this->Index."').style.display='block'";
        $nameDiv->col(3, 0, 0);
        $nameDiv->bold();
        if($GLOBALS['optionsDB']['showAddToCalendarButton']) {
            $nameDiv->body="<form id=\"icalform".$this->Index."\" method=\"post\" action=\"download-ics.php\"><i onclick=\"document.getElementById('id".$this->Index."').style.display='block'\" style=\"font-size: 30px;\" class=\"fa fa-info-circle\"></i>&nbsp;&nbsp;<input type=\"hidden\" name=\"appID\" value=\"".$this->Index."\"></input><i onclick=\"document.getElementById('icalform".$this->Index."').submit();\" style=\"font-size: 30px;\" class=\"fa fa-calendar-plus\"></i>&nbsp;&nbsp;".$this->Name."</form>";
        }
        else {
            $nameDiv->body="<i onclick=\"document.getElementById('id".$this->Index."').style.display='block'\" style=\"font-size: 30px;\" class=\"fa fa-info-circle\"></i>&nbsp;&nbsp;".$this->Name;
        }
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
        $btnDiv->col(2, 0, 0);
        $btnDiv->class="w3-row w3-mobile";
        if($this->Shifts) {
            $str=$str.$btnDiv->print();
        }
        else {
            $str=$str.$btnDiv->open();
            $indent++;
            if($this->Capacity) {
                if($this->Capacity > $this->getMeldungenVal(1) || $this->Wert == 1 || requirePermission("perm_editResponse")) {
                    $str=$str.$this->makeButtons(2, $indent, $this->Wert);
                }
                else {
                    $closed = new div;
            $closed->class="w3-col s9 m9 l9";
            $closed->class="w3-margin-left w3-center w3-padding";
            $closed->body="Alle Pl&auml;tze belegt";
            $str=$str.$closed->print();
                }
            }
            else {
                $str=$str.$this->makeButtons(3, $indent, $this->Wert);
            }
            $indent--;
            $str=$str.$btnDiv->close();
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
                $shiftmain->class="w3-border-top w3-border-white w3-padding w3-row";
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
                $shiftName->col(3, 0, 0);
                $shiftName->bold();
                $shiftName->body=$s->Name;
                $str=$str.$shiftName->print();

                $shiftTime = new div;
                $shiftTime->indent=$indent;
                $shiftTime->class="w3-margin-bottom";
                $shiftTime->col(3, 0, 0);
                if($s->Start != $s->End) {
                    $shiftTime->body=$s->getTime();
                }
                $str=$str.$shiftTime->print();
                
                $btnDiv = new div;
                $btnDiv->indent=$indent;
                $btnDiv->col(2, 0, 0);
                $str=$str.$btnDiv->open();
                $indent++;
                if($s->Bedarf) {
                    if($s->Bedarf > $s->getMeldungenVal(1) || $m->Wert == 1 || requirePermission("perm_editResponse")) {
                        $str=$str.$this->makeShiftButtonsUser(2, $indent, $s->Index, $m->Wert, $user);
                    }
                    else {
                        $closed = new div;
                        $closed->class="w3-col s9 m9 l9";
                        $closed->class="w3-margin-left w3-center w3-padding";
                        $closed->body="Alle Pl&auml;tze belegt";
                        $str=$str.$closed->print();
                    }
                }
                else {
                    $str=$str.$this->makeShiftButtonsUser(3, $indent, $s->Index, $m->Wert, $user);
                }
                /* $str=$str.$this->makeShiftButtons(3, $indent, $s->Index, $m->Wert); */
                $str=$str.$btnDiv->close();
                $indent--;
                
                $valdiv = new div;
                $valdiv->indent=$indent;
                $valdiv->class="w3-center w3-padding";
                $valdiv->col(1, 0, 0);
                if($s->Bedarf) {
                    $valdiv->body="<i class=\"fas fa-user-friends\"></i>&nbsp;&nbsp;".$s->getResponseString();
                }
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
        $str=$str."\t\t\t<div class=\"w3-col l3\">Datum:</div>\n<div class=\"w3-col l9\"><b>".$this->getGermanDate()."</b></div>\n";
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
        if($this->Capacity) {
            $str=$str."\t\t<div class=\"w3-container w3-row w3-margin\">\n";
            $str=$str."\t\t\t<div class=\"w3-col l3\">Pl&auml;tze:</div>\n<div class=\"w3-col l9\"><b>".$this->Capacity."</b></div>\n";
            $str=$str."\t\t</div>\n";
        }
        $str=$str."\t\t<div class=\"w3-container w3-row w3-margin\">\n";
        $str=$str."\t\t\t<div class=\"w3-col l3\">Auftritt:</div>\n<div class=\"w3-col l9\"><b>".bool2string($this->Auftritt)."</b></div>\n";
        $str=$str."\t\t</div>\n";
        $str=$str."\t\t<div class=\"w3-container w3-row w3-margin\">\n";
        $str=$str."\t\t\t<div class=\"w3-col l3\">Schichten zu besetzen:</div>\n<div class=\"w3-col l9\"><b>".bool2string($this->Shifts)."</b></div>\n";
        $str=$str."\t\t</div>\n";
        if($this->Auftritt) {
            $str=$str."\t\t<div class=\"w3-container w3-row w3-margin\">\n";

            $u = new User;
            $u->load_by_id($this->getUser());
            $instrument = $u->Instrument;

            $m = new Meldung;
            $m->load_by_user_event($this->getUser(), $this->Index);
            if($m->Index) {
                if($m->Instrument) $instrument = $m->Instrument;
            }

            $str=$str."\t\t\t<div class=\"w3-col l3\">Instrument f&uuml;r diesen Auftritt:</div>\n<div class=\"w3-col l4\"><select id=\"iSelect".$this->Index."\" class=\"w3-input\" name=\"Instrument\">".instrumentOption($instrument)."</select></div>\n";
            $str=$str."\t\t\t<button class=\"w3-col l1 w3-button ".$GLOBALS['optionsDB']['colorBtnEdit']."\" onclick=\"changeInstrument('".$GLOBALS['cronID']."', ".$this->getUser().", ".$this->Index.");\"><i class=\"fas fa-save\"></i></button>";
            $str=$str."\t\t</div>\n";
        }
        if(requirePermission("perm_editAppmnts")) {
            $str=$str."\t\t<div class=\"w3-container w3-row w3-margin\">\n";
            $str=$str."\t\t\t<div class=\"w3-col l3\">sichtbar:</div>\n<div class=\"w3-col l9\"><b>".bool2string($this->published)."</b></div>\n";
            $str=$str."\t\t</div>\n";
            $str=$str."\t\t<div class=\"w3-container w3-row w3-margin\">\n";
            $str=$str."\t\t\t<div class=\"w3-col l3\">Anmeldung offen:</div>\n<div class=\"w3-col l9\"><b>".bool2string($this->open)."</b></div>\n";
            $str=$str."\t\t</div>\n";
            $str=$str."\t\t<div class=\"w3-container w3-row w3-margin\">\n";
            $str=$str."\t\t\t<div class=\"w3-col l3\">neu:</div>\n<div class=\"w3-col l9\"><b>".bool2string($this->new)."</b></div>\n";
            $str=$str."\t\t</div>\n";
            $str=$str."\t\t<div class=\"w3-container w3-row w3-margin\">\n";
            $str=$str."\t\t\t<div class=\"w3-col l3\">ID:</div>\n<div class=\"w3-col l9\"><b>".$this->Index."</b></div>\n";
            $str=$str."\t\t</div>\n";

            if(!$this->Shifts) {
                // { Aushilfen
                $div = new div;
                $div->tag="form";
                $div->method="POST";
                $div->action="";
                $div->class="w3-container w3-row w3-margin w3-card w3-padding";
                $str.=$div->open();

                $aushilfe = new div;
                $aushilfe->class="w3-row w3-margin-bottom";
                $aushilfe->body="<b>Aushilfen</b>";
                $str.=$aushilfe->print();

                $row = new div;
                $row->class="w3-row w3-margin-bottom";
                $str.=$row->open();
                $aushilfe = new div;
                $aushilfe->class="w3-col l3";
                $aushilfe->body="&nbsp;";
                $str.=$aushilfe->print();

                $aushilfe = new div;
                $aushilfe->class="w3-col l2";
                $aushilfe->body="Name:";
                $str.=$aushilfe->print();

                $aushilfe = new div;
                $aushilfe->class="w3-col l3 w3-input w3-border";
                $aushilfe->class=$GLOBALS['optionsDB']['colorInputBackground'];
                $aushilfe->type="text";
                $aushilfe->tag="input";
                $aushilfe->name="Name";
                $str.=$aushilfe->print();
                $str.=$row->close();

                $str.=$row->open();
                $aushilfe = new div;
                $aushilfe->class="w3-col l3";
                $aushilfe->body="&nbsp;";
                $str.=$aushilfe->print();

                $aushilfe = new div;
                $aushilfe->class="w3-col l2";
                $aushilfe->body="Instrument:";
                $str.=$aushilfe->print();

                $aushilfe = new div;
                $aushilfe->tag="input";
                $aushilfe->type="hidden";
                $aushilfe->name="Termin";
                $aushilfe->value=$this->Index;
                $str.=$aushilfe->print();

                $aushilfe = new div;
                $aushilfe->tag="select";
                $aushilfe->class="w3-col l3 w3-input";
                $aushilfe->name="Instrument";
                $aushilfe->body=instrumentOption(0);
                $str.=$aushilfe->print();
                $str.=$row->close();

                $row = new div;
                $row->class="w3-row";
                $str.=$row->open();
                $aushilfe = new div;
                $aushilfe->class="w3-col l5";
                $aushilfe->body="&nbsp;";
                $str.=$aushilfe->print();

                $aushilfe = new div;
                $aushilfe->class="w3-col l3 w3-btn w3-input";
                $aushilfe->class=$GLOBALS['optionsDB']['colorBtnSubmit'];
                $aushilfe->tag="input";
                $aushilfe->type="submit";
                $aushilfe->name="insertAushilfe";
                $aushilfe->value="eintragen";
                $str.=$aushilfe->print();
                $str.=$row->close();

                $str.=$div->close();

                // aktive Aushilfen
                $div = new div;
                $div->class="w3-container w3-row w3-margin w3-card w3-padding";
                $str.=$div->open();

                $aushilfe = new div;
                $aushilfe->class="w3-row w3-margin-bottom";
                $aushilfe->body="<b>aktive Aushilfen</b>";
                $str.=$aushilfe->print();

                $str.=$this->aktiveAushilfenTermin();
                
                $str.=$div->close();
                // } Aushilfen
            }
            
            $str=$str."\t\t<form class=\"w3-center w3-bar w3-mobile\" action=\"new-termin.php\" method=\"POST\">\n";
            $str=$str."\t\t\t<button class=\"w3-button w3-center w3-mobile w3-block ".$GLOBALS['optionsDB']['colorBtnEdit']."\" type=\"submit\" name=\"id\" value=\"".$this->Index."\">bearbeiten</button>\n";
            $str=$str."\t\t\t<button class=\"w3-button w3-center w3-mobile w3-block ".$GLOBALS['optionsDB']['colorBtnEdit']."\" type=\"submit\" name=\"copy\" value=\"".$this->Index."\">kopieren</button>\n";
            $str=$str."\t\t</form>\n";
        }
        if(requirePermission("perm_editResponse")) {
                $str=$str."\t\t<form class=\"w3-center w3-bar w3-mobile\" action=\"tracking.php\" method=\"POST\">\n";
                $str=$str."\t\t\t<button class=\"w3-button w3-center w3-mobile w3-block ".$GLOBALS['optionsDB']['colorBtnEdit']."\" type=\"submit\" name=\"termin\" value=\"".$this->Index."\">Anwesenheitsliste</button>\n";
                $str=$str."\t\t</form>\n";
            }
            if(requirePermission("perm_sendEmail")) {
                $str=$str."\t\t<form class=\"w3-center w3-bar w3-mobile\" action=\"mail.php\" method=\"POST\">\n";
                $str=$str."\t\t\t<button class=\"w3-button w3-center w3-mobile w3-block ".$GLOBALS['optionsDB']['colorBtnEdit']."\" type=\"submit\" name=\"termin\" value=\"".$this->Index."\">Email an Teilnehmer</button>\n";
                $str=$str."\t\t</form>\n";
            }
        if(requirePermission("perm_editAppmnts")) {
            if($this->Shifts) {
                $str=$str."\t\t<form class=\"w3-center w3-bar w3-mobile\" action=\"edit-shifts.php\" method=\"POST\">\n";
                $str=$str."\t\t\t<button class=\"w3-button w3-center w3-mobile w3-block w3-margin-top ".$GLOBALS['optionsDB']['colorBtnEdit']."\" type=\"submit\" name=\"Termin\" value=\"".$this->Index."\">Schichten bearbeiten</button>\n";
                $str=$str."\t\t</form>\n";
            }
        }
        $str=$str."\t</div>\n";
        $str=$str."\t</div> <! -- Woher -->\n";
        return $str;
    }
    public function getResponseString() {
        $str=$this->getMeldungenVal(1);
        $str=$str." / ".$this->Capacity;
        return $str;
    }
    public function printMyResponseLine() {
        if($this->Shifts) return $this->printShiftResponseLine();
        $u = new User;
        $u->load_by_id($_SESSION['userid']);
        return $this->getResponseLine($u->getRegister());
    }
    public function printResponseLine() {
        if($this->Shifts) return $this->printShiftResponseLine();
        return $this->getResponseLine(0);
    }
    public function printShiftResponseLine() {
        $str="";
        $indent=1;

        $containerdiv = new div;
        $containerdiv->indent=$indent;
        $containerdiv->class="w3-margin-top w3-border w3-border-black w3-card";
        $containerdiv->class=$GLOBALS['optionsDB']['colorInputBackground'];
        $str=$str.$containerdiv->open();
        $indent++;
        
        $maindiv = new div;
        $maindiv->indent=$indent;
        $maindiv->class="w3-container w3-center";
        $str=$str.$maindiv->open();
        $indent++;
        
        $mainheader = new div;
        $mainheader->tag="h3";
        $mainheader->class="w3-left";
        $mainheader->body=$this->Name;
        $mainheader->indent=$indent;
        $str=$str.$mainheader->print();

        $mainheader = new div;
        $mainheader->tag="p";
        $mainheader->class="w3-right";
        $mainheader->body=$this->getGermanDate();
        $mainheader->indent=$indent;
        $str=$str.$mainheader->print();

        $str=$str.$maindiv->close();
        $indent--;

        $content = new div;
        $content->indent=$indent;
        $content->class="w3-container w3-margin-bottom";
        $str=$str.$content->open();
        $indent++;
        
        $shifts = $this->getShifts();
        for($i=0; $i<count($shifts); $i++) {
            $s = new Shift;
            $s->load_by_id($shifts[$i]);
            $shift = new div;
            $shift->indent=$indent;
            $shift->onclick="document.getElementById('ids".$s->Index."').style.display='block'";
            $shift->class="w3-row w3-border-bottom w3-border-black";
            $str=$str.$shift->open();
            $indent++;
            
            $shiftname = new div;
            $shiftname->indent=$indent;
            $shiftname->class="w3-col l4 m2 s2";
            $shiftname->body=$s->Name;
            $shiftname->bold();
            $str=$str.$shiftname->print();

            $shifttime = new div;
            $shifttime->indent=$indent;
            $shifttime->class="w3-col l4 m2 s2";
            $shifttime->body=$s->getTime();
            $str=$str.$shifttime->print();

            $shiftbedarf = new div;
            $shiftbedarf->indent=$indent;
            $shiftbedarf->class="w3-col l1 m2 s2 w3-center";
            $shiftbedarf->body="<i class=\"fas fa-user-friends\"></i> ";
            $shiftbedarf->body=$s->Bedarf;
            $str=$str.$shiftbedarf->print();

            $shiftresponseY = new div;
            $shiftresponseY->indent=$indent;
            $shiftresponseY->class="w3-col l1 m2 s2 w3-center";
            $shiftresponseY->class=$GLOBALS['optionsDB']['colorBtnYes'];
            $shiftresponseY->body="&#10004; ";
            $shiftresponseY->body=$s->getMeldungenVal(1)+$s->getAushilfenVal();
            $str=$str.$shiftresponseY->print();

            $shiftresponseN = new div;
            $shiftresponseN->indent=$indent;
            $shiftresponseN->class="w3-col l1 m2 s2 w3-center";
            $shiftresponseN->class=$GLOBALS['optionsDB']['colorBtnNo'];
            $shiftresponseN->body="&#10008; ";
            $shiftresponseN->body=$s->getMeldungenVal(2);
            $str=$str.$shiftresponseN->print();

            $shiftresponseM = new div;
            $shiftresponseM->indent=$indent;
            $shiftresponseM->class="w3-col l1 m2 s2 w3-center";
            $shiftresponseM->class=$GLOBALS['optionsDB']['colorBtnMaybe'];
            $shiftresponseM->body="? ";
            $shiftresponseM->body=$s->getMeldungenVal(3);
            $str=$str.$shiftresponseM->print();

            $str=$str.$shift->close();
            $indent--;

            $modal = new div;
            $modal->indent=$indent;
            $modal->id="ids".$s->Index;
            $modal->class="w3-modal";
            $str=$str.$modal->open();
            $indent++;
        
            $modalcontent = new div;
            $modalcontent->indent=$indent;
            $modalcontent->class="w3-modal-content";
            $str=$str.$modalcontent->open();
            $indent++;

            $modalheader = new div;
            $modalheader->tag="header";
            $modalheader->class="w3-container";
            $modalheader->class=$GLOBALS['optionsDB']['colorTitleBar'];
            $modalheader->indent=$indent;
            $str=$str.$modalheader->open();

            $modalclose = new div;
            $modalclose->tag="span";
            $modalclose->class="w3-button w3-display-topright";
            $modalclose->onclick="document.getElementById('ids".$s->Index."').style.display='none'";
            $modalclose->body="&times;";
            $indent++;
            $modalclose->indent=$indent;
            $str=$str.$modalclose->print();

            $modaltitle = new div;
            $modaltitle->tag="h2";
            $modaltitle->body=$this->Name." - ".$s->Name." ".$s->getTime();
            $modaltitle->indent=$indent;
            $str=$str.$modaltitle->print();

            $str=$str.$modalheader->close();
            $indent--;

            $modalbody = new div;
            $modalbody->indent = $indent;
            $modalbody->class="w3-container";
            $str=$str.$modalbody->open();
            $indent++;
            
            $modalY = new div;
            $modalY->indent=$indent;
            $modalY->class="w3-row w3-margin-top";
            $modalY->bold();
            $modalY->body="Zusagen";
            $str=$str.$modalY->open();
            $indent++;
            
            $u = $s->getMeldungenUser(1);
            for($j=0; $j<count($u); $j++) {
                $udiv = new div;
                $udiv->indent=$indent;
                $udiv->class="w3-row";
                $udiv->class=$GLOBALS['optionsDB']['colorBtnYes'];
                $udiv->body=$u[$j];
                $str=$str.$udiv->print();
            }
            $u = $s->getMeldungenAushilfenShift();
            for($j=0; $j<count($u); $j++) {
                $udiv = new div;
                $udiv->indent=$indent;
                $udiv->class="w3-row";
                $udiv->class=$GLOBALS['optionsDB']['colorBtnYes'];
                $udiv->body=$u[$j];
                $str=$str.$udiv->print();
            }
            $str=$str.$modalY->close();
            $indent--;

            $modalM = new div;
            $modalM->indent=$indent;
            $modalM->class="w3-row w3-margin-top";
            $modalM->bold();
            $modalM->body="unsicher";
            $str=$str.$modalM->open();
            $indent++;
            
            $u = $s->getMeldungenUser(3);
            for($j=0; $j<count($u); $j++) {
                $udiv = new div;
                $udiv->indent=$indent;
                $udiv->class="w3-row";
                $udiv->class=$GLOBALS['optionsDB']['colorBtnMaybe'];
                $udiv->body=$u[$j];
                $str=$str.$udiv->print();
            }
            $str=$str.$modalM->close();
            $indent--;

            $modalN = new div;
            $modalN->indent=$indent;
            $modalN->class="w3-row w3-margin-top";
            $modalN->bold();
            $modalN->body="Absagen";
            $str=$str.$modalN->open();
            $indent++;
            
            $u = $s->getMeldungenUser(2);
            for($j=0; $j<count($u); $j++) {
                $udiv = new div;
                $udiv->indent=$indent;
                $udiv->class="w3-row";
                $udiv->class=$GLOBALS['optionsDB']['colorBtnNo'];
                $udiv->body=$u[$j];
                $str=$str.$udiv->print();
            }
            $str=$str.$modalN->close();
            $indent--;

            $str=$str.$modalbody->close();
            $indent--;
            
            $str=$str.$modalcontent->close();
            $indent--;
            $str=$str.$modal->close();
            $indent--;

        }
                
        $str=$str.$content->close();
        $indent--;
        $str=$str.$containerdiv->close();
        $indent--;

        return $str;
    }

    public function getAushilfenRegister($filterregister) {
        $sql = sprintf("SELECT * FROM `%sAushilfen` INNER JOIN (SELECT `Index` AS `iIndex`, `Register` FROM `%sInstrument`) `%sInstrument` ON `Instrument` = `iIndex` WHERE `Termin` = \"%d\" AND `Register` = \"%d\";",
                       $GLOBALS['dbprefix'],
                       $GLOBALS['dbprefix'],
                       $GLOBALS['dbprefix'],
                       $this->Index,
                       $filterregister
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $aushilfen = array();
        while($row = mysqli_fetch_array($dbr)) {
            $aushilfen[] = $row;
        }
        return $aushilfen;
    }

    public function getAushilfen() {
        $sql = sprintf("SELECT * FROM `%sAushilfen` WHERE `Termin` = %d",
                       $GLOBALS['dbprefix'],
                       $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $aushilfen = array();
        while($row = mysqli_fetch_array($dbr)) {
            $aushilfen[] = $row['Index'];
        }
        return $aushilfen;
    }

    public function aktiveAushilfenTermin() {
        $str="";
        $aushilfen = $this->getAushilfen();
        foreach ($aushilfen as $aushilfe) {
            $a = new Aushilfe;
            $a->load_by_id($aushilfe);
            $str.=$a->TerminLine();
        }
        return $str;
    }

    public function getResponseLine($filterregister) {
        $sql = sprintf("(SELECT `Index`, `Timestamp`, `User`, `Termin`, `Wert`, `Instrument` AS `mInstrument`, `Guests`, `Nachname`, `Vorname`, `iName`, `Children`, `Register`, `rIndex`, `rName` FROM `%sMeldungen`
INNER JOIN (SELECT `Index` AS `uIndex`, `Vorname`, `Nachname`, `Instrument` AS `iInstrument` FROM `%sUser`) `%sUser` ON `User` = `uIndex`
INNER JOIN (SELECT `Index` AS `iIndex`, `Register`, `Name` AS `iName` FROM `%sInstrument`) `%sInstrument` ON `%sUser`.`iInstrument` = `iIndex`
INNER JOIN (SELECT `Index` AS `rIndex`, `Name` AS `rName`, `Sortierung` FROM `%sRegister`) `%sRegister` ON `Register` = `rIndex`
WHERE `Termin` = '%d' AND `%sMeldungen`.`Instrument` = '0')

UNION

(SELECT `Index`, `Timestamp`, `User`, `Termin`, `Wert`, `Instrument` AS `iInstrument`, `Guests`, `Nachname`, `Vorname`, `iName`, `Children`, `Register`, `rIndex`, `rName` FROM `%sMeldungen`
INNER JOIN (SELECT `Index` AS `uIndex`, `Vorname`, `Nachname`, `Instrument` AS `mInstrument` FROM `%sUser`) `%sUser` ON `User` = `uIndex`
INNER JOIN (SELECT `Index` AS `iIndex`, `Register`, `Name` AS `iName` FROM `%sInstrument`) `%sInstrument` ON `%sMeldungen`.`Instrument` = `iIndex`
INNER JOIN (SELECT `Index` AS `rIndex`, `Name` AS `rName`, `Sortierung` FROM `%sRegister`) `%sRegister` ON `Register` = `rIndex`
WHERE `Termin` = '%d' AND `%sMeldungen`.`Instrument` != '0')

ORDER BY `Nachname`, `Vorname`;",
                       $GLOBALS['dbprefix'],
                       $GLOBALS['dbprefix'],
                       $GLOBALS['dbprefix'],
                       $GLOBALS['dbprefix'],
                       $GLOBALS['dbprefix'],
                       $GLOBALS['dbprefix'],
                       $GLOBALS['dbprefix'],
                       $GLOBALS['dbprefix'],
                       $this->Index,
                       $GLOBALS['dbprefix'],
                       $GLOBALS['dbprefix'],
                       $GLOBALS['dbprefix'],
                       $GLOBALS['dbprefix'],
                       $GLOBALS['dbprefix'],
                       $GLOBALS['dbprefix'],
                       $GLOBALS['dbprefix'],
                       $GLOBALS['dbprefix'],
                       $GLOBALS['dbprefix'],
                       $this->Index,
                       $GLOBALS['dbprefix']
        );
        $dbr2 = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $aMeldungen = array();
        while($row = mysqli_fetch_array($dbr2)) {
            $aMeldungen[] = $row;
        }      
        
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

        $str = "<div class=\"w3-card w3-border w3-margin-top w3-border-black ".$GLOBALS['optionsDB']['colorInputBackground']."\"><div onclick=\"document.getElementById('id".$this->Index."').style.display='block'\" class=\"w3-container w3-center\"><h3 class=\"w3-left\">".$this->Name."</h3><p class=\"w3-right\">".$this->getGermanDate()."</p></div>\n";
        $str=$str."<div onclick=\"document.getElementById('id".$this->Index."').style.display='block'\" class=\"w3-container w3-margin-bottom\">\n";
        $whoYes = '';
        $whoNo = '';
        $whoMaybe = '';

        $aushilfen=array();
        if($this->Auftritt) {
            if($filterregister) {
                $sql = sprintf("SELECT * FROM `%sRegister` WHERE `Name` != 'keins' AND `Index` = '%d' ORDER BY `Sortierung`;",
                $GLOBALS['dbprefix'],
                $filterregister
                );
                $aushilfen = $this->getAushilfenRegister($filterregister);
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
                $aushilfen = $this->getAushilfenRegister($row['Index']);
                $nReg = $register->members();
                $snReg+=$nReg;
                $ja=0;
                $nein=0;
                $vielleicht=0;
                // while($row2 = mysqli_fetch_array($dbr2)) {
                foreach($aMeldungen as $row2) {
                    if($row2['rIndex'] != $row['Index']) continue;
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

                // { Aushilfen
                foreach($aushilfen as &$aushilfe) {
                    $A = new Aushilfe;
                    $A->load_by_id($aushilfe['Index']);
                    $ja++;
                    $sja++;
                    $antwort='ja';
                    $whoYes = $whoYes."<div class=\"w3-row ".$GLOBALS['optionsDB']['colorBtnYes']."\"><div class=\"w3-col l".$colsize[0]." m".$colsize[0]." s".$colsize[0]."\">".$A->getName()."</div>\n<div class=\"w3-col l".$colsize[1]." m".$colsize[1]." s".$colsize[1]."\">".$A->getInstrumentName()."</div>";
                    $whoYes=$whoYes."</div>\n";
                }
                // } Aushilfen
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
            // { Aushilfen
            $aushilfen = $this->getAushilfen();
            foreach($aushilfen as &$aushilfe) {
                $A = new Aushilfe;
                $A->load_by_id($aushilfe['Index']);
                $ja++;
                $antwort='ja';
                $whoYes = $whoYes."<div class=\"w3-row ".$GLOBALS['optionsDB']['colorBtnYes']."\"><div class=\"w3-col l".$colsize[0]." m".$colsize[0]." s".$colsize[0]."\">".$A->getName()."</div>\n<div class=\"w3-col l".$colsize[1]." m".$colsize[1]." s".$colsize[1]."\">&nbsp;</div>";
                $whoYes=$whoYes."</div>\n";
            }
            // } Aushilfen

            if($bus && $GLOBALS['optionsDB']['showChildOption']) {
                $str=$str."<div class=\"w3-row\"><div class=\"w3-col l9 m6 s6\">Kinder</div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnYes']." w3-col l1 m2 s2 w3-center\">&#10004; ".$childrenYes."</div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnNo']." w3-col l1 m2 s2 w3-center\">&#10008; ".$nein."</div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnMaybe']." w3-col l1 m2 s2 w3-center\">? ".$childrenMaybe."</div>\n</div>\n";
            }
            if($bus && $GLOBALS['optionsDB']['showGuestOption']) {
                $str=$str."<div class=\"w3-row\"><div class=\"w3-col l9 m6 s6\">G&auml;ste</div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnYes']." w3-col l1 m2 s2 w3-center\">&#10004; ".$guestsYes."</div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnNo']." w3-col l1 m2 s2 w3-center\">&#10008; ".$nein."</div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnMaybe']." w3-col l1 m2 s2 w3-center\">? ".$guestsMaybe."</div>\n</div>\n";
            }
            $str=$str."<div class=\"w3-row\"><div class=\"w3-col l9 m6 s6\"><b>Summe</b></div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnYes']." w3-col l1 m2 s2 w3-center\">&#10004; ".($ja+$childrenYes+$guestsYes)."</div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnNo']." w3-col l1 m2 s2 w3-center\">&#10008; ".$nein."</div>\n<div class=\"".$GLOBALS['optionsDB']['colorBtnMaybe']." w3-col l1 m2 s2 w3-center\">? ".($vielleicht+$childrenMaybe+$guestsMaybe)."</div>\n</div>\n";
        }
        $str=$str."</div></div>\n";
        
        $str=$str."<div id=\"id".$this->Index."\" class=\"w3-modal\">";
        $str=$str."<div class=\"w3-modal-content\">";
        $str=$str."<header class=\"w3-container ".$GLOBALS['optionsDB']['colorTitleBar']."\">";
        $str=$str."<span onclick=\"document.getElementById('id".$this->Index."').style.display='none'\""; 
        $str=$str."class=\"w3-button w3-display-topright\">&times;</span>";
        $str=$str."<h2>".$this->Name."</h2>";
        $str=$str."</header>";

        $str = $str."<div>";
        if($GLOBALS['optionsDB']['showOrchestraView']) {
            $str = $str."<div class=\"w3-container w3-margin-top\"><b>Besetzung</b></div>\n";
            $str = $str."<div class=\"w3-container w3-hide-small w3-hide-medium\">\n";
            $str = $str.printOrchestra($this->Index, 1);
            $str = $str."</div>";
            $str = $str."<div class=\"w3-container w3-hide-small w3-hide-large\">\n";
            $str = $str.printOrchestra($this->Index, 0.6);
            $str = $str."</div>";
            $str = $str."<div class=\"w3-container w3-hide-large w3-hide-medium\">\n";
            $str = $str.printOrchestra($this->Index, 0.4);
            $str = $str."</div>";
        }
        $str = $str."</div>";
        
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

        if(requirePermission("perm_editResponse")) {
                    $str = $str."<div class=\"w3-container w3-margin-top\"><b>noch nicht gemeldet</b></div>\n";
            $str = $str."<form class=\"w3-container w3-row\" action=\"termine.php\" method=\"POST\">";
            foreach($this->getMissingUsers() as &$missing) {
                $u = new User;
                $u->load_by_id($missing);
                $str=$str."<button class=\"w3-btn w3-border w3-margin-top w3-border-black w3-col s12 l4 m6 ".$GLOBALS['optionsDB']['colorBtnSubmit']."\" type=\"submit\" name=\"proxy\" value=\"".$u->Index."\">".$u->getName()."</button>\n";
            }
            $str = $str."</form>";
        }
        
        $str=$str."<div class=\"w3-container w3-margin-bottom\"><br />";
        $str=$str."</div>";
        $str=$str."</div>";
        $str=$str."</div>";

        return $str;
    }

    public function TrackingUser($user) {
        $u = new User;
        $u->load_by_id($user);
        $meldungen = $this->getMeldungenByUser($user);
        if($meldungen) {
            $m = new Meldung;
            $m->load_by_id($meldungen[0]);
            $this->Wert = $m->Wert;
        }
        else {
            $this->Wert = NULL;
        }
        $str="";
        $indent=0;
        
        $main = new div;
        $main->indent = $indent;
        $main->id="entry".$this->Index."_user".$user;
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
        $nameDiv->col(3, 0, 0);
        $nameDiv->bold();
        $nameDiv->body=$u->getName();
        $str=$str.$nameDiv->print();

        $startDiv = new div;
        $startDiv->indent=$indent;
        $startDiv->col(3, 0, 0);
        $startDiv->body=$u->getInstrument();
        $str=$str.$startDiv->print();

        $ortDiv = new div;
        $ortDiv->indent=$indent;
        $ortDiv->class="w3-margin-bottom";
        $ortDiv->col(3, 0, 0);
        $ortDiv->body=germanDate($u->LastLogin, 1);
        $str=$str.$ortDiv->print();

        $btnDiv = new Div;
        $btnDiv->indent = $indent;
        $btnDiv->col(2, 0, 0);
        $btnDiv->class="w3-row w3-mobile";
        if($this->Shifts) {
            $str=$str.$btnDiv->print();
        }
        else {
            $str=$str.$btnDiv->open();
            $indent++;
            $str=$str.$this->makeTrackButtonsUser(3, $indent, $this->Wert, $user);
            $indent--;
            $str=$str.$btnDiv->close();
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
            $saveBtn->onclick="track('".$GLOBALS['cronID']."', ".$user.", ".$this->Index.", ".$this->Wert.", -1, -1)";
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
                $shiftmain->class="w3-border-top w3-border-white w3-padding w3-row";
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
                $shiftName->col(3, 0, 0);
                $shiftName->bold();
                $shiftName->body=$s->Name;
                $str=$str.$shiftName->print();

                $shiftTime = new div;
                $shiftTime->indent=$indent;
                $shiftTime->class="w3-margin-bottom";
                $shiftTime->col(3, 0, 0);
                if($s->Start != $s->End) {
                    $shiftTime->body=$s->getTime();
                }
                $str=$str.$shiftTime->print();
                
                $btnDiv = new div;
                $btnDiv->indent=$indent;
                $btnDiv->col(2, 0, 0);
                $str=$str.$btnDiv->open();
                $indent++;
                if($s->Bedarf) {
                    if($s->Bedarf > $s->getMeldungenVal(1) || $m->Wert == 1 || requirePermission("perm_editResponse")) {
                        $str=$str.$this->makeShiftButtonsUser(2, $indent, $s->Index, $m->Wert, $user);
                    }
                    else {
                        $closed = new div;
                        $closed->class="w3-col s9 m9 l9";
                        $closed->class="w3-margin-left w3-center w3-padding";
                        $closed->body="Alle Pl&auml;tze belegt";
                        $str=$str.$closed->print();
                    }
                }
                else {
                    $str=$str.$this->makeShiftButtonsUser(3, $indent, $s->Index, $m->Wert, $user);
                }
                $str=$str.$btnDiv->close();
                $indent--;
                
                $valdiv = new div;
                $valdiv->indent=$indent;
                $valdiv->class="w3-center w3-padding";
                $valdiv->col(1, 0, 0);
                if($s->Bedarf) {
                    $valdiv->body="<i class=\"fas fa-user-friends\"></i>&nbsp;&nbsp;".$s->getResponseString();
                }
                $str=$str.$valdiv->print();

                $str=$str.$shiftmain->close();
                $indent--;
            }
        }
        $str=$str.$main->close();

        $str=$str."\t</div>\n";
        $str=$str."\t</div> <! -- Woher -->\n";
        return $str;
    }
    public function TrackingTable() {
        $sql = sprintf('SELECT `Index`, `Name` FROM `%sRegister` WHERE `Name` != "keins" ORDER BY `Sortierung`;',
        $GLOBALS['dbprefix']
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $str = "";
        while($row = mysqli_fetch_array($dbr)) {
            $M = new Register;
            $M->load_by_id($row['Index']);

            $str=$str."<div class=\"w3-container ".$GLOBALS['optionsDB']['colorTitleBar']." w3-margin-top\"><h3>".$M->Name." (".$M->members().")</h3></div>";

            $members = $M->getMembers();
            foreach($members as &$member) {
                $str=$str.$this->TrackingUser($member);
            }
        }
        return $str;
    }
};
?>
