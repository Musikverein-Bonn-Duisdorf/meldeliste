<?php
function bin2date($v) {
    $c=array(false, false, false, false, false, false, false);
    for($i=7; $i>=1; $i--) {
        if($v/2**($i-1)>=1) {
            $c[$i-1]=true;
            $v=$v-2**($i-1);
        }
    }
    return $c;
}

function bool2string($val) {
    if($val) return "ja";
    return "nein";
}

function bool2color($val) {
    if($val) return "w3-light-green";
    return "";
}

function checkCronDate($v) {
    $c = bin2date($v);
    $dow = intval(date("N"));
    if($c[$dow-1] == false) { 
        return false;
    }
    return true;
}

function genitiv($string) {
    $last = substr($string, -1);
    if($last == "s" || $last == "x") {
        return $string.'\'';
    }
    else {
        return $string."s";
    }
}

function medDate($string) {
    if($string == '') return;
    if($string == null) return;
    $y = substr($string, 0, 4);
    $m = substr($string, 5, 2);
    $d = substr($string, 8, 2);
    
    $date = mktime(0,0,0, $m, $d, $y);
    return date("d-M-Y", $date);
}

function germanDate($string, $monthLetters) {
    if($string == '') return;
    if($string == null) return;
    return germanDates($string, $monthLetters, false);
}

function germanDates($string, $monthLetters, $short) {
    if($string == '') {
	return;
    }
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
    $dows = array(
        1 => 'Montag',
        2 => 'Dienstag',
        3 => 'Mittwoch',
        4 => 'Donnerstag',
        5 => 'Freitag',
        6 => 'Samstag',
        7 => 'Sonntag'
    );

    if($short) {
        $months = array(
            "01" => "Jan",
            "02" => "Feb",
            "03" => "Mär",
            "04" => "Apr",
            "05" => "Mai",
            "06" => "Jun",
            "07" => "Jul",
            "08" => "Aug",
            "09" => "Sep",
            "10" => "Okt",
            "11" => "Nov",
            "12" => "Dez"
        );
        $dows = array(
            1 => 'Mo',
            2 => 'Di',
            3 => 'Mi',
            4 => 'Do',
            5 => 'Fr',
            6 => 'Sa',
            7 => 'So'
        );
    }
    $y = substr($string, 0, 4);
    $m = substr($string, 5, 2);
    $d = substr($string, 8, 2);

    $date = mktime(0,0,0, $m, $d, $y);
    $dow = date("N", $date);

    if($monthLetters) {
        $s = $dows[$dow].", ".$d.". ".$months[$m]." ".$y;
    } else {
        $s = $d.".".$m.".".$y;
    }
    return $s;
}

function germanDateSpan($string1, $string2) {
    return germanDates($string1, true, true)." - ".germanDates($string2, true, true);
}

function getActiveUsers($date) {
    $users = array();
    if($GLOBALS['optionsDB']['showConductor']) {
        $dirigent = '';
    }
    else {
        $dirigent = 'AND `iName` != "Dirigent"';
    }
    if($date) {
        $sql = sprintf('SELECT * FROM `%sUser` INNER JOIN (SELECT `Index` AS `iIndex`, `Name` AS `iName` FROM `%sInstrument`) `%sInstrument` ON `iIndex` = `Instrument` WHERE `Joined` >= "%s" AND (`DeletedOn` <= "%s" OR `DeletedOn` = NULL) AND `iName` != "Admin" %s ORDER BY `Nachname`, `Vorname`;',
        $GLOBALS['dbprefix'],
        $GLOBALS['dbprefix'],
        $GLOBALS['dbprefix'],
        $date,
        $date,
        $dirigent
        );
    }
    else {
        $sql = sprintf('SELECT * FROM `%sUser` INNER JOIN (SELECT `Index` AS `iIndex`, `Name` AS `iName` FROM `%sInstrument`) `%sInstrument` ON `iIndex` = `Instrument` WHERE `Deleted` = 0 AND `iName` != "Admin" %s ORDER BY `Nachname`, `Vorname`;',
        $GLOBALS['dbprefix'],
        $GLOBALS['dbprefix'],
        $GLOBALS['dbprefix'],
        $dirigent
        );
    }
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    while($row = mysqli_fetch_array($dbr)) {
        array_push($users, $row['Index']);
    }
    return $users;
}

function getAdminPage($string) {
    if($string == $_SESSION['page'] && $_SESSION['adminpage']) {
        echo $GLOBALS['optionsDB']['colorTitleBar'];
    }
    else {
        echo $GLOBALS['optionsDB']['colorNavAdmin'];
    }
}

