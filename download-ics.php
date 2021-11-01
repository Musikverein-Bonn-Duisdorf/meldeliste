<?php
include 'common/include.php';
session_start();

/* include 'libs/ics.php'; */
if(isset($_POST['appID'])) {

    $n = new Termin;
    $n->load_by_id($_POST['appID']);

    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename='.$n->Datum."_".$n->Name.'.ics');

    $tmp = strtotime("+120 minutes", strtotime($n->Datum." ".$n->Uhrzeit));
    $end = date('Y-m-d H:i:s', $tmp);

    if($n->Uhrzeit2) {
        $end = $n->Datum." ".$n->Uhrzeit2;        
    }

    $begin = $n->Datum." ".$n->Uhrzeit;
    if($n->Uhrzeit == NULL) {
        $begin = $n->Datum." 00:00:00";        
        $end = $n->Datum." 23:59:00";        
    }

$ics = new ICS(array(
  'timezone' => $n->Ort,
  'location' => $n->Ort,
  'description' => $n->Name,
  'dtstart' => $begin,
  'dtend' => $end,
  'summary' => $n->Beschreibung
));

echo $ics->to_string();
}
else {
    echo "Error: Appointment not found.";
}

?>