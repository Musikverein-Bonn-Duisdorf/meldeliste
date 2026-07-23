</div><!-- .app-main -->
</div><!-- .app-shell -->
<?php
/* Page-local modals must live outside .app-main (overflow/z-index stacking). */
if(!empty($GLOBALS['mlDeferredPageModals'])) {
    echo $GLOBALS['mlDeferredPageModals'];
    unset($GLOBALS['mlDeferredPageModals']);
}
?>
<div id="ajaxModalHost" class="w3-modal" onclick="if(event.target===this)closeModal();">
  <div id="ajaxModalContent" class="w3-modal-content"></div>
</div>
<script src="<?php echo assetUrl('js/listRowSearch.js'); ?>"></script>
<script src="<?php echo assetUrl('js/modal.js'); ?>"></script>
  </body>
</html>