function getBirthdays($date1, $date2) {
    $begin = new DateTime($date1);
    $end   = new DateTime($date2);

    $users = array();
    $birthdays = array();
    $ages = array();
    
    for($i = $begin; $i <= $end; $i->modify('+1 day')) {
        $date=$i->format("-m-d");
        $sql = sprintf('SELECT `Index`, `Birthday` FROM `%sUser` WHERE `Birthday` LIKE "%%%s" AND `Deleted` != 1 ORDER BY `Nachname`, `Vorname`;',
                       $GLOBALS['dbprefix'],
                       $date
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        while($row = mysqli_fetch_array($dbr)) {
            array_push($users, $row['Index']);
            array_push($birthdays, $row['Birthday']);
            $bday = new DateTime($row['Birthday']);
            $age=intval($i->format("Y"))-intval($bday->format("Y"));
            array_push($ages, $age);
        }
    }
    for($i = 0; $i < sizeof($users); $i++) {
        $day = new DateTime($birthdays[$i]);
        $u = new User;
        $u->load_by_id($users[$i]);
        echo "<div><i class=\"fa-solid fa-cake-candles\"></i> <b>".$u->getName()."</b> wird am <b>".$day->format("d.m.")."</b> ".$ages[$i].".</div>\n";
    }
}

function getCurrentBirthdays() {
    return getBirthdays(date('d.m.Y',strtotime("-7 days")), date("Y-m-d"));
}

function getNextRegNumber() {
    return RegNumber::nextForInstruments();
}

function getNextRegInventoryNumber($inventoryTypeId = 0) {
    $inventoryTypeId = (int)$inventoryTypeId;
    if($inventoryTypeId < 1) {
        $map = RegNumber::nextMapForInventoryTypes();
        if(empty($map)) return 1;
        return (int)reset($map);
    }
    return RegNumber::nextForType($inventoryTypeId);
}

function getOwner($index) {
    if($index == 0) {
        return $GLOBALS['optionsDB']['orgNameShort'];
    }
    
    $user = new User;
    $user->load_by_id($index);
    return $user->getName();
}

function getPage($string) {
    if($string == $_SESSION['page']) {
        echo $GLOBALS['optionsDB']['colorTitleBar'];
    }
    else {
        echo $GLOBALS['optionsDB']['colorNav'];
    }
}

function getShort($Vorname, $Nachname) {
    if(strlen($Vorname) >=2) {
        $end=2;
        if(substr($Vorname,1,1)=="&") {
            $end = strpos($Vorname, ";");
        }
        $short1 = substr($Vorname,0,$end);
    }
    else {
        $short1 = $Vorname;
    }
    if(strlen($Nachname) >=2) {
        $narray = explode(" ", $Nachname);
        $end=2;
        if(substr($narray[sizeof($narray)-1],1,1)=="&") {
            $end = strpos($narray[sizeof($narray)-1], ";");
        }
        $short2 = substr($narray[sizeof($narray)-1],0,$end);
    }
    else {
        $short2 = $Nachname;
    }
    return $short1.$short2;
}

function getShortAushilfe($Name) {
    $narray = explode(" ", $Name);
    if(sizeof($narray) > 1) {
        return getShort($narray[0], $narray[1]);
    }
    else {
        return substr($Name,0,4);
    }
}

function instrumentOption($val) {
    $str='';
    $str=$str."<option value=\"0\">keins</option>\n";
    // LEFT JOIN: Instrument types must appear even if Register rows are missing
    $sql = sprintf('SELECT `%sInstrument`.* FROM `%sInstrument` LEFT JOIN (SELECT `Index` AS `rIndex`, `Sortierung` AS `rSort` FROM `%sRegister`) `%sRegister` ON `rIndex` = `Register` WHERE `Spielbar` = 1 ORDER BY COALESCE(`rSort`, 9999), `Sortierung`;',
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix']
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    while($row = mysqli_fetch_array($dbr)) {
        if($val == $row['Index']) {
            $str=$str."<option value=\"".$row['Index']."\" selected>".$row['Name']."</option>\n";
        }
        else {
            $str=$str."<option value=\"".$row['Index']."\">".$row['Name']."</option>\n";
        }
    }
    return $str;
}

function instrumentOptionAll($val) {
    $str='';
    $str=$str."<option value=\"0\">keins</option>\n";
    // LEFT JOIN: Instrument types must appear even if Register rows are missing
    $sql = sprintf('SELECT `%sInstrument`.* FROM `%sInstrument` LEFT JOIN (SELECT `Index` AS `rIndex`, `Sortierung` AS `rSort` FROM `%sRegister`) `%sRegister` ON `rIndex` = `Register` ORDER BY COALESCE(`rSort`, 9999), `Sortierung`;',
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix']
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    if(!$dbr) return $str;
    while($row = mysqli_fetch_array($dbr)) {
        if($val == $row['Index']) {
            $str=$str."<option value=\"".$row['Index']."\" selected>".$row['Name']."</option>\n";
        }
        else {
            $str=$str."<option value=\"".$row['Index']."\">".$row['Name']."</option>\n";
        }
    }
    return $str;
}

function inventoryOptionAll($val) {
    $str='';
    $str=$str."<option value=\"0\">keins</option>\n";
    $sql = sprintf(
        'SELECT * FROM `%sInventory` ORDER BY `Sortierung`;',
        $GLOBALS['dbprefix']
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    while($row = mysqli_fetch_array($dbr)) {
        $label = $row['Typ'];
        if(!empty($row['Prefix'])) $label = $row['Prefix'].' — '.$row['Typ'];
        if($val == $row['Index']) {
            $str=$str."<option value=\"".$row['Index']."\" selected>".htmlspecialchars($label)."</option>\n";
        }
        else {
            $str=$str."<option value=\"".$row['Index']."\">".htmlspecialchars($label)."</option>\n";
        }
    }
    return $str;
}

function isHexColor($value) {
    if(!is_string($value)) return false;
    return (bool)preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', trim($value));
}

function normalizeHexColor($value) {
    $value = strtoupper(trim((string)$value));
    if(!isHexColor($value)) return '';
    if(strlen($value) === 4) {
        return '#'.$value[1].$value[1].$value[2].$value[2].$value[3].$value[3];
    }
    return $value;
}

function hexContrastText($hex) {
    $hex = normalizeHexColor($hex);
    if($hex === '') return '#000000';
    $r = hexdec(substr($hex, 1, 2));
    $g = hexdec(substr($hex, 3, 2));
    $b = hexdec(substr($hex, 5, 2));
    // Relative luminance (sRGB approx.)
    $luma = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;
    return ($luma > 0.55) ? '#000000' : '#FFFFFF';
}

function w3ColorToHex($class) {
    static $map = array(
        'w3-mvd-blue' => '#345A95',
        'w3-mvd-gray' => '#969696',
        'w3-mvd-dark-gray' => '#454545',
        'w3-mvd-egg' => '#FDF9E7',
        'w3-mvd-yellow' => '#FFC300',
        'w3-mvd-white' => '#FDFFFC',
        'w3-mvd-black' => '#040006',
        'w3-mvd-light-blue' => '#7F9DC1',
        'w3-amber' => '#FFC107',
        'w3-aqua' => '#00FFFF',
        'w3-blue' => '#2196F3',
        'w3-light-blue' => '#87CEEB',
        'w3-brown' => '#795548',
        'w3-cyan' => '#00BCD4',
        'w3-blue-grey' => '#607D8B',
        'w3-blue-gray' => '#607D8B',
        'w3-green' => '#4CAF50',
        'w3-light-green' => '#8BC34A',
        'w3-indigo' => '#3F51B5',
        'w3-khaki' => '#F0E68C',
        'w3-lime' => '#CDDC39',
        'w3-orange' => '#FF9800',
        'w3-deep-orange' => '#FF5722',
        'w3-pink' => '#E91E63',
        'w3-purple' => '#9C27B0',
        'w3-deep-purple' => '#673AB7',
        'w3-red' => '#F44336',
        'w3-sand' => '#FDF5E6',
        'w3-teal' => '#009688',
        'w3-yellow' => '#FFEB3B',
        'w3-white' => '#FFFFFF',
        'w3-black' => '#000000',
        'w3-grey' => '#9E9E9E',
        'w3-gray' => '#9E9E9E',
        'w3-light-grey' => '#F1F1F1',
        'w3-light-gray' => '#F1F1F1',
        'w3-dark-grey' => '#616161',
        'w3-dark-gray' => '#616161',
        'w3-pale-red' => '#FFDDDD',
        'w3-pale-green' => '#DDFFDD',
        'w3-pale-yellow' => '#FFFFCC',
        'w3-pale-blue' => '#DDFFFF',
        'w3-highway-brown' => '#633517',
        'w3-highway-red' => '#A6001A',
        'w3-highway-orange' => '#E06000',
        'w3-highway-schoolbus' => '#EE9600',
        'w3-highway-yellow' => '#FFAB00',
        'w3-highway-green' => '#004D33',
        'w3-highway-blue' => '#00477E',
    );
    $class = trim((string)$class);
    return isset($map[$class]) ? $map[$class] : '#808080';
}

function colorPickerValue($raw) {
    $raw = trim((string)$raw);
    if($raw === '') return '#808080';
    if(isHexColor($raw)) return normalizeHexColor($raw);
    return w3ColorToHex($raw);
}

function colorToCssClass($value) {
    $value = trim((string)$value);
    if($value === '') return '';
    if(isHexColor($value)) {
        $hex = normalizeHexColor($value);
        $class = 'cfg-hex-'.strtolower(substr($hex, 1));
        if(!isset($GLOBALS['cfgColorCssRules'])) {
            $GLOBALS['cfgColorCssRules'] = array();
        }
        $GLOBALS['cfgColorCssRules'][$class] = array(
            'bg' => $hex,
            'fg' => hexContrastText($hex),
        );
        return $class;
    }
    return $value;
}

function renderConfigColorCss($wrapStyleTag = true) {
    if(empty($GLOBALS['cfgColorCssRules']) || !is_array($GLOBALS['cfgColorCssRules'])) {
        return '';
    }
    $css = '';
    foreach($GLOBALS['cfgColorCssRules'] as $class => $colors) {
        $css .= '.'.preg_replace('/[^a-z0-9\-]/i', '', $class)
            .'{color:'.$colors['fg'].' !important;background-color:'.$colors['bg'].' !important;}';
    }
    if($css === '') return '';
    return $wrapStyleTag ? '<style type="text/css">'.$css.'</style>' : $css;
}

function getColorConfigParameters() {
    static $params = null;
    if($params !== null && count($params) > 0) return $params;
    $params = array();
    if(function_exists('getConfigDefaults')) {
        foreach(getConfigDefaults() as $item) {
            if(isset($item['Type']) && $item['Type'] === 'color' && isset($item['Parameter'])) {
                $params[$item['Parameter']] = true;
            }
        }
    }
    return $params;
}

function loadconfig() {
    $optionsDB = array();
    $sql = sprintf('SELECT * FROM `%sconfig`;',
		   $GLOBALS['dbprefix']
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    if($dbr) {
        while($row = mysqli_fetch_array($dbr)) {
            $optionsDB[$row['Parameter']] = $row['Value'];
        }
    }
    if(function_exists('getConfigDefaults')) {
        foreach(getConfigDefaults() as $item) {
            if(!array_key_exists($item['Parameter'], $optionsDB)) {
                $optionsDB[$item['Parameter']] = $item['Value'];
            }
        }
    }
    $colorParams = getColorConfigParameters();
    foreach($optionsDB as $param => $value) {
        if(isset($colorParams[$param]) || isHexColor($value)) {
            $optionsDB[$param] = colorToCssClass($value);
        }
    }
    return $optionsDB;
}

function loadPermissions($user) {
    $p = new Permissions;
    $p->load_by_user($user);
    return $p;
}

function loggedIn() {
    if(!isset($_SESSION['userid'])) {
	session_destroy();
	return false;
    }
    if($_SESSION['userid'] > 0) return true;
    session_destroy();
    return false;
}

function meldeWert($val) {
    switch($val) {
	case 1:
            return "ja";
	case 2:
            return "nein";
	case 3:
            return "vielleicht";
	default:
            break;
    }
}

function meldeSymbol($val) {
    $symbols = array("&#10004;", "&#10008;", "<b>?</b>");
    $colors = array($GLOBALS['optionsDB']['colorBtnYes'], $GLOBALS['optionsDB']['colorBtnNo'], $GLOBALS['optionsDB']['colorBtnMaybe']);

    $div = new div;
    $div->class="w3-button w3-border w3-border-black w3-center";
    switch($val) {
	case 1:
        $div->class=$colors[0];
        $div->body=$symbols[0];
        break;
	case 2:
        $div->class=$colors[1];
        $div->body=$symbols[1];
        break;
	case 3:
        $div->class=$colors[2];
        $div->body=$symbols[2];
        break;
	default:
        break;
    }
    return $div->print();
}

function mkAdmin() {
    $_SESSION['userid'] = 0;
    $_SESSION['admin'] = true;
    $_SESSION['username'] = 'SYSTEM';
}

function mkEmpty($str) {
    if($str) return $str;
    return "";
}

function mkNULL($str) {
    if($str) return $str;
    return "NULL";
}

function mkNULLstr($str) {
    if($str) return "\"".$str."\"";
    return "NULL";
}

function mkPrize($val) {
    if((float)$val != 0) {
        return sprintf("%.2f &euro;", $val);
    }
}

/**
 * Format a config value for log display (HTML-escaped).
 */
function formatConfigLogValue($value, $type = '') {
    if($value === null || $value === '') {
        return '(leer)';
    }
    if($type === 'bool') {
        return bool2string($value);
    }
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Write a DBupdate log entry for a config parameter change.
 */
function logConfigChange($parameter, $oldValue, $newValue, $type = '') {
    if((string)$oldValue === (string)$newValue) {
        return;
    }
    $logentry = new Log;
    $logentry->DBupdate(sprintf(
        'Config <b>%s</b>: %s &rArr; <b>%s</b>',
        htmlspecialchars((string)$parameter, ENT_QUOTES, 'UTF-8'),
        formatConfigLogValue($oldValue, $type),
        formatConfigLogValue($newValue, $type)
    ));
}

function printOrchestra($tid, $scale) {
    $width=1000*$scale;
    $height=600*$scale;
    $rowdistance=60*$scale;
    $minrowdistance=150*$scale;
    $str="<svg width=\"".$width."\" height=\"".$height."\">";

    $aMeldungen = array();
    $aAushilfen = array();
    $aInstrument = array();
    $aUser = array();
    if($tid) {
        $sql = sprintf("SELECT * FROM `%sMeldungen` INNER JOIN (SELECT `Index` AS `uIndex`, `Vorname`, `Nachname`, `Instrument` AS `uInstrument` FROM `%sUser`) `%sUser` ON `User` = `uIndex` WHERE `Termin` = %d ORDER BY `Instrument`, `Nachname`, `Vorname`;",
                       $GLOBALS['dbprefix'],
                       $GLOBALS['dbprefix'],
                       $GLOBALS['dbprefix'],
                       $tid                      
        );
        $dbMeldungen = mysqli_query($GLOBALS['conn'], $sql);
        while($row = mysqli_fetch_array($dbMeldungen)) {
            $aMeldungen[] = $row;
        }

        $sql = sprintf("SELECT * FROM `%sAushilfen` WHERE `Termin` = %d;",
        $GLOBALS['dbprefix'],
        $tid
        );
        $dbAushilfe = mysqli_query($GLOBALS['conn'], $sql);
        while($row = mysqli_fetch_array($dbAushilfe)) {
            $aAushilfen[] = $row;
        }
    }
    $sql = sprintf("SELECT * FROM `%sUser` WHERE `Deleted` = 0 ORDER BY `Nachname`, `Vorname`;",
                   $GLOBALS['dbprefix']
    );
    $dbUser = mysqli_query($GLOBALS['conn'], $sql);
    while($row = mysqli_fetch_array($dbUser)) {
        $aUser[] = $row;
    }
    $sql = sprintf("SELECT * FROM `%sInstrument`;",
                   $GLOBALS['dbprefix']
    );
    $dbInstrument = mysqli_query($GLOBALS['conn'], $sql);
    while($row = mysqli_fetch_array($dbInstrument)) {
        $aInstrument[] = $row;
    }

    $sql = sprintf('SELECT * FROM `%sRegister` ORDER BY `Row`;',
                   $GLOBALS['dbprefix']
    );
    $dbregister = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    $k=0;
    $i=0;
    $j=0;
    $lastrow=0;
    $lmaxradius = array();
    $rmaxradius = array();
    $radius=0;
    array_push($lmaxradius, 0);
    array_push($rmaxradius, 0);
    while($register = mysqli_fetch_array($dbregister)) {
        if($lastrow != $register['Row']) {
            array_push($lmaxradius, $lmaxradius[count($lmaxradius)-1]+$rowdistance);
            array_push($rmaxradius, $rmaxradius[count($rmaxradius)-1]+$rowdistance);
        }
        $lastrow = $register['Row'];
        if($register['Row'] > 0) {
            if($register['ArcMin'] < 90) {
                $radius = $lmaxradius[$register['Row']-1]+$rowdistance;
            }
            else {
                $radius = $rmaxradius[$register['Row']-1]+$rowdistance;
            }
        }
        if($radius<$minrowdistance) {
            $radius = $minrowdistance;
        }

        $registerInstruments = array();
        $sorting = array();
        for($idx = 0; $idx < count($aInstrument); $idx++) {
            if($aInstrument[$idx]['Register'] == $register['Index']) {
                $registerInstruments[] = $idx;
                $sorting[] = (int)$aInstrument[$idx]['Sortierung'];
            }
        }
        asort($sorting);
        $sortedInstruments = array();
        $keys = array_keys($sorting);
        for($idx = 0; $idx < count($registerInstruments); $idx++) {
            $sortedInstruments[] = $aInstrument[$registerInstruments[$keys[$idx]]]['Index'];
        }

        // combine Users and Aushilfen in one array()
        $allMusiker = array();
        foreach($aUser as $user) {
            $short=getShort($user['Vorname'], $user['Nachname']);
            $wert = -1;
            $instr = $user['Instrument'];
            foreach($aMeldungen AS $meldung) {
                if($meldung['User'] != $user['Index']) continue;
                if($meldung['Instrument'] != $user['Instrument'] && $meldung['Instrument'] > 0) {
                    $instr = $meldung['Instrument'];
                }
                $wert = $meldung['Wert'];
                break;
            }

            $line = array("short" => $short, "Instrument" => $instr, "Wert" => $wert);
            $allMusiker[] = $line;
        }
        if($tid) {
            foreach($aAushilfen as $user) {
                $short=getShortAushilfe($user['Name']);
                $line = array("short" => $short, "Instrument" => $user['Instrument'], "Wert" => 1);
                $allMusiker[] = $line;
            }
        }

        $allSorted = array();
        foreach($allMusiker as $m) {
            if($m["Wert"] != 1) {
                $allSorted[] = $m;
            }
        }
        foreach($allMusiker as $m) {
            if($m["Wert"] == 1) {
                $allSorted[] = $m;
            }
        }
        $allMusiker = $allSorted;        
        
        foreach($sortedInstruments as $instrument) {
            $skip = false;
            foreach($allMusiker AS $user) {
                if($tid) {
                    $skip = false;
                    $match = $user["Wert"];
                }
                if($user['Instrument'] != $instrument) continue;
                if($skip) continue;
                
                $short=$user['short'];
                if($register['Row']==0) {
                    $radius=0;
                    $arc=0;
                }
                else {
                    $arc = $register['ArcMin']+$k*($register['ArcMax']-$register['ArcMin'])/abs($register['ArcMax']-$register['ArcMin'])*40*$scale/(2*pi()*$radius)*360;
                    if($register['ArcMin'] < $register['ArcMax']) {
                        if($arc+20*$scale/(2*pi()*$radius)*360 >=$register['ArcMax']) {
                            $j++;
                            $radius += 40*$scale;
                            $k=0;
                        }
                    }
                    elseif($register['ArcMin'] > $register['ArcMax']) {
                        if($arc-20*$scale/(2*pi()*$radius)*360 <=$register['ArcMax']) {
                            $j++;
                            $radius += 40*$scale;
                            $k=0;
                        }
                    }
                    if($register['ArcMin'] < 90) {
                        if($radius > $lmaxradius[$register['Row']]) {
                            $lmaxradius[$register['Row']] = $radius;
                        }
                    }
                    else {
                        if($radius > $rmaxradius[$register['Row']]) {
                            $rmaxradius[$register['Row']] = $radius;
                        }
                    }
                    $arc = $register['ArcMin']+$k*($register['ArcMax']-$register['ArcMin'])/abs($register['ArcMax']-$register['ArcMin'])*40*$scale/(2*pi()*$radius)*360;
                }
                $x = $width/2-$radius*cos($arc/180*pi());
                $y = 40*$scale+$radius*sin($arc/180*pi());
                if($tid) {
                    if($match) {
                        switch($match) {
                        case 1:
                            $color = "#4CAF50";
                            $opacity = 1;
                            break;
                        case 2:
                            $color = "#f42316";
                            $opacity = 0.5;
                            break;
                        case 3:
                            $color = "#2196F3";
                            $opacity = 0.6;
                            break;
                        default:
                            $color = "#ffffff";
                            $opacity = 0.5;
                            break;
                        }
                    }
                    else {
                        $color = "#ffffff";
                        $opacity = 0.5;
                    }
                    $str=$str."<circle opacity=\"".$opacity."\" cx=\"".$x."\" cy=\"".$y."\" r=\"".(18*$scale)."\" stroke=\"black\" stroke-width=\"".(2*$scale)."\" fill=\"".$color."\" />\n";
                    $str=$str."<text opacity=\"".$opacity."\" text-anchor=\"middle\" alignment-baseline=\"central\" fill=\"#000000\" font-size=\"".(10*$scale)."\" x=\"".$x."\" y=\"".$y."\">".$short."</text>\n";
                }
                else {
                    $str=$str."<circle cx=\"".$x."\" cy=\"".$y."\" r=\"".(18*$scale)."\" stroke=\"black\" stroke-width=\"".(2*$scale)."\" fill=\"".$register['Color']."\" />\n";
                    $str=$str."<text text-anchor=\"middle\" alignment-baseline=\"central\" fill=\"#000000\" font-size=\"".(10*$scale)."\" x=\"".$x."\" y=\"".$y."\">".$short."</text>\n";
                }

                $k++;
            }
        }
        $k=0;
        $j=0;
        $i++;
    }

    $str=$str."</svg>";
    return $str;
}

function recordLogin() {
    $sql = sprintf("UPDATE `%sUser` SET `LastLogin` = CURRENT_TIMESTAMP() WHERE `Index` = %d;",
		   $GLOBALS['dbprefix'],
		   $_SESSION['userid']
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
}

function RegisterOption($val) {
    $sql = sprintf('SELECT * FROM `%sRegister` ORDER BY `Sortierung`;',
		   $GLOBALS['dbprefix']
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    while($row = mysqli_fetch_array($dbr)) {
        if($val == $row['Index']) {
            echo "<option value=\"".$row['Index']."\" selected>".$row['Name']."</option>\n";
        }
        else {
            echo "<option value=\"".$row['Index']."\">".$row['Name']."</option>\n";
        }
    }
}

function rebuildCalendars() {
    $sql = sprintf('SELECT * FROM `%sUser` WHERE `Instrument` != 0 AND `activeLink` IS NOT NULL;',
		   $GLOBALS['dbprefix']
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    while($row = mysqli_fetch_array($dbr)) {
        $c = new UserCalendar;
        $c->User = $row['Index'];
        $c->makeCalendar();
    }
}

function requireAdmin() {
    if(!$_SESSION['admin']) die("Admin permissions required.");
}

function requirePermission($perm) {
    $P = new Permissions;
    $P->load_by_user($_SESSION['userid']);
    return $P->getPermission($perm);
}

function isAdmin() {
    $P = new Permissions;
    $P->load_by_user($_SESSION['userid']);
    return $P->getAdmin();
}

function sql2time($time) {
    if($time != '') {
        return sql2timeRaw($time)." Uhr";
    }
}

function sql2timeRaw($time) {
    return substr($time, 0, 5);
}

function sqlerror() {
    if(!isset($GLOBALS['conn']) || !mysqli_errno($GLOBALS['conn'])) {
        return;
    }
    $msg = mysqli_errno($GLOBALS['conn']).": ".mysqli_error($GLOBALS['conn']);
    $color = isset($GLOBALS['optionsDB']['colorLogFatal']) ? $GLOBALS['optionsDB']['colorLogFatal'] : 'w3-red';
    echo "<div class=\"w3-container ".$color." w3-mobile w3-border w3-padding w3-border-black\"><b>SQL ERROR </b>".htmlspecialchars($msg)."</div>";
    if(class_exists('Log')) {
        $logentry = new Log;
        $logentry->error($msg);
    }
}

function string2gDate($string) {
    $y = substr($string, 0, 4);
    $m = substr($string, 5, 2);
    $d = substr($string, 8, 2);
    return "new Date(".intval($y).", ".(intval($m)-1).", ".intval($d).")";
}

function string2Date($string) {
    $y = substr($string, 0, 3);
    $m = substr($string, 5, 6);
    $d = substr($string, 8, 9);
}

function UserOptionAll($val) {
    $str='';
    $str=$str."<option value=\"0\">".$GLOBALS['optionsDB']['orgNameShort']."</option>\n";
    $sql = sprintf('SELECT * FROM `%sUser` WHERE `Deleted` = 0 ORDER BY `Nachname`, `Vorname`;',
    $GLOBALS['dbprefix']
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    if(!$dbr) return $str;
    while($row = mysqli_fetch_array($dbr)) {
        if($val == $row['Index']) {
            $str=$str."<option value=\"".$row['Index']."\" selected>".$row['Vorname']." ".$row['Nachname']."</option>\n";
        }
        else {
            $str=$str."<option value=\"".$row['Index']."\">".$row['Vorname']." ".$row['Nachname']."</option>\n";
        }
    }
    return $str;
}

function validateLink($hash) {
    $_SESSION['userid'] = 0;
    $sql = sprintf("SELECT * FROM `%sUser` WHERE `activeLink` = '%s';",
		   $GLOBALS['dbprefix'],
		   $hash
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    while($row = mysqli_fetch_array($dbr)) {
        $_SESSION['userid'] = $row['Index'];
        $_SESSION['Vorname'] = $row['Vorname'];
        $_SESSION['Nachname'] = $row['Nachname'];
        $_SESSION['username'] = $row['Vorname']." ".$row['Nachname'];
        $_SESSION['admin'] = (bool)$row['Admin'];
        $_SESSION['singleUsePW'] = (bool)$row['singleUsePW'];
        $logentry = new Log;
        $logentry->info("Login via Link.");
        recordLogin();
        $_SESSION['permissions'] = loadPermissions($row['Index']);
        return true;
    }
    $logentry = new Log;
    $logentry->error("Login not successful. Invalid hash for login via link <b>".htmlspecialchars($hash)."</b>.");
    return false;
}
function validateUser($login, $password) {
    $_SESSION['userid'] = 0;
    $login = trim((string)$login);
    if($login === '' || $password === null || $password === '') {
        $logentry = new Log;
        $logentry->error("Login not successful. Leerer Benutzername oder Passwort.");
        return false;
    }
    $sql = sprintf("SELECT * FROM `%sUser` WHERE `login` = '%s' AND `Deleted` != 1;",
		   $GLOBALS['dbprefix'],
		   mysqli_real_escape_string($GLOBALS['conn'], $login)
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    while($row = mysqli_fetch_assoc($dbr)) {
        $hash = (string)$row['Passhash'];
        if($hash !== '' && password_verify($password, $hash)) {
            $_SESSION['userid'] = $row['Index'];
            $_SESSION['Vorname'] = $row['Vorname'];
            $_SESSION['Nachname'] = $row['Nachname'];
            $_SESSION['username'] = $row['Vorname']." ".$row['Nachname'];
            $_SESSION['admin'] = (bool)$row['Admin'];
            $_SESSION['singleUsePW'] = (bool)$row['singleUsePW'];
            $logentry = new Log;
            $logentry->info("Login via Password.");
            recordLogin();
            $_SESSION['permissions'] = loadPermissions($row['Index']);
            return true;
        }
    }
    $logentry = new Log;
    $logentry->error("Login not successful. Invalid password for username <b>".htmlspecialchars($login)."</b>.");
    return false;
}

function VehicleOption($val) {
    $sql = sprintf('SELECT * FROM `%svehicle`;',
		   $GLOBALS['dbprefix']
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    while($row = mysqli_fetch_array($dbr)) {
        if($val == $row['Index']) {
            echo "<option value=\"".$row['Index']."\" selected>".$row['Name']."</option>\n";
        }
        else {
            echo "<option value=\"".$row['Index']."\">".$row['Name']."</option>\n";
        }
    }
}

/**
 * True if mail body looks like HTML (WYSIWYG) rather than plain text.
 */
function mailBodyLooksLikeHtml($text) {
    return (bool)preg_match('/<[a-z][\s\S]*>/i', (string)$text);
}

/**
 * Allowlist-sanitize HTML for email bodies (MELD-46).
 */
function sanitizeMailHtml($html) {
    $html = (string)$html;
    if($html === '') {
        return '';
    }
    $html = preg_replace('#<(script|iframe|object|embed|form|input|button|link|meta|style|svg|math)(\s[^>]*)?>[\s\S]*?</\1>#i', '', $html);
    $html = preg_replace('#<(script|iframe|object|embed|form|input|button|link|meta|style|svg|math)(\s[^>]*)?/?>#i', '', $html);
    $html = strip_tags($html, '<p><br><b><strong><i><em><u><ul><ol><li><a><h1><h2><h3><h4><blockquote>');
    $html = preg_replace('/\son[a-z]+\s*=\s*("|\')[\s\S]*?\1/i', '', $html);
    $html = preg_replace('/\son[a-z]+\s*=\s*[^\s>]+/i', '', $html);
    $html = preg_replace('/\s(href|src)\s*=\s*("|\')\s*javascript:[^"\']*\2/i', ' href="#"', $html);
    $html = preg_replace('/\s(href|src)\s*=\s*javascript:[^\s>]+/i', ' href="#"', $html);
    return $html;
}

/**
 * Format stored mail body for safe HTML display (preview / inbox).
 */
function formatMailBodyForDisplay($text) {
    $text = (string)$text;
    if($text === '') {
        return '';
    }
    if(mailBodyLooksLikeHtml($text)) {
        return sanitizeMailHtml($text);
    }
    return nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
}

/**
 * Format stored mail body for embedding into the PHPMailer HTML wrapper.
 */
function formatMailBodyForEmail($text) {
    return formatMailBodyForDisplay($text);
}

/**
 * Strip leading personal greeting from outbox body (plain or HTML).
 */
function stripMailBodyGreeting($body, $vorname) {
    $body = (string)$body;
    $vorname = (string)$vorname;
    $anrede = $vorname !== '' ? 'Hallo '.$vorname.',' : 'Hallo,';
    $prefix = $anrede."\n\n";
    if(strpos($body, $prefix) === 0) {
        return substr($body, strlen($prefix));
    }
    $htmlPrefix = '<p>'.htmlspecialchars($anrede, ENT_QUOTES, 'UTF-8').'</p>';
    if(strpos($body, $htmlPrefix) === 0) {
        return substr($body, strlen($htmlPrefix));
    }
    // TinyMCE may wrap without escaping differently
    $htmlPrefixLoose = '<p>'.$anrede.'</p>';
    if(strpos($body, $htmlPrefixLoose) === 0) {
        return substr($body, strlen($htmlPrefixLoose));
    }
    return $body;
}
?>
