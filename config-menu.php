<?php
session_start();
$_SESSION['page']='config';
$_SESSION['adminpage']=true;
include "common/header.php";
requireAdmin();
$fill = false;
if(isset($_POST['save'])) {
    $sql = sprintf('SELECT * FROM `%sconfig`;',
    $GLOBALS['dbprefix']
    );
    $dbr = mysqli_query($conn, $sql);
    sqlerror();
    while($row = mysqli_fetch_array($dbr)) {
        switch($row['Type']) {
        case "days":
            $val=0;
            for($i=1; $i<=7; $i++) {
                if(isset($_POST[$row['Parameter'].$i])) {                
                    $val+=2**($i-1);
                }
            }
            if($val == $row['Value']) break;
            $sql = sprintf('UPDATE `%sconfig` SET `Value` = "%s" WHERE `Parameter` = "%s";',
            $GLOBALS['dbprefix'],
            $val,
            $row['Parameter']
            );            
            $dbr2 = mysqli_query($conn, $sql);
            sqlerror();
            break;
        case "bool":
        case "uint":
        case "int":
        case "time":
        case "string":
        case "color":
        default:
            if(isset($_POST[$row['Parameter']])) {
                $sql = sprintf('UPDATE `%sconfig` SET `Value` = "%s" WHERE `Parameter` = "%s";',
                $GLOBALS['dbprefix'],
                mysqli_real_escape_string($conn, $_POST[$row['Parameter']]),
                $row['Parameter']
                );
                if($_POST[$row['Parameter']] == $row['Value']) break;
                $dbr2 = mysqli_query($conn, $sql);
                sqlerror();
            }
            break;
        }
    }
}
?>
<script>
function savePara(Parameter, Value) {
	if (window.XMLHttpRequest) {
	    // AJAX nutzen mit IE7+, Chrome, Firefox, Safari, Opera
	    xmlhttp=new XMLHttpRequest();
	}
	else {
	    // AJAX mit IE6, IE5
	    xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}
	var str = "savePara.php?cmd=change&id="+<?php echo "\"".$GLOBALS['cronID']."\""; ?>+"&para="+Parameter+"&value="+Value;
	xmlhttp.open("GET",str,true);
	xmlhttp.send();    
}
</script>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
    <h2>globale Einstellungen</h2>
</div>
<div class="w3-container w3-card w3-margin w3-padding <?php echo $GLOBALS['optionsDB']['colorWarning']; ?>">
  <div class="w3-col l3 m3 s2 w3-center">
    <i class="fas fa-exclamation-triangle"></i>
</div>
  <div class="w3-col l6 m6 s8 w3-center">
<b>Achtung, &Auml;nderungen dieser Einstellungen wirken sich auf alle Nutzer aus.</b>
</div>

  <div class="w3-col l3 m3 s2 w3-center">
    <i class="fas fa-exclamation-triangle"></i>
</div>
</div>
<form action="config-menu.php" method="POST">
<div class="w3-container w3-padding w3-border-bottom w3-border-black">
    <div class="w3-col l3"><b>Parameter</b></div>
    <div class="w3-col l5"><b>Beschreibung</b></div>
    <div class="w3-col l4 w3-center"><b>Wert</b></div>
</div>
<?php
    $sql = sprintf('SELECT * FROM `%sconfig` ORDER BY `Parameter`;',
    $GLOBALS['dbprefix']
    );
