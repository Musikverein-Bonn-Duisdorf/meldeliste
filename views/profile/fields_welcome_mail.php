<?php
/**
 * Admin: send stored welcome mail (MELD-240). Shown under Kontakt / Stammdaten.
 * Expects: $fill, $edit, $canEditUsers, $btnSubmit
 */
$fullUserEdit = ($edit != 2) || !empty($canEditUsers);
if($fill && $fullUserEdit) {
?>
<div class="profile-field">
  <input class="w3-btn <?php echo htmlspecialchars($btnSubmit, ENT_QUOTES, 'UTF-8'); ?> w3-border w3-mobile" type="submit" name="newmail" value="Willkommens-Mail">
</div>
<?php
}
