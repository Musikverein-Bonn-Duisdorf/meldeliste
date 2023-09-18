<?php
session_start();
$_SESSION['page']='users';
$_SESSION['adminpage']=true;
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
if(isset($_POST['newmail'])) {
    $n = new User;
    $n->load_by_id($_POST['Index']);
    $n->fill_from_array($_POST);
    if($_POST['Index'] > 0) {
        $n->newmail("");
    }
}
if($_SESSION['admin']) {
    $sql = sprintf('SELECT COUNT(`Index`) AS `Count` FROM `%sUser` WHERE `Deleted` != 1;',
    $GLOBALS['dbprefix']
    );
$dbr = mysqli_query($conn, $sql);
sqlerror();
$row = mysqli_fetch_array($dbr);
$nMusiker = $row['Count'];
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
    <h2>Liste aller User (<?php echo $nMusiker; ?>)</h2>
</div>

<div>
<input class="w3-input w3-border w3-padding" type="text" placeholder="Nach Musiker suchen..." id="filterString" onkeyup="filterMusiker()">
</div>
<div id="Liste">
<?php
$sql = sprintf('SELECT `Index` FROM `%sUser` WHERE `Deleted` != 1 ORDER BY `Nachname`, `Vorname`;',
$GLOBALS['dbprefix']
);
$dbr = mysqli_query($conn, $sql);
sqlerror();
while($row = mysqli_fetch_array($dbr)) {
    $M = new User;
    $M->load_by_id($row['Index']);
    $M->printUserTableLine();
}
?>
</div>
<script src="js/filterMusiker.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>

<?php }
else {
?>
    <meta http-equiv="refresh" content="0; URL=index.php" />
<?php
}

include "common/footer.php";
?>
