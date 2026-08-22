<?php
class Termin
{
    private $_data = array('Index' => null, 'Datum' => null, 'EndDatum' => null, 'Uhrzeit' => null, 'Uhrzeit2' => null, 'Abfahrt' => null, 'Capacity' => null, 'Vehicle' => 1, 'Name' => null, 'Auftritt' => null, 'Ort1' => null, 'Ort2' => null, 'Ort3' => null, 'Ort4' => null, 'Beschreibung' => null, 'Shifts' => null, 'open' => 1, 'Wert' => null, 'Children' => null, 'Guests' => null, 'new' => null, 'vName' => null, 'defaultFreeText' => null, 'VisibilitySpec' => null, 'GuestMusicians' => null, 'Sammlungen' => null, 'PostDiscord' => 0, 'Created' => null, 'Updated' => null);
    /** @var array<int,int>|null */
    private $_meldungenCountsByWert = null;
    /** @var array<int,string>|null */
    private $_freeTextByUser = null;
    /** @var int|null Override for getUser() when rendering proxy/AJAX rows (MELD-153) */
    private $_renderUserOverride = null;
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
	    case 'open':
	    case 'Wert':
	    case 'Children':
	    case 'Guests':
	    case 'vName':
	    case 'new':
        case 'defaultFreeText':
        case 'VisibilitySpec':
        case 'GuestMusicians':
        case 'Sammlungen':
        case 'PostDiscord':
        case 'Created':
        case 'Updated':
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
        case 'PostDiscord':
            $this->_data[$key] = (int)$val;
            break;
	    case 'Datum':
	    case 'EndDatum':
	    case 'Uhrzeit':
	    case 'Uhrzeit2':
	    case 'Abfahrt':
        case 'defaultFreeText':
        case 'VisibilitySpec':
        case 'GuestMusicians':
        case 'Sammlungen':
        case 'Created':
        case 'Updated':
            $this->_data[$key] = trim((string)$val);
            break;
	    case 'Name':
	    case 'Beschreibung':
	    case 'vName':
	    case 'Ort1':
	    case 'Ort2':
	    case 'Ort3':
	    case 'Ort4':
            $this->_data[$key] = trim((string)$val);
            break;
	    case 'Auftritt':
	    case 'Shifts':
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
        
