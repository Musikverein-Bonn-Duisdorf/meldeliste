<?php
session_start();
$_SESSION['page']='home';
include "common/header.php";

$state = 1;

if(isset($_POST['letter'])) {
    $state = 2;
}
?>
<div class="w3-container w3-dark-gray">
    <h2>Im Namen eines Anderen anmelden</h2>
</div>
<?php if($state == 1) { ?>
    <div class="w3-container w3-dark-gray">
	<h3>1. Buchstabe des Nachnamens</h3>
    </div>
    <form class="w3-container w3-row" action="" method="POST">
	<?php
	$letters = range('A', 'Z');
	foreach ($letters as $letter) {
	    echo "<button class=\"w3-btn w3-border w3-margin-top w3-border-black w3-col s4 l2 m2 ".$GLOBALS['commonColors']['submit']."\" value=\"".$letter."\" name=\"letter\" type=\"submit\"><b>".$letter."</b></button>";
	}
	?>
    </form>
<?php } ?>

<?php if($state == 2) { ?>
    <div class="w3-container w3-dark-gray">
	<h3>Name</h3>
    </div>
    <form class="w3-container w3-row" action="termine.php" method="POST">
	<?php
	$sql = sprintf('SELECT * FROM `%sUser` WHERE `Nachname` LIKE "%s%%";',
    $GLOBALS['dbprefix'],
    $_POST['letter']);
	$dbr = mysqli_query($conn, $sql);
    sqlerror();
	while($row = mysqli_fetch_array($dbr)) {
	    echo "<button class=\"w3-btn w3-border w3-margin-top w3-border-black w3-col s12 l4 m6 ".$GLOBALS['commonColors']['submit']."\" type=\"submit\" name=\"proxy\" value=\"".$row['Index']."\">".$row['Vorname']." ".$row['Nachname']."</button>\n";
	}
	?>
    </form>
<?php } ?>

<?php if($state > 1) { ?>
    <form class="w3-container w3-row" action="" method="POST">
	<button class="w3-btn w3-border w3-margin-top w3-border-black s12 m12 l12 w3-green" type="submit">zur&uuml;ck</button>
    </form>
<?php } ?>

<?php
include "common/footer.php";
?>
