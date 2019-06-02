<?php
function bool2string($val) {
    if($val) return "ja";
    return "nein";
}

function instrumentOption($val) {
    $sql = 'SELECT * FROM `MVD`.`Instrument` ORDER BY `Register`, `Name`;';
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    while($row = mysqli_fetch_array($dbr)) {
        if($val == $row['Index']) {
            echo "<option value=\"".$row['Index']."\" selected>".$row['Name']."</option>\n";
        }
        else {
            echo "<option value=\"".$row['Index']."\">".$row['Name']."</option>\n";
        }
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

function validateLink($hash) {
    $_SESSION['userid'] = 0;
    $sql = sprintf("SELECT * FROM `User` WHERE `activeLink` = '%s';", $hash);
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    while($row = mysqli_fetch_array($dbr)) {
        $_SESSION['userid'] = $row['Index'];
        $_SESSION['Vorname'] = $row['Vorname'];
        $_SESSION['Nachname'] = $row['Nachname'];
        $_SESSION['username'] = $row['Vorname']." ".$row['Nachname'];
        $_SESSION['admin'] = (bool)$row['Admin'];
        $logentry = new Log;
        $logentry->info("Login via Link.");
        return true;
        break;
    }
    return false;
}
function validateUser($login, $password) {
    $_SESSION['userid'] = 0;
    $sql = sprintf("SELECT * FROM `User` WHERE `login` = '%s';",
		   $login
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    while($row = mysqli_fetch_array($dbr)) {
        if(password_verify($password, $row['Passhash'])) {
            $_SESSION['userid'] = $row['Index'];
            $_SESSION['Vorname'] = $row['Vorname'];
            $_SESSION['Nachname'] = $row['Nachname'];
            $_SESSION['username'] = $row['Vorname']." ".$row['Nachname'];
            $_SESSION['admin'] = (bool)$row['Admin'];
            $logentry = new Log;
            $logentry->info("Login via Password.");
            return true;
        }
        break;
    }
    return false;
}

function loggedIn() {
    if(!isset($_SESSION['userid'])) return false;
    if($_SESSION['userid']) return true;
    return false;
}

function sql2time($time) {
    return substr($time, 0, 5);
}

?>
