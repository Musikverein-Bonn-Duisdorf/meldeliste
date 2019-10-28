<?php
session_start();
$_SESSION['page']='home';
include "common/header.php";
?>
<script>
function getStatus(user, termin) {
	if (window.XMLHttpRequest) {
	    // AJAX nutzen mit IE7+, Chrome, Firefox, Safari, Opera
	    xmlhttp=new XMLHttpRequest();
	}
	else {
	    // AJAX mit IE6, IE5
	    xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}
	var str = "melde.php?cmd=status&id="+<?php echo "\"".$GLOBALS['cronID']."\""; ?>+"&user="+user+"&termin="+termin;
	xmlhttp.open("GET",str,true);
	xmlhttp.send();    
}
function melde(user, termin, wert, Children, Guests) {
	if (window.XMLHttpRequest) {
	    // AJAX nutzen mit IE7+, Chrome, Firefox, Safari, Opera
	    xmlhttp=new XMLHttpRequest();
	}
	else {
	    // AJAX mit IE6, IE5
	    xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}
	xmlhttp.onreadystatechange=function() {
	    if (xmlhttp.readyState==4 && xmlhttp.status==200) {
            var oldel = document.getElementById("entry"+termin);
            var newel = document.createElement('div');
            newel.innerHTML = xmlhttp.responseText;
            oldel.parentNode.replaceChild(newel, oldel);
	    }
	}
    if(Children == -1) {
        var ChildInput = document.getElementById("Children"+termin);
        Children = ChildInput.value;
    }
    if(Guests == -1) {
        var GuestInput = document.getElementById("Guests"+termin);
        Guests = GuestInput.value;
    }
	var str = "melde.php?cmd=save&id="+<?php echo "\"".$GLOBALS['cronID']."\""; ?>+"&user="+user+"&termin="+termin+"&wert="+wert+"&Children="+Children+"&Guests="+Guests;
	xmlhttp.open("GET",str,true);
	xmlhttp.send();
    }
</script>
<div class="w3-container <?php echo $GLOBALS['commonColors']['titlebar'] ;?>">
<h2>Home</h2>
</div>
<div class="w3-container <?php echo $GLOBALS['commonColors']['titlebar'] ;?>">
<h3>Bevorstehende Termine</h3>
</div>
<?php
$now = date("Y-m-d");
if($GLOBALS['optionsDB']['entriesMainPage'] > 0) {
$sql = sprintf('SELECT `Index` FROM `%sTermine` WHERE `Datum` >= "%s" AND `published` > 0 ORDER BY `Datum`, `Uhrzeit` LIMIT %s;',
$GLOBALS['dbprefix'],
$now,
$GLOBALS['optionsDB']['entriesMainPage']
);
}
else {
$sql = sprintf('SELECT `Index` FROM `%sTermine` WHERE `Datum` >= "%s" AND `published` > 0 ORDER BY `Datum`, `Uhrzeit`;',
$GLOBALS['dbprefix'],
$now);
}
$dbr = mysqli_query($conn, $sql);
sqlerror();
while($row = mysqli_fetch_array($dbr)) {
    $M = new Termin;
    $M->load_by_id($row['Index']);
    echo $M->printBasicTableLine();
}
?>
<?php
include "common/footer.php";
?>
