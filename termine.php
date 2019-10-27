<?php
session_start();
$_SESSION['page']='termine';
include "common/header.php";
if(isset($_POST['proxy'])) {
    $user = $_POST['proxy'];
    $proxy = new User;
    $proxy->load_by_id($user);
}
else {
    $user = $_SESSION['userid'];
}

if(isset($_POST['insert'])) {
    $n = new Termin;
    $n->fill_from_array($_POST);
    $n->save();
}
if(isset($_POST['delete'])) {
    $n = new Termin;
    $n->fill_from_array($_POST);
    $n->delete();
}
if(isset($_POST['meldung'])) {
    $m = new Meldung;

    $m->load_by_user_event($user, $_POST['Index']);
    if($m->User < 1) {
        $m = new Meldung;
        $m->User = $user;
        $m->Termin = $_POST['Index'];
    }
    $m->Wert = $_POST['meldung'];
    $m->save();
}
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

<?php
if(isset($_POST['proxy'])) {
?>
<div class="w3-container <?php echo $GLOBALS['commonColors']['proxy']; ?>">
    <h2>Termin&uuml;bersicht <?php echo $proxy->getName();?></h2>
</div>
<?php
} else {
?>
<div class="w3-container <?php echo $GLOBALS['commonColors']['titlebar']; ?>">
<h2>Termin&uuml;bersicht</h2>
</div>
<?php
}
$now = date("Y-m-d");
if($_SESSION['admin']) {
    $sql = sprintf('SELECT `Index` FROM `%sTermine` WHERE `Datum` >= "%s" ORDER BY `Datum`, `Uhrzeit`;',
    $GLOBALS['dbprefix'],
    $now
    );
}
else {
    $sql = sprintf('SELECT `Index` FROM `%sTermine` WHERE `published` = 1 AND `Datum` >= "%s" ORDER BY `Datum`, `Uhrzeit`;',
    $GLOBALS['dbprefix'],
    $now
    );
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