$dbr = mysqli_query($conn, $sql);
sqlerror();
while($row = mysqli_fetch_array($dbr)) {
    echo "<div class=\"w3-container w3-padding w3-border-bottom w3-boder-black ".$GLOBALS['commonColors']['Hover']."\">\n";
    echo "<div class=\"w3-col l3 m3 s12\"><b>".$row['Parameter']."</b></div><div class=\"w3-col l5 m5 s12\">".$row['Description']."</div>\n";
    switch($row['Type']) {
    case 'bool':
        echo "<div class=\"w3-col l4 m4 s12 w3-center\">nein&nbsp;&nbsp;\n";
        echo "<input type=\"radio\" name=\"".$row['Parameter']."\" value=\"0\" ";
        if($row['Value'] == 0) echo "checked";
        echo "/>&nbsp;&nbsp;\n";
        echo "<input type=\"radio\" name=\"".$row['Parameter']."\" value=\"1\" ";
        if($row['Value'] == 1) echo "checked";
        echo "/>&nbsp;&nbsp;\n";
        echo "&nbsp;&nbsp;ja</div>\n";
        break;
    case 'days':
        $dows=array("Mo", "Di", "Mi", "Do", "Fr", "Sa", "So");
        echo "<div class=\"w3-col l4 m4 s12 w3-center\">\n";
        $c=array('', '', '', '', '', '', '');
        $v=$row['Value'];
        for($i=7; $i>=1; $i--) {
            if($v/2**($i-1)>=1) {
                $c[$i-1]='checked';
                $v=$v-2**($i-1);
            }
        }
        for($i=1; $i<=7; $i++) {
            echo $dows[$i-1]."&nbsp;<input type=\"checkbox\" name=\"".$row['Parameter'].$i."\" value=\"".$i."\" ".$c[$i-1]."/>&nbsp;\n";
        }
        echo "</div>\n";
        break;
    case 'time':
        echo "<input class=\"w3-col l4 m4 s12 w3-center\" type=\"time\" name=\"".$row['Parameter']."\" value=\"".$row['Value']."\" />\n";
        break;
    case 'int':
        echo "<input class=\"w3-col l4 m4 s12 w3-center\" type=\"number\" name=\"".$row['Parameter']."\" value=\"".$row['Value']."\" />\n";
        break;
    case 'uint':
        echo "<input class=\"w3-col l4 m4 s12 w3-center\" type=\"number\" min=\"0\" name=\"".$row['Parameter']."\" value=\"".$row['Value']."\" />\n";
        break;
    case 'text':
        echo "<textarea class=\"w3-col l4 m4 s12\" rows=\"10\" cols=\"30\" type=\"text\" name=\"".$row['Parameter']."\">".$row['Value']."</textarea>\n";
        break;
    case 'string':
        echo "<input class=\"w3-col l4 m4 s12\" type=\"text\" name=\"".$row['Parameter']."\" value=\"".$row['Value']."\" />\n";
        break;
    case 'email':
        echo "<input class=\"w3-col l4 m4 s12\" type=\"email\" name=\"".$row['Parameter']."\" value=\"".$row['Value']."\" />\n";
        break;
    case 'color':
        $colors=array("", "w3-red", "w3-pink", "w3-purple", "w3-deep-purple", "w3-indigo", "w3-blue", "w3-light-blue", "w3-aqua", "w3-cyan", "w3-teal", "w3-green", "w3-light-green", "w3-lime", "w3-sand", "w3-khaki", "w3-yellow", "w3-amber", "w3-orange", "w3-deep-orange", "w3-blue-gray", "w3-brown", "w3-light-gray", "w3-gray", "w3-dark-gray", "w3-pale-red", "w3-pale-yellow", "w3-pale-green", "w3-pale-blue", "w3-highway-brown", "w3-highway-red", "w3-highway-orange", "w3-highway-schoolbus", "w3-highway-yellow", "w3-highway-green", "w3-highway-blue");
        echo "<div class=\"w3-col l4 m4 s12 w3-center w3-dropdown-hover\"><button class=\"w3-button ".$GLOBALS['commonColors']['BtnEdit']."\">Farbauswahl</button>";
        echo "<div class=\"w3-dropdown-content w3-row w3-center w3-border w3-border-black\">";
        for($i=0; $i<count($colors); $i++) {
            if($colors[$i] == $row['Value']) {
                echo "<div class=\"w3-btn w3-col l2 m2 s2 ".$colors[$i]." w3-padding w3-margin-right w3-center w3-border w3-border-black\"><b>".$colors[$i]."</b></div>";
            }
            else {
                echo "<div class=\"w3-btn w3-col l2 m2 s2 ".$colors[$i]." w3-padding w3-margin-right w3-center\" onclick=\"savePara('".$row['Parameter']."', '".$colors[$i]."')\">".$colors[$i]."</div>";
            }
        }
        echo "</div>\n";
        echo "</div>\n";
        break;
    default:
        echo "<div class=\"w3-col l4 w3-center\">kein Typ spezifiziert.</div>\n";
        break;
    }
    echo "</div>";
}
?>
<button class="w3-btn w3-padding <?php echo $GLOBALS['commonColors']['submit']; ?> w3-border w3-margin w3-mobile" type="submit" name="save" value="speichern" >speichern</button>
    </form>
      
<?php
include "common/footer.php";
?>
