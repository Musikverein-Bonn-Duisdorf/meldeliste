<?php
/**
 * Rechte-Chip-Picker (wie Gruppen) — Admin anlegen/bearbeiten.
 * Expects: $adminUserEdit; optional $n, $fill, $inputBg
 */
if(!isset($adminUserEdit)) {
    $adminUserEdit = !empty($canEditUsers) && isset($edit) && (int)$edit === 3;
}
if(empty($adminUserEdit) || !requirePermission('perm_editPermissions')) {
    return;
}
if(!isset($inputBg)) {
    $inputBg = $GLOBALS['optionsDB']['colorInputBackground'];
}
$catalog = Permissions::permissionCatalog();
$permObj = new Permissions;
$inherited = array();
if(!empty($fill) && isset($n) && (int)$n->Index > 0) {
    $permObj->load_by_user((int)$n->Index);
    $inherited = Group::inheritedPermissionSources((int)$n->Index);
}
$sessionUserId = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
$targetUserId = (!empty($fill) && isset($n)) ? (int)$n->Index : 0;
$lockEditPerms = ($targetUserId > 0 && $sessionUserId === $targetUserId);

$selected = array();
foreach($catalog as $item) {
    $key = $item['key'];
    if(((int)$permObj->$key) === 1 || ($lockEditPerms && $key === 'perm_editPermissions')) {
        $selected[] = $key;
    }
}

$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
$catalogJson = json_encode(array('permissions' => $catalog), $jsonFlags);
$selectedJson = json_encode(array_values($selected), $jsonFlags);
$inheritedJson = json_encode($inherited ? $inherited : new stdClass(), $jsonFlags);
?>
<div class="profile-pref-group" id="profile-perms-wrap"
     data-perm-catalog="<?php echo htmlspecialchars((string)$catalogJson, ENT_QUOTES, 'UTF-8'); ?>"
     data-selected-perms="<?php echo htmlspecialchars((string)$selectedJson, ENT_QUOTES, 'UTF-8'); ?>"
     data-inherited-perms="<?php echo htmlspecialchars((string)$inheritedJson, ENT_QUOTES, 'UTF-8'); ?>"
     data-perm-input-name="userPermissions[]"
     data-locked-perm="<?php echo $lockEditPerms ? 'perm_editPermissions' : ''; ?>">
  <h3 class="profile-col-title">Rechte</h3>
  <input type="hidden" name="userPermissionsPosted" value="1">
  <div class="profile-group-picker profile-perm-picker w3-border <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>">
    <div id="profile-perm-chips" class="mail-recipient-chips profile-perm-tiles" aria-live="polite"></div>
    <input type="text" id="profile-perm-input" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Recht tippen…" autocomplete="off" aria-label="Recht hinzufügen">
    <div id="profile-perm-suggest" class="mail-recipient-suggest" hidden></div>
    <div id="profile-perm-hiddens" hidden></div>
  </div>
</div>
