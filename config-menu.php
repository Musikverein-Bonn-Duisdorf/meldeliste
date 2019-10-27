<?php
session_start();
$_SESSION['page']='config';
include "common/header.php";
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
        default:
            if(isset($_POST[$row['Parameter']])) {
                $sql = sprintf('UPDATE `%sconfig` SET `Value` = "%s" WHERE `Parameter` = "%s";',
                $GLOBALS['dbprefix'],
                $_POST[$row['Parameter']],
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
<div class="w3-container <?php echo $GLOBALS['commonColors']['titlebar']; ?>">
    <h2>globale Einstellungen</h2>
</div>
<form action="config-menu.php" method="POST">
<div class="w3-container w3-padding w3-border-bottom w3-border-black">
    <div class="w3-col l3"><b>Parameter</b></div>
    <div class="w3-col l5"><b>Beschreibung</b></div>
    <div class="w3-col l4 w3-center"><b>Wert</b></div>
</div>
<?php
    $sql = sprintf('SELECT * FROM `%sconfig`;',
    $GLOBALS['dbprefix']
    );
$dbr = mysqli_query($conn, $sql);
sqlerror();
while($row = mysqli_fetch_array($dbr)) {
    echo "<div class=\"w3-container w3-padding w3-border-bottom w3-boder-black\">\n";
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
    default:
        echo "<div class=\"w3-col l4 w3-center\">kein Typ spezifiziert.</div>\n";
        break;
    }
    echo "</div>";
}
?>
<input class="w3-btn <?php echo $GLOBALS['commonColors']['submit']; ?> w3-border w3-margin w3-mobile" type="submit" name="save" value="speichern">
    </form>
<script>
function clearInput(name) {
  var x = document.getElementsByName(name);
  for(i=0; i<x.length; i++) {
      x[i].value = '';
  }
}
</script>
      
<?php
include "common/footer.php";
?>
