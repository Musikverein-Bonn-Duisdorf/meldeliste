<?php
/**
 * Admin: send stored welcome mail (MELD-240). Shown under Kontakt / Stammdaten.
 * Expects: $n, $fill, $edit, $canEditUsers, $btnSubmit
 */
$fullUserEdit = ($edit != 2) || !empty($canEditUsers);
$hasValidWelcomeEmail = false;
if($fill && isset($n) && is_object($n)) {
    foreach(array((string)$n->Email, (string)$n->Email2) as $addr) {
        $addr = trim($addr);
        if($addr !== '' && filter_var($addr, FILTER_VALIDATE_EMAIL)) {
            $hasValidWelcomeEmail = true;
            break;
        }
    }
}
if($fill && $fullUserEdit && $hasValidWelcomeEmail) {
?>
<div class="profile-field">
  <input class="w3-btn <?php echo htmlspecialchars($btnSubmit, ENT_QUOTES, 'UTF-8'); ?> w3-border" type="submit" name="newmail" value="Willkommens-Mail senden">
</div>
<?php
}
