<?php
/**
 * Actions: Speichern prominent, Admin-Aktionen sekundär.
 */
$fullUserEdit = ($edit != 2) || !empty($canEditUsers);
$hasSecondary = $fill && $fullUserEdit;
?>
<div class="profile-actions">
  <div class="profile-actions-primary">
    <input class="w3-btn profile-btn-primary <?php echo htmlspecialchars($btnSubmit, ENT_QUOTES, 'UTF-8'); ?> w3-border w3-mobile" type="submit" name="insert" value="Speichern">
  </div>
<?php if($hasSecondary) { ?>
  <details class="profile-actions-more">
    <summary>Weitere Aktionen</summary>
    <div class="profile-actions-secondary">
<?php if($fill && $fullUserEdit) { ?>
      <input class="w3-btn <?php echo htmlspecialchars($btnSubmit, ENT_QUOTES, 'UTF-8'); ?> w3-border w3-mobile" type="submit" name="passwd" value="Zufallspasswort">
      <input class="w3-btn <?php echo htmlspecialchars($btnSubmit, ENT_QUOTES, 'UTF-8'); ?> w3-border w3-mobile" type="submit" name="newmail" value="Email mit Link">
      <input class="w3-btn <?php echo htmlspecialchars($btnSubmit, ENT_QUOTES, 'UTF-8'); ?> w3-border w3-mobile" type="submit" name="deactivate" value="Deaktivieren">
<?php } ?>
<?php if($fill && $canEditUsers) { ?>
      <button type="button" class="w3-btn <?php echo htmlspecialchars($btnDelete, ENT_QUOTES, 'UTF-8'); ?> w3-border w3-mobile" onclick="document.getElementById('delmodal').style.display='block'">Löschen</button>
<?php } ?>
    </div>
  </details>
<?php } ?>
</div>
