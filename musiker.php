<?php
session_start();
$_SESSION['page']='musiker';
include "common/header.php";

if(isset($_POST['insert'])) {
    $n = new User;
    $n->load_by_id($_POST['Index']);
    $n->fill_from_array($_POST);
    $n->save();
    if(isset($_POST['pw1']) && isset($_POST['pw2'])) {
        if($_POST['pw1'] == $_POST['pw2'] && $_POST['pw1'] != '') {
            $n->passwd($_POST['pw1']);
        }
    }
}
if(isset($_POST['delete'])) {
    $n = new User;
    $n->load_by_id($_POST['Index']);
    $n->delete();
}
if(isset($_POST['passwd'])) {
    $n = new User;
    $n->load_by_id($_POST['Index']);
    $n->fill_from_array($_POST);
    if($_POST['Index'] > 0) {
        $n->passwd("");
    }
}
if($_SESSION['admin']) {
    $sql = sprintf('SELECT COUNT(`Index`) AS `Count` FROM `%sUser`;',
    $GLOBALS['dbprefix']
    );
$dbr = mysqli_query($conn, $sql);
sqlerror();
$row = mysqli_fetch_array($dbr);
$nMusiker = $row['Count'];
?>
<div class="w3-container <?php echo $GLOBALS['commonColors']['titlebar']; ?>">
    <h2>Liste aller Musiker (<?php echo $nMusiker; ?>)</h2>
</div>
<?php
$sql = sprintf('SELECT `Index` FROM `%sUser` ORDER BY `Nachname`, `Vorname`;',
$GLOBALS['dbprefix']
);
$dbr = mysqli_query($conn, $sql);
sqlerror();
while($row = mysqli_fetch_array($dbr)) {
    $M = new User;
    $M->load_by_id($row['Index']);
    $M->printTableLine();
}
?>
<?php }
else {
?>
    <meta http-equiv="refresh" content="0; URL=index.php" />
<?php
}
include "common/footer.php";
?>