        $str = sprintf("Termin-ID: %d, Termin: (%d) <b>%s</b>",
        $this->Index,
        $this->Index,
        $this->Name
        );
        if($this->Datum != $old->Datum) $str.=", Datum: ".$old->getDate()." &rArr; <b>".$this->getDate()."</b>";
        if($this->EndDatum != $old->EndDatum) $str.=", Enddatum: ".medDate($old->EndDatum)." &rArr; <b>".medDate($this->EndDatum)."</b>";
        if($this->Uhrzeit != $old->Uhrzeit) $str.=", Uhrzeit: ".sql2timeRaw($old->Uhrzeit)." &rArr; <b>".sql2timeRaw($this->Uhrzeit)."</b>";
        if($this->Uhrzeit2 != $old->Uhrzeit2) $str.=", Uhrzeit2: ".sql2timeRaw($old->Uhrzeit2)." &rArr; <b>".sql2timeRaw($this->Uhrzeit2)."</b>";
        if($this->Capacity != $old->Capacity) $str.=", Capacity: ".$old->Capacity." &rArr; <b>".$this->Capacity."</b>";
        if(!empty($GLOBALS['optionsDB']['showTravelTime']) && $this->Abfahrt != $old->Abfahrt) {
            $str.=", Abfahrt: ".sql2timeRaw($old->Abfahrt)." &rArr; <b>".sql2timeRaw($this->Abfahrt)."</b>";
        }
        if(!empty($GLOBALS['optionsDB']['showVehicle']) && $this->Vehicle != $old->Vehicle) {
            $str.=", Vehicle: ".$old->Vehicle." &rArr; <b>".$this->Vehicle."</b>";
        }
        if($this->Name != $old->Name) $str.=", Name: ".$old->Name." &rArr; <b>".$this->Name."</b>";
        if(boolsDiffer($this->Auftritt, $old->Auftritt)) $str.=", Besetzung: ".bool2string($old->Auftritt)." &rArr; <b>".bool2string($this->Auftritt)."</b>";
        if(boolsDiffer($this->Shifts, $old->Shifts)) $str.=", Schichten &amp; Aufgaben: ".bool2string($old->Shifts)." &rArr; <b>".bool2string($this->Shifts)."</b>";
        if($this->Ort1 != $old->Ort1) $str.=", Ort1: ".$old->Ort1." &rArr; <b>".$this->Ort1."</b>";
        if($this->Ort2 != $old->Ort2) $str.=", Ort2: ".$old->Ort2." &rArr; <b>".$this->Ort2."</b>";
        if($this->Ort3 != $old->Ort3) $str.=", Ort3: ".$old->Ort3." &rArr; <b>".$this->Ort3."</b>";
        if($this->Ort4 != $old->Ort4) $str.=", Ort4: ".$old->Ort4." &rArr; <b>".$this->Ort4."</b>";
        if($this->Beschreibung != $old->Beschreibung) $str.=", Beschreibung: ".$old->Beschreibung." &rArr; <b>".$this->Beschreibung."</b>";
        $oldVis = AudienceSpec::canonicalJson($old->VisibilitySpec, array('allowNamedGroups' => true));
        $newVis = AudienceSpec::canonicalJson($this->VisibilitySpec, array('allowNamedGroups' => true));
        if($oldVis !== $newVis) {
            $str.=", sichtbar für: ".htmlspecialchars($old->getVisibilityLabel(), ENT_QUOTES, 'UTF-8')
                ." &rArr; <b>".htmlspecialchars($this->getVisibilityLabel(), ENT_QUOTES, 'UTF-8')."</b>";
        }
        $oldGuests = $old->getGuestMusiciansArray();
        $newGuests = $this->getGuestMusiciansArray();
        sort($oldGuests);
        sort($newGuests);
        if($oldGuests !== $newGuests) {
            $str.=", Gastmusiker: ".count($oldGuests)." &rArr; <b>".count($newGuests)."</b>";
        }
        $oldSammlungen = $old->getSammlungenArray();
        $newSammlungen = $this->getSammlungenArray();
        sort($oldSammlungen);
        sort($newSammlungen);
        if($oldSammlungen !== $newSammlungen) {
            $str.=", Sammlungen: ".count($oldSammlungen)." &rArr; <b>".count($newSammlungen)."</b>";
        }
        if(boolsDiffer($this->PostDiscord, $old->PostDiscord)) {
            $str.=", Discord: ".bool2string($old->PostDiscord)." &rArr; <b>".bool2string($this->PostDiscord)."</b>";
        }
        if(boolsDiffer($this->open, $old->open)) $str.=", open: ".bool2string($old->open)." &rArr; <b>".bool2string($this->open)."</b>";
        if(boolsDiffer($this->new, $old->new)) $str.=", neu: ".bool2string($old->new)." &rArr; <b>".bool2string($this->new)."</b>";
        if(!empty($GLOBALS['optionsDB']['showChildOption'])) {
            if($this->Children != $old->Children) $str.=", Children: ".$old->Children." &rArr; <b>".$this->Children."</b>";
        }
        if(!empty($GLOBALS['optionsDB']['showGuestOption'])) {
            if($this->Guests != $old->Guests) $str.=", Guests: ".$old->Guests." &rArr; <b>".$this->Guests."</b>";
        }
        if($this->defaultFreeText != $old->defaultFreeText) $str.=", Ort4: ".$old->defaultFreeText." &rArr; <b>".$this->defaultFreeText."</b>"; 
        return $str;
    }
    
    public function getVars() {
        if(!empty($GLOBALS['optionsDB']['showVehicle']) && !$this->vName && $this->Vehicle) {
            $sql = sprintf('SELECT * FROM `%svehicle` WHERE `Index` = %d;',
            $GLOBALS['dbprefix'],
            $this->Vehicle
            );
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
            sqlerror();
            $row = mysqli_fetch_array($dbr);
            $this->vName = $row ? $row['Name'] : '';
        }
        $parts = array();
        $parts[] = sprintf('Termin-ID: %d', (int)$this->Index);
        if($this->Name !== null && $this->Name !== '') {
            $parts[] = sprintf('Termin: (%d) <b>%s</b>', (int)$this->Index, $this->Name);
        }
        $dateStr = $this->getDate();
        if($dateStr !== null && $dateStr !== '') {
            $parts[] = sprintf("Datum: <b>%s</b>", $dateStr);
        }
        if($this->Uhrzeit !== null && $this->Uhrzeit !== '') {
            $parts[] = sprintf("Beginn: <b>%s</b>", sql2timeRaw($this->Uhrzeit));
        }
        if($this->Uhrzeit2 !== null && $this->Uhrzeit2 !== '') {
            $parts[] = sprintf("Ende: <b>%s</b>", sql2timeRaw($this->Uhrzeit2));
        }
        if(!empty($GLOBALS['optionsDB']['showTravelTime']) && $this->Abfahrt !== null && $this->Abfahrt !== '') {
            $parts[] = sprintf("Abfahrt: <b>%s</b>", sql2timeRaw($this->Abfahrt));
        }
        if(!empty($GLOBALS['optionsDB']['showVehicle']) && $this->vName !== null && $this->vName !== '') {
            $parts[] = sprintf("mit: <b>%s</b>", $this->vName);
        }
        if((int)$this->Capacity > 0) {
            $parts[] = sprintf("max. Teilnehmer: <b>%d</b>", (int)$this->Capacity);
        }
        if($this->Auftritt) {
            $parts[] = "Besetzung: <b>".bool2string($this->Auftritt)."</b>";
        }
        if($this->Ort1 !== null && $this->Ort1 !== '') {
            $parts[] = sprintf("Ort1: <b>%s</b>", $this->Ort1);
        }
        if($this->Ort2 !== null && $this->Ort2 !== '') {
            $parts[] = sprintf("Ort2: <b>%s</b>", $this->Ort2);
        }
        if($this->Ort3 !== null && $this->Ort3 !== '') {
            $parts[] = sprintf("Ort3: <b>%s</b>", $this->Ort3);
        }
        if($this->Ort4 !== null && $this->Ort4 !== '') {
            $parts[] = sprintf("Ort4: <b>%s</b>", $this->Ort4);
        }
        if($this->Beschreibung !== null && $this->Beschreibung !== '') {
            $parts[] = sprintf("Beschreibung: <b>%s</b>", $this->Beschreibung);
        }
        if($this->Shifts) {
            $parts[] = "Schichten &amp; Aufgaben: <b>".bool2string($this->Shifts)."</b>";
        }
        $parts[] = "sichtbar für: <b>".htmlspecialchars($this->getVisibilityLabel(), ENT_QUOTES, 'UTF-8')."</b>";
        if((int)$this->PostDiscord) {
            $parts[] = "Discord: <b>".bool2string($this->PostDiscord)."</b>";
        }
        $parts[] = "offen: <b>".bool2string($this->open)."</b>";
        if($this->defaultFreeText !== null && $this->defaultFreeText !== '') {
            $parts[] = sprintf("FreeText: <b>%s</b>", $this->defaultFreeText);
        }
        return implode(', ', $parts);
    }
    public function save() {
        if(!$this->is_valid()) return false;
        if($this->defaultFreeText == "") $this->defaultFreeText = null;
        if($this->Index > 0) {
            $logentry = new Log;
            $logentry->DBupdate($this->getChanges());
            $this->update();

            if($this->shouldPublishToDiscord()) {
                $this->publishToDiscord(true);
            }
        }
        else {
            $this->insert();
            $logentry = new Log;
            $logentry->DBinsert($this->getVars());

            if($this->isListed()) {
	        $this->makeAlwaysYes();
        	$this->makeAlwaysMaybe();
            }
            if($this->shouldPublishToDiscord()) {
                $this->publishToDiscord(false);
            }
        }
    }

    /**
     * Discord when listed, webhook configured, and PostDiscord checkbox set.
     */
    public function shouldPublishToDiscord() {
        if(!$this->isListed()) {
            return false;
        }
        if(!class_exists('Discord') || !Discord::isConfigured()) {
            return false;
        }
        return (int)$this->PostDiscord > 0;
    }

    /**
     * Post appointment to Discord when shouldPublishToDiscord(). Never echoes; logs errors only.
     * @param bool $isUpdate true for update message, false for new appointment
     */
    private function publishToDiscord($isUpdate) {
        if(!$this->shouldPublishToDiscord()) {
            return;
        }
        $webhookUrl = isset($GLOBALS['optionsDB']['DiscordWebHookURL'])
            ? trim((string)$GLOBALS['optionsDB']['DiscordWebHookURL'])
            : '';
        if($webhookUrl === '') {
            return;
        }
        $botname = isset($GLOBALS['optionsDB']['DiscordBotName'])
            ? (string)$GLOBALS['optionsDB']['DiscordBotName']
            : 'Bot';
        $message = $isUpdate ? $this->DiscordMessageUpdate() : $this->DiscordMessage();
        try {
            $discord = new Discord($webhookUrl);
            if(!$discord->hasValidWebhookUrl()) {
                return;
            }
            $discord->sendMessage($message, $botname);
        } catch (Exception $e) {
            $logentry = new Log;
            $logentry->error(sprintf(
                'Discord-Post fehlgeschlagen | Termin-ID: <b>%d</b>, Name: <b>%s</b>, Exception: <b>%s</b>',
                (int)$this->Index,
                htmlspecialchars((string)$this->Name),
                htmlspecialchars($e->getMessage())
            ));
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
        $sql = sprintf('INSERT INTO `%sTermine` (`Datum`, `EndDatum`, `Uhrzeit`, `Uhrzeit2`, `Abfahrt`, `Capacity`, `Vehicle`, `Name`, `Beschreibung`, `Shifts`, `Auftritt`, `Ort1`, `Ort2`, `Ort3`, `Ort4`, `open`, `defaultFreeText`, `VisibilitySpec`, `GuestMusicians`, `Sammlungen`, `PostDiscord`) VALUES ("%s", %s, %s, %s, %s, "%d", "%d", "%s", "%s", "%d", "%d", "%s", "%s", "%s", "%s", "%d", "%s", %s, %s, %s, "%d");',
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
                       $this->open,
                       mysqli_real_escape_string($GLOBALS['conn'], (string)$this->defaultFreeText),
                       $this->sqlVisibilitySpec(),
                       $this->sqlGuestMusicians(),
                       $this->sqlSammlungen(),
                       (int)$this->PostDiscord
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
        if(!$this->EndDatum) return medDate($this->Datum);
        return "(".medDate($this->Datum)." - ".medDate($this->EndDatum).")";
    }
    public function getGermanDate() {
        if(!$this->EndDatum) return germanDate($this->Datum, 1);
        return germanDateSpan($this->Datum, $this->EndDatum);
    }

    /**
     * Plain text used by client-side Termin/Rückmeldungen search (data-search).
     */
    public function getSearchText() {
        $parts = array(
            (string)$this->Name,
            (string)$this->Beschreibung,
            (string)$this->getOrt(),
            (string)$this->getGermanDate(),
            (string)germanWeekdayShort($this->Datum),
            (string)$this->Datum,
            (string)$this->EndDatum,
            (string)$this->Uhrzeit,
            (string)$this->Uhrzeit2,
        );
        $parts = array_filter($parts, function($p) {
            return $p !== '';
        });
        return preg_replace('/\s+/u', ' ', trim(implode(' ', $parts)));
    }

    public function getSearchDataAttr() {
        return 'data-search="'.htmlspecialchars($this->getSearchText(), ENT_QUOTES, 'UTF-8').'"';
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
    protected function makeAlwaysMaybe() {
        if($this->Shifts) return;
        $users = explode(",", $GLOBALS['optionsDB']['alwaysMaybeNewAppmnts']);
        foreach($users as $user) {
            $m = new Meldung;
            $m->load_by_user_event(intval($user), $this->Index);
            if($m->User < 1) {
                $m = new Meldung;
                $m->User = intval($user);
                $m->Termin = $this->Index;
                $m->Wert = 3;
                $m->save();
            }
        }
    }
    protected function getUser() {
        if($this->_renderUserOverride !== null && (int)$this->_renderUserOverride > 0) {
            return (int)$this->_renderUserOverride;
        }
        if(isset($_POST['proxy'])) {
            return $_POST['proxy'];
        }
        if(isset($_GET['user'])) {
            return $_GET['user'];
        }
        // AJAX melde/meldeshift speichern die Zielperson als POST user (MELD-153)
        if(isset($_POST['user']) && (int)$_POST['user'] > 0) {
            return (int)$_POST['user'];
        }
        if(isset($_SESSION['proxy']) && (int)$_SESSION['proxy'] > 0) {
            return (int)$_SESSION['proxy'];
        }
        if(isset($_SESSION['userid'])) {
            return $_SESSION['userid'];
        }
        return 0;
    }

    /** Force list/modal rendering for a specific user (im Auftrag / AJAX). */
    public function setRenderUser($userId) {
        $userId = (int)$userId;
        $this->_renderUserOverride = $userId > 0 ? $userId : null;
    }

    /**
     * Load Wert/Children/Guests for $userId onto this Termin (MELD-153).
     */
    public function loadMeldungStateForUser($userId) {
        $userId = (int)$userId;
        $this->Wert = null;
        $this->Children = null;
        $this->Guests = null;
        if($userId < 1 || !(int)$this->Index) {
            return;
        }
        $sql = sprintf(
            'SELECT `Wert`, `Children`, `Guests` FROM `%sMeldungen` WHERE `Termin` = "%d" AND `User` = "%d";',
            $GLOBALS['dbprefix'],
            (int)$this->Index,
            $userId
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = $dbr ? mysqli_fetch_array($dbr) : null;
        if(is_array($row)) {
            $this->fill_from_array($row);
        }
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
        $sql = sprintf('UPDATE `%sTermine` SET `Datum` = "%s", `EndDatum` = %s, `Uhrzeit` = %s, `Uhrzeit2` = %s, `Abfahrt` = %s, `Capacity`= "%d", `Vehicle`= "%d", `Name` = "%s", `Beschreibung` = "%s", `Shifts` = "%d", `Auftritt` = "%d", `Ort1` = "%s", `Ort2` = "%s", `Ort3` = "%s", `Ort4` = "%s", `open` = "%d", `new` = "%d", `defaultFreeText` = "%s", `VisibilitySpec` = %s, `GuestMusicians` = %s, `Sammlungen` = %s, `PostDiscord` = "%d", `Updated` = CURRENT_TIMESTAMP WHERE `Index` = "%d";',
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
        $this->open,
        $this->new,
                       mysqli_real_escape_string($GLOBALS['conn'], (string)$this->defaultFreeText),
                       $this->sqlVisibilitySpec(),
                       $this->sqlGuestMusicians(),
                       $this->sqlSammlungen(),
                       (int)$this->PostDiscord,
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
            if($key === 'VisibilitySpec' || $key === 'visibilitySpec'
                || $key === 'GuestMusicians' || $key === 'guestMusicians'
                || $key === 'Sammlungen' || $key === 'sammlungen') {
                continue;
            }
            if(array_key_exists($key, $this->_data)) {
                $this->_data[$key] = $val;
            }
        }
        $hadVisibility = isset($row['VisibilitySpec']) || isset($row['visibilitySpec']);
        $hadGuests = isset($row['GuestMusicians']) || isset($row['guestMusicians']);
        $hadSammlungen = isset($row['Sammlungen']) || isset($row['sammlungen']);
        if($hadVisibility) {
            $raw = isset($row['VisibilitySpec']) ? $row['VisibilitySpec'] : $row['visibilitySpec'];
            if(is_array($raw)) {
                $this->setVisibilitySpecArray($raw, false);
            }
            elseif(is_string($raw) || $raw === null) {
                $this->setVisibilitySpecArray($raw, false);
            }
        }
        if($hadGuests) {
            $raw = isset($row['GuestMusicians']) ? $row['GuestMusicians'] : $row['guestMusicians'];
            $this->setGuestMusiciansArray($raw);
        }
        if($hadSammlungen) {
            $raw = isset($row['Sammlungen']) ? $row['Sammlungen'] : $row['sammlungen'];
            $this->setSammlungenArray($raw);
        }
        if($hadVisibility || $hadGuests) {
            $this->mergeGuestMusiciansIntoVisibilityUsers();
            $this->syncGuestMusiciansFromVisibility();
        }
    }

    /**
     * GuestMusicians aus VisibilitySpec.users ableiten (Active = 0).
     */
    public function syncGuestMusiciansFromVisibility() {
        $vis = $this->getVisibilitySpecArray();
        $userIds = array();
        foreach($vis['users'] as $uid) {
            $uid = (int)$uid;
            if($uid > 0) {
                $userIds[$uid] = $uid;
            }
        }
        if(!count($userIds)) {
            $this->setGuestMusiciansArray(array());
            return;
        }
        $sql = sprintf(
            'SELECT `Index` FROM `%sUser` WHERE `Index` IN (%s) AND `Deleted` != 1 AND `Active` = 0;',
            $GLOBALS['dbprefix'],
            implode(',', array_map('intval', $userIds))
        );
        $ids = array();
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if($dbr) {
            while($row = mysqli_fetch_array($dbr)) {
                $ids[] = (int)$row['Index'];
            }
        }
        $this->setGuestMusiciansArray($ids);
    }

    /**
     * Sicherstellen, dass gespeicherte GuestMusicians auch als Visibility-Personen-Chips sichtbar sind.
     */
    public function mergeGuestMusiciansIntoVisibilityUsers() {
        $guestIds = $this->getGuestMusiciansArray();
        if(!count($guestIds)) {
            return;
        }
        $vis = $this->getVisibilitySpecArray();
        $have = array_fill_keys($vis['users'], true);
        $changed = false;
        foreach($guestIds as $gid) {
            $gid = (int)$gid;
            if($gid > 0 && empty($have[$gid])) {
                $vis['users'][] = $gid;
                $have[$gid] = true;
                $changed = true;
            }
        }
        if($changed) {
            $this->setVisibilitySpecArray($vis, false);
        }
    }

    /**
     * @return array{groups:string[],registers:int[],users:int[],namedGroups:int[]}
     */
    public function getVisibilitySpecArray() {
        return AudienceSpec::normalize($this->VisibilitySpec, array(
            'allowNamedGroups' => true,
            'defaultGroups' => null,
        ));
    }

    /**
     * @param mixed $spec
     * @param bool $syncGuests GuestMusicians aus Personen-Chips ableiten
     */
    public function setVisibilitySpecArray($spec, $syncGuests = true) {
        $norm = AudienceSpec::normalize($spec, array(
            'allowNamedGroups' => true,
            'defaultGroups' => null,
        ));
        if(AudienceSpec::isEmpty($norm)) {
            $this->VisibilitySpec = null;
            if($syncGuests) {
                $this->setGuestMusiciansArray(array());
            }
            return;
        }
        $this->VisibilitySpec = json_encode(array(
            'groups' => $norm['groups'],
            'registers' => $norm['registers'],
            'users' => $norm['users'],
            'namedGroups' => $norm['namedGroups'],
        ));
        if($syncGuests) {
            $this->syncGuestMusiciansFromVisibility();
        }
    }

    protected function sqlVisibilitySpec() {
        $raw = $this->VisibilitySpec;
        if($raw === null || $raw === '') {
            return 'NULL';
        }
        $norm = AudienceSpec::normalize($raw, array('allowNamedGroups' => true, 'defaultGroups' => null));
        if(AudienceSpec::isEmpty($norm)) {
            return 'NULL';
        }
        return '"'.mysqli_real_escape_string($GLOBALS['conn'], json_encode(array(
            'groups' => $norm['groups'],
            'registers' => $norm['registers'],
            'users' => $norm['users'],
            'namedGroups' => $norm['namedGroups'],
        ))).'"';
    }

    /**
     * @return int[]
     */
    public function getGuestMusiciansArray() {
        $raw = $this->GuestMusicians;
        if(is_array($raw)) {
            $decoded = $raw;
        }
        else {
            $decoded = json_decode(trim((string)$raw), true);
        }
        $ids = array();
        if(is_array($decoded)) {
            foreach($decoded as $id) {
                $id = (int)$id;
                if($id > 0) {
                    $ids[$id] = $id;
                }
            }
        }
        return array_values($ids);
    }

    /**
     * @param mixed $input JSON string, array of ids, or null
     */
    public function setGuestMusiciansArray($input) {
        $ids = array();
        if(is_array($input)) {
            foreach($input as $id) {
                $id = (int)$id;
                if($id > 0) {
                    $ids[$id] = $id;
                }
            }
        }
        else {
            $raw = trim((string)$input);
            if($raw !== '') {
                $decoded = json_decode($raw, true);
                if(is_array($decoded)) {
                    foreach($decoded as $id) {
                        $id = (int)$id;
                        if($id > 0) {
                            $ids[$id] = $id;
                        }
                    }
                }
            }
        }
        $list = array_values($ids);
        $this->GuestMusicians = count($list) ? json_encode($list) : null;
    }

    protected function sqlGuestMusicians() {
        $ids = $this->getGuestMusiciansArray();
        if(!count($ids)) {
            return 'NULL';
        }
        return '"'.mysqli_real_escape_string($GLOBALS['conn'], json_encode($ids)).'"';
    }

    /** Nullable JSON int[] of archiv_Collection ids for SQL INSERT/UPDATE. */
    protected function sqlSammlungen() {
        $ids = $this->getSammlungenArray();
        if(!count($ids)) {
            return 'NULL';
        }
        return '"'.mysqli_real_escape_string($GLOBALS['conn'], json_encode($ids)).'"';
    }

    /** @return list<int> */
    public function getSammlungenArray() {
        $raw = $this->Sammlungen;
        if(is_array($raw)) {
            $list = $raw;
        }
        else {
            $s = trim((string)$raw);
            if($s === '') {
                return array();
            }
            $decoded = json_decode($s, true);
            $list = is_array($decoded) ? $decoded : array();
        }
        $ids = array();
        foreach($list as $id) {
            $id = (int)$id;
            if($id > 0) {
                $ids[$id] = $id;
            }
        }
        return array_values($ids);
    }

    /** @param mixed $input JSON string, array, or null */
    public function setSammlungenArray($input) {
        if(is_array($input)) {
            $list = $input;
        }
        else {
            $s = trim((string)$input);
            if($s === '') {
                $this->Sammlungen = null;
                return;
            }
            $decoded = json_decode($s, true);
            $list = is_array($decoded) ? $decoded : array();
        }
        $ids = array();
        foreach($list as $id) {
            $id = (int)$id;
            if($id > 0) {
                $ids[$id] = $id;
            }
        }
        $list = array_values($ids);
        $this->Sammlungen = count($list) ? json_encode($list) : null;
    }

    /**
     * Single "Programm" chip → modal with all linked Archiv-Sammlungen.
     * @return string
     */
    public function renderProgrammChipHtml() {
        if(!function_exists('archivFeatureEnabled') || !archivFeatureEnabled()) {
            return '';
        }
        $tid = (int)$this->Index;
        if($tid < 1 || !count($this->getSammlungenArray())) {
            return '';
        }
        if(!function_exists('entityOpenHtml')) {
            return '';
        }
        return entityOpenHtml('programm', $tid, 'Programm', 'programm');
    }

    /**
     * Catalog of guests (Active=0) for chip UI.
     * @return array{users:array<int,array{id:int,label:string,meta:string}>}
     */
    public static function buildGuestMusicianCatalog() {
        $catalog = array('users' => array());
        $sql = sprintf(
            'SELECT u.`Index`, u.`Vorname`, u.`Nachname`, COALESCE(r.`Name`, "") AS `RegisterName`
             FROM `%sUser` u
             LEFT JOIN `%sInstrument` i ON i.`Index` = u.`Instrument`
             LEFT JOIN `%sRegister` r ON r.`Index` = i.`Register`
             WHERE u.`Deleted` != 1 AND u.`Active` = 0
             ORDER BY u.`Nachname`, u.`Vorname`;',
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix']
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if($dbr) {
            while($u = mysqli_fetch_array($dbr)) {
                $catalog['users'][] = array(
                    'id' => (int)$u['Index'],
                    'label' => trim($u['Vorname'].' '.$u['Nachname']),
                    'meta' => html_entity_decode((string)$u['RegisterName'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                );
            }
        }
        return $catalog;
    }

    /**
     * Resolved guest musician rows for this termin (chip display).
     * @return array<int,array{id:int,label:string,meta:string}>
     */
    public function getGuestMusicianEntries() {
        $ids = $this->getGuestMusiciansArray();
        if(!count($ids)) {
            return array();
        }
        $sql = sprintf(
            'SELECT u.`Index`, u.`Vorname`, u.`Nachname`, COALESCE(r.`Name`, "") AS `RegisterName`
             FROM `%sUser` u
             LEFT JOIN `%sInstrument` i ON i.`Index` = u.`Instrument`
             LEFT JOIN `%sRegister` r ON r.`Index` = i.`Register`
             WHERE u.`Index` IN (%s)
             ORDER BY u.`Nachname`, u.`Vorname`;',
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            implode(',', array_map('intval', $ids))
        );
        $byId = array();
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if($dbr) {
            while($u = mysqli_fetch_array($dbr)) {
                $byId[(int)$u['Index']] = array(
                    'id' => (int)$u['Index'],
                    'label' => trim($u['Vorname'].' '.$u['Nachname']),
                    'meta' => html_entity_decode((string)$u['RegisterName'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                );
            }
        }
        $out = array();
        foreach($ids as $id) {
            if(isset($byId[$id])) {
                $out[] = $byId[$id];
            }
            else {
                $out[] = array('id' => $id, 'label' => 'User #'.$id, 'meta' => '');
            }
        }
        return $out;
    }

    /**
     * Read-only chips for GuestMusicians (same visual language as visibility chips).
     * @return string
     */
    public function renderGuestMusiciansChipsHtml() {
        $entries = $this->getGuestMusicianEntries();
        if(!count($entries)) {
            return '<span class="w3-text-grey">—</span>';
        }
        $html = '<div class="mail-recipient-chips" role="list" aria-label="Gastmusiker">';
        foreach($entries as $e) {
            $label = 'Gastmusiker: '.$e['label'];
            if($e['meta'] !== '') {
                $label .= ' ('.$e['meta'].')';
            }
            $html .= '<span class="mail-recipient-chip mail-recipient-chip--guestMusician" role="listitem">'
                .htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
                .'</span>';
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * Human-readable visibility audience for logs/admin UI.
     * @return string
     */
    public function getVisibilityLabel() {
        return AudienceSpec::formatLabel($this->getVisibilitySpecArray(), array('allowNamedGroups' => true));
    }

    /**
     * True when VisibilitySpec targets an audience (not versteckt).
     */
    public function isListed() {
        return !AudienceSpec::isEmpty($this->getVisibilitySpecArray());
    }

    /**
     * SQL fragment: termin has a non-empty VisibilitySpec (listed, not versteckt).
     *
     * @param string $alias Table alias including trailing dot, e.g. "`t`." or ""
     * @return string
     */
    public static function sqlIsListed($alias = '') {
        return $alias.'`VisibilitySpec` IS NOT NULL AND '.$alias.'`VisibilitySpec` != \'\'';
    }

    /**
     * List visibility for a user (MELD-61).
     * Empty VisibilitySpec: only perm_showHiddenAppmnts (versteckt).
     * Non-empty spec: matching users (+ showHidden / existing meldung overrides in viewer mode).
     *
     * @param int $userId
     * @param array $opts asViewer (bool, default true) — admin/meldung overrides for UI
     * @return bool
     */
    public function isVisibleToUser($userId, $opts = array()) {
        $userId = (int)$userId;
        $asViewer = !array_key_exists('asViewer', $opts) || !empty($opts['asViewer']);
        $spec = $this->getVisibilitySpecArray();
        $isHiddenAudience = AudienceSpec::isEmpty($spec);
        $canShowHidden = $this->userCanShowHiddenAppointments($userId);

        if($isHiddenAudience) {
            return $asViewer && $canShowHidden;
        }
        if($userId > 0 && AudienceSpec::userMatches($userId, $spec)) {
            return true;
        }
        if($asViewer) {
            if($canShowHidden) {
                return true;
            }
            if($userId > 0 && $this->getMeldungenByUser($userId)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Show-hidden right for this user (ICS has no session). Session as fallback.
     */
    private function userCanShowHiddenAppointments($userId) {
        $userId = (int)$userId;
        if($userId > 0 && Permissions::loadEffectiveByUser($userId)->getPermission('perm_showHiddenAppmnts')) {
            return true;
        }
        return requirePermission('perm_showHiddenAppmnts');
    }

    /**
     * Gray out list cards when the current user is outside the intended audience
     * (empty VisibilitySpec, or only visible via perm_showHiddenAppmnts).
     */
    public function shouldStyleAsUnpublished($userId = null) {
        if($userId === null) {
            $userId = $this->getUser();
        }
        $userId = (int)$userId;
        $spec = $this->getVisibilitySpecArray();
        if(AudienceSpec::isEmpty($spec)) {
            return true;
        }
        return !($userId > 0 && AudienceSpec::userMatches($userId, $spec));
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
        $user = (int)$this->getUser();
        if($user > 0) {
            $this->loadMeldungStateForUser($user);
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
    public function getMeldungenVal($val) {
        $val = (int)$val;
        $counts = $this->getMeldungenCountsByWert();
        $r = isset($counts[$val]) ? (int)$counts[$val] : 0;
        return $r;
    }

    /**
     * @return array<int,int> Wert => count
     */
    protected function getMeldungenCountsByWert() {
        if(isset($this->_meldungenCountsByWert) && is_array($this->_meldungenCountsByWert)) {
            return $this->_meldungenCountsByWert;
        }
        $this->_meldungenCountsByWert = array();
        $sql = sprintf(
            'SELECT `Wert`, COUNT(*) AS `c` FROM `%sMeldungen` WHERE `Termin` = %d GROUP BY `Wert`;',
            $GLOBALS['dbprefix'],
            (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if($dbr) {
            while($row = mysqli_fetch_array($dbr)) {
                $this->_meldungenCountsByWert[(int)$row['Wert']] = (int)$row['c'];
            }
        }
        return $this->_meldungenCountsByWert;
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
    /**
     * One editable table row for a shift (or empty new row).
     * @param int|string $shiftId existing Index, or 0 / "new_N" for a new row key
     */
    public function shiftEditRowHtml($shiftId, $inputBg = '') {
        $s = new Shift;
        $id = 0;
        $key = 'new_0';
        if(is_string($shiftId) && $shiftId !== '') {
            $key = $shiftId;
            if(strpos($shiftId, 'new_') !== 0 && ctype_digit($shiftId)) {
                $s->load_by_id((int)$shiftId);
                $id = (int)$s->Index;
                $key = (string)$id;
            }
        }
        elseif((int)$shiftId > 0) {
            $s->load_by_id((int)$shiftId);
            $id = (int)$s->Index;
            $key = (string)$id;
        }
        $h = function ($x) {
            return htmlspecialchars((string)$x, ENT_QUOTES, 'UTF-8');
        };
        if($inputBg === '') {
            $inputBg = isset($GLOBALS['optionsDB']['colorInputBackground']) ? (string)$GLOBALS['optionsDB']['colorInputBackground'] : '';
        }
        $btnDelete = isset($GLOBALS['optionsDB']['colorBtnDelete']) ? (string)$GLOBALS['optionsDB']['colorBtnDelete'] : 'w3-red';

        $startVal = Shift::normalizedTime($s->Start);
        $endVal = Shift::normalizedTime($s->End);
        $nameVal = ($s->Name !== null && $s->Name !== '') ? $h($s->Name) : '';
        $startAttr = $startVal !== null ? ' value="'.$h(substr($startVal, 0, 5)).'"' : '';
        $endAttr = $endVal !== null ? ' value="'.$h(substr($endVal, 0, 5)).'"' : '';
        $prefix = 'shifts['.$h($key).']';
        $isNew = ($id < 1);

        $str = '<tr class="shift-edit-row'.($isNew ? ' shift-edit-row--new' : '').'" data-shift-key="'.$h($key).'">';
        $str .= '<td data-label="Bezeichnung">';
        $str .= '<input id="shift-name-'.$h($key).'" class="w3-input w3-border profile-control '.$h($inputBg).'" name="'.$prefix.'[Name]" type="text" placeholder="Bezeichnung"'.($nameVal !== '' ? ' value="'.$nameVal.'"' : '').'>';
        $str .= '</td>';
        $str .= '<td data-label="Beginn">';
        $str .= '<div class="shift-edit-time-wrap">';
        $str .= '<input id="shift-start-'.$h($key).'" class="w3-input w3-border profile-control '.$h($inputBg).'" name="'.$prefix.'[Start]" type="time" step="60"'.$startAttr.'>';
        $str .= '<button type="button" class="termin-form-clear shift-edit-clear" title="Beginn leeren" aria-label="Beginn leeren">&#10006;</button>';
        $str .= '</div>';
        $str .= '</td>';
        $str .= '<td data-label="Ende">';
        $str .= '<div class="shift-edit-time-wrap">';
        $str .= '<input id="shift-end-'.$h($key).'" class="w3-input w3-border profile-control '.$h($inputBg).'" name="'.$prefix.'[End]" type="time" step="60"'.$endAttr.'>';
        $str .= '<button type="button" class="termin-form-clear shift-edit-clear" title="Ende leeren" aria-label="Ende leeren">&#10006;</button>';
        $str .= '</div>';
        $str .= '</td>';
        $str .= '<td data-label="Bedarf">';
        $str .= '<input id="shift-bedarf-'.$h($key).'" class="w3-input w3-border profile-control '.$h($inputBg).'" name="'.$prefix.'[Bedarf]" type="number" min="0" value="'.$h((string)(int)$s->Bedarf).'">';
        $str .= '</td>';
        $str .= '<td data-label="Aktion" class="shift-edit-row-actions">';
        if($isNew) {
            $str .= '<button type="button" class="w3-btn w3-border '.$h($btnDelete).' shift-edit-remove-row">Entfernen</button>';
        }
        else {
            $str .= '<button type="button" class="w3-btn w3-border '.$h($btnDelete).'" onclick="document.getElementById(\'delmodal'.$id.'\').style.display=\'block\'">Löschen</button>';
        }
        $str .= '</td>';
        $str .= '</tr>';
        return $str;
    }

    /**
     * Delete-confirmation modal for one shift (outside the main edit form).
     */
    public function shiftDeleteModalHtml($shiftId) {
        $s = new Shift;
        $s->load_by_id((int)$shiftId);
        $id = (int)$s->Index;
        if($id < 1) {
            return '';
        }
        $h = function ($x) {
            return htmlspecialchars((string)$x, ENT_QUOTES, 'UTF-8');
        };
        $btnSubmit = isset($GLOBALS['optionsDB']['colorBtnSubmit']) ? (string)$GLOBALS['optionsDB']['colorBtnSubmit'] : 'w3-purple';
        $titleBar = isset($GLOBALS['optionsDB']['colorTitleBar']) ? (string)$GLOBALS['optionsDB']['colorTitleBar'] : '';
        $warning = isset($GLOBALS['optionsDB']['colorWarning']) ? (string)$GLOBALS['optionsDB']['colorWarning'] : '';
        $timeLabel = $s->getTime();
        $confirmName = $h($s->Name).($timeLabel !== '' ? ' '.$h($timeLabel) : '');

        $str = '<div id="delmodal'.$id.'" class="w3-modal">';
        $str .= '<div class="w3-modal-content w3-card">';
        $str .= '<header class="w3-container '.$h($titleBar).'">';
        $str .= '<button type="button" class="w3-button w3-display-topright" onclick="document.getElementById(\'delmodal'.$id.'\').style.display=\'none\'" aria-label="Schließen">&times;</button>';
        $str .= '<h2>Löschen bestätigen</h2>';
        $str .= '</header>';
        $str .= '<div class="w3-container w3-padding '.$h($warning).'">';
        $str .= '<p>Sind Sie sicher, dass Sie <b>'.$confirmName.'</b> löschen wollen?<br>Alle Meldungen zu dieser Schicht/Aufgabe werden ebenfalls gelöscht.</p>';
        $str .= '</div>';
        $str .= '<div class="shift-edit-actions w3-padding w3-margin-bottom">';
        $str .= '<form action="edit-shifts.php" method="POST">';
        $str .= '<input type="hidden" name="Termin" value="'.(int)$this->Index.'">';
        $str .= '<button type="submit" class="w3-btn w3-border '.$h($btnSubmit).'" name="delete" value="'.$id.'">Ja, löschen</button>';
        $str .= '</form>';
        $str .= '<button type="button" class="w3-btn w3-border '.$h($btnSubmit).'" onclick="document.getElementById(\'delmodal'.$id.'\').style.display=\'none\'">Abbrechen</button>';
        $str .= '</div>';
        $str .= '</div>';
        $str .= '</div>';
        return $str;
    }

    /**
     * Shared table editor for all shifts of this termin (+ one empty row + add/remove).
     */
    public function printShiftEdit() {
        $h = function ($x) {
            return htmlspecialchars((string)$x, ENT_QUOTES, 'UTF-8');
        };
        $inputBg = isset($GLOBALS['optionsDB']['colorInputBackground']) ? (string)$GLOBALS['optionsDB']['colorInputBackground'] : '';
        $btnEdit = isset($GLOBALS['optionsDB']['colorBtnEdit']) ? (string)$GLOBALS['optionsDB']['colorBtnEdit'] : 'w3-teal';
        $btnSubmit = isset($GLOBALS['optionsDB']['colorBtnSubmit']) ? (string)$GLOBALS['optionsDB']['colorBtnSubmit'] : 'w3-purple';
        $shiftIds = $this->getShifts();

        $str = '<form class="shift-edit-form" action="edit-shifts.php" method="POST" data-shift-new-counter="1">';
        $str .= '<input type="hidden" name="Termin" value="'.(int)$this->Index.'">';
        $str .= '<div class="shift-edit-table-wrap">';
        $str .= '<table class="shift-edit-table">';
        $str .= '<thead><tr>';
        $str .= '<th scope="col">Bezeichnung</th>';
        $str .= '<th scope="col">Beginn</th>';
        $str .= '<th scope="col">Ende</th>';
        $str .= '<th scope="col">Bedarf</th>';
        $str .= '<th scope="col">Aktion</th>';
        $str .= '</tr></thead><tbody>';
        foreach($shiftIds as $shiftId) {
            $str .= $this->shiftEditRowHtml((int)$shiftId, $inputBg);
        }
        $str .= $this->shiftEditRowHtml('new_0', $inputBg);
        $str .= '</tbody></table></div>';
        $str .= '<div class="shift-edit-actions shift-edit-form-actions">';
        $str .= '<button type="button" class="w3-btn w3-border '.$h($btnSubmit).'" id="shift-edit-add-row">Zeile hinzufügen</button>';
        $str .= '<button type="submit" class="w3-btn w3-border '.$h($btnEdit).'" name="save_all" value="1">Alle speichern</button>';
        $str .= '</div>';
        $str .= '</form>';
        $str .= '<template id="shift-edit-row-template">'.$this->shiftEditRowHtml('__KEY__', $inputBg).'</template>';
        foreach($shiftIds as $shiftId) {
            $str .= $this->shiftDeleteModalHtml((int)$shiftId);
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
        if($c) return $c;
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

    /**
     * Compact date block for Hauptseite melde rows (MELD-141):
     * two-letter weekday in a box, numeric date left-aligned — no relative countdown.
     */
    protected function makeListDateInfo() {
        $dow = germanWeekdayShort($this->Datum);
        if($this->EndDatum) {
            $dateText = germanDateCompact($this->Datum).' – '.germanDateCompact($this->EndDatum);
        }
        else {
            $dateText = germanDateCompact($this->Datum);
        }

        $timeBits = array();
        if($this->Uhrzeit) {
            $t = sql2timeRaw($this->Uhrzeit);
            if($this->Uhrzeit2) {
                $t .= ' – '.sql2timeRaw($this->Uhrzeit2);
            }
            $timeBits[] = $t;
        }
        $travel = '';
        if(($GLOBALS['optionsDB']['showTravelTime'] || $GLOBALS['optionsDB']['showVehicle'])
            && ($this->Abfahrt || $this->vName)) {
            $parts = array();
            if($this->Abfahrt && $GLOBALS['optionsDB']['showTravelTime']) {
                $parts[] = sql2timeRaw($this->Abfahrt);
            }
            if($GLOBALS['optionsDB']['showVehicle'] && $this->vName) {
                $parts[] = $this->vName;
            }
            if($parts) {
                $travel = '('.implode(' ', $parts).')';
            }
        }
        if($travel !== '') {
            $timeBits[] = $travel;
        }

        $title = htmlspecialchars($this->getGermanDate(), ENT_QUOTES, 'UTF-8');
        $str = '<div class="melde-date" title="'.$title.'">';
        $str .= '<span class="melde-weekday">'.htmlspecialchars($dow, ENT_QUOTES, 'UTF-8').'</span>';
        $str .= '<span class="melde-date-details">';
        $str .= '<span class="melde-date-day">'.htmlspecialchars($dateText, ENT_QUOTES, 'UTF-8').'</span>';
        if($timeBits) {
            $str .= '<span class="melde-date-time">'.htmlspecialchars(implode(' ', $timeBits), ENT_QUOTES, 'UTF-8').'</span>';
        }
        $str .= '</span></div>';
        return $str;
    }
    protected function makeButtons($N, $indent, $val) {
        return $this->makeButtonsUser($N, $indent, $val, $this->getUser());
    }
    protected function makeButtonsUser($N, $indent, $val, $user, $listStyle = false) {
        $symbols = array("&#10004;", "&#10008;", "<b>?</b>");
        $colors = array($GLOBALS['optionsDB']['colorBtnYes'], $GLOBALS['optionsDB']['colorBtnNo'], $GLOBALS['optionsDB']['colorBtnMaybe']);
        
        $str="";
        for($i=1; $i<=$N; $i++) {
            $btn = new div;
            $btn->indent = $indent;
            if(!$listStyle) {
                $btn->class="w3-col s3 m3 l3";
                $btn->class="w3-margin-left";
            }
            if($this->open == false && requirePermission("perm_editResponse") == false) {
                if($GLOBALS['optionsDB']['AppmntAlwaysDecline']) {
                    if($i != 2) {
                        if(!$listStyle) {
                            $str=$str.$btn->print();
                        }
                        continue;
                    }
                }
                else {
                    if(!$listStyle) {
                        $str=$str.$btn->print();
                    }
                    continue;
                }
            }
            $btn->tag="button";
            if($listStyle) {
                $btn->class="melde-btn";
            }
            $btn->class="w3-btn";
            $btn->class="w3-border";
            $btn->class="w3-border-black";
            /* $btn->class="w3-margin-top"; */
            $btn->class="w3-center";
            $btn->type="button";
            $btn->body=$symbols[$i-1];

            if($val && $val != $i) {
                $btn->class=$GLOBALS['optionsDB']['colorDisabled'];
            }
            else {
                $btn->class=$colors[$i-1];
            }
            if($val != $i) {
                $btn->onclick="melde(".$user.", ".$this->Index.", ".$i.", ".(int)$this->Children.", ".(int)$this->Guests.")";
                $btn->name="meldung";
                $btn->value=$i;
            }
            $str=$str.$btn->print();
        }
        return $str;
    }
    protected function makeShiftButtonsUser($N, $indent, $shift, $val, $user, $listStyle = false) {
        $symbols = array("&#10004;", "&#10008;", "<b>?</b>");
        $colors = array($GLOBALS['optionsDB']['colorBtnYes'], $GLOBALS['optionsDB']['colorBtnNo'], $GLOBALS['optionsDB']['colorBtnMaybe']);
        
        $str="";
        for($i=1; $i<=$N; $i++) {
            $btn = new div;
            $btn->indent = $indent;

            if(!$listStyle) {
                $btn->class="w3-col s3 m3 l3";
                $btn->class="w3-margin-left";
            }
            if(!$this->open && !requirePermission("perm_editResponse")) {
                if($GLOBALS['optionsDB']['AppmntAlwaysDecline']) {
                    if($i != 2) {
                        if(!$listStyle) {
                            $str=$str.$btn->print();
                        }
                        continue;
                    }
                }
                else {
                    if(!$listStyle) {
                        $str=$str.$btn->print();
                    }
                    continue;
                }
            }
            $btn->tag="button";
            if($listStyle) {
                $btn->class="melde-btn";
            }
            $btn->class="w3-btn";
            $btn->class="w3-border";
            $btn->class="w3-border-black";
            /* $btn->class="w3-margin-top"; */
            $btn->class="w3-center";
            $btn->type="button";
            $btn->body=$symbols[$i-1];

            if($val && $val != $i) {
                $btn->class=$GLOBALS['optionsDB']['colorDisabled'];
            }
            else {
                $btn->class=$colors[$i-1];
            }
            if($val != $i) {
                $btn->onclick="meldeShift(".$user.", ".$shift.", ".$this->Index.", ".$i.")";
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
                $btn->onclick="meldeExtShift(".$this->getUser().", ".$shift.", ".$this->Index.", ".$i.")";
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
            $admStatusDiv->onclick="getStatus(".$user.", ".$this->Index.")";
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
    /**
     * Resolve hex for a config color CSS class (cfg-hex-… or legacy w3-*).
     * @param string $colorClass
     * @return string hex or ''
     */
    protected function resolveMeldeColorHex($colorClass) {
        $colorClass = trim((string)$colorClass);
        if($colorClass === '') {
            return '';
        }
        if(!empty($GLOBALS['cfgColorCssRules'][$colorClass]['bg'])
            && isHexColor($GLOBALS['cfgColorCssRules'][$colorClass]['bg'])) {
            return normalizeHexColor($GLOBALS['cfgColorCssRules'][$colorClass]['bg']);
        }
        if(function_exists('w3ColorToHex')) {
            $hex = w3ColorToHex($colorClass);
            if(isHexColor($hex)) {
                return normalizeHexColor($hex);
            }
        }
        return '';
    }

    protected function meldeRowResponseOpacity() {
        $raw = isset($GLOBALS['optionsDB']['meldeRowResponseOpacity'])
            ? (string)$GLOBALS['optionsDB']['meldeRowResponseOpacity']
            : '1';
        $opacity = (float)str_replace(',', '.', $raw);
        if(!is_finite($opacity)) {
            $opacity = 1.0;
        }
        return max(0.0, min(1.0, $opacity));
    }

    /**
     * Soft response tint via CSS vars (meldeRowResponseOpacity), or empty if full opacity / unknown color.
     * @return array{0:string,1:string} [extraClass, styleAttrIncludingLeadingSpace]
     */
    protected function meldeSoftTintAttrs($colorClass) {
        $opacity = $this->meldeRowResponseOpacity();
        if($opacity >= 0.999) {
            return array('', '');
        }
        $hex = $this->resolveMeldeColorHex($colorClass);
        if($hex === '') {
            return array('', '');
        }
        $fg = hexContrastTextOnFill($hex, $opacity);
        $style = ' style="--melde-response-opacity:'.$opacity
            .';--melde-response-bg:'.$hex
            .';--melde-response-fg:'.$fg.'"';
        return array('melde-row--responded', $style);
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
    public function printBasicTableLine($userId = null) {
        if($userId !== null && (int)$userId > 0) {
            $this->setRenderUser((int)$userId);
            $this->loadMeldungStateForUser((int)$userId);
        }
        $user = $this->getUser();
        $tid = (int)$this->Index;
        $h = function ($s) {
            return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
        };

        $isShifts = (bool)$this->Shifts;
        $classes = array('melde-row', 'w3-card-4');
        $mainColor = $this->mainColor();
        $mainHover = $this->mainHover();
        if($mainHover) {
            $classes[] = $mainHover;
        }
        if($this->shouldStyleAsUnpublished($user)) {
            $classes[] = $GLOBALS['optionsDB']['styleAppmntUnpublished'];
        }

        /*
         * Soft tint must REPLACE the solid color class: cfg-hex-* uses
         * background !important and would sit under the 0.55 overlay (= wrong opacity).
         * Schichten: tint on the whole card so the date gutter matches the header
         * (shift lines keep their own response colors on the right).
         */
        $styleAttr = '';
        $mainSoftClass = '';
        $mainSoftStyle = '';
        if(!$isShifts) {
            list($tintClass, $tintStyle) = $this->meldeSoftTintAttrs($mainColor);
            if($tintClass !== '') {
                $classes[] = $tintClass;
                $styleAttr = $tintStyle;
            }
            elseif($mainColor) {
                $classes[] = $mainColor;
            }
        }
        else {
            list($mainSoftClass, $mainSoftStyle) = $this->meldeSoftTintAttrs($mainColor);
            if($mainSoftClass === '' && $mainColor) {
                $mainSoftClass = $mainColor;
                $mainSoftStyle = '';
            }
            if($mainSoftClass !== '') {
                $classes[] = $mainSoftClass;
                $styleAttr = $mainSoftStyle;
            }
        }

        $rowClasses = array('melde-row-main');
        $lineHover = $this->lineHover();
        if($lineHover) {
            $rowClasses[] = $lineHover;
        }

        $str = '<div id="entry'.$tid.'_user'.$user.'" class="'.implode(' ', $classes).'"'.$styleAttr.' data-termin-id="'.$tid.'" '.$this->getSearchDataAttr().'>';
        $str .= '<div class="'.implode(' ', $rowClasses).'">';
        $str .= '<div class="melde-date-col">'.$this->makeListDateInfo().'</div>';
        $str .= '<div class="melde-date-rail" aria-hidden="true"></div>';

        $name = $this->Name !== null && $this->Name !== '' ? $this->Name : 'Termin';
        $str .= '<div class="melde-main">';
        $str .= '<div class="melde-title">'.$h($name).'</div>';
        $desc = trim((string)$this->Beschreibung);
        if($desc !== '') {
            $str .= '<div class="melde-desc">'.$h($desc).'</div>';
        }
        $ort = trim((string)$this->getOrt());
        if($ort !== '') {
            $str .= '<div class="melde-ort">'.$h($ort).'</div>';
        }
        $programmChip = $this->renderProgrammChipHtml();
        if($programmChip !== '') {
            $str .= '<div class="melde-programm mail-recipient-chips" data-melde-stop>'.$programmChip.'</div>';
        }
        $str .= '</div>';

        $str .= '<div class="melde-actions" data-melde-stop>';
        $str .= $this->makeListMetaHtml($user);
        if(!$this->Shifts) {
            $str .= '<div class="melde-btns">';
            if($this->Capacity) {
                if($this->Capacity > $this->getMeldungenVal(1) || $this->Wert == 1 || requirePermission('perm_editResponse')) {
                    $str .= $this->makeButtonsUser(2, 0, $this->Wert, $user, true);
                }
                else {
                    $str .= '<span class="melde-full">Alle Plätze belegt</span>';
                }
            }
            else {
                $str .= $this->makeButtonsUser(3, 0, $this->Wert, $user, true);
            }
            $str .= '</div>';
            if(!empty($GLOBALS['optionsDB']['showAddToCalendarButton'])) {
                $str .= '<a id="icalform'.$tid.'" class="melde-ical melde-ical-btn" href="download-ics.php?appID='.$tid.'"'
                    .' title="In Kalender" aria-label="In Kalender eintragen" download>'
                    .'<i class="fa fa-calendar-plus" aria-hidden="true"></i></a>';
            }
            $str .= $this->renderMeldeResponseBtn($tid, $this->getUserRegisterFilter($user));
        }
        $str .= '</div>'; // melde-actions
        $str .= '</div>'; // melde-row-main

        if($this->defaultFreeText) {
            $ft = new AppmntFreeTextResponse;
            $ft->load_by_user_event($user, $this->Index);
            $ftVal = $ft->Text ? $h($ft->Text) : '';
            $ph = $h($this->defaultFreeText);
            $str .= '<div class="melde-extra melde-freetext" data-melde-stop>';
            $str .= '<label class="melde-extra-label" for="FreeText'.$tid.'">Zusatzangabe</label>';
            $str .= '<input type="text" id="FreeText'.$tid.'" name="AppmntFreeTextResponse" class="w3-input w3-border melde-freetext-input '.$GLOBALS['optionsDB']['colorInputBackground'].'" placeholder="'.$ph.'" value="'.$ftVal.'">';
            $str .= '<button type="button" class="w3-btn w3-border w3-border-black '.$GLOBALS['optionsDB']['colorBtnSubmit'].'" onclick="meldeFT('.$user.', '.$tid.')"><b>speichern</b></button>';
            $str .= '</div>';
        }

        if(($GLOBALS['optionsDB']['showGuestOption'] || $GLOBALS['optionsDB']['showChildOption'])
            && ($this->Wert == 1 || $this->Wert == 3) && $this->vName == 'Bus') {
            $str .= '<div class="melde-extra melde-guests" data-melde-stop>';
            if($GLOBALS['optionsDB']['showChildOption']) {
                $str .= '<label class="melde-extra-label" for="Children'.$tid.'">Kinder</label>';
                $str .= '<input type="number" min="0" id="Children'.$tid.'" name="Children" class="melde-num" value="'.(int)$this->Children.'">';
            }
            if($GLOBALS['optionsDB']['showGuestOption']) {
                $str .= '<label class="melde-extra-label" for="Guests'.$tid.'">Gäste</label>';
                $str .= '<input type="number" min="0" id="Guests'.$tid.'" name="Guests" class="melde-num" value="'.(int)$this->Guests.'">';
            }
            $str .= '<button type="button" class="w3-btn w3-border w3-border-black '.$GLOBALS['optionsDB']['colorBtnSubmit'].'" onclick="melde('.$user.', '.$tid.', '.(int)$this->Wert.', -1, -1)"><b>speichern</b></button>';
            $str .= '</div>';
        }

        if($this->Shifts) {
            $shifts = $this->getShifts();
            for($i = 0; $i < count($shifts); $i++) {
                $s = new Shift;
                $s->load_by_id($shifts[$i]);
                $m = new Shiftmeldung;
                $m->load_by_user_event($user, $s->Index);

                $shiftClasses = array('melde-shift');
                $shiftClasses[] = $GLOBALS['optionsDB']['HoverEffect'];
                $shiftStyleAttr = '';
                $lc = $this->getLineColor($m->Wert);
                if($lc) {
                    list($tintClass, $tintStyle) = $this->meldeSoftTintAttrs($lc);
                    if($tintClass !== '') {
                        $shiftClasses[] = $tintClass;
                        $shiftStyleAttr = $tintStyle;
                    }
                    else {
                        $shiftClasses[] = $lc;
                    }
                }
                $str .= '<div class="'.implode(' ', array_filter($shiftClasses)).'"'.$shiftStyleAttr.' data-melde-stop>';
                $str .= '<div class="melde-shift-info">';
                $str .= '<div class="melde-shift-name">'.$h($s->Name).'</div>';
                if($s->getTime() !== '') {
                    $str .= '<div class="melde-shift-time">'.$h($s->getTime()).'</div>';
                }
                $str .= '</div>';
                $str .= '<div class="melde-actions">';
                if($s->Bedarf) {
                    $str .= '<div class="melde-meta"><i class="fas fa-user-friends" aria-hidden="true"></i> '.$h($s->getResponseString()).'</div>';
                }
                $str .= '<div class="melde-btns">';
                if($s->Bedarf) {
                    if($s->Bedarf > $s->getMeldungenVal(1) || $m->Wert == 1 || requirePermission('perm_editResponse')) {
                        $str .= $this->makeShiftButtonsUser(2, 0, $s->Index, $m->Wert, $user, true);
                    }
                    else {
                        $str .= '<span class="melde-full">Alle Plätze belegt</span>';
                    }
                }
                else {
                    $str .= $this->makeShiftButtonsUser(3, 0, $s->Index, $m->Wert, $user, true);
                }
                $str .= '</div>';
                if(!empty($GLOBALS['optionsDB']['showAddToCalendarButton'])) {
                    $sid = (int)$s->Index;
                    $str .= '<a id="icalform'.$tid.'_s'.$sid.'" class="melde-ical melde-ical-btn" href="download-ics.php?appID='.$tid.'&amp;shiftID='.$sid.'"'
                        .' title="In Kalender" aria-label="In Kalender eintragen" download>'
                        .'<i class="fa fa-calendar-plus" aria-hidden="true"></i></a>';
                }
                $str .= $this->renderShiftResponseBtn((int)$s->Index);
                $str .= '</div>';
                $str .= '</div>';
            }
        }

        $str .= '</div>';
        return $str;
    }

    /**
     * Status-Mail button or capacity count for list rows (MELD-141).
     */
    protected function makeListMetaHtml($user) {
        if(requirePermission('perm_sendEmail') && !empty($GLOBALS['optionsDB']['statusPerMail'])) {
            return '<button type="button" class="melde-meta-btn w3-btn w3-border w3-border-black '.$GLOBALS['optionsDB']['colorBtnSubmit'].'"'
                .' onclick="getStatus('.(int)$user.', '.(int)$this->Index.')">Status per Mail</button>';
        }
        if($this->Capacity) {
            return '<div class="melde-meta"><i class="fas fa-user-friends" aria-hidden="true"></i> '
                .htmlspecialchars($this->getResponseString(), ENT_QUOTES, 'UTF-8').'</div>';
        }
        return '';
    }

    /**
     * Register filter for response modal (own register in Terminübersicht; MELD-68).
     */
    protected function getUserRegisterFilter($userId) {
        $userId = (int)$userId;
        if($userId < 1) {
            return 0;
        }
        $u = new User;
        $u->load_by_id($userId);
        $reg = $u->getRegister();
        return $reg ? (int)$reg : 0;
    }

    /**
     * MELD-170: open termin response modal from Terminübersicht (iCal-style icon button).
     */
    protected function renderMeldeResponseBtn($terminId, $register = 0) {
        $terminId = (int)$terminId;
        $register = (int)$register;
        $onclick = "event.stopPropagation();openModal('terminResponse', ".$terminId;
        if($register > 0) {
            $onclick .= ', '.$register;
        }
        $onclick .= ')';
        return '<button type="button" class="melde-ical melde-ical-btn melde-response-btn"'
            .' onclick="'.$onclick.'" title="Meldungen" aria-label="Meldungen">'
            .'<i class="fas fa-comment-dots" aria-hidden="true"></i></button>';
    }

    /**
     * MELD-170: open shift response modal from Terminübersicht.
     */
    protected function renderShiftResponseBtn($shiftId) {
        $shiftId = (int)$shiftId;
        if($shiftId < 1) {
            return '';
        }
        return '<button type="button" class="melde-ical melde-ical-btn melde-response-btn"'
            .' onclick="event.stopPropagation();openModal(\'shiftResponse\', '.$shiftId.')"'
            .' title="Meldungen" aria-label="Meldungen Schicht">'
            .'<i class="fas fa-comment-dots" aria-hidden="true"></i></button>';
    }

    /**
     * MELD-175: Person mit Zusage/Unsicher in Sichtbarkeit aufnehmen, wenn Termin sonst unsichtbar.
     */
    public function ensureUserVisibleForMeldedResponse($userId, $wert) {
        $userId = (int)$userId;
        $wert = (int)$wert;
        if($userId < 1 || ($wert !== 1 && $wert !== 3)) {
            return false;
        }
        if($this->isVisibleToUser($userId, array('asViewer' => false))) {
            return false;
        }
        $vis = $this->getVisibilitySpecArray();
        $have = array_fill_keys($vis['users'], true);
        if(!empty($have[$userId])) {
            return false;
        }
        $vis['users'][] = $userId;
        $this->setVisibilitySpecArray($vis, false);
        $this->save();
        return true;
    }

    /**
     * Compact melde prompt for calendar clicks (MELD-126).
     * Ja/Nein/Vielleicht like the list; „Weitere Optionen“ opens the detail modal.
     */
    public function getCalendarMeldeModalHtml() {
        $buttonsHtml = null;
        $capacityFull = false;
        if(!$this->Shifts) {
            if($this->Capacity) {
                if($this->Capacity > $this->getMeldungenVal(1) || $this->Wert == 1 || requirePermission('perm_editResponse')) {
                    $buttonsHtml = $this->makeButtonsUser(2, 0, $this->Wert, $this->getUser(), true);
                }
                else {
                    $capacityFull = true;
                }
            }
            else {
                $buttonsHtml = $this->makeButtonsUser(3, 0, $this->Wert, $this->getUser(), true);
            }
        }
        return render('termin/calendar_melde_modal', array(
            'termin' => $this,
            'timeInfo' => $this->makeTimeInfo(),
            'buttonsHtml' => $buttonsHtml,
            'capacityFull' => $capacityFull,
        ));
    }

    public function getDetailModalHtml() {
        $userId = (int)$this->getUser();
        $instrument = null;
        if($this->Auftritt) {
            $u = new User;
            $u->load_by_id($userId);
            $instrument = $u->Instrument;
            $m = new Meldung;
            $m->load_by_user_event($userId, $this->Index);
            if($m->Index && $m->Instrument) {
                $instrument = $m->Instrument;
            }
        }
        return render('termin/detail_modal', array(
            'termin' => $this,
            'userId' => $userId,
            'instrument' => $instrument,
        ));
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
    /**
     * Status chips for response overview cards (MELD-149).
     */
    protected function renderResponseStatusChips($ja, $nein, $vielleicht, $allLabel = '') {
        $h = function ($s) {
            return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
        };
        $html = '<div class="melde-response-chips">';
        if($allLabel !== '') {
            $html .= '<span class="melde-response-chip melde-response-chip--sum" title="Gemeldet / Register">'
                .'<i class="fas fa-user-friends" aria-hidden="true"></i> '.$h($allLabel).'</span>';
        }
        $html .= '<span class="melde-response-chip '.$GLOBALS['optionsDB']['colorBtnYes'].'" title="Zusagen">'
            .'&#10004; '.(int)$ja.'</span>';
        $html .= '<span class="melde-response-chip '.$GLOBALS['optionsDB']['colorBtnNo'].'" title="Absagen">'
            .'&#10008; '.(int)$nein.'</span>';
        $html .= '<span class="melde-response-chip '.$GLOBALS['optionsDB']['colorBtnMaybe'].'" title="Unsicher">'
            .'? '.(int)$vielleicht.'</span>';
        $html .= '</div>';
        return $html;
    }

    /**
     * Melde-row shell for Meldungen / Mein Register / Archiv (MELD-149).
     * @param string $onclick JS for card click (empty = no row click)
     * @param string $actionsHtml right-side chips / meta
     * @param string $bodyHtml optional content below the main row
     */
    protected function renderMeldeResponseCard($filterregister, $onclick, $actionsHtml, $bodyHtml = '') {
        $tid = (int)$this->Index;
        $filterregister = (int)$filterregister;
        $h = function ($s) {
            return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
        };
        $name = $this->Name !== null && $this->Name !== '' ? (string)$this->Name : 'Termin';
        $classes = array('melde-row', 'melde-response-row', 'w3-card-4');
        $bg = isset($GLOBALS['optionsDB']['colorInputBackground'])
            ? (string)$GLOBALS['optionsDB']['colorInputBackground'] : '';
        if($bg !== '') {
            $classes[] = $bg;
        }
        $attrs = ' id="responseLine'.$tid.'" data-termin="'.$tid.'" data-register="'.$filterregister.'"'
            .' class="'.implode(' ', $classes).'" '.$this->getSearchDataAttr();
        if($onclick !== '') {
            $attrs .= ' onclick="'.$onclick.'" role="button" tabindex="0"'
                .' onkeydown="if(event.key===\'Enter\'||event.key===\' \'){event.preventDefault();'.$onclick.';}"';
        }
        $html = '<div'.$attrs.'>';
        $html .= '<div class="melde-row-main">';
        $html .= '<div class="melde-date-col">'.$this->makeListDateInfo().'</div>';
        $html .= '<div class="melde-date-rail" aria-hidden="true"></div>';
        $html .= '<div class="melde-main">';
        $html .= '<div class="melde-title">'.$h($name).'</div>';
        $ort = trim((string)$this->getOrt());
        if($ort !== '') {
            $html .= '<div class="melde-ort">'.$h($ort).'</div>';
        }
        $html .= '</div>';
        $html .= '<div class="melde-actions">'.$actionsHtml.'</div>';
        $html .= '</div>'; // melde-row-main
        if($bodyHtml !== '') {
            $html .= $bodyHtml;
        }
        $html .= '</div>';
        return $html;
    }

    public function printShiftResponseLine() {
        $shifts = $this->getShifts();
        $body = '<div class="melde-response-body">';
        if(count($shifts) === 0) {
            $body .= '<div class="melde-response-empty">Noch keine Schichten &amp; Aufgaben angelegt.</div>';
        }
        else {
            foreach($shifts as $shiftId) {
                $s = new Shift;
                $s->load_by_id($shiftId);
                $ja = (int)$s->getMeldungenVal(1);
                $nein = (int)$s->getMeldungenVal(2);
                $vielleicht = (int)$s->getMeldungenVal(3);
                $bedarf = (int)$s->Bedarf;
                $allLabel = $bedarf > 0 ? ($ja.' / '.$bedarf) : '';
                $time = (string)$s->getTime();
                $body .= '<div class="melde-shift melde-response-shift" role="button" tabindex="0"'
                    .' onclick="event.stopPropagation();openModal(\'shiftResponse\', '.(int)$s->Index.')"'
                    .' onkeydown="if(event.key===\'Enter\'||event.key===\' \'){event.preventDefault();event.stopPropagation();openModal(\'shiftResponse\', '.(int)$s->Index.');}">';
                $body .= '<div class="melde-shift-info">';
                $body .= '<div class="melde-shift-name">'.htmlspecialchars((string)$s->Name, ENT_QUOTES, 'UTF-8').'</div>';
                if($time !== '') {
                    $body .= '<div class="melde-shift-time">'.htmlspecialchars($time, ENT_QUOTES, 'UTF-8').'</div>';
                }
                $body .= '</div>';
                $body .= '<div class="melde-actions">';
                $body .= $this->renderResponseStatusChips($ja, $nein, $vielleicht, $allLabel);
                $body .= '</div>';
                $chipPreview = $this->renderRegisterResponseChipBodyHtml($this->buildShiftResponseLists($s));
                if($chipPreview !== '') {
                    $body .= $chipPreview;
                }
                $body .= '</div>';
            }
        }
        $body .= '</div>';
        return $this->renderMeldeResponseCard(0, '', '', $body);
    }

    public function getShiftResponseModalHtml($s) {
        $lists = $this->buildShiftResponseLists($s);

        $canEditResponse = requirePermission('perm_editResponse');
        $userCatalogJson = '[]';
        if($canEditResponse && function_exists('loanUserChipCatalog')) {
            $userCatalogJson = json_encode(loanUserChipCatalog(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
            if($userCatalogJson === false) {
                $userCatalogJson = '[]';
            }
        }

        if($canEditResponse) {
            $yesHtml = $this->renderEditableResponseSectionHtml($lists['whoYes'], 1);
            $maybeHtml = $this->renderEditableResponseSectionHtml($lists['whoMaybe'], 3);
            $noHtml = $this->renderEditableResponseSectionHtml($lists['whoNo'], 2);
        }
        else {
            $yesHtml = $this->renderReadOnlyResponseSectionHtml($lists['whoYes'], 1);
            $maybeHtml = $this->renderReadOnlyResponseSectionHtml($lists['whoMaybe'], 3);
            $noHtml = $this->renderReadOnlyResponseSectionHtml($lists['whoNo'], 2);
        }

        return render('termin/shift_response_modal', array(
            'terminId' => (int)$this->Index,
            'shiftId' => (int)$s->Index,
            'terminName' => (string)$this->Name,
            'shiftName' => (string)$s->Name,
            'shiftTime' => (string)$s->getTime(),
            'yesHtml' => $yesHtml,
            'maybeHtml' => $maybeHtml,
            'noHtml' => $noHtml,
            'countYes' => count($lists['whoYes']),
            'countMaybe' => count($lists['whoMaybe']),
            'countNo' => count($lists['whoNo']),
            'canEditResponse' => $canEditResponse,
            'userCatalogJson' => $userCatalogJson,
        ));
    }

    /**
     * @param Shift $s
     * @return array{whoYes:array,whoNo:array,whoMaybe:array}
     */
    private function buildShiftResponseLists($s) {
        $aMeldungen = $s->fetchResponseMeldungenRows();
        $registerIds = $this->getRegisterIdsExcludingKeins();
        return $this->buildResponseListsFromMeldungen($aMeldungen, false, $registerIds);
    }

    /**
     * Active member counts per register (Deleted != 1).
     * @return array<int,int>
     */
    protected function getRegisterMemberCounts() {
        $sql = sprintf(
            'SELECT i.`Register` AS `Register`, COUNT(*) AS `c`
             FROM `%sUser` u
             INNER JOIN `%sInstrument` i ON u.`Instrument` = i.`Index`
             WHERE u.`Deleted` != 1 AND u.`Active` = 1
             GROUP BY i.`Register`;',
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix']
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $counts = array();
        if($dbr) {
            while($row = mysqli_fetch_array($dbr)) {
                $counts[(int)$row['Register']] = (int)$row['c'];
            }
        }
        return $counts;
    }

    /**
     * Shared UNION for response aggregation / name lists.
     * @return array
     */
    protected function fetchResponseMeldungenRows() {
        $sql = sprintf("(SELECT `Index`, `Timestamp`, `User`, `Termin`, `Wert`, `Instrument` AS `mInstrument`, `Guests`, `Nachname`, `Vorname`, `iName`, `Children`, `Register`, `rIndex`, `rName`, `rColor`, `rSort` FROM `%sMeldungen`
INNER JOIN (SELECT `Index` AS `uIndex`, `Vorname`, `Nachname`, `Instrument` AS `iInstrument` FROM `%sUser`) `%sUser` ON `User` = `uIndex`
INNER JOIN (SELECT `Index` AS `iIndex`, `Register`, `Name` AS `iName` FROM `%sInstrument`) `%sInstrument` ON `%sUser`.`iInstrument` = `iIndex`
INNER JOIN (SELECT `Index` AS `rIndex`, `Name` AS `rName`, `Sortierung` AS `rSort`, `Color` AS `rColor` FROM `%sRegister`) `%sRegister` ON `Register` = `rIndex`
WHERE `Termin` = '%d' AND `%sMeldungen`.`Instrument` = '0')

UNION

(SELECT `Index`, `Timestamp`, `User`, `Termin`, `Wert`, `Instrument` AS `iInstrument`, `Guests`, `Nachname`, `Vorname`, `iName`, `Children`, `Register`, `rIndex`, `rName`, `rColor`, `rSort` FROM `%sMeldungen`
INNER JOIN (SELECT `Index` AS `uIndex`, `Vorname`, `Nachname`, `Instrument` AS `mInstrument` FROM `%sUser`) `%sUser` ON `User` = `uIndex`
INNER JOIN (SELECT `Index` AS `iIndex`, `Register`, `Name` AS `iName` FROM `%sInstrument`) `%sInstrument` ON `%sMeldungen`.`Instrument` = `iIndex`
INNER JOIN (SELECT `Index` AS `rIndex`, `Name` AS `rName`, `Sortierung` AS `rSort`, `Color` AS `rColor` FROM `%sRegister`) `%sRegister` ON `Register` = `rIndex`
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
        if($dbr2) {
            while($row = mysqli_fetch_array($dbr2)) {
                $aMeldungen[] = $row;
            }
        }
        return $aMeldungen;
    }

    public function getResponseLine($filterregister) {
        $filterregister = (int)$filterregister;
        $bus = ($this->vName == 'Bus');
        $modalOpen = "openModal('terminResponse', ".$this->Index.($filterregister ? ', '.$filterregister : '').')';

        if($filterregister) {
            $lists = $this->buildResponseLists($filterregister);
            $ja = count($lists['whoYes']);
            $nein = count($lists['whoNo']);
            $vielleicht = count($lists['whoMaybe']);
            $actions = $this->renderResponseStatusChips($ja, $nein, $vielleicht);
            $body = $this->renderRegisterResponseChipBodyHtml($lists);
            return $this->renderMeldeResponseCard($filterregister, $modalOpen, $actions, $body);
        }

        if($this->Auftritt && !$filterregister) {
            $aMeldungen = $this->fetchResponseMeldungenRows();
            $memberCounts = $this->getRegisterMemberCounts();
            $snReg = 0;
            $sql = sprintf(
                "SELECT `Index` FROM `%sRegister` WHERE `Name` != 'keins' ORDER BY `Sortierung`;",
                $GLOBALS['dbprefix']
            );
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
            sqlerror();
            while($row = mysqli_fetch_array($dbr)) {
                $regId = (int)$row['Index'];
                $snReg += isset($memberCounts[$regId]) ? (int)$memberCounts[$regId] : 0;
            }
            $ja = 0;
            $nein = 0;
            $vielleicht = 0;
            $childrenYes = 0;
            $guestsYes = 0;
            $childrenMaybe = 0;
            $guestsMaybe = 0;
            foreach($aMeldungen as $row2) {
                switch((int)$row2['Wert']) {
                case 1:
                    $ja++;
                    if($GLOBALS['optionsDB']['showChildOption'] && $bus) {
                        $childrenYes += (int)$row2['Children'];
                    }
                    if($GLOBALS['optionsDB']['showGuestOption'] && $bus) {
                        $guestsYes += (int)$row2['Guests'];
                    }
                    break;
                case 2:
                    $nein++;
                    break;
                case 3:
                    $vielleicht++;
                    if($GLOBALS['optionsDB']['showChildOption'] && $bus) {
                        $childrenMaybe += (int)$row2['Children'];
                    }
                    if($GLOBALS['optionsDB']['showGuestOption'] && $bus) {
                        $guestsMaybe += (int)$row2['Guests'];
                    }
                    break;
                default:
                    break;
                }
            }
            $sumYes = $ja + $childrenYes + $guestsYes;
            $sumMaybe = $vielleicht + $childrenMaybe + $guestsMaybe;
            $sumAll = (string)($ja + $nein + $vielleicht);
            if($bus && ($GLOBALS['optionsDB']['showChildOption'] || $GLOBALS['optionsDB']['showGuestOption'])) {
                $sumAll .= '+'.($childrenYes + $childrenMaybe + $guestsYes + $guestsMaybe);
            }
            $sumAll .= ' / '.sprintf('%02d', $snReg);
            $actions = $this->renderResponseStatusChips($sumYes, $nein, $sumMaybe, $sumAll);
            return $this->renderMeldeResponseCard($filterregister, $modalOpen, $actions);
        }

        // Non-Auftritt: aggregate only
        $sql = sprintf(
            "SELECT * FROM `%sMeldungen` INNER JOIN (SELECT `Index` AS `uIndex`, `Vorname`, `Nachname` FROM `%sUser`) `%sUser` ON `User` = `uIndex` WHERE `Termin` = '%d' ORDER BY `Nachname`, `Vorname`;",
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $ja = 0;
        $nein = 0;
        $vielleicht = 0;
        $childrenYes = 0;
        $guestsYes = 0;
        $childrenMaybe = 0;
        $guestsMaybe = 0;
        while($row = mysqli_fetch_array($dbr)) {
            switch($row['Wert']) {
            case 1:
                $ja++;
                if($GLOBALS['optionsDB']['showChildOption'] && $bus) {
                    $childrenYes += (int)$row['Children'];
                }
                if($GLOBALS['optionsDB']['showGuestOption'] && $bus) {
                    $guestsYes += (int)$row['Guests'];
                }
                break;
            case 2:
                $nein++;
                break;
            case 3:
                $vielleicht++;
                $childrenMaybe += (int)$row['Children'];
                $guestsMaybe += (int)$row['Guests'];
                break;
            default:
                break;
            }
        }
        $metaRows = '';
        if($bus && $GLOBALS['optionsDB']['showChildOption']) {
            $metaRows .= '<div class="melde-response-reg melde-response-reg--meta">';
            $metaRows .= '<div class="melde-response-reg-name">Kinder</div>';
            $metaRows .= $this->renderResponseStatusChips($childrenYes, $nein, $childrenMaybe);
            $metaRows .= '</div>';
        }
        if($bus && $GLOBALS['optionsDB']['showGuestOption']) {
            $metaRows .= '<div class="melde-response-reg melde-response-reg--meta">';
            $metaRows .= '<div class="melde-response-reg-name">Gäste</div>';
            $metaRows .= $this->renderResponseStatusChips($guestsYes, $nein, $guestsMaybe);
            $metaRows .= '</div>';
        }
        $actions = $this->renderResponseStatusChips(
            $ja + $childrenYes + $guestsYes,
            $nein,
            $vielleicht + $childrenMaybe + $guestsMaybe
        );
        $body = $metaRows !== '' ? '<div class="melde-response-body">'.$metaRows.'</div>' : '';
        return $this->renderMeldeResponseCard($filterregister, $modalOpen, $actions, $body);
    }

    private function makeResponseEntry($wert, $name, $instrument, $children, $guests, $userId, $bus, $registerColor = '', $registerId = 0, $registerName = '', $registerSort = 0) {
        $statusColors = array(
            1 => $GLOBALS['optionsDB']['colorBtnYes'],
            2 => $GLOBALS['optionsDB']['colorBtnNo'],
            3 => $GLOBALS['optionsDB']['colorBtnMaybe'],
        );
        $regHex = function_exists('normalizeHexColor') ? normalizeHexColor($registerColor) : '';
        $registerId = (int)$registerId;
        $registerName = (string)$registerName;
        if($registerName === 'keins') {
            $registerId = 0;
            $registerName = '';
        }
        $entry = array(
            'colorClass' => '',
            'statusClass' => isset($statusColors[$wert]) ? $statusColors[$wert] : '',
            'registerColor' => $regHex,
            'registerId' => $registerId,
            'registerName' => $registerName,
            'registerSort' => (int)$registerSort,
            'name' => $name,
            'userId' => (int)$userId > 0 ? (int)$userId : 0,
            'instrument' => $instrument,
            'children' => null,
            'guests' => null,
            'freeText' => null,
        );
        if($GLOBALS['optionsDB']['showChildOption'] && $bus) {
            $entry['children'] = ($wert == 2) ? false : (int)$children;
        }
        if($GLOBALS['optionsDB']['showGuestOption'] && $bus) {
            $entry['guests'] = ($wert == 2) ? false : (int)$guests;
        }
        if(($wert == 1 || $wert == 3) && $this->defaultFreeText && isAdmin() && $userId) {
            $map = $this->getFreeTextByUser();
            $uid = (int)$userId;
            if(isset($map[$uid]) && $map[$uid] !== '') {
                $entry['freeText'] = $map[$uid];
            }
        }
        return $entry;
    }

    /**
     * @return array<int,string>
     */
    protected function getFreeTextByUser() {
        if(is_array($this->_freeTextByUser)) {
            return $this->_freeTextByUser;
        }
        $this->_freeTextByUser = array();
        $sql = sprintf(
            'SELECT `User`, `Text` FROM `%sAppmntFreeTextResponse` WHERE `Termin` = %d;',
            $GLOBALS['dbprefix'],
            (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if($dbr) {
            while($row = mysqli_fetch_array($dbr)) {
                $text = isset($row['Text']) ? (string)$row['Text'] : '';
                if($text !== '') {
                    $this->_freeTextByUser[(int)$row['User']] = $text;
                }
            }
        }
        return $this->_freeTextByUser;
    }

    private function renderResponseEntries($entries, $colsize = null) {
        $html = '';
        foreach($entries as $entry) {
            $html .= render('termin/response_line', array(
                'entry' => $entry,
            ));
        }
        return $html;
    }

    /**
     * @return list<int>
     */
    private function getRegisterIdsExcludingKeins() {
        $sql = sprintf(
            "SELECT `Index` FROM `%sRegister` WHERE `Name` != 'keins' ORDER BY `Sortierung`;",
            $GLOBALS['dbprefix']
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $registerIds = array();
        if($dbr) {
            while($row = mysqli_fetch_array($dbr)) {
                $registerIds[] = (int)$row['Index'];
            }
        }
        return $registerIds;
    }

    private function appendResponseEntryFromRow(array $row2, $bus, &$whoYes, &$whoNo, &$whoMaybe) {
        $name = $row2['Vorname'].' '.$row2['Nachname'];
        $instrument = isset($row2['iName']) ? (string)$row2['iName'] : '';
        $regColor = isset($row2['rColor']) ? $row2['rColor'] : '';
        $regId = isset($row2['rIndex']) ? (int)$row2['rIndex'] : 0;
        $regName = isset($row2['rName']) ? (string)$row2['rName'] : '';
        $regSort = isset($row2['rSort']) ? (int)$row2['rSort'] : 0;
        switch((int)$row2['Wert']) {
        case 1:
            $whoYes[] = $this->makeResponseEntry(1, $name, $instrument, $row2['Children'], $row2['Guests'], $row2['User'], $bus, $regColor, $regId, $regName, $regSort);
            break;
        case 2:
            $whoNo[] = $this->makeResponseEntry(2, $name, $instrument, $row2['Children'], $row2['Guests'], $row2['User'], $bus, $regColor, $regId, $regName, $regSort);
            break;
        case 3:
            $whoMaybe[] = $this->makeResponseEntry(3, $name, $instrument, $row2['Children'], $row2['Guests'], $row2['User'], $bus, $regColor, $regId, $regName, $regSort);
            break;
        default:
            break;
        }
    }

    /**
     * @param array $aMeldungen
     * @param list<int> $registerIds
     * @return array{whoYes:array,whoNo:array,whoMaybe:array}
     */
    private function buildResponseListsFromMeldungen($aMeldungen, $bus, array $registerIds) {
        $whoYes = array();
        $whoNo = array();
        $whoMaybe = array();
        $seen = array();
        foreach($registerIds as $registerId) {
            $registerId = (int)$registerId;
            foreach($aMeldungen as $row2) {
                if((int)$row2['rIndex'] !== $registerId) {
                    continue;
                }
                $uid = (int)$row2['User'];
                if(isset($seen[$uid])) {
                    continue;
                }
                $seen[$uid] = true;
                $this->appendResponseEntryFromRow($row2, $bus, $whoYes, $whoNo, $whoMaybe);
            }
        }
        foreach($aMeldungen as $row2) {
            $uid = (int)$row2['User'];
            if(isset($seen[$uid])) {
                continue;
            }
            $seen[$uid] = true;
            $this->appendResponseEntryFromRow($row2, $bus, $whoYes, $whoNo, $whoMaybe);
        }
        return array(
            'whoYes' => $whoYes,
            'whoNo' => $whoNo,
            'whoMaybe' => $whoMaybe,
        );
    }

    /**
     * @param array $entries
     * @return list<array{registerId:int,registerName:string,registerSort:int,registerColor:string,entries:array}>
     */
    /**
     * @param array $entries
     * @return array
     */
    private function sortResponseEntriesByRegister(array $entries) {
        $groups = array();
        foreach($entries as $entry) {
            $userId = isset($entry['userId']) ? (int)$entry['userId'] : 0;
            if($userId < 1) {
                continue;
            }
            $registerId = isset($entry['registerId']) ? (int)$entry['registerId'] : 0;
            $key = $registerId > 0 ? 'r'.$registerId : 'r0';
            if(!isset($groups[$key])) {
                $groups[$key] = array(
                    'registerId' => $registerId,
                    'registerName' => isset($entry['registerName']) ? (string)$entry['registerName'] : '',
                    'registerSort' => isset($entry['registerSort']) ? (int)$entry['registerSort'] : 9999,
                    'entries' => array(),
                );
            }
            $groups[$key]['entries'][] = $entry;
        }
        $groupList = array_values($groups);
        usort($groupList, function ($a, $b) {
            if($a['registerId'] === 0 && $b['registerId'] !== 0) {
                return 1;
            }
            if($b['registerId'] === 0 && $a['registerId'] !== 0) {
                return -1;
            }
            if($a['registerSort'] !== $b['registerSort']) {
                return $a['registerSort'] <=> $b['registerSort'];
            }
            return strcasecmp($a['registerName'], $b['registerName']);
        });
        $sorted = array();
        foreach($groupList as $group) {
            $items = $group['entries'];
            usort($items, function ($a, $b) {
                return strcasecmp((string)$a['name'], (string)$b['name']);
            });
            foreach($items as $entry) {
                $sorted[] = $entry;
            }
        }
        return $sorted;
    }

    private function responseChipTitleFromEntry(array $entry, $name) {
        $meta = array();
        if(!empty($entry['instrument'])) {
            $meta[] = (string)$entry['instrument'];
        }
        if(isset($entry['children']) && $entry['children'] !== null && $entry['children'] !== false && (int)$entry['children'] > 0) {
            $meta[] = '+'.(int)$entry['children'].' Kinder';
        }
        if(isset($entry['guests']) && $entry['guests'] !== null && $entry['guests'] !== false && (int)$entry['guests'] > 0) {
            $meta[] = '+'.(int)$entry['guests'].' Gäste';
        }
        if(isset($entry['freeText']) && $entry['freeText'] !== null && (string)$entry['freeText'] !== '') {
            $meta[] = (string)$entry['freeText'];
        }
        $title = (string)$name;
        if(count($meta)) {
            $title .= ' · '.implode(' · ', $meta);
        }
        return $title;
    }

    private function responseChipAttrsFromEntry(array $entry, callable $h) {
        $regHex = '';
        if(!empty($entry['registerColor']) && function_exists('normalizeHexColor')) {
            $regHex = normalizeHexColor($entry['registerColor']);
        }
        $chipCls = 'mail-recipient-chip';
        $chipStyle = '';
        if($regHex !== '') {
            $chipCls .= ' melde-response-editable-chip--register';
            $chipStyle = ' style="--melde-chip-reg:'.$h($regHex).'"';
        }
        else {
            $chipCls .= ' mail-recipient-chip--user';
        }
        return array($chipCls, $chipStyle);
    }

    private function renderReadOnlyResponseChipHtml(array $entry, callable $h) {
        $userId = isset($entry['userId']) ? (int)$entry['userId'] : 0;
        if($userId < 1) {
            return '';
        }
        $name = isset($entry['name']) ? (string)$entry['name'] : '';
        $title = $this->responseChipTitleFromEntry($entry, $name);
        list($chipCls, $chipStyle) = $this->responseChipAttrsFromEntry($entry, $h);
        $chipCls .= ' melde-response-readonly-chip';
        $mayOpen = function_exists('entityMayOpen') && entityMayOpen('user', $userId);
        if($mayOpen) {
            $chipCls .= ' entity-open';
        }
        $titleAttr = $title !== $name ? ' title="'.$h($title).'"' : '';
        $html = '<span class="'.$chipCls.'" role="listitem"';
        if($mayOpen) {
            $html .= ' tabindex="0" data-entity-type="user" data-entity-id="'.$userId.'"';
        }
        $html .= $chipStyle.$titleAttr.'>';
        $html .= '<span>'.$h($name).'</span>';
        $html .= '</span>';
        return $html;
    }

    private function renderReadOnlyResponseChipRowHtml(array $entries, callable $h) {
        $sorted = $this->sortResponseEntriesByRegister($entries);
        if(!count($sorted)) {
            return '';
        }
        $html = '<div class="mail-recipient-chips melde-response-readonly-chips" role="list">';
        foreach($sorted as $entry) {
            $html .= $this->renderReadOnlyResponseChipHtml($entry, $h);
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * Read-only chip section (Ja / Nein / Unsicher) for modal and Mein Register.
     * @param array $entries
     * @param int $wert 1|2|3
     */
    private function renderReadOnlyResponseSectionHtml($entries, $wert) {
        $h = function ($s) {
            return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
        };
        $row = $this->renderReadOnlyResponseChipRowHtml($entries, $h);
        if($row === '') {
            return '';
        }
        return '<div class="melde-response-readonly-section" data-melde-wert="'.(int)$wert.'">'.$row.'</div>';
    }

    /**
     * Inline chip preview on Mein Register list rows (register-filtered).
     * @param array{whoYes:array,whoNo:array,whoMaybe:array} $lists
     */
    private function renderRegisterResponseChipBodyHtml(array $lists) {
        $h = function ($s) {
            return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
        };
        $sections = array(
            array('wert' => 1, 'label' => 'Zusagen', 'entries' => $lists['whoYes'], 'chipClass' => $GLOBALS['optionsDB']['colorBtnYes']),
            array('wert' => 3, 'label' => 'Unsicher', 'entries' => $lists['whoMaybe'], 'chipClass' => $GLOBALS['optionsDB']['colorBtnMaybe']),
            array('wert' => 2, 'label' => 'Absagen', 'entries' => $lists['whoNo'], 'chipClass' => $GLOBALS['optionsDB']['colorBtnNo']),
        );
        $html = '';
        foreach($sections as $section) {
            if(!count($section['entries'])) {
                continue;
            }
            $row = $this->renderReadOnlyResponseChipRowHtml($section['entries'], $h);
            if($row === '') {
                continue;
            }
            $html .= '<div class="melde-response-inline-section" data-melde-wert="'.(int)$section['wert'].'">';
            $html .= '<span class="melde-response-inline-label melde-response-chip '.$h($section['chipClass']).'">'.$h($section['label']).'</span>';
            $html .= $row;
            $html .= '</div>';
        }
        if($html === '') {
            return '';
        }
        return '<div class="melde-response-body melde-response-body--chips" data-melde-stop onclick="event.stopPropagation()">'.$html.'</div>';
    }

    private function renderEditableResponseChipHtml(array $entry, $wert, callable $h) {
        $userId = isset($entry['userId']) ? (int)$entry['userId'] : 0;
        if($userId < 1) {
            return '';
        }
        $name = isset($entry['name']) ? (string)$entry['name'] : '';
        $title = $this->responseChipTitleFromEntry($entry, $name);
        list($chipCls, $chipStyle) = $this->responseChipAttrsFromEntry($entry, $h);
        $chipCls .= ' melde-response-editable-chip';
        $titleAttr = $title !== $name ? ' title="'.$h($title).'"' : '';
        $html = '<span class="'.$chipCls.'" role="listitem"'
            .' data-user-id="'.$userId.'" data-melde-wert="'.(int)$wert.'"'.$chipStyle.$titleAttr.'>';
        $html .= '<span>'.$h($name).'</span>';
        $html .= '<button type="button" class="mail-recipient-chip-remove" aria-label="Entfernen">&times;</button>';
        $html .= '</span>';
        return $html;
    }

    /**
     * MELD-175: editable chip row for one response section (Ja/Nein/Unsicher).
     * @param array $entries
     * @param int $wert 1|2|3
     */
    private function renderEditableResponseSectionHtml($entries, $wert) {
        if(!requirePermission('perm_editResponse')) {
            return '';
        }
        $wert = (int)$wert;
        $h = function ($s) {
            return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
        };
        $html = '<div class="melde-response-editable-section" data-melde-wert="'.$wert.'">';
        $sorted = $this->sortResponseEntriesByRegister($entries);
        if(count($sorted)) {
            $html .= '<div class="mail-recipient-chips melde-response-editable-chips" role="list">';
            foreach($sorted as $entry) {
                $html .= $this->renderEditableResponseChipHtml($entry, $wert, $h);
            }
            $html .= '</div>';
        }
        $html .= '<div class="melde-response-add profile-field">';
        $html .= '<input type="text" class="w3-input w3-border profile-control melde-response-add-input '.$GLOBALS['optionsDB']['colorInputBackground'].'"'
            .' placeholder="Person…" autocomplete="off" aria-label="Person hinzufügen" data-melde-wert="'.$wert.'">';
        $html .= '<div class="mail-recipient-suggest melde-response-add-suggest" hidden></div>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    private function buildResponseLists($filterregister) {
        $aMeldungen = $this->fetchResponseMeldungenRows();

        if($this->vName == "Bus") {
            $cols = (int)$GLOBALS['optionsDB']['showChildOption']+(int)$GLOBALS['optionsDB']['showGuestOption']+2;
            $bus=true;
        }
        else {
            $cols = 2;
            $bus=false;
        }
        if($this->defaultFreeText && isAdmin()) $cols++;
        switch($cols) {
        case 3:
            $colsize = array(4,3,5);
            break;
        case 4:
            $colsize = array(4,4,2,2);
            break;
        case 5:
            $colsize = array(3,3,2,2,2);
            break;
        case 2:
        default:
            $colsize = array(6,6);
            break;
        }

        $whoYes = array();
        $whoNo = array();
        $whoMaybe = array();

        $filterregister = (int)$filterregister;
        if($filterregister) {
            $registerIds = array($filterregister);
        }
        else {
            $registerIds = $this->getRegisterIdsExcludingKeins();
        }
        $listsByRegister = $this->buildResponseListsFromMeldungen($aMeldungen, $bus, $registerIds);
        $whoYes = $listsByRegister['whoYes'];
        $whoNo = $listsByRegister['whoNo'];
        $whoMaybe = $listsByRegister['whoMaybe'];

        return array(
            'whoYes' => $whoYes,
            'whoNo' => $whoNo,
            'whoMaybe' => $whoMaybe,
            'colsize' => $colsize,
            'bus' => $bus,
        );
    }

    public function getResponseModalHtml($filterregister = 0) {
        $lists = $this->buildResponseLists($filterregister);
        $bus = $lists['bus'];

        $orchestraFull = '';
        $orchestraActive = '';
        $showOrchestra = !empty($GLOBALS['optionsDB']['showOrchestraView']) && (bool)$this->Auftritt;
        if($showOrchestra) {
            $orchestraData = loadOrchestraData($this->Index);
            $orchestraFull = printOrchestra($this->Index, 1, false, $orchestraData);
            $orchestraActive = printOrchestra($this->Index, 1, true, $orchestraData);
        }

        $titleParts = array($this->Name);
        if($this->Datum) {
            $when = germanDate($this->Datum, 1);
            if($this->Uhrzeit) {
                $when .= ', '.sql2time($this->Uhrzeit);
                if($this->Uhrzeit2) {
                    $when .= ' – '.sql2time($this->Uhrzeit2);
                }
            }
            $titleParts[] = $when;
        }
        $terminTitle = implode(' — ', array_filter($titleParts));

        $canEditResponse = requirePermission('perm_editResponse');
        $userCatalogJson = '[]';
        if($canEditResponse && function_exists('loanUserChipCatalog')) {
            $userCatalogJson = json_encode(loanUserChipCatalog(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
            if($userCatalogJson === false) {
                $userCatalogJson = '[]';
            }
        }

        if($canEditResponse) {
            $whoYesHtml = $this->renderEditableResponseSectionHtml($lists['whoYes'], 1);
            $whoMaybeHtml = $this->renderEditableResponseSectionHtml($lists['whoMaybe'], 3);
            $whoNoHtml = $this->renderEditableResponseSectionHtml($lists['whoNo'], 2);
        }
        else {
            $whoYesHtml = $this->renderReadOnlyResponseSectionHtml($lists['whoYes'], 1);
            $whoMaybeHtml = $this->renderReadOnlyResponseSectionHtml($lists['whoMaybe'], 3);
            $whoNoHtml = $this->renderReadOnlyResponseSectionHtml($lists['whoNo'], 2);
        }

        return render('termin/response_modal', array(
            'terminId' => (int)$this->Index,
            'filterRegister' => (int)$filterregister,
            'terminName' => $terminTitle,
            'showOrchestra' => $showOrchestra,
            'orchestraFull' => $orchestraFull,
            'orchestraActive' => $orchestraActive,
            'showChildrenHeader' => ($GLOBALS['optionsDB']['showChildOption'] && $bus),
            'showGuestsHeader' => ($GLOBALS['optionsDB']['showGuestOption'] && $bus),
            'whoYesHtml' => $whoYesHtml,
            'whoMaybeHtml' => $whoMaybeHtml,
            'whoNoHtml' => $whoNoHtml,
            'countYes' => count($lists['whoYes']),
            'countMaybe' => count($lists['whoMaybe']),
            'countNo' => count($lists['whoNo']),
            'canEditResponse' => $canEditResponse,
            'userCatalogJson' => $userCatalogJson,
        ));
    }

    private function DiscordMessage() {
        $message = ":mega: :notes: **neuer Termin** in der Meldeliste :notes: :mega:\n";
        $message .= $this->getDate()." **".$this->Name."**\n";
        if($this->Beschreibung) { $message .= "*".$this->Beschreibung."*\n"; }
        if($this->Uhrzeit) { $message .= "**Uhrzeit**: ".$this->Uhrzeit." Uhr\n"; }
        if($this->Ort1) { $message .= "**Ort**: *".$this->Ort1."*\n"; }
        return $message;
    }
    private function DiscordMessageUpdate() {
        $message = ":mega: :notes: **Terminänderung** in der Meldeliste :notes: :mega:\n";
        $message .= $this->getDate()." **".$this->Name."**\n";
        if($this->Beschreibung) { $message .= "*".$this->Beschreibung."*\n"; }
        if($this->Uhrzeit) { $message .= "**Uhrzeit**: ".$this->Uhrzeit." Uhr\n"; }
        if($this->Ort1) { $message .= "**Ort**: *".$this->Ort1."*\n"; }
        return $message;
    }
};
?>
