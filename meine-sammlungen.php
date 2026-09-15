<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page'] = 'meinesammlungen';
$_SESSION['adminpage'] = false;

include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
if(!loggedIn()) {
    header('Location: login.php');
    exit;
}
if(!empty($_SESSION['singleUsePW'])) {
    header('Location: changePW.php');
    exit;
}

$userId = (int)$_SESSION['userid'];
$collections = CollectionGrant::listVisibleToUser($userId);

include_once 'common/header.php';
adminListPageBegin('', 'Meine Sammlungen', array('hideKicker' => true));
?>

<?php if(!archivFeatureEnabled() || !count($collections)) { ?>
  <div class="mail-list-item"><div class="mail-list-primary">Keine Sammlungen.</div></div>
<?php } else { ?>
  <div class="sammlung-fold-list">
<?php
foreach($collections as $c) {
    echo sammlungFoldHtml((int)$c['id'], $c['name']);
}
?>
  </div>
<?php } ?>

<?php
adminListPageEnd();
include 'common/footer.php';
?>
