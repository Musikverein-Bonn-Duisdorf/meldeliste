<?php
session_start();
$_SESSION['page']='home';
include "common/header.php";

$state = 1;

if(isset($_POST['letter'])) {
    $state = 2;
}
elseif(isset($_POST['name'])) {
    $state = 3;
}
elseif(isset($_POST['termin'])) {
    $state = 4;
}
elseif(isset($_POST['confirm'])) {
    $state = 5;
}
?>
<div class="w3-container w3-dark-gray">
    <h2>Bitte eintragen...</h2>
</div>
<?php if($state == 1) { ?>
    <div class="w3-container w3-dark-gray">
	<h3>1. Buchstabe des Nachnamens</h3>
    </div>
    <form class="w3-container w3-row" action="" method="POST">
	<?php
	$letters = range('A', 'Z');
	foreach ($letters as $letter) {
	    echo "<button class=\"w3-btn w3-border w3-col s3 w3-margin w3-red\" value=\"".$letter."\" name=\"letter\" type=\"submit\"><b>".$letter."</b></button>";
	}
	?>
    </form>
<?php } ?>

<?php if($state == 2) { ?>
    <div class="w3-container w3-dark-gray">
	<h3>Name</h3>
    </div>
    <form action="" method="POST">
	<?php
	$sql = sprintf('SELECT * FROM `User` WHERE `Nachname` LIKE "%s%%";', $_POST['letter']);
	$dbr = mysqli_query($conn, $sql);
	while($row = mysqli_fetch_array($dbr)) {
	    echo "<button class=\"button\" type=\"submit\" name=\"name\" value=\"".$row['Index']."\">".$row['Vorname']." ".$row['Nachname']."</button><br />\n";
	}
	?>
    </form>
<?php } ?>


<?php if($state == 3) { ?>
    <div class="w3-container w3-dark-gray">
	<h3>Termin ausw&auml;hlen</h3>
    </div>
    <form action="" method="POST">
	<button class="button" type="submit" name="termin">Es gibt noch keinen Termin</button>
    </form>
<?php } ?>

<?php if($state == 4) { ?>
    <div class="w3-container w3-dark-gray">
	<h3>Danke f&uuml;r deine Meldung</h3>
    </div>
    <meta http-equiv="refresh" content="3;public-entry.php" />
<?php } ?>
<?php if($state > 1) { ?>
    <form action="" method="POST">
	<button class="button" type="submit">zur&uuml;ck</button>
    </form>
<?php } ?>

<?php
include "common/footer.php";
?>
