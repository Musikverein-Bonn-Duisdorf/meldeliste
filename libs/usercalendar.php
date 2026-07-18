<?php
class UserCalendar
{
    private $_data = array('User' => null);

    public function __get($key) {
        switch($key) {
	    case 'User':
            return $this->_data[$key];
            break;
        default:
            break;
        }
    }

    public function __set($key, $val) {
        switch($key) {
	    case 'User':
            $this->_data[$key] = $val;
            break;
        }	
    }

    public function makeHeader() {
        $str="BEGIN:VCALENDAR\n";
        $str.="VERSION:2.0\n";
        $str.="CREATED:19960329T133000Z\n";
        $str.="CALSCALE:GREGORIAN\n";
        return $str;
    }

    public function makeFooter() {
        $str="END:VCALENDAR\n";
        return $str;
    }

    public function makeAppmnt($id) {
        $n = new Termin;
        $n->load_by_id($id);
        $m = new Meldung;
	$status = 1;
        if($this->User) {
    	    $status = $m->getUserEvent($this->User, $id);
            if($status == 2) return "";
	}
        
        $str="BEGIN:VEVENT\n";

        if($n->EndDatum) {
            $end = date('Ymd\THis', strtotime($n->EndDatum." 23:59:00"));
            if($n->Uhrzeit2) {
                $end = date('Ymd\THis', strtotime($n->EndDatum." ".$n->Uhrzeit2));
            }
            $begin = date('Ymd\THis', strtotime($n->Datum." ".$n->Uhrzeit));
            if($n->Uhrzeit == NULL) {
                $begin = date('Ymd\THis', strtotime($n->Datum." 00:00:00"));
                $end = date('Ymd\THis', strtotime($n->EndDatum." 23:59:00"));
            }        
        }
        else {
            $end = date('Ymd\THis', strtotime("+120 minutes", strtotime($n->Datum." ".$n->Uhrzeit)));

            if($n->Uhrzeit2) {
                $end = date('Ymd\THis', strtotime($n->Datum." ".$n->Uhrzeit2));
            }

            $begin = date('Ymd\THis', strtotime($n->Datum." ".$n->Uhrzeit));
            if($n->Uhrzeit == NULL) {
                $begin = date('Ymd\THis', strtotime($n->Datum." 00:00:00"));
                $end = date('Ymd\THis', strtotime($n->Datum." 23:59:00"));
            }
        }
        $str.="SUMMARY:".$n->Name."\n";
        $str.="DESCRIPTION:".$n->Beschreibung."\n";

        if($status == null || $status == 3) {
            $str.="STATUS:TENTATIVE\n";
        }
        else {
            $str.="STATUS:CONFIRMED\n";
        }
        
        $str.="DTSTART;TZID=Europe/Berlin:".$begin."\n";
        $str.="DTEND;TZID=Europe/Berlin:".$end."\n";
        $str.="LOCATION:".$n->Ort1.",".$n->Ort2.",".$n->Ort3.",".$n->Ort4."\n";

        $str.="END:VEVENT\n";
        return $str;
    }

    public function makeCalendar() {
	if($this->User) {
        	$u = new User;
        	$u->load_by_id($this->User);
        	$out = fopen("calendars/MVDcal_".$u->activeLink.".ics", "w");
 	}
	else {
		$out = fopen("calendars/MVDcal_all.ics", "w");
	}
       	
	    $sql = sprintf('SELECT `Index` FROM `%sTermine` WHERE `Datum` >= current_date - interval "%d" day ORDER BY `Datum` DESC, `Uhrzeit` DESC;',
                  $GLOBALS['dbprefix'],
                  $GLOBALS['optionsDB']['calendarPastDays'] // <-- TODO : make configurable
       	);
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();

	$str=$this->makeHeader();
        while($row = mysqli_fetch_array($dbr)) {
   	    $str.=$this->makeAppmnt($row['Index']);
        }
        $str.=$this->makeFooter();
        fwrite($out, $str);
        fclose($out);
    }
}
?>
