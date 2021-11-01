<?php
include 'common/include.php';
session_start();

/* include 'libs/ics.php'; */
if(isset($_POST['appID'])) {

    $n = new Termin;
    $n->load_by_id($_POST['appID']);

    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename='.$n->Datum."_".$n->Name.'.ics');

    date_default_timezone_set('Europe/Berlin');
    $end = gmdate('Y-m-d H:i:s', strtotime("+120 minutes", strtotime($n->Datum." ".$n->Uhrzeit)));

    if($n->Uhrzeit2) {
        $end = gmdate('Y-m-d H:i:s', strtotime($n->Datum." ".$n->Uhrzeit2));
    }

    $begin = gmdate('Y-m-d H:i:s', strtotime($n->Datum." ".$n->Uhrzeit));
    if($n->Uhrzeit == NULL) {
        $begin = gmdate('Y-m-d H:i:s', strtotime($n->Datum." 00:00:00"));
        $end = gmdate('Y-m-d H:i:s', strtotime($n->Datum." 23:59:00"));
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