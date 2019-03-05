<?php
function bool2string($val) {
    if($val) return "ja";
    return "nein";
}

function instrumentOption() {
    $sql = 'SELECT * FROM `MVD`.`Instrument` ORDER BY `Register`;';
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    while($row = mysqli_fetch_array($dbr)) {
        echo "<option value=\"".$row['Index']."\">".$row['Name']."</option>\n";
    }
}
?>