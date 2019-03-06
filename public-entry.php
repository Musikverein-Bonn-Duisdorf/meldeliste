<?php
include "header.php";

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

<?php if($state > 1) { ?>
    <form action="" method="POST">
    <button class="button" type="submit">zur&uuml;ck</button>
    </form>
<?php } ?>

<?php if($state == 1) { ?>
<h1>1. Buchstabe des Nachnamens</h1>
<form action="" method="POST">
<?php
$letters = range('A', 'Z');
foreach ($letters as $letter) {
    echo "<button class=\"button\" value=\"".$letter."\" name=\"letter\" type=\"submit\">".$letter."</button>";
}
?>
</form>
<?php } ?>

<?php if($state == 2) { ?>
<h1>Name</h1>
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

<?php
include "footer.php";
?>