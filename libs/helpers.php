<?php
function bool2string($val) {
    if($val) return "ja";
    return "nein";
}

function instrumentOption() {
    $sql = 'SELECT * FROM `MVD`.`Instrument` ORDER BY `Register`, `Name`;';
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    while($row = mysqli_fetch_array($dbr)) {
        echo "<option value=\"".$row['Index']."\">".$row['Name']."</option>\n";
    }
}

function getPage($string) {
    if($string == $_SESSION['page']) {
        echo ' w3-dark-gray';
    }
}

function string2Date($string) {
    $y = substr($string, 0, 3);
    $m = substr($string, 5, 6);
    $d = substr($string, 8, 9);
}

function germanDate($string, $monthLetters) {
    $months = array(
        "01" => "Januar",
        "02" => "Februar",
        "03" => "März",
        "04" => "April",
        "05" => "Mai",
        "06" => "Juni",
        "07" => "Juli",
        "08" => "August",
        "09" => "September",
        "10" => "Oktober",
        "11" => "November",
        "12" => "Dezember"
    );
    
    $y = substr($string, 0, 4);
    $m = substr($string, 5, 2);
    $d = substr($string, 8, 2);
    if($monthLetters) {
        $s = $d.". ".$months[$m]." ".$y;
    } else {
        $s = $d.".".$m.".".$y;
    }
    return $s;
}

?>