<?php
/**
 * Form hidden fields shared by layouts.
 */
?>
<input type="hidden" name="profile_layout" value="<?php echo htmlspecialchars($profileLayout, ENT_QUOTES, 'UTF-8'); ?>">
<?php if($edit == 2) { ?>
<input type="hidden" name="mode" value="useredit">
<input type="hidden" name="id" value="<?php echo (int)$userid; ?>">
<?php } elseif($canEditUsers) { ?>
<input type="hidden" name="return_to" value="<?php echo htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8'); ?>">
<input type="hidden" name="return_token" value="<?php echo htmlspecialchars($returnToken, ENT_QUOTES, 'UTF-8'); ?>">
<?php } ?>
