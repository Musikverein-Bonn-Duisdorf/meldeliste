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
        $status = $m->getUserEvent($this->User, $id);
        if($status == 2) return "";
        
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
        $u = new User;
        $u->load_by_id($this->User);
        $out = fopen("calendars/MVDcal_".$u->activeLink.".ics", "w");
        $str=$this->makeHeader();

        $sql = sprintf('SELECT `Index` FROM `%sTermine` WHERE `Datum` >= current_date - interval "%d" day ORDER BY `Datum` DESC, `Uhrzeit` DESC;',
                       $GLOBALS['dbprefix'],
                       365 // <-- TODO : make configurable
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        while($row = mysqli_fetch_array($dbr)) {
   	    $str.=$this->makeAppmnt($row['Index']);
        }
        $str.=$this->makeFooter();
        fwrite($out, $str);
        fclose($out);
    }
}
/*
 === sample ics format ===
  
BEGIN:VCALENDAR
VERSION:2.0
CREATED:19960329T133000Z
CALSCALE:GREGORIAN
   BEGIN:VEVENT
   SUMMARY:Access-A-Ride Pickup
   DTSTART;TZID=Europe/Berlin:20130802T103400
   DTEND;TZID=Europe/Berlin:20130802T110400
   LOCATION:1000 Broadway Ave.\, Brooklyn
   DESCRIPTION: Access-A-Ride trip to 900 Jay St.\, Brooklyn
   STATUS:CONFIRMED CANCELLED TENTATIVE
   SEQUENCE:3
      BEGIN:VALARM
      TRIGGER:-PT10M
      DESCRIPTION:Pickup Reminder
      ACTION:DISPLAY
      END:VALARM
   END:VEVENT
END:VCALENDAR
 */

//     if(isset($_POST['appID'])) {

//     $n = new Termin;
//     $n->load_by_id($_POST['appID']);

//     header('Content-Type: text/calendar; charset=utf-8');
//     header('Content-Disposition: attachment; filename='.$n->Datum."_".$n->Name.'.ics');

//     date_default_timezone_set('Europe/Berlin');
//     if($n->EndDatum) {
//         $end = date('Y-m-d H:i:s', strtotime($n->EndDatum." 23:59:00"));
//         if($n->Uhrzeit2) {
//             $end = date('Y-m-d H:i:s', strtotime($n->EndDatum." ".$n->Uhrzeit2));
//         }
//         $begin = date('Y-m-d H:i:s', strtotime($n->Datum." ".$n->Uhrzeit));
//         if($n->Uhrzeit == NULL) {
//             $begin = date('Y-m-d H:i:s', strtotime($n->Datum." 00:00:00"));
//             $end = date('Y-m-d H:i:s', strtotime($n->EndDatum." 23:59:00"));
//         }        
//     }
//     else {
//         $end = date('Y-m-d H:i:s', strtotime("+120 minutes", strtotime($n->Datum." ".$n->Uhrzeit)));

//         if($n->Uhrzeit2) {
//             $end = date('Y-m-d H:i:s', strtotime($n->Datum." ".$n->Uhrzeit2));
//         }

//         $begin = date('Y-m-d H:i:s', strtotime($n->Datum." ".$n->Uhrzeit));
//         if($n->Uhrzeit == NULL) {
//             $begin = date('Y-m-d H:i:s', strtotime($n->Datum." 00:00:00"));
//             $end = date('Y-m-d H:i:s', strtotime($n->Datum." 23:59:00"));
//         }
//     }

//     $ics = new ICS(array(
//         'timezone' => 'Europe/Berlin',
//         'location' => $n->getOrt(),
//         'description' => $n->Beschreibung,
//         'dtstart' => $begin,
//         'dtend' => $end,
//         'summary' => $n->Name
//     ));

// echo $ics->to_string();
// }
// else {
//     echo "Error: Appointment not found.";
// }

?>
