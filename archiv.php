<?php
session_start();
$_SESSION['page']='archiv';
$_SESSION['adminpage']=true;
include "common/header.php";
if(!requirePermission("perm_showResponse")) {
    denyAccess();
}

$chunk = listChunkTermine('past', 'response', '', 50, (int)$_SESSION['userid']);
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2>Rückmeldungen - Archiv</h2>
</div>
<div class="w3-row">
  <div class="w3-panel w3-col l3 w3-left"></div>
  <div class="w3-col l6 s12 m12" id="Liste" data-cron-id="<?php echo htmlspecialchars((string)$GLOBALS['cronID'], ENT_QUOTES, 'UTF-8'); ?>">
<?php echo $chunk['html']; ?>
<?php echo listChunkRenderSentinel('archiv', $chunk['nextCursor'], $chunk['hasMore']); ?>
  </div>
  <div class="w3-col l3"></div>
</div>
<script src="js/infiniteScroll.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<?php
include "common/footer.php";
?>
