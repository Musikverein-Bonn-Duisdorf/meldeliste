<?php
/**
 * Read-only contact meta from user modal (when editing existing user).
 * Expects: $n, $fill, $edit, $adminUserEdit, $canEditUsers
 */
if(!($fill && (int)$n->Index > 0)) {
    return;
}
$showMetaDetails = !empty($adminUserEdit) || (int)$edit === 2 || !empty($canEditUsers);
if(!$showMetaDetails) {
    return;
}
?>
<div class="profile-field">
  <span class="profile-label">Erstellt</span>
  <div class="profile-value"><?php echo germanDate($n->Joined, 1); ?></div>
</div>
<div class="profile-field">
  <span class="profile-label">Letzter Login</span>
  <div class="profile-value"><?php echo germanDate($n->LastLogin, 1); ?></div>
</div>
<div class="profile-field">
  <span class="profile-label">Anwesenheit</span>
  <div class="profile-value"><?php echo germanDate($n->getLastVisit(), 1); ?></div>
</div>
<div class="profile-field">
  <span class="profile-label">Meldequote</span>
  <div class="profile-value"><?php echo (float)$n->getMeldeQuote() * 100; ?> %</div>
</div>
