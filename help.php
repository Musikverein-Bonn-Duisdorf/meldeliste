<?php
session_start();
$_SESSION['page']='help';
$_SESSION['adminpage']=false;
include "common/header.php";

$helpUser = new User;
$helpUser->load_by_id((int)$_SESSION['userid']);
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2>Hilfe &amp; Info</h2>
</div>

<div class="w3-row help-layout">
  <div class="w3-col l7 m12 s12 help-col-guide">
    <div class="w3-container w3-margin-top">
      <?php echo render('help/guide', array(
          'helpUser' => $helpUser,
          'optionsDB' => $GLOBALS['optionsDB'],
      )); ?>
    </div>
  </div>
  <div class="w3-col l5 m12 s12 help-col-changelog" id="help-changelog">
    <div class="w3-container w3-margin-top">
      <h2>Changelog</h2>
      <?php echo renderChangelogHtml(); ?>
    </div>
  </div>
</div>

<?php
include "common/footer.php";
?>
