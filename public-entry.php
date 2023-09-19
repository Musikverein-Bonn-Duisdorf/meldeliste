<?php
session_start();
$_SESSION['page']='public-entry';
$_SESSION['adminpage']=true;
include "common/header.php";
if(!requirePermission("perm_editResponse")) die();

$state = 1;

if(isset($_POST['letter'])) {
    $state = 2;
}
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar'] ;?>">
    <h2>Im Namen eines Anderen anmelden</h2>
</div>
<?php if($state == 1) { ?>
    <div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar'] ;?>">
	<h3>Erster Buchstabe des Nachnamens</h3>
    </div>
    <form class="w3-container w3-row" action="" method="POST">
	<?php
	$letters = range('A', 'Z');
	foreach ($letters as $letter) {
	    echo "<button class=\"w3-btn w3-border w3-margin-top w3-border-black w3-col s4 l2 m2 ".$GLOBALS['optionsDB']['colorBtnSubmit']."\" value=\"".$letter."\" name=\"letter\" type=\"submit\"><b>".$letter."</b></button>";
	}
	?>
    </form>
<?php } ?>

<?php if($state == 2) { ?>
    <div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar'] ;?>">
	<h3>Name</h3>
    </div>
    <form class="w3-container w3-row" action="termine.php" method="POST">
	<?php
	$sql = sprintf('SELECT * FROM `%sUser` WHERE `Nachname` LIKE "%s%%" AND `Deleted` = 0;',
    $GLOBALS['dbprefix'],
    $_POST['letter']);
	$dbr = mysqli_query($conn, $sql);
    sqlerror();
	while($row = mysqli_fetch_array($dbr)) {
	    echo "<button class=\"w3-btn w3-border w3-margin-top w3-border-black w3-col s12 l4 m6 ".$GLOBALS['optionsDB']['colorBtnSubmit']."\" type=\"submit\" name=\"proxy\" value=\"".$row['Index']."\">".$row['Vorname']." ".$row['Nachname']."</button>\n";
	}
	?>
    </form>
<?php } ?>

<?php if($state > 1) { ?>
    <form class="w3-container w3-row" action="" method="POST">
	<button class="w3-btn w3-border w3-margin-top w3-border-black s12 m12 l12 <?php echo $GLOBALS['optionsDB']['colorBtnEdit'] ;?>" type="submit">zur&uuml;ck</button>
    </form>
<?php } ?>

<?php
include "common/footer.php";
?>
